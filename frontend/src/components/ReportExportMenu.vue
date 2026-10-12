<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const emit = defineEmits(['excel', 'pdf', 'print'])

const open = ref(false)
const menuRef = ref(null)

const close = () => { open.value = false }

const handleAction = (eventName) => {
  emit(eventName)
  close()
}

const handleClickOutside = (event) => {
  if (!menuRef.value?.contains(event.target)) close()
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div ref="menuRef" class="relative z-[3000]">
    <button
      type="button"
      @click.stop="open = !open"
      class="inline-flex items-center gap-2 rounded-xl border border-[#2B2353]/25 bg-[#2B2353]/10 px-4 py-2.5 text-sm font-semibold text-[#2B2353] shadow-sm transition hover:bg-[#2B2353]/15"
    >
      Exportar
      <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="open"
        class="absolute right-0 top-full z-[3000] mt-2 w-48 origin-top-right overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
      >
        <button
          type="button"
          @click="handleAction('excel')"
          class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-xs font-medium text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-700"
        >
          <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2h8L20 7.5v12A2.5 2.5 0 0 1 17.5 22h-11A2.5 2.5 0 0 1 4 19.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 12h8M8 16h8" />
          </svg>
          Exportar Excel
        </button>
        <button
          type="button"
          @click="handleAction('pdf')"
          class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-xs font-medium text-gray-700 transition hover:bg-[#2B2353]/10 hover:text-[#2B2353]"
        >
          <svg class="h-4 w-4 shrink-0 text-[#2B2353]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6.5A2.5 2.5 0 0 0 4 4.5v15A2.5 2.5 0 0 0 6.5 22h11a2.5 2.5 0 0 0 2.5-2.5V8l-6-6Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 16h8M8 12h4" />
          </svg>
          Exportar PDF
        </button>
        <div class="mx-3 border-t border-gray-100" />
        <button
          type="button"
          @click="handleAction('print')"
          class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-xs font-medium text-gray-700 transition hover:bg-gray-50"
        >
          <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 14h10v7H7z" />
          </svg>
          Imprimir
        </button>
      </div>
    </Transition>
  </div>
</template>
