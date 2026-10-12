<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const registros = ref([])
const lotes = ref([])
const destinos = ref([])
const usuarios = ref([])
const loading = ref(false)
const submitting = ref(false)
const showModal = ref(false)
const showEntrega = ref(false)
const seleccionado = ref(null)
const editando = ref(null)
const busqueda = ref('')
const estadoFiltro = ref('')
const destinoFiltro = ref('')
const mapaRef = ref(null)
let mapa = null
let capaRutas = null

const user = computed(() => JSON.parse(localStorage.getItem('user') || 'null') || {})
const esDistribuidor = computed(() => Number(user.value.id_rol) === 5)
const distribuidores = computed(() => usuarios.value.filter(u => Number(u.id_rol) === 5))
const lista = (data) => Array.isArray(data) ? data : (data.data ?? [])
const origen = [-16.5415, -68.1968]
const origenNombre = 'Inal Ltda. Helados Panda'
const origenDireccion = 'Carretera a Viacha, Kilometro 8, Numero 777'

const form = ref({
  lote_id: '',
  destino_id: '',
  responsable_id: '',
  cantidad_productos: 1,
  estado: 'designado',
  observaciones: '',
})
const entrega = ref({ observaciones: '' })

const registrosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return registros.value.filter(r => {
    const texto = !q ||
      r.lote?.codigo_lote?.toLowerCase().includes(q) ||
      r.lote?.producto?.nombre?.toLowerCase().includes(q) ||
      r.destino?.nombre?.toLowerCase().includes(q) ||
      r.responsable?.name?.toLowerCase().includes(q)
    const estado = !estadoFiltro.value || r.estado === estadoFiltro.value
    const destino = !destinoFiltro.value || Number(r.destino_id) === Number(destinoFiltro.value)
    return texto && estado && destino
  })
})

const destinosAsignados = computed(() => {
  if (!esDistribuidor.value) return destinos.value
  const ids = new Set(registros.value.map(r => r.destino_id).filter(Boolean))
  return destinos.value.filter(d => ids.has(d.id_destino))
})

const destinosMapa = computed(() => {
  const base = destinosAsignados.value
  return base.filter(d => !destinoFiltro.value || Number(d.id_destino) === Number(destinoFiltro.value))
})

const paramsExport = computed(() => ({
  buscar: busqueda.value || null,
  estado: estadoFiltro.value || null,
  destino_id: destinoFiltro.value || null,
}))

const cargar = async () => {
  loading.value = true
  try {
    const [resTraz, resLotes, resDest, resUsers] = await Promise.all([
      api.get('/trazabilidad'),
      api.get('/lotes'),
      api.get('/destinos'),
      api.get('/users'),
    ])
    registros.value = lista(resTraz.data)
    lotes.value = lista(resLotes.data)
    destinos.value = lista(resDest.data)
    usuarios.value = resUsers.data.usuarios ?? lista(resUsers.data)
    if (!seleccionado.value && registros.value.length) seleccionado.value = registros.value[0]
    if (esDistribuidor.value && !estadoFiltro.value) estadoFiltro.value = 'designado'
    await dibujarMapa()
  } finally {
    loading.value = false
  }
}

const abrirNuevo = () => {
  editando.value = null
  form.value = { lote_id: '', destino_id: '', responsable_id: distribuidores.value[0]?.id || '', cantidad_productos: 1, estado: 'designado', observaciones: '' }
  showModal.value = true
}
const abrirEditar = (r) => {
  editando.value = r.id_distribucion
  form.value = {
    lote_id: r.lote_id || '',
    destino_id: r.destino_id || '',
    responsable_id: r.responsable_id || '',
    cantidad_productos: r.cantidad_productos || 1,
    estado: r.estado || 'designado',
    observaciones: r.observaciones || '',
  }
  showModal.value = true
}
const guardar = async () => {
  submitting.value = true
  try {
    if (editando.value) await api.put(`/trazabilidad/${editando.value}`, form.value)
    else await api.post('/trazabilidad', form.value)
    showModal.value = false
    await cargar()
  } finally {
    submitting.value = false
  }
}
const abrirEntrega = (r) => {
  seleccionado.value = r
  entrega.value = { observaciones: r.observaciones || '' }
  showEntrega.value = true
}
const marcarEntregado = async () => {
  submitting.value = true
  try {
    await api.put(`/trazabilidad/${seleccionado.value.id_distribucion}`, {
      ...seleccionado.value,
      estado: 'entregado',
      observaciones: entrega.value.observaciones,
    })
    showEntrega.value = false
    await cargar()
  } finally {
    submitting.value = false
  }
}
const eliminar = async (r) => {
  if (!confirm(`Eliminar ruta ${r.lote?.codigo_lote || ''}?`)) return
  await api.delete(`/trazabilidad/${r.id_distribucion}`)
  await cargar()
}
const exportarPDF = async () => {
  const res = await api.get('/reportes/trazabilidad/pdf', { responseType: 'blob', params: paramsExport.value })
  descargarPdfBlob(res.data, 'reporte_trazabilidad.pdf')
}
const imprimirReporte = async () => {
  const res = await api.get('/reportes/trazabilidad/pdf', { responseType: 'blob', params: paramsExport.value })
  imprimirPdfBlob(res.data, 'reporte_trazabilidad')
}
const exportarExcel = async () => {
  const res = await api.get('/reportes/trazabilidad/excel', { responseType: 'blob', params: paramsExport.value })
  descargarBlob(res.data, 'reporte_trazabilidad.xlsx')
}
const badgeEstado = (estado) => estado === 'entregado' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
const destinoColor = (index) => ['bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-purple-100 text-purple-700'][index % 4]
const lineaColor = (index) => ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444'][index % 5]
const fecha = (f) => f ? String(f).replace('T', ' ').slice(0, 16) : '-'

