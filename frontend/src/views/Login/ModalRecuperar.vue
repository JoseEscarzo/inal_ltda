<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
      style="font-family:'Inter',sans-serif;">

      <div class="bg-gray-900 border border-gray-800 w-full max-w-sm p-8 rounded-3xl shadow-2xl animate-fade-in">

        <!-- ══ PASO 1 — Ingresar Gmail ══ -->
        <template v-if="forgotStep === 1">

          <div class="text-center mb-6">
            <div class="bg-teal-500/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-teal-500/20">
              <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-white" style="font-family:'Playfair Display',serif;">
              Recuperar Acceso
            </h3>
            <p class="text-gray-400 text-sm mt-2">Ingresa tu Gmail para recibir un código de seguridad.</p>
          </div>

          <!-- Alerta error -->
          <transition name="slide-down">
            <div v-if="errorMsg" class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              {{ errorMsg }}
            </div>
          </transition>

          <div class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Tu Gmail</label>
              <input
                v-model="forgotEmail"
                type="email"
                placeholder="ejemplo@gmail.com"
                class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 border-2 border-gray-700 focus:border-teal-500 outline-none transition-all duration-300 focus:scale-[1.01]"
              />
            </div>
            <button
              @click="sendOtpCode"
              :disabled="loadingForgot"
              class="w-full bg-teal-500 hover:bg-teal-400 active:scale-95 disabled:bg-teal-800 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-teal-500/20"
            >
              <svg v-if="loadingForgot" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span>{{ loadingForgot ? 'Enviando código...' : 'Enviar Código' }}</span>
            </button>
          </div>
        </template>

        <!-- ══ PASO 2 — Verificar OTP ══ -->
        <template v-else-if="forgotStep === 2">

          <div class="text-center mb-6">
            <div class="bg-teal-500/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-teal-500/20">
              <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-white" style="font-family:'Playfair Display',serif;">
              Verifica el Código
            </h3>
            <p class="text-gray-400 text-xs mt-2">
              Enviamos un código de 6 dígitos a:<br/>
              <span class="text-teal-400 font-semibold">{{ forgotEmail }}</span>
            </p>
          </div>

          <!-- Alerta error -->
          <transition name="slide-down">
            <div v-if="errorMsg" class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              {{ errorMsg }}
            </div>
          </transition>

          <div class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Código de 6 dígitos</label>
              <input
                v-model="otpCode"
                type="text"
                maxlength="6"
                placeholder="000000"
                class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 border-2 border-gray-700 focus:border-teal-500 outline-none transition-all duration-300 text-center tracking-[0.5rem] font-bold text-xl"
              />
            </div>
            <button
              @click="verifyOtpCode"
              :disabled="loadingForgot"
              class="w-full bg-teal-500 hover:bg-teal-400 active:scale-95 disabled:bg-teal-800 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-teal-500/20"
            >
              <svg v-if="loadingForgot" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span>{{ loadingForgot ? 'Verificando...' : 'Validar Código' }}</span>
            </button>
            <button
              @click="forgotStep = 1; errorMsg = ''"
              class="w-full text-gray-500 text-xs uppercase font-semibold tracking-widest hover:text-teal-400 transition-colors"
            >
              ← Volver a intentar
            </button>
          </div>
        </template>

        <!-- ══ PASO 3 — Nueva contraseña ══ -->
        <template v-else-if="forgotStep === 3">

          <div class="text-center mb-6">
            <div class="bg-teal-500/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-teal-500/20">
              <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-white" style="font-family:'Playfair Display',serif;">
              Nueva Contraseña
            </h3>
            <p class="text-gray-400 text-sm mt-2">Crea una nueva contraseña segura.</p>
          </div>

          <!-- Alerta error -->
          <transition name="slide-down">
            <div v-if="errorMsg" class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/50 text-red-400 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              {{ errorMsg }}
            </div>
          </transition>

          <!-- Alerta éxito -->
          <transition name="fade">
            <div v-if="successMsg" class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/50 text-green-400 text-sm flex items-center gap-2">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              {{ successMsg }}
            </div>
          </transition>

          <div class="space-y-4">

            <!-- Nueva contraseña -->
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Contraseña Nueva</label>
              <div class="relative">
                <input
                  v-model="newPassword"
                  :type="showNewPass ? 'text' : 'password'"
                  maxlength="8"
                  placeholder="••••••••"
                  class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 pr-12 border-2 border-gray-700 focus:border-teal-500 outline-none transition-all duration-300 focus:scale-[1.01]"
                />
                <button
                  type="button"
                  @click="showNewPass = !showNewPass"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-teal-400 transition-colors duration-200 focus:outline-none"
                >
                  <svg v-if="!showNewPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                  </svg>
                </button>
              </div>

              <!-- Barra de seguridad -->
              <div v-if="newPassword.length > 0" class="mt-2">
                <div class="flex gap-1 mb-1">
                  <div
                    v-for="n in 4" :key="n"
                    class="h-1 flex-1 rounded-full transition-all duration-500"
                    :class="n <= seguridadNivel ? seguridadColor : 'bg-gray-700'"
                  ></div>
                </div>
                <p class="text-xs transition-all duration-300" :class="seguridadColorTexto">
                  Seguridad: {{ seguridadTexto }}
                </p>
              </div>

              <!-- Requisitos -->
              <div class="mt-3 p-3 rounded-xl bg-gray-800/50 border border-gray-700/50 space-y-1.5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Debe tener:</p>
                <div v-for="req in requisitos" :key="req.label" class="flex items-center gap-2">
                  <svg class="w-3.5 h-3.5 transition-colors duration-300 shrink-0"
                    :class="req.cumple ? 'text-teal-400' : 'text-gray-600'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="req.cumple" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    <circle v-else cx="12" cy="12" r="4" stroke-width="2"/>
                  </svg>
                  <span class="text-xs transition-colors duration-300"
                    :class="req.cumple ? 'text-teal-400' : 'text-gray-600'">
                    {{ req.label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Confirmar contraseña -->
            <div>
              <label class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2 block">Confirmar Contraseña</label>
              <div class="relative">
                <input
                  v-model="confirmPassword"
                  :type="showConfirmPass ? 'text' : 'password'"
                  maxlength="8"
                  placeholder="••••••••"
                  class="w-full bg-gray-800/80 text-white placeholder-gray-500 rounded-xl px-4 py-3 pr-12 border-2 transition-all duration-300 focus:scale-[1.01] outline-none"
                  :class="confirmPassword.length > 0
                    ? (newPassword === confirmPassword ? 'border-teal-500' : 'border-red-500')
                    : 'border-gray-700 focus:border-teal-500'"
                />
                <button
                  type="button"
                  @click="showConfirmPass = !showConfirmPass"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-teal-400 transition-colors duration-200 focus:outline-none"
                >
                  <svg v-if="!showConfirmPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                  </svg>
                </button>
              </div>
              <!-- Coincidencia -->
              <transition name="slide-down">
                <p v-if="confirmPassword.length > 0 && newPassword !== confirmPassword"
                  class="text-red-400 text-xs mt-1 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  Las contraseñas no coinciden
                </p>
              </transition>
            </div>

            <button
              @click="handleResetSubmit"
              :disabled="loadingForgot || !isForgotPasswordValid"
              class="w-full bg-teal-500 hover:bg-teal-400 active:scale-95 disabled:bg-teal-800 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-teal-500/20"
            >
              <svg v-if="loadingForgot" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span>{{ loadingForgot ? 'Guardando...' : 'Cambiar Contraseña' }}</span>
            </button>
          </div>
        </template>

        <!-- Botón cerrar -->
        <button
          @click="closeModal"
          class="w-full mt-6 text-gray-600 text-xs font-semibold uppercase tracking-widest hover:text-red-400 transition-colors"
        >
          Cancelar y cerrar
        </button>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps(['show'])
const emit  = defineEmits(['close'])

const forgotStep      = ref(1)
const forgotEmail     = ref('')
const otpCode         = ref('')
const loadingForgot   = ref(false)
const newPassword     = ref('')
const confirmPassword = ref('')
const showNewPass     = ref(false)
const showConfirmPass = ref(false)
const errorMsg        = ref('')
const successMsg      = ref('')

const delay = (ms) => new Promise((r) => setTimeout(r, ms))

/* ── Barra de seguridad ── */
const seguridadNivel = computed(() => {
  const p = newPassword.value
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
  ['', 'text-red-400', 'text-orange-400', 'text-yellow-400', 'text-green-400'][seguridadNivel.value] || 'text-red-400'
)

/* ── Requisitos ── */
const forgotPasswordStrength = computed(() => ({
  hasLength: newPassword.value.length === 8,
  hasUpper:  /[A-Z]/.test(newPassword.value),
  hasNumber: /[0-9]/.test(newPassword.value),
  hasLower:  /[a-z]/.test(newPassword.value),
}))

const requisitos = computed(() => [
  { label: 'Exactamente 8 caracteres',   cumple: forgotPasswordStrength.value.hasLength },
  { label: 'Al menos 1 letra mayúscula', cumple: forgotPasswordStrength.value.hasUpper  },
  { label: 'Al menos 1 letra minúscula', cumple: forgotPasswordStrength.value.hasLower  },
  { label: 'Al menos 1 número (0–9)',    cumple: forgotPasswordStrength.value.hasNumber },
])

const isForgotPasswordValid = computed(() =>
  forgotPasswordStrength.value.hasLength &&
  forgotPasswordStrength.value.hasUpper  &&
  forgotPasswordStrength.value.hasNumber &&
  newPassword.value === confirmPassword.value
)

/* ── Acciones ── */
async function sendOtpCode() {
  errorMsg.value = ''
  if (!forgotEmail.value.toLowerCase().endsWith('@gmail.com')) {
    errorMsg.value = 'Ingresa un correo @gmail.com válido.'
    return
  }
  loadingForgot.value = true
  await delay(2000)
  loadingForgot.value = false
  forgotStep.value = 2
}

async function verifyOtpCode() {
  errorMsg.value = ''
  if (otpCode.value.length !== 6) {
    errorMsg.value = 'El código debe tener exactamente 6 dígitos.'
    return
  }
  loadingForgot.value = true
  await delay(1500)
  loadingForgot.value = false
  forgotStep.value = 3
}

async function handleResetSubmit() {
  if (!isForgotPasswordValid.value) return
  errorMsg.value   = ''
  successMsg.value = ''
  loadingForgot.value = true
  await delay(2000)
  loadingForgot.value = false
  successMsg.value = '¡Contraseña actualizada correctamente! Ya puedes iniciar sesión.'
  await delay(1800)
  closeModal()
}

function closeModal() {
  emit('close')
  setTimeout(() => {
    forgotStep.value      = 1
    forgotEmail.value     = ''
    otpCode.value         = ''
    newPassword.value     = ''
    confirmPassword.value = ''
    showNewPass.value     = false
    showConfirmPass.value = false
    errorMsg.value        = ''
    successMsg.value      = ''
  }, 300)
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from,   .fade-leave-to     { opacity: 0; }

.slide-down-enter-active { transition: all 0.3s ease; }
.slide-down-enter-from   { opacity: 0; transform: translateY(-5px); }
</style>