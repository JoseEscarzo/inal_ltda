<template>
  <div class="min-h-screen text-white selection:bg-purple-500/30 font-inter bg-indigo-900">

    <div class="fixed inset-0 bg-gradient-to-br from-indigo-800 via-purple-700 to-fuchsia-800 z-0"></div>

    <ModalRecuperar
      :show="showForgotModal"
      @close="showForgotModal = false"
    />

    <div class="min-h-screen flex flex-col items-center justify-center relative overflow-hidden z-10">

      <div class="absolute top-6 right-6 md:top-8 md:right-10 z-30 flex items-center gap-4">
        <div class="text-right">
          <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-md" style="font-family:'Playfair Display',serif;">
            IN<span class="text-yellow-300">AL</span>
          </h1>
          <p class="text-white/90 text-xs md:text-sm font-medium tracking-wide">Industria de Alimentos</p>
        </div>
        <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl overflow-hidden border-2 border-white/30 shadow-lg bg-white p-1">
          <img :src="logoUrl" alt="Logo" class="w-full h-full object-contain drop-shadow-md" />
        </div>
      </div>

      <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div
          v-for="h in helados"
          :key="h.id"
          class="helado drop-shadow-2xl"
          :style="`left:${h.x}%; animation-duration:${h.dur}s; animation-delay:${h.delay}s;`"
          v-html="h.svg"
        ></div>
      </div>

      <div class="w-full max-w-md relative z-10 px-4 sm:px-0 form-wrapper mt-24" :class="{'wrong-entry': isShakeError}">

        <div ref="pandaRef" class="panda-wrapper absolute -top-24 left-1/2 transform -translate-x-1/2 w-48 h-40 z-0">
          <div class="absolute -top-4 left-0 w-14 h-14 bg-[#1a1a1a] rounded-full"></div>
          <div class="absolute -top-4 right-0 w-14 h-14 bg-[#1a1a1a] rounded-full"></div>

          <div class="absolute inset-0 bg-[#f9f9f9] rounded-t-full border-4 border-gray-200 overflow-hidden flex justify-center">
            <div class="absolute top-12 left-3 w-16 h-20 bg-[#1a1a1a] rounded-[50px] -rotate-12 flex items-center justify-center">
              <div class="w-6 h-6 bg-white rounded-full relative shadow-inner">
                <div class="absolute top-1/2 left-1/2 w-3.5 h-3.5 bg-[#1a1a1a] rounded-full transition-none origin-center"
                     :style="{ transform: `translate(calc(-50% + ${eyeX}px), calc(-50% + ${eyeY}px))` }"></div>
              </div>
            </div>

            <div class="absolute top-12 right-3 w-16 h-20 bg-[#1a1a1a] rounded-[50px] rotate-12 flex items-center justify-center">
              <div class="w-6 h-6 bg-white rounded-full relative shadow-inner">
                <div class="absolute top-1/2 left-1/2 w-3.5 h-3.5 bg-[#1a1a1a] rounded-full transition-none origin-center"
                     :style="{ transform: `translate(calc(-50% + ${eyeX}px), calc(-50% + ${eyeY}px))` }"></div>
              </div>
            </div>

            <div class="absolute bottom-4 w-8 h-4 bg-[#1a1a1a] rounded-[20px]"></div>
          </div>
        </div>

        <div class="relative z-10 bg-[#ffffff] backdrop-blur-md rounded-3xl shadow-2xl p-6 sm:p-8 border border-gray-100">

          <div class="paw left absolute -top-4 left-2 w-10 h-14 bg-[#1a1a1a] rounded-full shadow-lg z-20 transition-all duration-500 ease-in-out"
               :class="{ 'hands-up-left': isPasswordFocused }">
            <div class="w-full h-full relative"><div class="absolute top-1 left-2 w-6 h-6 bg-gray-700/30 rounded-full"></div></div>
          </div>

          <div class="paw right absolute -top-4 right-2 w-10 h-14 bg-[#1a1a1a] rounded-full shadow-lg z-20 transition-all duration-500 ease-in-out"
               :class="{ 'hands-up-right': isPasswordFocused }">
            <div class="w-full h-full relative"><div class="absolute top-1 right-2 w-6 h-6 bg-gray-700/30 rounded-full"></div></div>
          </div>

          <div class="absolute -bottom-5 left-10 w-16 h-12 bg-[#1a1a1a] rounded-b-[40px] rounded-t-[10px] z-20 shadow-md">
              <div class="w-full h-full relative"><div class="absolute bottom-2 left-3 w-10 h-6 bg-gray-700/30 rounded-full"></div></div>
          </div>
          <div class="absolute -bottom-5 right-10 w-16 h-12 bg-[#1a1a1a] rounded-b-[40px] rounded-t-[10px] z-20 shadow-md">
              <div class="w-full h-full relative"><div class="absolute bottom-2 right-3 w-10 h-6 bg-gray-700/30 rounded-full"></div></div>
          </div>

          <FormularioLogin
            v-if="isLogin"
            @toggle-mode="isLogin = false"
            @forgot-password="showForgotModal = true"
          />

          <FormularioRegistro
            v-else
            @toggle-mode="isLogin = true"
          />
        </div><div class="mt-6 text-center text-white/80 text-sm font-medium tracking-wide drop-shadow-md">
          Copyright &copy; INAL 2026 | v1.1.0 | prod
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import FormularioLogin from './FormularioLogin.vue'
import FormularioRegistro from './FormularioRegistro.vue'
import ModalRecuperar from './ModalRecuperar.vue'
import logoImg from '@/assets/panda.png.avif'