const parseCoordenadas = (valor) => {
  const match = String(valor || '').match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/)
  if (!match) return null
  const lat = Number(match[1])
  const lon = Number(match[2])
  return Number.isFinite(lat) && Number.isFinite(lon) ? [lat, lon] : null
}

const fallbackDestino = (index) => [
  origen[0] + 0.035 - (index * 0.014),
  origen[1] + 0.055 + ((index % 2) * 0.045),
]

const puntoDestino = (destino, index) => parseCoordenadas(destino?.direccion) || fallbackDestino(index)

const crearIcono = (label, color) => L.divIcon({
  className: '',
  html: `<div style="width:34px;height:34px;border-radius:999px;background:${color};border:4px solid white;box-shadow:0 8px 18px rgba(15,23,42,.22);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:11px">${label}</div>`,
  iconSize: [34, 34],
  iconAnchor: [17, 17],
})

const dibujarMapa = async () => {
  await nextTick()
  if (!mapaRef.value) return

  if (!mapa) {
    mapa = L.map(mapaRef.value, { zoomControl: true, attributionControl: true }).setView(origen, 12)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
    }).addTo(mapa)
    capaRutas = L.layerGroup().addTo(mapa)
  }

  capaRutas.clearLayers()
  const bounds = L.latLngBounds([origen])
  L.marker(origen, { icon: crearIcono('IN', '#059669') }).addTo(capaRutas).bindPopup(`${origenNombre}<br>${origenDireccion}`)

  destinosMapa.value.forEach((destino, index) => {
    const color = lineaColor(index)
    const destinoPunto = puntoDestino(destino, index)
    bounds.extend(destinoPunto)
    L.polyline([origen, destinoPunto], { color, weight: 4, dashArray: '8 8' }).addTo(capaRutas)
    L.marker(destinoPunto, { icon: crearIcono('D', color) })
      .addTo(capaRutas)
      .bindTooltip(destino?.nombre || 'Destino', {
        permanent: true,
        direction: 'right',
        offset: [18, 0],
        className: 'text-xs font-semibold text-gray-700 bg-white/90 border-0 shadow-sm rounded px-2 py-1',
      })
      .bindPopup(destino?.nombre || 'Destino')
  })

  if (destinosMapa.value.length) mapa.fitBounds(bounds.pad(0.25))
  setTimeout(() => mapa?.invalidateSize(), 80)
}

