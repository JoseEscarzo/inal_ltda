<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const cameraApi = axios.create({
  baseURL: import.meta.env.VITE_CAMERA_SERVICE_URL || 'http://localhost:5001',
  timeout: 3000,
})

const loadingCamaras = ref(false)
const servicioDisponible = ref(false)
const modoPantallaCompleta = ref(false)
const saludServicio = ref(null)
const ultimaActualizacion = ref('')

const estadisticasBase = [
  { titulo: 'Camaras activas', key: 'activas', subtexto: 'streams en linea', colorTexto: 'text-green-500', iconBg: 'bg-blue-100', iconColor: 'text-blue-500', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
  { titulo: 'Camaras con error', key: 'errores', subtexto: 'sin conexion', colorTexto: 'text-red-500', iconBg: 'bg-red-100', iconColor: 'text-red-500', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
  { titulo: 'Videos cargados', key: 'videos', subtexto: 'desde camera-service/videos', colorTexto: 'text-indigo-500', iconBg: 'bg-purple-100', iconColor: 'text-purple-500', icon: 'M7 4v16l13-8L7 4z' },
  { titulo: 'FPS stream', key: 'fps', subtexto: 'cuadros por segundo', colorTexto: 'text-orange-500', iconBg: 'bg-orange-100', iconColor: 'text-orange-500', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  { titulo: 'Servicio', key: 'servicio', subtexto: 'camera-service Flask', colorTexto: 'text-green-500', iconBg: 'bg-teal-100', iconColor: 'text-teal-500', icon: 'M5 13l4 4L19 7' },
]

const camaras = ref([
  { id: 'fallback_1', name: 'Linea 1 - Camara 1', status: 'Sin servicio', error: true, stream_url: '' },
  { id: 'fallback_2', name: 'Linea 1 - Camara 2', status: 'Sin servicio', error: true, stream_url: '' },
  { id: 'fallback_3', name: 'Linea 2 - Camara 1', status: 'Sin servicio', error: true, stream_url: '' },
])

const esError = (cam) => String(cam.status || '').toLowerCase().includes('sin')

const camarasActivas = computed(() => camaras.value.filter((cam) => !esError(cam)).length)
const camarasError = computed(() => camaras.value.filter((cam) => esError(cam)).length)
const videosCargados = computed(() => saludServicio.value?.videos ?? camaras.value.filter((cam) => cam.type === 'video').length)
const fpsStream = computed(() => saludServicio.value?.fps ?? 24)
const cameraServiceUrl = computed(() => cameraApi.defaults.baseURL)

const estadisticas = computed(() => estadisticasBase.map((stat) => {
  if (stat.key === 'activas') return { ...stat, valor: camarasActivas.value }
  if (stat.key === 'errores') return { ...stat, valor: camarasError.value }
  if (stat.key === 'videos') return { ...stat, valor: videosCargados.value }
  if (stat.key === 'fps') return { ...stat, valor: fpsStream.value }
  if (stat.key === 'servicio') {
    return {
      ...stat,
      valor: servicioDisponible.value ? 'Online' : 'Offline',
      colorTexto: servicioDisponible.value ? 'text-green-500' : 'text-red-500',
    }
  }
  return stat
}))

const cargarCamaras = async () => {
  loadingCamaras.value = true
  try {
    const [camarasResponse, healthResponse] = await Promise.all([
      cameraApi.get('/api/cameras'),
      cameraApi.get('/health').catch(() => ({ data: null })),
    ])

    saludServicio.value = healthResponse.data
    camaras.value = (Array.isArray(camarasResponse.data) ? camarasResponse.data : []).map((cam) => ({
      ...cam,
      nombre: cam.name,
      estado: cam.status,
      error: String(cam.status || '').toLowerCase().includes('sin'),
    }))
    servicioDisponible.value = true
    ultimaActualizacion.value = new Date().toLocaleString('es-BO', { hour12: false })
  } catch (error) {
    servicioDisponible.value = false
    saludServicio.value = null
    camaras.value = camaras.value.map((cam) => ({ ...cam, status: 'Sin servicio', estado: 'Sin servicio', error: true }))
  } finally {
    loadingCamaras.value = false
  }
}

onMounted(cargarCamaras)
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div v-for="stat in estadisticas" :key="stat.titulo" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-2">
          <h3 class="text-sm font-semibold text-gray-500">{{ stat.titulo }}</h3>
          <div :class="`p-2 rounded-lg ${stat.iconBg}`">
            <svg class="w-5 h-5" :class="stat.iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
            </svg>
          </div>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-800">{{ stat.valor }}</p>
          <p class="text-xs mt-1" :class="stat.colorTexto">{{ stat.subtexto }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex justify-between items-center mb-4 gap-3">
          <div>
            <h2 class="text-lg font-bold text-gray-800">Vista en tiempo real de camaras</h2>
            <p class="text-xs mt-1" :class="servicioDisponible ? 'text-emerald-600' : 'text-red-500'">
              {{ servicioDisponible ? 'Servicio de camaras conectado' : 'Inicia camera-service para ver los videos' }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="cargarCamaras" class="text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full font-semibold hover:bg-gray-200">
              Actualizar
            </button>
            <button @click="modoPantallaCompleta = true" class="text-sm text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-full font-semibold hover:bg-indigo-100">
              Ver todas
            </button>
          </div>
        </div>

        <div v-if="loadingCamaras" class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div v-for="n in 8" :key="n" class="rounded-xl bg-gray-100 aspect-video animate-pulse"></div>
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div v-for="cam in camaras" :key="cam.id" class="relative rounded-xl overflow-hidden bg-gray-900 aspect-video group border border-gray-100">
            <img v-if="cam.stream_url && servicioDisponible" :src="cam.stream_url" :alt="cam.name" class="absolute inset-0 h-full w-full object-cover" />
            <div v-else class="absolute inset-0 flex items-center justify-center bg-slate-800 text-white/50 text-xs text-center px-3">
              Sin stream<br>camera-service apagado
            </div>
            <div class="absolute inset-x-0 top-0 h-12 bg-gradient-to-b from-black/70 to-transparent"></div>
            <div class="absolute top-2 left-2 bg-black/70 text-white text-[10px] px-2 py-0.5 rounded font-mono">{{ cam.name || cam.nombre }}</div>
            <div class="absolute bottom-2 left-2 flex items-center bg-white/95 px-2 py-0.5 rounded-full shadow">
              <span class="w-2 h-2 rounded-full mr-1 animate-pulse" :class="esError(cam) ? 'bg-red-500' : 'bg-green-500'"></span>
              <span class="text-[10px] font-bold" :class="esError(cam) ? 'text-red-600' : 'text-green-600'">{{ cam.status || cam.estado }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="lg:col-span-4 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-start justify-between gap-3 mb-5">
            <div>
              <h2 class="text-lg font-bold text-gray-800">Estado del servicio</h2>
              <p class="text-xs text-gray-500 mt-1">Conexion directa con camera-service.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold" :class="servicioDisponible ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
              {{ servicioDisponible ? 'Online' : 'Offline' }}
            </span>
          </div>

          <div class="space-y-3">

            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
                <p class="text-[11px] uppercase tracking-wide text-emerald-600 font-bold">Activas</p>
                <p class="text-xl font-black text-emerald-700">{{ camarasActivas }}</p>
              </div>
              <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-3">
                <p class="text-[11px] uppercase tracking-wide text-indigo-600 font-bold">Videos</p>
                <p class="text-xl font-black text-indigo-700">{{ videosCargados }}</p>
              </div>
            </div>
            <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
              <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Carpeta de prueba</p>
              <p class="text-sm font-semibold text-gray-800 break-all">
                {{ saludServicio?.videos_dir || 'camera-service/videos' }}
              </p>
            </div>
            <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
              <p class="text-[11px] uppercase tracking-wide text-gray-400 font-bold">Ultima actualizacion</p>
              <p class="text-sm font-semibold text-gray-800">{{ ultimaActualizacion || 'Sin actualizar' }}</p>
            </div>
          </div>
        </div>

        <div class="bg-[#2B2353] rounded-2xl shadow-sm p-5 text-white">
          <h2 class="text-lg font-bold mb-4">Camaras detectadas</h2>
          <div class="space-y-3">
            <div v-for="cam in camaras" :key="`side-${cam.id}`" class="rounded-xl bg-white/10 border border-white/10 p-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-bold">{{ cam.name || cam.nombre }}</p>
                  <p class="text-[11px] text-white/60">{{ cam.type === 'video' ? 'Video MP4 en loop' : 'Stream simulado' }}</p>
                </div>
                <span class="px-2 py-1 rounded-full text-[10px] font-bold" :class="esError(cam) ? 'bg-red-500/20 text-red-200' : 'bg-emerald-500/20 text-emerald-200'">
                  {{ cam.status || cam.estado }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="modoPantallaCompleta" class="fixed inset-0 z-[1900] bg-slate-950 text-white p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-2xl font-black">Monitoreo en tiempo real</h2>
            <p class="text-sm text-white/60">Vista completa de todas las camaras de prueba.</p>
          </div>
          <button @click="modoPantallaCompleta = false" class="rounded-xl bg-white/10 px-4 py-2 text-sm font-bold hover:bg-white/20">
            Cerrar
          </button>
        </div>
        <div class="grid h-[calc(100vh-96px)] grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 overflow-auto">
          <div v-for="cam in camaras" :key="`full-${cam.id}`" class="relative overflow-hidden rounded-2xl bg-black border border-white/10">
            <img v-if="cam.stream_url && servicioDisponible" :src="cam.stream_url" :alt="cam.name" class="h-full w-full object-cover" />
            <div v-else class="flex h-full min-h-64 items-center justify-center text-white/50">Sin stream disponible</div>
            <div class="absolute left-4 top-4 rounded-xl bg-black/70 px-3 py-2 text-sm font-mono">{{ cam.name || cam.nombre }}</div>
            <div class="absolute bottom-4 left-4 rounded-full bg-white px-3 py-1 text-xs font-bold" :class="esError(cam) ? 'text-red-600' : 'text-emerald-600'">
              {{ cam.status || cam.estado }}
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