const logoUrl = logoImg
const isLogin = ref(true)
const showForgotModal = ref(false)

/* ── ESTADOS REACTIVOS DEL PANDA ── */
const pandaRef = ref(null)
const eyeX = ref(0)
const eyeY = ref(0)
const isPasswordFocused = ref(false)
const isShakeError = ref(false)

/* ── FUNCIONES PARA GENERAR HELADOS 3D (Sabores Dinámicos) ── */
// Paleta con palito de madera, cuerpo de helado y brillo
const crearPaleta = (color1, color2, id) => `
<svg width="90" height="90" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="gradP${id}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="${color1}" />
      <stop offset="100%" stop-color="${color2}" />
    </linearGradient>
  </defs>
  <path d="M45 60 h10 v25 a5 5 0 0 1 -10 0 Z" fill="#D4A373" />
  <path d="M45 60 h5 v25 a5 5 0 0 1 -5 0 Z" fill="#000000" opacity="0.1"/>
  <rect x="30" y="15" width="40" height="55" rx="18" fill="url(#gradP${id})" />
  <rect x="35" y="20" width="8" height="40" rx="4" fill="#ffffff" opacity="0.4" />
</svg>`

// Cono con textura de galleta, bola de helado sombreada y reflejo
const crearCono = (color1, color2, id) => `
<svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <radialGradient id="gradC${id}" cx="35%" cy="35%" r="65%">
      <stop offset="0%" stop-color="${color1}" />
      <stop offset="100%" stop-color="${color2}" />
    </radialGradient>
  </defs>
  <polygon points="50,95 25,45 75,45" fill="#E29578" />
  <polygon points="50,95 50,45 75,45" fill="#000000" opacity="0.15"/>
  <path d="M35 45 L50 70 M45 45 L50 60 M65 45 L50 70 M55 45 L50 60" stroke="#B07D62" stroke-width="1.5" />
  <path d="M 22 45 Q 35 55 50 45 Q 65 55 78 45 Z" fill="url(#gradC${id})" />
  <circle cx="50" cy="30" r="22" fill="url(#gradC${id})" />
  <circle cx="42" cy="22" r="6" fill="#ffffff" opacity="0.3" />
</svg>`

