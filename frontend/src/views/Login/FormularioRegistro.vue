  <template>
    <div style="font-family: 'Inter', sans-serif;">
      <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-900 mb-1" style="font-family:'Playfair Display',serif;">
          {{ showOtpStep ? 'Verifica tu cuenta' : 'Crea tu perfil' }}
        </h2>
        <p class="text-gray-500 text-sm">
          {{ showOtpStep ? `Introduce el código enviado a ${email}` : 'Completa tus datos para unirte a INAL' }}
        </p>
      </div>

      <transition name="shake">
        <div v-if="errorMsg" class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm flex items-center gap-3 shadow-sm">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          {{ errorMsg }}
        </div>
      </transition>

      <transition name="fade">
        <div v-if="successMsg" class="mb-5 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-3 shadow-sm">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ successMsg }}
        </div>
      </transition>

      <div v-if="!showOtpStep" class="space-y-4">
        <transition name="fade">
          <div v-if="missingFields.length > 0 && hasStartedTyping" class="p-3 rounded-xl bg-red-50/80 border border-red-100">
            <p class="text-[11px] font-bold text-red-500 uppercase tracking-wide mb-2 flex items-center gap-1">
              Faltan completar:
            </p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="field in missingFields" :key="field" class="text-[10px] bg-white text-red-500 border border-red-200 px-2 py-0.5 rounded-md font-medium shadow-sm">
                {{ field }}
              </span>
            </div>
          </div>
        </transition>

        <div>
          <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Nombres</label>
          <input v-model="regData.name" @input="filterText('name')" type="text" placeholder="Ej. Juan Carlos" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Apellido paterno</label>
            <input v-model="regData.lastP" @input="filterText('lastP')" type="text" placeholder="Ej. Pérez" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
          </div>
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Apellido materno</label>
            <input v-model="regData.lastM" @input="filterText('lastM')" type="text" placeholder="Ej. Gómez" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Correo electrónico</label>
            <input v-model="email" type="email" placeholder="tu@correo.com" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
          </div>
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Número de celular</label>
            <input v-model="regData.phone" @input="filterPhone" type="tel" maxlength="8" placeholder="Ej. 70000000" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Contraseña</label>
            <div class="relative">
              <input v-model="password" :type="showPass ? 'text' : 'password'" maxlength="8" placeholder="••••••••" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 pr-10 outline-none border-2 border-transparent focus:border-purple-500 focus:bg-gray-800 transition-all" />
              <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-purple-400 transition-colors">
                <svg v-if="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
              </button>
            </div>
          </div>
          <div>
            <label class="text-sm font-semibold text-gray-600 mb-1.5 block">Confirmar contraseña</label>
            <input v-model="passwordConfirm" :type="showPass ? 'text' : 'password'" maxlength="8" placeholder="Repite tu contraseña" class="w-full bg-gray-800/80 text-white placeholder-gray-400 rounded-xl px-4 py-2.5 outline-none border-2 border-transparent transition-all focus:border-purple-500 focus:bg-gray-800" />
          </div>
        </div>

        <div v-if="password.length > 0" class="mt-1">
          <div class="flex gap-1 mb-1">
            <div v-for="n in 4" :key="n" class="h-1 flex-1 rounded-full transition-all duration-500" :class="n <= seguridadNivel ? seguridadColor : 'bg-gray-200'"></div>
          </div>
          <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
            <div v-for="req in requisitos" :key="req.label" class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" :class="req.cumple ? 'text-purple-600' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="req.cumple" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                <circle v-else cx="12" cy="12" r="4" stroke-width="2"/>
              </svg>
              <span class="text-[11px] font-medium" :class="req.cumple ? 'text-purple-700' : 'text-gray-400'">{{ req.label }}</span>
            </div>
          </div>
        </div>

        <div class="mt-4 relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50 via-purple-50 to-fuchsia-50 border border-purple-200 p-4 shadow-sm">
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-purple-300/20 rounded-full blur-3xl pointer-events-none"></div>
          <div class="relative z-10">
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
              <label class="text-sm font-bold text-purple-900 block">Autenticación Biómetrica</label>
            </div>

            <p class="text-xs text-purple-700/80 mb-3 pr-2">
              Captura tu rostro para activar la seguridad inteligente. El encuadre es automático.
            </p>

            <div class="flex items-center gap-2 mb-3">
              <span v-if="modelsLoading" class="flex items-center gap-1.5 text-[11px] font-medium text-amber-600 bg-amber-100 px-2 py-0.5 rounded-md">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                Cargando motor de IA...
              </span>
              <span v-else-if="modelsReady" class="text-[11px] font-medium text-purple-700 bg-purple-200/50 px-2 py-0.5 rounded-md">✨ Cámara lista para captura</span>
              <span v-else-if="modelsPreloadFailed" class="text-[11px] font-medium text-amber-600 bg-amber-100 px-2 py-0.5 rounded-md">Esperando conexión para IA</span>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
              <div class="flex-1">
                <button
                  type="button"
                  :disabled="loading"
                  class="w-full bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-xl transition-all shadow-md shadow-purple-500/30 flex justify-center items-center gap-2"
                  @click="faceModalOpen = true"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  Iniciar captura facial
                </button>
                <p v-if="faceHint" class="text-[11px] font-medium text-purple-600 mt-2 text-center">{{ faceHint }}</p>
              </div>

              <div v-if="facePreviewUrl" class="mx-auto shrink-0 relative group">
                <div class="absolute inset-0 bg-purple-500 rounded-xl blur-[4px] opacity-40 group-hover:opacity-60 transition-opacity"></div>
                <img
                  :src="facePreviewUrl"
                  alt="Foto capturada"
                  class="relative h-16 w-16 sm:h-20 sm:w-20 rounded-xl object-cover border-2 border-white shadow-sm"
                />
                <div class="absolute -bottom-2 -right-2 bg-green-500 rounded-full p-1 border-2 border-white">
                  <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <FaceCaptureModal
          :show="faceModalOpen"
          :user-resumen="resumenUsuarioParaModal"
          @close="faceModalOpen = false"
          @captured="onFaceCaptured"
        />

        <button @click="handleSubmit" :disabled="loading" class="w-full mt-2 bg-purple-600 hover:bg-purple-500 disabled:bg-purple-300 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-purple-500/30 transition-all text-sm">
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
          <span>{{ loading ? 'Configurando cuenta...' : 'Crear mi perfil ahora' }}</span>
        </button>

        <p class="text-center text-gray-500 text-sm mt-4">
          ¿Ya tienes cuenta?
          <button type="button" @click="$emit('toggle-mode')" class="text-purple-600 hover:text-purple-500 font-bold bg-transparent border-none cursor-pointer transition-colors">Inicia Sesión</button>
        </p>
      </div>

      <div v-else class="space-y-6 animate-fade-in">
        <div class="p-6 bg-purple-50 border border-purple-100 rounded-2xl text-center shadow-sm">
          <label class="text-xs font-bold text-purple-600 uppercase tracking-widest mb-4 block">Código de Seguridad</label>

          <input
            v-model="otpCode"
            type="text"
            maxlength="6"
            placeholder="000000"
            class="w-full bg-transparent text-gray-800 text-center text-5xl tracking-[0.75rem] font-mono outline-none placeholder-gray-300"
            autofocus
          />

          <div class="h-1 w-full bg-purple-200 mt-4 rounded-full overflow-hidden">
            <div class="h-full bg-purple-600 transition-all duration-300" :style="{ width: (otpCode.length / 6) * 100 + '%' }"></div>
          </div>
        </div>

        <button @click="handleVerifyOtp" :disabled="loading || otpCode.length < 6" class="w-full bg-purple-600 hover:bg-purple-500 disabled:bg-purple-300 text-white font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-purple-500/20 transition-all">
          <svg v-if="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
          <span>{{ loading ? 'Verificando...' : 'Confirmar Código' }}</span>
        </button>

        <div class="flex flex-col gap-3 text-center">
          <button type="button" @click="showOtpStep = false" class="text-gray-500 text-xs hover:text-purple-600 transition-colors uppercase font-bold tracking-widest">
            ← Volver y corregir datos
          </button>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, computed, reactive, watch, onUnmounted, onMounted } from 'vue'
  import { useAuthStore } from '@/stores/auth'
  import { useRouter } from 'vue-router'
  import { laravelErrorMessage } from '@/utils/apiErrors'
  import { useFaceRegistration } from '@/composables/useFaceRegistration'
  import { buildUserRegistrationPayload } from '@/services/userRegistrationPayload'
  import FaceCaptureModal from '@/components/FaceCaptureModal.vue'

  const emit = defineEmits(['toggle-mode'])
  const authStore = useAuthStore()
  const router = useRouter()

  const regData          = reactive({ name: '', lastP: '', lastM: '', phone: '' })
  const email            = ref('')
  const password         = ref('')
  const showPass         = ref(false)
  const loading          = ref(false)
  const errorMsg         = ref('')
  const successMsg       = ref('')
  const hasStartedTyping = ref(false)

  const passwordConfirm = ref('')
  const showOtpStep = ref(false)
  const otpCode = ref('')

  const faceModalOpen = ref(false)
  const facePhotoBase64 = ref('')
  const faceDescriptor = ref(null)
  const facePreviewUrl = ref('')
  const faceHint = ref('')
  const modelsLoading = ref(false)
  const modelsReady = ref(false)
  const modelsPreloadFailed = ref(false)

  const { loadModels } = useFaceRegistration()

  const resumenUsuarioParaModal = computed(() => {
    const nombre = `${regData.name} ${regData.lastP} ${regData.lastM}`.trim()
    const partes = [nombre, email.value, regData.phone].filter(Boolean)
    return partes.join(' · ')
  })

  function onFaceCaptured({ dataUrl, descriptor }) {
    facePhotoBase64.value = dataUrl
    faceDescriptor.value = descriptor
    facePreviewUrl.value = dataUrl
    faceModalOpen.value = false
    faceHint.value = '¡Biometría registrada correctamente!'
  }

  onMounted(() => {
    modelsLoading.value = true
    loadModels()
      .then(() => {
        modelsReady.value = true
      })
      .catch(() => {
        modelsPreloadFailed.value = true
      })
      .finally(() => {
        modelsLoading.value = false
      })
  })

  watch(showOtpStep, (otp) => {
    if (otp) {
      faceModalOpen.value = false
    }
  })

  const delay = (ms) => new Promise((r) => setTimeout(r, ms))

  watch(
    [() => regData.name, () => regData.lastP, () => regData.lastM, () => regData.phone, email, password],
    () => { hasStartedTyping.value = true }
  )

  /* ── Barra de seguridad ── */
  const seguridadNivel = computed(() => {
    const p = password.value
    let n = 0
    if (p.length >= 4)                       n++
    if (p.length >= 6)                       n++
    if (/[A-Z]/.test(p) && /[0-9]/.test(p)) n++
    if (/[a-z]/.test(p) && p.length === 8)  n++
    return n
  })
  const seguridadTexto = computed(() =>
    ['', 'Muy débil', 'Débil', 'Buena', 'Fuerte'][seguridadNivel.value] || 'Muy débil'
  )
  const seguridadColor = computed(() =>
    ['', 'bg-red-500', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'][seguridadNivel.value] || 'bg-red-500'
  )
  const seguridadColorTexto = computed(() =>
    ['', 'text-red-500', 'text-orange-500', 'text-yellow-600', 'text-green-600'][seguridadNivel.value] || 'text-red-500'
  )

  /* ── Requisitos contraseña ── */
  const regPasswordStrength = computed(() => ({
    hasLength: password.value.length === 8,
    hasUpper:  /[A-Z]/.test(password.value),
    hasNumber: /[0-9]/.test(password.value),
    hasLower:  /[a-z]/.test(password.value),
    matches:   password.value === passwordConfirm.value && passwordConfirm.value !== ''
  }))

  const requisitos = computed(() => [
    { label: '8 caracteres exactos',      cumple: regPasswordStrength.value.hasLength },
    { label: '1 mayúscula',               cumple: regPasswordStrength.value.hasUpper  },
    { label: '1 minúscula',               cumple: regPasswordStrength.value.hasLower  },
    { label: '1 número',                  cumple: regPasswordStrength.value.hasNumber },
    { label: 'Coinciden',                 cumple: regPasswordStrength.value.matches   },
  ])

  const emailValido = computed(() =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)
  )

  const faceOk = computed(
    () => Array.isArray(faceDescriptor.value) && faceDescriptor.value.length === 128 && facePhotoBase64.value.length > 0
  )

  const missingFields = computed(() => {
    const f = []
    if (!regData.name.trim()) f.push('Nombre')
    if (!regData.lastP.trim()) f.push('Ap. Paterno')
    if (!regData.lastM.trim()) f.push('Ap. Materno')
    if (!email.value.trim()) f.push('Correo')
    else if (!emailValido.value) f.push('Correo inválido')
    if (regData.phone.length === 0) f.push('Celular')
    else if (regData.phone.length < 8) f.push('Celular incompleto')
    if (!faceOk.value) f.push('Biometría')
    if (!regPasswordStrength.value.hasLength) f.push('Contraseña')
    if (!regPasswordStrength.value.matches) f.push('Confirmar contraseña')
    return f
  })

  const isRegValid = computed(() => missingFields.value.length === 0)

  function filterText(field) {
    regData[field] = regData[field].replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')
  }
  function filterPhone() {
    regData.phone = regData.phone.replace(/\D/g, '').substring(0, 8)
  }

  onUnmounted(() => {
    faceModalOpen.value = false
  })

  async function handleSubmit() {
    if (!isRegValid.value) {
      errorMsg.value = 'Por favor, verifica que todos los campos y requisitos se cumplan.'
      return
    }

    loading.value = true
    errorMsg.value = ''
    successMsg.value = ''

    try {
      const fullName = `${regData.name} ${regData.lastP} ${regData.lastM}`.trim()

      const payload = buildUserRegistrationPayload({
        nombreCompleto: fullName,
        email: email.value,
        phone: regData.phone,
        password: password.value,
        password_confirmation: passwordConfirm.value,
        fotoDataUri: facePhotoBase64.value,
        face_descriptor: faceDescriptor.value,
      })

      await authStore.register(payload)

      successMsg.value = '¡Usuario registrado! Revisa tu código de verificación.'
      await delay(1000)

      showOtpStep.value = true
      successMsg.value = ''

    } catch (err) {
      errorMsg.value = laravelErrorMessage(err)
    } finally {
      loading.value = false
    }
  }

  async function handleVerifyOtp() {
    if (otpCode.value.length < 6) {
      errorMsg.value = 'El código debe tener 6 dígitos.'
      return
    }

    loading.value = true
    errorMsg.value = ''

    try {
      await authStore.verifyOtp({
        email: email.value,
        otp_code: otpCode.value
      })

      successMsg.value = '¡Cuenta verificada con éxito! Entrando...'

      await delay(1500)
      router.push('/catalogo')

    } catch (err) {
      errorMsg.value = laravelErrorMessage(err)
    } finally {
      loading.value = false
    }
  }
  </script>

  <style scoped>
  .shake-enter-active { animation: shake 0.4s ease; }
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%      { transform: translateX(-8px); }
    40%      { transform: translateX(8px); }
    60%      { transform: translateX(-5px); }
    80%      { transform: translateX(5px); }
  }
  .fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
  .fade-enter-from,   .fade-leave-to     { opacity: 0; }
  </style>
