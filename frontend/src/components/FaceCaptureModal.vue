<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="face-modal-title"
    >
      <!-- Fondo oscuro + desenfoque (el formulario se ve detrás) -->
      <div
        class="absolute inset-0 bg-gray-950/75 backdrop-blur-md"
        @click.self="onCancel"
      />

      <div
        class="relative z-10 w-full max-w-lg max-h-[92vh] overflow-y-auto rounded-2xl border border-teal-500/30 bg-gray-900/95 shadow-2xl shadow-teal-900/20"
      >
        <div class="flex items-start justify-between gap-3 border-b border-gray-700/80 px-4 py-3">
          <div>
            <h2 id="face-modal-title" class="text-lg font-bold text-white" style="font-family: 'Playfair Display', serif">
              Captura de rostro
            </h2>
            <p v-if="userResumen" class="mt-1 text-xs text-gray-400 leading-relaxed">
              {{ userResumen }}
            </p>
            <p class="mt-1 text-[11px] text-gray-500">
              El cuadro verde indica el rostro detectado; la foto se toma sola al mantenerlo estable.
            </p>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold uppercase tracking-wide text-gray-400 hover:bg-gray-800 hover:text-white"
            @click="onCancel"
          >
            Cerrar
          </button>
        </div>

        <div class="p-4 space-y-3">
          <p v-if="status" class="text-sm text-teal-300/90">{{ status }}</p>
          <p v-if="errorLine" class="text-sm text-red-400">{{ errorLine }}</p>

          <div class="relative w-full overflow-hidden rounded-xl border border-gray-700 bg-black aspect-[4/3]">
            <video
              ref="videoRef"
              class="absolute inset-0 h-full w-full object-cover"
              playsinline
              muted
              autoplay
            />
            <canvas
              ref="overlayRef"
              class="pointer-events-none absolute inset-0 h-full w-full"
            />
          </div>

          <canvas ref="snapCanvas" class="hidden" width="1" height="1" />

          <p class="text-center text-xs text-gray-500">
            Al terminar, la cámara se apaga sola.
          </p>

          <button
            type="button"
            class="w-full rounded-xl border border-gray-600 py-3 text-sm font-semibold text-gray-300 hover:bg-gray-800"
            @click="onCancel"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted, nextTick } from 'vue'
import * as faceapi from 'face-api.js'
import {
  useFaceRegistration,
  tinyFaceDetectorOptions,
  snapshotVideoToDataUrl,
} from '@/composables/useFaceRegistration'
import { getCameraStream, formatCameraError } from '@/utils/camera'

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Texto breve con datos del formulario (solo lectura) */
  userResumen: { type: String, default: '' },
})

const emit = defineEmits(['close', 'captured'])

const { loadModels } = useFaceRegistration()

const videoRef = ref(null)
const overlayRef = ref(null)
const snapCanvas = ref(null)

const status = ref('')
const errorLine = ref('')

let localStream = null
let loopTimer = null
let stableFrames = 0
let captureDone = false

/** Cuántos ciclos seguidos con rostro válido antes de capturar (~1 s con intervalo 70 ms) */
const STABLE_FRAMES = 15
const LOOP_MS = 70

function stopIntervalOnly() {
  if (loopTimer != null) {
    clearInterval(loopTimer)
    loopTimer = null
  }
  stableFrames = 0
}

function stopCamera() {
  stopIntervalOnly()
  localStream?.getTracks().forEach((t) => t.stop())
  localStream = null
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
  const o = overlayRef.value
  if (o) {
    const ctx = o.getContext('2d')
    if (ctx) ctx.clearRect(0, 0, o.width, o.height)
  }
}

function syncOverlaySize() {
  const v = videoRef.value
  const o = overlayRef.value
  if (!v || !o) return
  const w = v.clientWidth
  const h = v.clientHeight
  if (w < 2 || h < 2) return
  if (o.width !== w || o.height !== h) {
    o.width = w
    o.height = h
  }
}

function drawGreenBox(det) {
  const v = videoRef.value
  const o = overlayRef.value
  if (!v || !o || !det) return
  syncOverlaySize()
  const ctx = o.getContext('2d')
  if (!ctx) return
  const vw = v.videoWidth
  const vh = v.videoHeight
  if (!vw || !vh) return

  ctx.clearRect(0, 0, o.width, o.height)
  const box = det.detection.box
  const sx = o.width / vw
  const sy = o.height / vh
  ctx.strokeStyle = '#22c55e'
  ctx.lineWidth = Math.max(2, Math.round(o.width / 160))
  ctx.strokeRect(box.x * sx, box.y * sy, box.width * sx, box.height * sy)
}

function clearOverlay() {
  const o = overlayRef.value
  if (!o) return
  const ctx = o.getContext('2d')
  if (ctx) ctx.clearRect(0, 0, o.width, o.height)
}

async function detectFrame() {
  if (captureDone || !props.show) return
  const v = videoRef.value
  if (!v || v.readyState < 2) return

  try {
    const det = await faceapi
      .detectSingleFace(v, tinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptor()

    if (det && det.detection.score >= 0.46) {
      stableFrames += 1
      drawGreenBox(det)
      if (stableFrames >= STABLE_FRAMES) {
        finalizeAutoCapture(det)
      }
    } else {
      stableFrames = 0
      clearOverlay()
    }
  } catch {
    stableFrames = 0
    clearOverlay()
  }
}

function finalizeAutoCapture(det) {
  if (captureDone) return
  captureDone = true
  stopIntervalOnly()

  const v = videoRef.value
  const c = snapCanvas.value
  const dataUrl = v && c ? snapshotVideoToDataUrl(v, c, 0.86) : null
  const descriptor = det ? Array.from(det.descriptor) : []

  stopCamera()

  if (dataUrl && descriptor.length === 128) {
    emit('captured', { dataUrl, descriptor })
  }
  emit('close')
}

function onCancel() {
  captureDone = true
  stopCamera()
  status.value = ''
  errorLine.value = ''
  emit('close')
}

watch(
  () => props.show,
  async (open) => {
    errorLine.value = ''
    status.value = ''
    captureDone = false
    if (!open) {
      stopCamera()
      return
    }
    await nextTick()
    status.value = 'Cargando reconocimiento facial…'
    try {
      await loadModels()
      localStream = await getCameraStream()
      const el = videoRef.value
      if (!el) {
        throw new Error('NO_VIDEO')
      }
      el.srcObject = localStream
      await new Promise((resolve, reject) => {
        const onMeta = () => {
          el.removeEventListener('loadedmetadata', onMeta)
          el.play().then(resolve).catch(reject)
        }
        if (el.readyState >= 1) {
          el.play().then(resolve).catch(reject)
        } else {
          el.addEventListener('loadedmetadata', onMeta, { once: true })
        }
      })
      status.value =
        'Mantén el rostro centrado. Verás un recuadro verde; la foto se guardará automáticamente.'
      loopTimer = window.setInterval(() => {
        void detectFrame()
      }, LOOP_MS)
    } catch (err) {
      errorLine.value =
        err?.message === 'NO_VIDEO'
          ? 'No se pudo mostrar la cámara en el modal.'
          : formatCameraError(err)
      status.value = ''
      stopCamera()
    }
  },
)

onUnmounted(() => {
  stopCamera()
})
</script>