// Copa tipo Sundae de cristal, bola de helado y cereza
const crearCopa = (color1, color2, id) => `
<svg width="95" height="95" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="glass${id}" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.1"/>
    </linearGradient>
    <radialGradient id="gradE${id}" cx="35%" cy="35%" r="65%">
      <stop offset="0%" stop-color="${color1}" />
      <stop offset="100%" stop-color="${color2}" />
    </radialGradient>
  </defs>
  <path d="M40 85 h20 l-5 -15 h-10 Z" fill="#ffffff" opacity="0.4" />
  <circle cx="50" cy="35" r="20" fill="url(#gradE${id})" />
  <circle cx="50" cy="12" r="6" fill="#D50000" />
  <path d="M50 6 Q 55 0 60 5" fill="none" stroke="#388E3C" stroke-width="2" />
  <path d="M30 40 L40 70 H60 L70 40 Z" fill="url(#glass${id})" stroke="#ffffff" stroke-width="1.5"/>
  <path d="M35 42 L42 65" stroke="#ffffff" stroke-width="2" opacity="0.6"/>
</svg>`

/* ── CONFIGURACIÓN DE SABORES Y HELADOS CAYENDO ── */
const sabores = {
  fresa: { c1: '#ff9a9e', c2: '#ff758c' },
  chocolate: { c1: '#8b5a2b', c2: '#4e342e' },
  menta: { c1: '#a7ffeb', c2: '#1de9b6' },
  vainilla: { c1: '#fdfbfb', c2: '#ebedee' },
  mango: { c1: '#f6d365', c2: '#fda085' },
  mora: { c1: '#8fd3f4', c2: '#4facfe' }
}

const helados = [
  { id: 1, x: 8,  dur: 13.5, delay: 0.0, svg: crearPaleta(sabores.fresa.c1, sabores.fresa.c2, 1) },
  { id: 2, x: 18, dur: 16.0, delay: 2.5, svg: crearCono(sabores.chocolate.c1, sabores.chocolate.c2, 2) },
  { id: 3, x: 28, dur: 11.5, delay: 5.0, svg: crearCopa(sabores.menta.c1, sabores.menta.c2, 3) },
  { id: 4, x: 42, dur: 15.0, delay: 1.0, svg: crearCono(sabores.vainilla.c1, sabores.vainilla.c2, 4) },
  { id: 5, x: 55, dur: 19.2, delay: 3.5, svg: crearPaleta(sabores.mango.c1, sabores.mango.c2, 5) },
  { id: 6, x: 68, dur: 12.5, delay: 0.5, svg: crearCono(sabores.fresa.c1, sabores.fresa.c2, 6) },
  { id: 7, x: 78, dur: 17.0, delay: 4.0, svg: crearCopa(sabores.mora.c1, sabores.mora.c2, 7) },
  { id: 8, x: 88, dur: 14.5, delay: 2.0, svg: crearPaleta(sabores.chocolate.c1, sabores.chocolate.c2, 8) },
  { id: 9, x: 95, dur: 18.0, delay: 6.0, svg: crearCono(sabores.menta.c1, sabores.menta.c2, 9) },
  { id: 1, x: 8,  dur: 13.5, delay: 0.0, svg: crearPaleta(sabores.fresa.c1, sabores.fresa.c2, 1) },
  { id: 2, x: 18, dur: 16.0, delay: 2.5, svg: crearCono(sabores.chocolate.c1, sabores.chocolate.c2, 2) },
  { id: 3, x: 28, dur: 11.5, delay: 5.0, svg: crearCopa(sabores.menta.c1, sabores.menta.c2, 3) },
  { id: 4, x: 42, dur: 15.0, delay: 1.0, svg: crearCono(sabores.vainilla.c1, sabores.vainilla.c2, 4) },
  { id: 5, x: 55, dur: 19.2, delay: 3.5, svg: crearPaleta(sabores.mango.c1, sabores.mango.c2, 5) },
  { id: 6, x: 68, dur: 12.5, delay: 0.5, svg: crearCono(sabores.fresa.c1, sabores.fresa.c2, 6) },
  { id: 7, x: 78, dur: 17.0, delay: 4.0, svg: crearCopa(sabores.mora.c1, sabores.mora.c2, 7) },
  { id: 8, x: 88, dur: 14.5, delay: 2.0, svg: crearPaleta(sabores.chocolate.c1, sabores.chocolate.c2, 8) },
  { id: 9, x: 95, dur: 18.0, delay: 6.0, svg: crearCono(sabores.menta.c1, sabores.menta.c2, 9) },
]

