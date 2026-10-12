<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const lineas = ref([])
const inventario = ref([])
const loading = ref(false)
const submitting = ref(false)
const showModal = ref(false)
const editando = ref(null)
const busqueda = ref('')
const estadoFiltro = ref('')
const tab = ref('general')

const form = ref({ nombre: '', descripcion: '', estado: 'activa', turno: 'manana' })
const lista = (data) => Array.isArray(data) ? data : (data.data ?? [])

const lineasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase()
  return lineas.value.filter(l => {
    const texto = !q || l.nombre?.toLowerCase().includes(q) || l.turno?.toLowerCase().includes(q) || l.descripcion?.toLowerCase().includes(q)
    const estado = !estadoFiltro.value || l.estado === estadoFiltro.value
    return texto && estado
  })
})

const entradasRecientes = computed(() =>
  inventario.value.filter(m => ['ingreso', 'ajuste'].includes(m.clasificacion)).slice(0, 6)
)
const salidasRecientes = computed(() =>
  inventario.value.filter(m => m.clasificacion === 'salida').slice(0, 6)
)
const stats = computed(() => ({
  entradas: entradasRecientes.value.length,
  salidas: salidasRecientes.value.length,
}))
const paramsExport = computed(() => ({ buscar: busqueda.value || null, estado: estadoFiltro.value || null }))

const cargar = async () => {
  loading.value = true
  try {
    const [resLineas, resInventario] = await Promise.all([
      api.get('/lineas'),
      api.get('/inventario', { params: { limit: 200 } }),
    ])
    lineas.value = lista(resLineas.data)
    inventario.value = lista(resInventario.data)
  } finally {
    loading.value = false
  }
}

const abrirNuevo = () => {
  editando.value = null
  form.value = { nombre: '', descripcion: '', estado: 'activa', turno: 'manana' }
  showModal.value = true
}
const abrirEditar = (linea) => {
  editando.value = linea.id_linea
  form.value = {
    nombre: linea.nombre || '',
    descripcion: linea.descripcion || '',
    estado: linea.estado || 'activa',
    turno: linea.turno || 'manana',
  }
  showModal.value = true
}
const guardar = async () => {
  submitting.value = true
  try {
    if (editando.value) await api.put(`/lineas/${editando.value}`, form.value)
    else await api.post('/lineas', form.value)
    showModal.value = false
    await cargar()
  } finally {
    submitting.value = false
  }
}
const eliminar = async (linea) => {
  if (!confirm(`Eliminar ${linea.nombre}?`)) return
  await api.delete(`/lineas/${linea.id_linea}`)
  await cargar()
}
const exportarPDF = async () => {
  const res = await api.get('/reportes/lineas/pdf', { responseType: 'blob', params: paramsExport.value })
  descargarPdfBlob(res.data, 'reporte_lineas.pdf')
}
const imprimirReporte = async () => {
  const res = await api.get('/reportes/lineas/pdf', { responseType: 'blob', params: paramsExport.value })
  imprimirPdfBlob(res.data, 'reporte_lineas')
}
const exportarExcel = async () => {
  const res = await api.get('/reportes/lineas/excel', { responseType: 'blob', params: paramsExport.value })
  descargarBlob(res.data, 'reporte_lineas.xlsx')
}
const fecha = (f) => f ? String(f).replace('T', ' ').slice(0, 16) : '-'
const badge = (estado) => ({
  activa: 'bg-emerald-100 text-emerald-700',
  pausada: 'bg-amber-100 text-amber-700',
  mantenimiento: 'bg-red-100 text-red-700',
}[estado] || 'bg-gray-100 text-gray-600')

