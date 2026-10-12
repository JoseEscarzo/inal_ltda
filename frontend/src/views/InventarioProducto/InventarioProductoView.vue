<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import axios from 'axios'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const movimientos = ref([])
const archivados = ref([])
const productos = ref([])
const loading = ref(false)
const submitting = ref(false)
const vistaArchivados = ref(false)
const busqueda = ref('')
const desde = ref('')
const hasta = ref('')
const tipoProducto = ref('')
const tipoMovimiento = ref('')
const showModal = ref(false)
let filtroTimer = null

const form = ref({
  id_inventario: null,
  producto_id: '',
  cantidad: 1,
  clasificacion: 'ajuste',
})

const tiposProducto = ['Materia Prima', 'Insumo', 'Producto Terminado']

const listaActual = computed(() => vistaArchivados.value ? archivados.value : movimientos.value)
const movimientosFiltrados = computed(() => listaActual.value)
const stockTotal = computed(() => movimientos.value.reduce((s, m) => s + Number(m.stock || 0), 0))
const entradas = computed(() => movimientos.value.filter(m => ['ingreso', 'ajuste'].includes(m.clasificacion)).length)
const salidas = computed(() => movimientos.value.filter(m => m.clasificacion === 'salida').length)
const stockActualPorProducto = computed(() => {
  const mapa = {}
  movimientos.value.forEach((m) => {
    const id = m.producto_id || m.producto?.id_producto
    if (id && mapa[id] === undefined) {
      mapa[id] = {
        stock: Number(m.stock || 0),
        producto: m.producto || productos.value.find((p) => String(p.id_producto) === String(id)),
      }
    }
  })
  return Object.values(mapa)
})
const valorInventarioCosto = computed(() => stockActualPorProducto.value.reduce((total, item) =>
  total + (Number(item.stock || 0) * Number(item.producto?.precio_costo || 0)), 0
))
const valorVentaEstimada = computed(() => stockActualPorProducto.value.reduce((total, item) =>
  total + (Number(item.stock || 0) * Number(item.producto?.precio_distribuidor || 0)), 0
))

const paramsReporte = computed(() => ({
  buscar: busqueda.value || null,
  desde: desde.value || null,
  hasta: hasta.value || null,
  tipo_producto: tipoProducto.value || null,
  clasificacion: tipoMovimiento.value || null,
}))

const normalizarLista = (data) => Array.isArray(data) ? data : (data.data ?? [])

const cargarActivos = async () => {
  loading.value = true
  try {
    const [resInv, resProd] = await Promise.all([
      api.get('/inventario', { params: paramsReporte.value }),
      api.get('/productos'),
    ])

    movimientos.value = normalizarLista(resInv.data)
    productos.value = normalizarLista(resProd.data)
  } catch (e) {
    console.error('Error cargando inventario:', e)
  } finally {
    loading.value = false
  }
}

const cargarArchivados = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/inventario/inactivos', { params: paramsReporte.value })
    archivados.value = normalizarLista(data)
  } catch (e) {
    console.error('Error cargando inventario archivado:', e)
  } finally {
    loading.value = false
  }
}

const cargar = async () => {
  if (vistaArchivados.value) {
    await cargarArchivados()
  } else {
    await cargarActivos()
  }
}

const cambiarVistaArchivados = async () => {
  vistaArchivados.value = !vistaArchivados.value
  if (vistaArchivados.value && archivados.value.length === 0) {
    await cargarArchivados()
  }
}

const cargarConEspera = () => {
  if (filtroTimer) clearTimeout(filtroTimer)
  filtroTimer = setTimeout(cargar, 350)
}

const abrirAjuste = () => {
  form.value = { id_inventario: null, producto_id: '', cantidad: 1, clasificacion: 'ajuste' }
  showModal.value = true
}

const guardarAjuste = async () => {
  if (!form.value.producto_id || Number(form.value.cantidad) <= 0) return
  submitting.value = true
  try {
    await api.post('/inventario', form.value)
    showModal.value = false
    await cargar()
  } finally {
    submitting.value = false
  }
}

