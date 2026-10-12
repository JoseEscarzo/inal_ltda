<template>
  <div>
    <div v-if="!showLoginOtp">
      <div class="mb-8">
        <h2 class="text-4xl font-bold text-white mb-2" style="font-family:'Playfair Display',serif;">
          Iniciar Sesion
        </h2>
        <p class="text-gray-400" style="font-family:'Inter',sans-serif;">Ingresa tus datos</p>
      </div>

      <transition name="shake">
        <div v-if="errorMsg"
          class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm flex items-center gap-3">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
          </svg>
          {{ errorMsg }}
        </div>
      </transition>

      <transition name="fade">
        <div v-if="successMsg"
          class="mb-5 p-4 rounded-xl bg-green-500/10 border border-green-500/50 text-green-400 text-sm flex items-center gap-3">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ successMsg }}
        </div>
      </transition>

      <div class="space-y-5">
        <div>
          <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Correo
            Electrónico</label>
          <input v-model="email" type="email" placeholder="tu@gmail.com" :disabled="bloqueado" @blur="validarEmail"
            @input="emailError = ''"
            class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 outline-none border-2 transition-all duration-300 focus:scale-[1.01] font-inter disabled:opacity-50"
            :class="emailError ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-teal-500'" />
          <transition name="slide-down">
            <p v-if="emailError" class="text-red-400 text-xs mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd" />
              </svg>
              {{ emailError }}
            </p>
          </transition>
        </div>

        <div>
          <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Contraseña</label>
          <div class="relative">
            <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" maxlength="8"
              :disabled="bloqueado" @blur="validarPassword" @input="passwordError = ''"
              class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 pr-12 outline-none border-2 transition-all duration-300 focus:scale-[1.01] font-inter disabled:opacity-50"
              :class="passwordError ? 'border-red-500 focus:border-red-400' : 'border-gray-700 focus:border-teal-500'" />
            <button type="button" @click="showPassword = !showPassword"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-teal-400 transition-colors duration-200 focus:outline-none">
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>

          <transition name="slide-down">
            <p v-if="passwordError" class="text-red-400 text-xs mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd" />
              </svg>
              {{ passwordError }}
            </p>
          </transition>
        </div>

        <p class="text-xs text-gray-500 leading-relaxed">
        </p>

        <div class="flex justify-end">
          <button type="button" @click="$emit('forgot-password')"
            class="text-teal-400 text-sm hover:text-teal-300 hover:underline transition-colors font-inter bg-transparent border-none cursor-pointer">
            ¿Olvidaste tu contraseña?
          </button>
        </div>

        <button @click="iniciarSesion" :disabled="botonBloqueadoFinal"
          class="w-full font-bold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 font-inter"
          :class="botonBloqueadoFinal
            ? 'bg-gray-600 cursor-not-allowed text-gray-400 opacity-80'
            : 'bg-teal-500 hover:bg-teal-400 active:scale-95 text-white shadow-lg shadow-teal-500/20'">
          <svg v-if="cargando" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
          </svg>
          <span>{{ textoBoton }}</span>
        </button>


      </div>
    </div>

    <div v-else class="space-y-6">
      <div class="mb-8">
        <h2 class="text-4xl font-bold text-white mb-2" style="font-family:'Playfair Display',serif;">
          Dispositivo nuevo
        </h2>
        <p class="text-gray-400 text-sm">Código enviado a <span class="text-teal-400">{{ email }}</span>. Revísalo (y
          spam). Solo la primera vez en este equipo.</p>
      </div>

      <transition name="shake">
        <div v-if="otpErrorMsg" class="mb-4 p-4 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm">
          {{ otpErrorMsg }}
        </div>
      </transition>

      <div class="p-6 bg-gray-800/40 border border-gray-700/50 rounded-2xl text-center">
        <label class="text-xs font-semibold text-teal-400 uppercase tracking-widest mb-4 block">Código de 6
          dígitos</label>
        <input v-model="loginOtpCode" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
          class="w-full bg-transparent text-white text-center text-5xl tracking-[0.75rem] font-mono outline-none placeholder-gray-700"
          @input="loginOtpCode = loginOtpCode.replace(/\D/g, '').slice(0, 6)" />
        <div class="h-1 w-full bg-gray-700 mt-2 rounded-full overflow-hidden">
          <div class="h-full bg-teal-500 transition-all duration-300"
            :style="{ width: (loginOtpCode.length / 6) * 100 + '%' }" />
        </div>
      </div>

      <button type="button" :disabled="cargandoOtp || loginOtpCode.length < 6"
        class="w-full bg-teal-500 hover:bg-teal-400 disabled:bg-teal-900 disabled:opacity-60 text-white font-bold py-3 rounded-xl flex items-center justify-center gap-2 shadow-lg"
        @click="confirmarDispositivo">
        <svg v-if="cargandoOtp" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
        </svg>
        <span>{{ cargandoOtp ? 'Verificando…' : 'Confirmar y entrar' }}</span>
      </button>

      <button type="button"
        class="w-full text-gray-500 text-xs hover:text-teal-400 uppercase font-bold tracking-widest py-2"
        @click="volverAlLogin">
        ← Volver al inicio de sesión
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { getOrCreateDeviceId } from '@/utils/deviceId'
import { laravelErrorMessage } from '@/utils/apiErrors'