onMounted(cargar)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Linea Produccion</h2>
        <p class="text-sm text-gray-500 mt-0.5">Panel de flujo, entradas, salidas y lineas activas.</p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <ReportExportMenu @excel="exportarExcel" @pdf="exportarPDF" @print="imprimirReporte" />
        <button @click="abrirNuevo" class="px-5 py-2.5 rounded-xl bg-[#2B2353] text-white text-sm font-semibold shadow-md">Nueva Linea</button>
      </div>
    </div>

    <div class="flex gap-3 border-b border-gray-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Entradas hoy</p><p class="text-2xl font-bold">{{ stats.entradas }}</p><p class="text-xs text-emerald-700">Inventario reciente</p></div>
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Salidas hoy</p><p class="text-2xl font-bold">{{ stats.salidas }}</p><p class="text-xs text-emerald-700">Despachos recientes</p></div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
      <h3 class="font-bold text-gray-800 mb-4">Flujo de produccion</h3>
      <div class="flex items-center gap-3 overflow-x-auto pb-1">
        <div class="min-w-40 rounded-xl bg-emerald-50 text-emerald-800 px-4 py-3 text-sm text-center font-semibold">Entrada<br></div>
        <span class="text-gray-400">-></span>
        <div class="min-w-44 rounded-xl bg-amber-50 text-amber-800 px-4 py-3 text-sm text-center font-semibold">Proceso<br></div>
        <span class="text-gray-400">-></span>
        <div class="min-w-44 rounded-xl bg-blue-50 text-blue-800 px-4 py-3 text-sm text-center font-semibold">Salida<br></div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
      <input v-model="busqueda" class="md:col-span-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm" placeholder="Buscar linea, turno o descripcion..." />
      <select v-model="estadoFiltro" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
        <option value="">Todos los estados</option>
        <option value="activa">Activa</option>
        <option value="pausada">Pausada</option>
        <option value="mantenimiento">Mantenimiento</option>
      </select>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3">
        <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
        <p class="text-sm text-gray-400">Cargando lineas...</p>
      </div>
    </div>
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between"><h3 class="font-bold text-gray-800">Entradas recientes</h3><span class="text-xs text-gray-400">{{ entradasRecientes.length }}</span></div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50"><tr><th class="text-left px-5 py-3">Insumo</th><th class="text-left px-5 py-3">Cant.</th><th class="text-left px-5 py-3">Estado</th></tr></thead>
          <tbody>
            <tr v-for="m in entradasRecientes" :key="m.id_inventario" class="border-t"><td class="px-5 py-3 font-semibold">{{ m.producto?.nombre }}</td><td class="px-5 py-3">{{ m.cantidad }}</td><td class="px-5 py-3"><span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs">Aprobado</span></td></tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between"><h3 class="font-bold text-gray-800">Salidas recientes</h3><span class="text-xs text-gray-400">{{ salidasRecientes.length }}</span></div>
        <table class="w-full text-sm">
          <thead class="bg-gray-50"><tr><th class="text-left px-5 py-3">Producto</th><th class="text-left px-5 py-3">Unidades</th><th class="text-left px-5 py-3">Lote</th></tr></thead>
          <tbody>
            <tr v-for="m in salidasRecientes" :key="m.id_inventario" class="border-t"><td class="px-5 py-3 font-semibold">{{ m.producto?.nombre }}</td><td class="px-5 py-3">{{ m.cantidad }}</td><td class="px-5 py-3">{{ m.lote?.codigo_lote || '-' }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="text-left px-5 py-3">Linea</th><th class="text-left px-5 py-3">Turno</th><th class="text-left px-5 py-3">Estado</th><th class="text-left px-5 py-3">Lotes</th><th class="text-right px-5 py-3">Acciones</th></tr></thead>
        <tbody>
          <tr v-for="linea in lineasFiltradas" :key="linea.id_linea" class="border-t hover:bg-gray-50">
            <td class="px-5 py-3"><b>{{ linea.nombre }}</b><p class="text-xs text-gray-500">{{ linea.descripcion }}</p></td>
            <td class="px-5 py-3">{{ linea.turno || '-' }}</td>
            <td class="px-5 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="badge(linea.estado)">{{ linea.estado || 'sin estado' }}</span></td>
            <td class="px-5 py-3">{{ linea.lotes?.length || 0 }}</td>
            <td class="px-5 py-3 text-right"><button @click="abrirEditar(linea)" class="text-[#2B2353] font-semibold mr-3">Editar</button><button @click="eliminar(linea)" class="text-red-500 font-semibold">Eliminar</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5 flex justify-between"><h3 class="text-white font-bold">{{ editando ? 'Editar linea' : 'Nueva linea' }}</h3><button @click="showModal = false" class="text-white/70">Cerrar</button></div>
          <div class="p-6 space-y-4">
            <input v-model="form.nombre" class="w-full px-4 py-2.5 rounded-xl border border-gray-200" placeholder="Nombre de la linea" />
            <div class="grid grid-cols-2 gap-3">
              <select v-model="form.turno" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="manana">Manana</option><option value="tarde">Tarde</option><option value="noche">Noche</option></select>
              <select v-model="form.estado" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="activa">Activa</option><option value="pausada">Pausada</option><option value="mantenimiento">Mantenimiento</option></select>
            </div>
            <textarea v-model="form.descripcion" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-200" placeholder="Descripcion"></textarea>
          </div>
          <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3"><button @click="showModal = false" class="px-5 py-2.5 rounded-xl bg-white border border-gray-200">Cancelar</button><button @click="guardar" :disabled="submitting" class="px-5 py-2.5 rounded-xl bg-[#2B2353] text-white">{{ submitting ? 'Guardando...' : 'Guardar' }}</button></div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
