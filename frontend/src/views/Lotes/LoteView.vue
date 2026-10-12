<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const lotes = ref([])
const productos = ref([])
const loading = ref(false)
const seleccionado = ref(null)
const busqueda = ref('')
const estadoFiltro = ref('')
const productoFiltro = ref('')
const hoy = new Date()
const editandoVencimiento = ref(false)
const vencimientoEdit = ref('')
const guardandoVencimiento = ref(false)
const errorVencimiento = ref('')

const lista = (data) => Array.isArray(data) ? data : (data.data ?? [])
const productosTerminados = computed(() => productos.value.filter(p => p.tipo_producto === 'Producto Terminado'))
const fechaCorta = (fecha) => fecha ? String(fecha).slice(0, 10) : '-'
const diasHasta = (fecha) => fecha ? Math.ceil((new Date(fecha) - hoy) / 86400000) : 9999
const formatCantidad = (valor) => {
  const numero = Number(valor || 0)
  return Number.isInteger(numero) ? numero.toString() : numero.toFixed(3).replace(/0+$/, '').replace(/\.$/, '')
}
const stockPorProducto = computed(() => {
  const mapa = {}
  lotes.value.forEach((lote) => {
    if (lote.estado === 'inactivo' || lote.estado === 'vencido') return
    const id = lote.producto_id || lote.producto?.id_producto
    if (!id) return
    mapa[id] = (mapa[id] || 0) + Number(lote.cantidad || 0)
  })
  return mapa
})
const stockProductoSeleccionado = computed(() => {
  const id = seleccionado.value?.producto_id || seleccionado.value?.producto?.id_producto
  return id ? stockPorProducto.value[id] || 0 : 0
})
const lotesDelProductoSeleccionado = computed(() => {
  const id = seleccionado.value?.producto_id || seleccionado.value?.producto?.id_producto
  if (!id) return []
  return lotes.value
    .filter(l => Number(l.producto_id || l.producto?.id_producto) === Number(id))
    .sort((a, b) => new Date(a.fecha_fin || 0) - new Date(b.fecha_fin || 0))
})
const totalReceta = computed(() => seleccionado.value?.producto?.receta?.length || 0)
const diasRestantesSeleccionado = computed(() => diasHasta(seleccionado.value?.fecha_fin))
const progresoVidaUtil = computed(() => {
  if (!seleccionado.value?.fecha_inicio || !seleccionado.value?.fecha_fin) return 0
  const inicio = new Date(seleccionado.value.fecha_inicio)
  const fin = new Date(seleccionado.value.fecha_fin)
  const total = fin - inicio
  if (total <= 0) return 100
  return Math.min(100, Math.max(0, Math.round(((hoy - inicio) / total) * 100)))
})

const estadoCalculado = (lote) => {
  if (lote.estado === 'inactivo') return 'inactivo'
  if (lote.estado === 'vencido') return 'vencido'
  if (lote.estado === 'cerrado') return 'cerrado'
  if (lote.estado === 'incidencia') return 'incidencia'
  const dias = diasHasta(lote.fecha_fin)
  if (dias <= 0) return 'vencido'
  if (dias >= 0 && dias <= 30) return 'por vencer'
  return lote.estado || 'activo'
}

const lotesFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return lotes.value.filter(l => {
    const texto = !q ||
      l.codigo_lote?.toLowerCase().includes(q) ||
      l.producto?.nombre?.toLowerCase().includes(q) ||
      l.producto?.codigo?.toLowerCase().includes(q)
    const estado = !estadoFiltro.value || estadoCalculado(l) === estadoFiltro.value
    const producto = !productoFiltro.value || Number(l.producto_id) === Number(productoFiltro.value)
    return texto && estado && producto
  })
})

const stats = computed(() => ({
  activos: lotes.value.filter(l => ['activo', 'por vencer'].includes(estadoCalculado(l))).length,
  total: lotes.value.length,
  unidades: lotes.value
    .filter(l => !['inactivo', 'vencido'].includes(estadoCalculado(l)))
    .reduce((s, l) => s + Number(l.cantidad || 0), 0),
  porVencer: lotes.value.filter(l => estadoCalculado(l) === 'por vencer').length,
  vencidos: lotes.value.filter(l => estadoCalculado(l) === 'vencido').length,
  incidencias: lotes.value.filter(l => estadoCalculado(l) === 'incidencia').length,
}))