const emit = defineEmits(['toggle-mode', 'forgot-password', 'login-success'])

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const emailError = ref('')
const passwordError = ref('')
const errorMsg = ref('')
const successMsg = ref('')
const cargando = ref(false)

const showLoginOtp = ref(false)
const loginOtpCode = ref('')
const otpErrorMsg = ref('')
const cargandoOtp = ref(false)

const intentos = ref(0)
const maxIntentos = 3
const bloqueado = computed(() => intentos.value >= maxIntentos)

const roleHomePaths = {
  1: '/dashboard',
  2: '/inventario',
  3: '/monitoreo',
  5: '/destinos',
}

const getRoleHomePath = () => {
  return roleHomePaths[Number(authStore.user?.id_rol)] || '/productos'
}

const emailEsValido = computed(() => /^[^\s@]+@gmail\.com$/.test(email.value))

const botonBloqueadoFinal = computed(() => {
  return cargando.value || bloqueado.value || !emailEsValido.value || password.value.length < 8
})

const textoBoton = computed(() => {
  if (bloqueado.value) return 'Cuenta bloqueada temporalmente'
  if (cargando.value) return 'Iniciando sesión...'
  return 'Iniciar Sesión'
})

const validarEmail = () => {
  if (!email.value) emailError.value = 'El correo electrónico es obligatorio'
  else if (!emailEsValido.value) emailError.value = 'Debes usar una cuenta @gmail.com'
  else emailError.value = ''
}

const validarPassword = () => {
  if (!password.value) passwordError.value = 'La contraseña es obligatoria'
  else if (password.value.length < 8) passwordError.value = 'Mínimo 8 caracteres'
  else passwordError.value = ''
}

function volverAlLogin() {
  showLoginOtp.value = false
  loginOtpCode.value = ''
  otpErrorMsg.value = ''
}

const iniciarSesion = async () => {
  if (botonBloqueadoFinal.value) return

  validarEmail()
  validarPassword()
  if (emailError.value || passwordError.value) return

  cargando.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const deviceId = getOrCreateDeviceId()
    const result = await authStore.login({
      email: email.value,
      password: password.value,
      device_id: deviceId,
    })

    if (result.requiresLoginOtp) {
      intentos.value = 0
      showLoginOtp.value = true
      otpErrorMsg.value = ''
      loginOtpCode.value = ''
      return
    }

    intentos.value = 0
    successMsg.value = '¡Bienvenido al Sistema!'
    setTimeout(() => {
      emit('login-success')
      router.push(getRoleHomePath())
    }, 600)
  } catch (err) {
    intentos.value++
    const restantes = maxIntentos - intentos.value
    const backendMessage =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.email?.[0] ||
      'Credenciales incorrectas'

    if (intentos.value >= maxIntentos) {
      errorMsg.value = 'Has superado el número de intentos. Botón bloqueado.'
    } else {
      errorMsg.value = `${backendMessage} Te quedan ${restantes} intentos.`
    }
  } finally {
    cargando.value = false
  }
}

const confirmarDispositivo = async () => {
  if (loginOtpCode.value.length < 6) return
  cargandoOtp.value = true
  otpErrorMsg.value = ''
  try {
    await authStore.verifyLoginDevice({
      email: email.value,
      password: password.value,
      otp_code: loginOtpCode.value,
      device_id: getOrCreateDeviceId(),
    })
    successMsg.value = '¡Dispositivo verificado!'
    setTimeout(() => {
      emit('login-success')
      router.push(getRoleHomePath())
    }, 600)
  } catch (err) {
    otpErrorMsg.value = laravelErrorMessage(err)
  } finally {
    cargandoOtp.value = false
  }
}
</script>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}

.cursor-not-allowed:hover {
  background-color: #4b5563 !important;
  transform: none !important;
}

.shake-enter-active {
  animation: shake 0.4s ease;
}

@keyframes shake {

  0%,
  100% {
    transform: translateX(0);
  }

  20% {
    transform: translateX(-8px);
  }

  40% {
    transform: translateX(8px);
  }

  60% {
    transform: translateX(-5px);
  }

  80% {
    transform: translateX(5px);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-down-enter-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-5px);
}
</style>