/* ── LÓGICA CORREGIDA DEL MOVIMIENTO DE OJOS (Trigonometría) ── */
const handleMouseMove = (event) => {
  if (!pandaRef.value) return;

  const rect = pandaRef.value.getBoundingClientRect();
  const pandaCenterX = rect.left + rect.width / 2;
  const pandaCenterY = rect.top + rect.height / 2;

  const mouseX = event.clientX;
  const mouseY = event.clientY;

  const deltaX = mouseX - pandaCenterX;
  const deltaY = mouseY - pandaCenterY;

  const angle = Math.atan2(deltaY, deltaX);
  const distance = Math.min(7, Math.hypot(deltaX, deltaY) / 30);

  eyeX.value = Math.cos(angle) * distance;
  eyeY.value = Math.sin(angle) * distance;
};

/* ── DETECCIÓN DE CONTRASEÑA Y SHAKE ── */
const handleFocusIn = (e) => {
  if (e.target && e.target.type === 'password') {
    isPasswordFocused.value = true;
  }
};

const handleFocusOut = (e) => {
  if (e.target && e.target.type === 'password') {
    isPasswordFocused.value = false;
  }
};

const handleGlobalClick = (e) => {
  if (e.target && (e.target.tagName === 'BUTTON' || e.target.closest('button'))) {
    // Lógica para error (animación temblor) se activa aquí si es necesario
  }
};

onMounted(() => {
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('focusin', handleFocusIn);
  document.addEventListener('focusout', handleFocusOut);
  document.addEventListener('click', handleGlobalClick);
});

onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('focusin', handleFocusIn);
  document.removeEventListener('focusout', handleFocusOut);
  document.removeEventListener('click', handleGlobalClick);
});

</script>

<style scoped>
/* ══ ESTILOS PARA LOS HELADOS CAYENDO ══ */
.helado {
  position: absolute;
  top: -120px; /* Ajustado por el nuevo tamaño */
  opacity: 0;
  animation: caerHelado linear infinite;
  filter: drop-shadow(0px 10px 8px rgba(0,0,0,0.25)); /* Sombra extra para acentuar el 3D */
}
@keyframes caerHelado {
  0%   { transform: translateY(0) rotate(-15deg); opacity: 0; }
  10%  { opacity: 0.9; }
  90%  { opacity: 0.9; }
  100% { transform: translateY(120vh) rotate(345deg); opacity: 0; }
}

/* ══ FORZAR COLORES DE LOS COMPONENTES HIJOS SIN TOCAR SU BACKEND/VUE ══ */
.form-wrapper :deep(h1),
.form-wrapper :deep(h2),
.form-wrapper :deep(h3) {
  color: #000000 !important;
}

.form-wrapper :deep(a),
.form-wrapper :deep(.text-teal-500),
.form-wrapper :deep(.text-teal-400),
.form-wrapper :deep(span.text-teal-500) {
  color: #4c1d95 !important;
  font-weight: 600 !important;
}

/* ══ ANIMACIONES DEL PANDA ══ */
.hands-up-left {
  transform: translateY(85px) translateX(65px) rotate(35deg);
  height: 60px !important;
}

.hands-up-right {
  transform: translateY(85px) translateX(-65px) rotate(-35deg);
  height: 60px !important;
}

/* ══ ANIMACIÓN SHAKE WRONG-ENTRY ══ */
.wrong-entry {
  animation: shakeError 0.4s ease-in-out;
}
@keyframes shakeError {
  0%, 100% { transform: translateX(0) }
  20%, 60% { transform: translateX(-10px) }
  40%, 80% { transform: translateX(10px) }
}

.font-inter { font-family: 'Inter', sans-serif; }
</style>