const paramsExport = computed(() => ({
  buscar: busqueda.value || null,
  estado: estadoFiltro.value || null,
  producto_id: productoFiltro.value || null,
}))

const cargar = async () => {
  loading.value = true
  try {
    const [resLotes, resProductos] = await Promise.all([
      api.get('/lotes'),
      api.get('/productos?per_page=200'),
    ])
    lotes.value = lista(resLotes.data)
    productos.value = lista(resProductos.data)
    if (!seleccionado.value && lotes.value.length) seleccionarLote(lotes.value[0])
  } finally {
    loading.value = false
  }
}

const exportarPDF = async () => {
  const res = await api.get('/reportes/lotes/pdf', { responseType: 'blob', params: paramsExport.value })
  descargarPdfBlob(res.data, 'reporte_lotes.pdf')
}

const imprimirReporte = async () => {
  const res = await api.get('/reportes/lotes/pdf', { responseType: 'blob', params: paramsExport.value })
  imprimirPdfBlob(res.data, 'reporte_lotes')
}

const exportarExcel = async () => {
  const res = await api.get('/reportes/lotes/excel', { responseType: 'blob', params: paramsExport.value })
  descargarBlob(res.data, 'reporte_lotes.xlsx')
}

const badgeEstado = (estado) => ({
  activo: 'bg-emerald-100 text-emerald-700 border-emerald-200',
  'por vencer': 'bg-amber-100 text-amber-800 border-amber-200',
  vencido: 'bg-red-100 text-red-700 border-red-200',
  inactivo: 'bg-gray-100 text-gray-600 border-gray-200',
  incidencia: 'bg-red-100 text-red-700 border-red-200',
  cerrado: 'bg-gray-100 text-gray-600 border-gray-200',
}[estado] || 'bg-blue-100 text-blue-700 border-blue-200')

const barraEstado = (estado) => ({
  activo: 'bg-emerald-500',
  'por vencer': 'bg-amber-500',
  vencido: 'bg-red-500',
  inactivo: 'bg-gray-400',
  incidencia: 'bg-red-500',
}[estado] || 'bg-emerald-500')

const seleccionarLote = (lote) => {
  seleccionado.value = lote
  editandoVencimiento.value = false
  vencimientoEdit.value = fechaCorta(lote.fecha_fin)
  errorVencimiento.value = ''
}

const iniciarEdicionVencimiento = () => {
  vencimientoEdit.value = fechaCorta(seleccionado.value?.fecha_fin)
  errorVencimiento.value = ''
  editandoVencimiento.value = true
}

const guardarVencimiento = async () => {
  if (!seleccionado.value || !vencimientoEdit.value) return
  errorVencimiento.value = ''
  guardandoVencimiento.value = true
  try {
    const { data } = await api.patch(`/lotes/${seleccionado.value.id_lote}`, {
      fecha_fin: vencimientoEdit.value,
    })
    const actualizado = data
    lotes.value = lotes.value.map(l => l.id_lote === actualizado.id_lote ? actualizado : l)
    seleccionado.value = actualizado
    editandoVencimiento.value = false
  } catch (error) {
    errorVencimiento.value = error?.response?.data?.message || 'No se pudo actualizar el vencimiento.'
  } finally {
    guardandoVencimiento.value = false
  }
}

const desactivarLote = async () => {
  if (!seleccionado.value) return
  await api.delete(`/lotes/${seleccionado.value.id_lote}`)
  await cargar()
  const actualizado = lotes.value.find(l => l.id_lote === seleccionado.value.id_lote)
  if (actualizado) seleccionarLote(actualizado)
}

const activarLote = async () => {
  if (!seleccionado.value) return
  const { data } = await api.patch(`/lotes/${seleccionado.value.id_lote}/activar`)
  lotes.value = lotes.value.map(l => l.id_lote === data.id_lote ? data : l)
  seleccionarLote(data)
}