const abrirEditar = (m) => {
  form.value = {
    id_inventario: m.id_inventario,
    producto_id: m.producto_id || m.producto?.id_producto || '',
    cantidad: Number(m.cantidad || 1),
    clasificacion: m.clasificacion || 'ajuste',
  }
  showModal.value = true
}

const guardarMovimiento = async () => {
  if (!form.value.producto_id || Number(form.value.cantidad) <= 0) return
  submitting.value = true
  try {
    if (form.value.id_inventario) {
      await api.put(`/inventario/${form.value.id_inventario}`, form.value)
    } else {
      await api.post('/inventario', form.value)
    }
    showModal.value = false
    await cargar()
  } finally {
    submitting.value = false
  }
}

const archivar = async (m) => {
  await api.delete(`/inventario/${m.id_inventario}`)
  await cargar()
}

const activar = async (m) => {
  await api.patch(`/inventario/${m.id_inventario}/activar`)
  await cargar()
}

const eliminarDefinitivo = async (m) => {
  await api.delete(`/inventario/${m.id_inventario}/definitivo`)
  await cargar()
}

const exportarPDF = async () => {
  const response = await api.get('/reportes/inventario/pdf', { responseType: 'blob', params: paramsReporte.value })
  descargarPdfBlob(response.data, 'reporte_inventario.pdf')
}

const imprimirReporte = async () => {
  const response = await api.get('/reportes/inventario/pdf', { responseType: 'blob', params: paramsReporte.value })
  imprimirPdfBlob(response.data, 'reporte_inventario')
}

const exportarExcel = async () => {
  const response = await api.get('/reportes/inventario/excel', { responseType: 'blob', params: paramsReporte.value })
  descargarBlob(response.data, 'reporte_inventario.xlsx')
}

const badgeMovimiento = (tipo) => ({
  ingreso: 'bg-emerald-100 text-emerald-700',
  salida: 'bg-red-100 text-red-700',
  rechazado: 'bg-orange-100 text-orange-700',
  ajuste: 'bg-blue-100 text-blue-700',
}[tipo] || 'bg-gray-100 text-gray-600')

const formatFecha = (fecha) => fecha ? fecha.toString().replace('T', ' ').slice(0, 16) : '-'
const formatoMoneda = (valor) => `${Number(valor || 0).toFixed(2)} Bs`
const valorCostoMovimiento = (m) => Number(m.stock || 0) * Number(m.producto?.precio_costo || 0)