watch([destinosMapa], dibujarMapa)
onMounted(cargar)
onBeforeUnmount(() => {
  if (mapa) {
    mapa.remove()
    mapa = null
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Trazabilidad</h2>
        <p class="text-sm text-gray-500 mt-0.5">Rutas desde {{ origenNombre }} hacia destinos asignados.</p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <ReportExportMenu @excel="exportarExcel" @pdf="exportarPDF" @print="imprimirReporte" />
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <h3 class="font-bold text-gray-800">Mapa de transportes</h3>
        <span class="text-xs px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">Origen: {{ origenNombre }}</span>
      </div>
      <div class="relative h-80 rounded-2xl border border-gray-200 overflow-hidden">
        <div ref="mapaRef" class="h-full w-full bg-gray-100"></div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
      <input v-model="busqueda" class="md:col-span-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm" placeholder="Buscar destino o responsable..." />
      <select v-model="destinoFiltro" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
        <option value="">Todos los destinos</option>
        <option v-for="d in destinosAsignados" :key="d.id_destino" :value="d.id_destino">{{ d.nombre }}</option>
      </select>
      <select v-model="estadoFiltro" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
        <option value="">Todos los estados</option>
        <option value="designado">Designado</option>
        <option value="entregado">Entregado</option>
      </select>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3">
        <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
        <p class="text-sm text-gray-400">Cargando trazabilidad...</p>
      </div>
    </div>
    <div v-else class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-500">
            <tr>
              <th class="text-left px-5 py-3 font-semibold">Salida</th>
              <th class="text-left px-5 py-3 font-semibold">Producto</th>
              <th class="text-left px-5 py-3 font-semibold">Lote</th>
              <th class="text-left px-5 py-3 font-semibold">Destino</th>
              <th class="text-left px-5 py-3 font-semibold">Distribuidor</th>
              <th class="text-left px-5 py-3 font-semibold">Cantidad</th>
              <th class="text-left px-5 py-3 font-semibold">Estado</th>
              <th class="text-left px-5 py-3 font-semibold">Observacion</th>
              <th v-if="esDistribuidor" class="text-right px-5 py-3 font-semibold">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registrosFiltrados" :key="r.id_distribucion" @click="seleccionado = r"
              class="border-t border-gray-100 hover:bg-emerald-50/40 cursor-pointer"
              :class="seleccionado?.id_distribucion === r.id_distribucion ? 'bg-emerald-50/70' : ''">
              <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ r.salida?.nro_informe || '-' }}</td>
              <td class="px-5 py-4 font-semibold text-gray-800">{{ r.lote?.producto?.nombre || 'Producto' }}</td>
              <td class="px-5 py-4 text-gray-600">{{ r.lote?.codigo_lote || '-' }}</td>
              <td class="px-5 py-4 text-gray-700">{{ r.destino?.nombre || '-' }}</td>
              <td class="px-5 py-4 text-gray-600">{{ r.responsable?.name || 'Sin responsable' }}</td>
              <td class="px-5 py-4 font-semibold text-gray-800">{{ r.cantidad_productos }}</td>
              <td class="px-5 py-4">
                <span class="text-xs font-semibold px-3 py-1 rounded-full capitalize" :class="badgeEstado(r.estado)">{{ r.estado }}</span>
              </td>
              <td class="px-5 py-4 text-gray-500 max-w-xs truncate">{{ r.observaciones || '-' }}</td>
              <td v-if="esDistribuidor" class="px-5 py-4 text-right">
                <button v-if="r.estado === 'designado'" @click.stop="abrirEntrega(r)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200">Entregado</button>
              </td>
            </tr>
            <tr v-if="!registrosFiltrados.length">
              <td :colspan="esDistribuidor ? 9 : 8" class="text-center py-10 text-gray-400">No hay salidas registradas para trazabilidad</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="seleccionado" class="bg-white rounded-2xl border border-gray-100 p-5">
      <h3 class="font-bold text-gray-800 mb-4">Detalle de ruta</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Origen</p><b>{{ origenNombre }}</b><p class="text-xs text-gray-500 mt-1">{{ origenDireccion }}</p></div>
        <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Destino</p><b>{{ seleccionado.destino?.nombre }}</b></div>
        <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Estado</p><b>{{ seleccionado.estado }}</b></div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5 flex justify-between"><h3 class="text-white font-bold">{{ editando ? 'Editar ruta' : 'Nueva ruta' }}</h3><button @click="showModal = false" class="text-white/70">Cerrar</button></div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <select v-model="form.lote_id" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="">Lote...</option><option v-for="l in lotes" :key="l.id_lote" :value="l.id_lote">{{ l.codigo_lote }} - {{ l.producto?.nombre }}</option></select>
            <select v-model="form.destino_id" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="">Destino...</option><option v-for="d in destinos" :key="d.id_destino" :value="d.id_destino">{{ d.nombre }}</option></select>
            <select v-model="form.responsable_id" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="">Distribuidor...</option><option v-for="u in distribuidores" :key="u.id" :value="u.id">{{ u.name }}</option></select>
            <input v-model.number="form.cantidad_productos" type="number" min="1" class="px-4 py-2.5 rounded-xl border border-gray-200" placeholder="Cantidad" />
            <select v-model="form.estado" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white"><option value="designado">Designado</option><option value="entregado">Entregado</option></select>
            <textarea v-model="form.observaciones" rows="2" class="md:col-span-2 px-4 py-2.5 rounded-xl border border-gray-200" placeholder="Observaciones"></textarea>
          </div>
          <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3"><button @click="showModal = false" class="px-5 py-2.5 rounded-xl bg-white border border-gray-200">Cancelar</button><button @click="guardar" :disabled="submitting" class="px-5 py-2.5 rounded-xl bg-[#2B2353] text-white">{{ submitting ? 'Guardando...' : 'Guardar ruta' }}</button></div>
        </div>
      </div>

      <div v-if="showEntrega" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5"><h3 class="text-white font-bold">Marcar como entregado</h3></div>
          <div class="p-6 space-y-3">
            <p class="text-sm text-gray-600">Destino asignado: <b>{{ seleccionado?.destino?.nombre }}</b></p>
            <textarea v-model="entrega.observaciones" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-gray-200" placeholder="Observaciones de entrega"></textarea>
          </div>
          <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3"><button @click="showEntrega = false" class="px-5 py-2.5 rounded-xl bg-white border border-gray-200">Cancelar</button><button @click="marcarEntregado" :disabled="submitting" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white">Entregado</button></div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