onMounted(cargar)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Lotes</h2>
        <p class="text-sm text-gray-500 mt-0.5">Productos terminados con lote generado automaticamente.</p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <ReportExportMenu @excel="exportarExcel" @pdf="exportarPDF" @print="imprimirReporte" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Total lotes activos</p><p class="text-2xl font-bold">{{ stats.activos }}</p></div>
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Total lotes</p><p class="text-2xl font-bold">{{ stats.total }}</p></div>
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Unidades en lotes</p><p class="text-2xl font-bold text-[#2B2353]">{{ formatCantidad(stats.unidades) }}</p></div>
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Prox. a vencer</p><p class="text-2xl font-bold text-amber-600">{{ stats.porVencer }}</p></div>
      <div class="bg-white rounded-2xl border border-gray-100 p-5"><p class="text-xs text-gray-500">Vencidos</p><p class="text-2xl font-bold text-red-600">{{ stats.vencidos }}</p></div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
      <input v-model="busqueda" class="md:col-span-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm" placeholder="Buscar lote, producto o codigo..." />
      <select v-model="productoFiltro" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
        <option value="">Todos los productos</option>
        <option v-for="p in productosTerminados" :key="p.id_producto" :value="p.id_producto">{{ p.nombre }}</option>
      </select>
      <select v-model="estadoFiltro" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
        <option value="">Todos los lotes</option>
        <option value="activo">Activos</option>
        <option value="por vencer">Por vencer</option>
        <option value="vencido">Vencidos</option>
        <option value="inactivo">Inactivos</option>
      </select>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3">
        <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
        <p class="text-sm text-gray-400">Cargando lotes...</p>
      </div>
    </div>
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <button v-for="lote in lotesFiltrados" :key="lote.id_lote" @click="seleccionarLote(lote)"
        class="text-left bg-white rounded-2xl border p-4 shadow-sm hover:shadow-md transition"
        :class="[seleccionado?.id_lote === lote.id_lote ? 'border-[#2B2353]' : 'border-gray-100', estadoCalculado(lote) === 'inactivo' ? 'opacity-70 bg-gray-50' : '']">
        <p class="text-xs font-bold text-emerald-700">{{ lote.codigo_lote }}</p>
        <h3 class="font-bold text-gray-800">{{ lote.producto?.nombre || 'Producto terminado' }}</h3>
        <p class="text-sm text-gray-500">{{ lote.producto?.codigo || 'Sin codigo' }} - Vence {{ fechaCorta(lote.fecha_fin) }}</p>
        <div class="mt-3 flex items-center justify-between rounded-xl bg-[#FAFBFF] border border-gray-100 px-3 py-2">
          <span class="text-xs font-semibold text-gray-500">Cantidad del lote</span>
          <strong class="text-sm text-[#2B2353]">{{ formatCantidad(lote.cantidad) }}</strong>
        </div>
        <div class="h-1 rounded-full bg-gray-100 my-3 overflow-hidden">
          <div class="h-full" :class="barraEstado(estadoCalculado(lote))" :style="{ width: estadoCalculado(lote) === 'vencido' ? '100%' : (estadoCalculado(lote) === 'por vencer' ? '70%' : '42%') }"></div>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border" :class="badgeEstado(estadoCalculado(lote))">{{ estadoCalculado(lote) }}</span>
      </button>
    </div>

    <section v-if="seleccionado" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="bg-gradient-to-r from-[#2B2353] via-[#627DF2] to-[#9150F7] p-6 text-white">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">Control de lote perecedero</p>
            <h3 class="text-2xl font-bold mt-1">{{ seleccionado.producto?.nombre || 'Producto terminado' }}</h3>
            <p class="text-white/75 text-sm mt-1">{{ seleccionado.codigo_lote }} - {{ seleccionado.producto?.codigo || 'Sin codigo' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full border bg-white/15 border-white/25 text-white">
              {{ estadoCalculado(seleccionado) }}
            </span>
            <button
              v-if="estadoCalculado(seleccionado) !== 'inactivo'"
              @click="desactivarLote"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/15 border border-white/25 text-white hover:bg-white/25"
            >
              Desactivar lote
            </button>
            <button
              v-else
              @click="activarLote"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-[#2B2353] hover:bg-white/90"
            >
              Activar lote
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-6">
          <div class="rounded-2xl bg-white/12 border border-white/15 p-4">
            <p class="text-xs text-white/70 font-semibold uppercase">Produccion</p>
            <strong class="block text-lg mt-1">{{ fechaCorta(seleccionado.fecha_inicio) }}</strong>
            <small class="text-white/60">Fecha de registro del producto</small>
          </div>
          <div class="rounded-2xl bg-white/12 border border-white/15 p-4">
            <p class="text-xs text-white/70 font-semibold uppercase">Vencimiento</p>
            <strong class="block text-lg mt-1">{{ fechaCorta(seleccionado.fecha_fin) }}</strong>
            <small class="text-white/60">Vida util base: 4 meses</small>
          </div>
          <div class="rounded-2xl bg-white/12 border border-white/15 p-4">
            <p class="text-xs text-white/70 font-semibold uppercase">Dias restantes</p>
            <strong class="block text-lg mt-1" :class="diasRestantesSeleccionado <= 30 ? 'text-amber-200' : 'text-white'">
              {{ diasRestantesSeleccionado >= 0 ? diasRestantesSeleccionado : 0 }}
            </strong>
            <small class="text-white/60">{{ diasRestantesSeleccionado < 0 ? 'Lote vencido' : 'Antes del vencimiento' }}</small>
          </div>
          <div class="rounded-2xl bg-white/12 border border-white/15 p-4">
            <p class="text-xs text-white/70 font-semibold uppercase">Cantidad lote</p>
            <strong class="block text-lg mt-1">{{ formatCantidad(seleccionado.cantidad) }}</strong>
            <small class="text-white/60">Unidades de este lote</small>
          </div>
        </div>
      </div>

      <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Producto</p>
              <p class="font-bold text-gray-900 mt-1">{{ seleccionado.producto?.nombre || '-' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Codigo producto</p>
              <p class="font-bold text-gray-900 mt-1">{{ seleccionado.producto?.codigo || '-' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Linea</p>
              <p class="font-bold text-gray-900 mt-1">{{ seleccionado.linea?.nombre || 'Sin linea' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Turno</p>
              <p class="font-bold text-gray-900 mt-1">{{ seleccionado.turno || 'Sin turno' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Stock total del producto</p>
              <p class="font-bold text-gray-900 mt-1">{{ formatCantidad(stockProductoSeleccionado) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-4">
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">Lotes del producto</p>
              <p class="font-bold text-gray-900 mt-1">{{ lotesDelProductoSeleccionado.length }}</p>
            </div>
          </div>

          <div class="overflow-hidden rounded-2xl border border-gray-100">
            <div class="px-5 py-4 bg-gray-50">
              <h4 class="font-bold text-gray-900">Stock dividido por lote</h4>
              <p class="text-xs text-gray-500 mt-0.5">El inventario total se conserva por producto, pero aqui se ve como se reparte por cada fabricacion.</p>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-white text-gray-500">
                <tr>
                  <th class="text-left px-5 py-3 font-semibold">Lote</th>
                  <th class="text-left px-5 py-3 font-semibold">Produccion</th>
                  <th class="text-left px-5 py-3 font-semibold">Vence</th>
                  <th class="text-left px-5 py-3 font-semibold">Estado</th>
                  <th class="text-right px-5 py-3 font-semibold">Cantidad</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="loteProducto in lotesDelProductoSeleccionado"
                  :key="loteProducto.id_lote"
                  class="border-t border-gray-100"
                  :class="loteProducto.id_lote === seleccionado.id_lote ? 'bg-violet-50/70' : 'hover:bg-[#FAFBFF]'"
                >
                  <td class="px-5 py-3 font-semibold text-gray-900">{{ loteProducto.codigo_lote }}</td>
                  <td class="px-5 py-3 text-gray-600">{{ fechaCorta(loteProducto.fecha_inicio) }}</td>
                  <td class="px-5 py-3 text-gray-600">{{ fechaCorta(loteProducto.fecha_fin) }}</td>
                  <td class="px-5 py-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full border" :class="badgeEstado(estadoCalculado(loteProducto))">{{ estadoCalculado(loteProducto) }}</span>
                  </td>
                  <td class="px-5 py-3 text-right font-bold text-[#2B2353]">{{ formatCantidad(loteProducto.cantidad) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="overflow-hidden rounded-2xl border border-gray-100">
            <div class="px-5 py-4 bg-gray-50 flex items-center justify-between">
              <div>
                <h4 class="font-bold text-gray-900">Receta del producto</h4>
                <p class="text-xs text-gray-500 mt-0.5">Materia prima utilizada para este producto terminado.</p>
              </div>
              <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white border border-gray-200 text-gray-500">{{ totalReceta }} item(s)</span>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-white text-gray-500">
                <tr>
                  <th class="text-left px-5 py-3 font-semibold">Materia prima</th>
                  <th class="text-left px-5 py-3 font-semibold">Cantidad por unidad</th>
                  <th class="text-left px-5 py-3 font-semibold">Total receta</th>
                  <th class="text-left px-5 py-3 font-semibold">Unidad</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in seleccionado.producto?.receta || []" :key="r.id_receta" class="border-t border-gray-100 hover:bg-[#FAFBFF]">
                  <td class="px-5 py-3 font-semibold text-gray-900">{{ r.materia_prima?.nombre || '-' }}</td>
                  <td class="px-5 py-3 text-gray-600">{{ r.cantidad_por_unidad }}</td>
                  <td class="px-5 py-3 text-gray-600">{{ r.cantidad_total }}</td>
                  <td class="px-5 py-3 text-gray-600">{{ r.unidad || r.materia_prima?.presentacion || '-' }}</td>
                </tr>
                <tr v-if="!seleccionado.producto?.receta?.length">
                  <td colspan="4" class="px-5 py-8 text-center text-gray-400">Este producto terminado aun no tiene receta registrada.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <aside class="rounded-2xl border border-gray-100 bg-[#FAFBFF] p-5 h-fit">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h4 class="font-bold text-gray-900">Vencimiento</h4>
              <p class="text-xs text-gray-500 mt-0.5">Ajuste permitido por control de calidad.</p>
            </div>
            <button
              v-if="!editandoVencimiento"
              @click="iniciarEdicionVencimiento"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold text-[#2B2353] bg-white border border-gray-200 hover:border-[#9150F7]/40"
            >
              Editar
            </button>
          </div>

          <div class="mt-5">
            <div class="h-3 rounded-full bg-gray-200 overflow-hidden">
              <div
                class="h-full rounded-full"
                :class="barraEstado(estadoCalculado(seleccionado))"
                :style="{ width: `${progresoVidaUtil}%` }"
              ></div>
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
              <span>{{ fechaCorta(seleccionado.fecha_inicio) }}</span>
              <span>{{ fechaCorta(seleccionado.fecha_fin) }}</span>
            </div>
          </div>

          <div class="mt-5 rounded-xl bg-white border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-bold uppercase">Fecha actual</p>
            <p class="font-bold text-gray-900 mt-1">{{ fechaCorta(seleccionado.fecha_fin) }}</p>
          </div>

          <div v-if="editandoVencimiento" class="mt-4 space-y-3">
            <label class="block">
              <span class="block text-xs font-semibold text-gray-500 mb-1.5">Nueva fecha de vencimiento</span>
              <input
                v-model="vencimientoEdit"
                type="date"
                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#9150F7]/25"
              />
            </label>
            <p v-if="errorVencimiento" class="text-xs text-red-500">{{ errorVencimiento }}</p>
            <div class="flex justify-end gap-2">
              <button
                @click="editandoVencimiento = false"
                class="px-3 py-2 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-100"
              >
                Cancelar
              </button>
              <button
                @click="guardarVencimiento"
                :disabled="guardandoVencimiento"
                class="px-3 py-2 rounded-lg text-xs font-semibold text-white bg-[#2B2353] hover:bg-[#3a2f70] disabled:opacity-60"
              >
                {{ guardandoVencimiento ? 'Guardando...' : 'Guardar fecha' }}
              </button>
            </div>
          </div>
        </aside>
      </div>
    </section>
  </div>
</template>