onMounted(cargarActivos)
onBeforeUnmount(() => {
  if (filtroTimer) clearTimeout(filtroTimer)
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ vistaArchivados ? 'Inventario Archivado' : 'Inventario de Productos' }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">Controla ingresos, salidas, stock y movimientos por fecha.</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <button @click="cambiarVistaArchivados" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition shadow-sm">
          {{ vistaArchivados ? 'Ver Activos' : 'Ver Archivados' }}
        </button>
        <ReportExportMenu v-if="!vistaArchivados" @excel="exportarExcel" @pdf="exportarPDF" @print="imprimirReporte" />
        <button v-if="!vistaArchivados" @click="abrirAjuste" class="flex items-center gap-2 bg-[#2B2353] hover:bg-[#3d3275] text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-md transition-all duration-200">Nuevo Ajuste</button>
      </div>
    </div>

    <div v-if="!vistaArchivados" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Movimientos</p>
        <p class="text-2xl font-bold text-gray-800">{{ movimientos.length }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Entradas</p>
        <p class="text-2xl font-bold text-emerald-600">{{ entradas }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Salidas</p>
        <p class="text-2xl font-bold text-red-500">{{ salidas }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Valor inventario</p>
        <p class="text-2xl font-bold text-[#2B2353]">{{ formatoMoneda(valorInventarioCosto) }}</p>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Venta estimada</p>
        <p class="text-2xl font-bold text-emerald-600">{{ formatoMoneda(valorVentaEstimada) }}</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-6 gap-3">
      <input v-model="busqueda" @input="cargarConEspera" class="md:col-span-2 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" placeholder="Buscar producto, código o movimiento..." />
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Desde</label>
        <input v-model="desde" @change="cargar" type="date" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Hasta</label>
        <input v-model="hasta" @change="cargar" type="date" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl" />
      </div>
      <select v-model="tipoProducto" @change="cargar" class="self-end px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white">
        <option value="">Todos los tipos</option>
        <option v-for="tipo in tiposProducto" :key="tipo" :value="tipo">{{ tipo }}</option>
      </select>
      <select v-model="tipoMovimiento" @change="cargar" class="self-end px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white">
        <option value="">Todos los movimientos</option>
        <option value="ajuste">Ajuste</option>
        <option value="ingreso">Ingreso</option>
        <option value="salida">Salida</option>
        <option value="rechazado">Rechazado</option>
      </select>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
          <p class="text-sm text-gray-400">Cargando inventario...</p>
        </div>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Fecha</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Producto</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Codigo</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Tipo</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Movimiento</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Cantidad</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Stock</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Valor costo</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="m in movimientosFiltrados" :key="m.id_inventario" class="hover:bg-[#F4F7FE]/60 transition-colors" :class="vistaArchivados ? 'opacity-70' : ''">
            <td class="px-6 py-4 text-gray-500">{{ formatFecha(m.fecha_registro) }}</td>
            <td class="px-6 py-4 font-semibold text-gray-800">{{ m.producto?.nombre || '-' }}</td>
            <td class="px-6 py-4"><span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">{{ m.producto?.codigo || '-' }}</span></td>
            <td class="px-6 py-4 text-gray-600">{{ m.producto?.tipo_producto || '-' }}</td>
            <td class="px-6 py-4"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="badgeMovimiento(m.clasificacion)">{{ m.clasificacion || '-' }}</span></td>
            <td class="px-6 py-4 text-right font-bold" :class="m.clasificacion === 'salida' ? 'text-red-500' : 'text-emerald-600'">{{ m.clasificacion === 'salida' ? '-' : '+' }}{{ m.cantidad }}</td>
            <td class="px-6 py-4 text-right font-bold text-gray-800">{{ m.stock }}</td>
            <td class="px-6 py-4 text-right font-bold text-[#2B2353]">{{ formatoMoneda(valorCostoMovimiento(m)) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">
                <template v-if="!vistaArchivados">
                  <button @click="archivar(m)" class="p-2 rounded-lg text-gray-400 hover:text-orange-500 hover:bg-orange-50 transition-colors" title="Archivar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                  </button>
                  <button @click="abrirEditar(m)" class="p-2 rounded-lg text-gray-400 hover:text-[#2B2353] hover:bg-[#2B2353]/10 transition-colors" title="Editar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                </template>
                <template v-else>
                  <button @click="activar(m)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200">Activar</button>
                  <button @click="eliminarDefinitivo(m)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200">Eliminar</button>
                </template>
              </div>
            </td>
          </tr>
          <tr v-if="!movimientosFiltrados.length">
            <td colspan="9" class="text-center py-12 text-gray-400">No hay movimientos de inventario</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5">
            <h3 class="text-white font-bold">{{ form.id_inventario ? 'Editar Movimiento de Inventario' : 'Nuevo Ajuste de Inventario' }}</h3>
            <p class="text-white/60 text-xs mt-1">{{ form.id_inventario ? 'Modifica la cantidad registrada del movimiento.' : 'Suma stock manualmente cuando corresponda.' }}</p>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Producto</label>
              <select v-model="form.producto_id" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white">
                <option value="">Seleccionar...</option>
                <option v-for="p in productos" :key="p.id_producto" :value="p.id_producto">{{ p.nombre }} - {{ p.tipo_producto || 'Sin tipo' }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Cantidad</label>
              <input v-model.number="form.cantidad" type="number" min="1" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl" />
            </div>
          </div>
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button @click="showModal = false" class="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl">Cancelar</button>
            <button @click="guardarMovimiento" :disabled="submitting" class="px-5 py-2.5 text-sm font-semibold text-white bg-[#2B2353] rounded-xl disabled:opacity-60">
              {{ submitting ? 'Guardando...' : (form.id_inventario ? 'Guardar Cambios' : 'Guardar Ajuste') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
