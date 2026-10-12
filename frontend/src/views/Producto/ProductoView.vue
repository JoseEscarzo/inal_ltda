<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

// ── Estado ────────────────────────────────────────────────
const productos = ref([])
const productosInactivos = ref([])
const inventario = ref([])
const loading = ref(false)
const submitting = ref(false)
const showModal = ref(false)
const editando = ref(false)
const modalPaso = ref('producto')
const vistaInactivos = ref(false)
const vistaTipo = ref('todos')
const busqueda = ref('')
const filtroCategoria = ref('')
const paginaActual = ref(1)
const porPagina = 8

const form = ref({
  id_producto: null,
  nombre: '',
  codigo: '',
  descripcion: '',
  categoria: '',
  tipo_producto: '',
  presentacion: '',
  precio_costo: 0,
  precio_distribuidor: 0,
  precio_venta_publico: 0,
})

const errores = ref({})
const recetaOriginal = ref([])
const cantidadProduccion = ref(100)
const receta = ref([
  { materia_prima_id: '', cantidad_por_unidad: 1, unidad: '' },
])

// ── Modal de confirmación ─────────────────────────────────
const confirmModal = ref({
  open: false,
  title: '',
  message: '',
  detail: '',
  type: 'warning',
  confirmLabel: 'Confirmar',
  resolve: null,
})

function openConfirm({ title, message, detail = '', type = 'warning', confirmLabel = 'Confirmar' }) {
  return new Promise((resolve) => {
    confirmModal.value = { open: true, title, message, detail, type, confirmLabel, resolve }
  })
}

function handleConfirm(result) {
  confirmModal.value.open = false
  if (confirmModal.value.resolve) confirmModal.value.resolve(result)
}

//Opciones
const categorias = [
  'Lácteos', 'Cárnicos', 'Bebidas', 'Conservas',
  'Cereales', 'Snacks', 'Congelados', 'Vegetal', 'Otros',
]
const tiposProducto = ['Materia Prima', 'Insumo', 'Producto Terminado']
const presentaciones = ['Litros', 'Kilos', 'Unidades', 'Bolsa', 'Botella', 'Tarro', 'Caja', 'Lata', 'Tetrapack', 'Sachet', 'Balde 5 Litros', 'Otros']

const formProductoVacio = () => ({
  id_producto: null,
  nombre: '',
  codigo: '',
  descripcion: '',
  categoria: '',
  tipo_producto: '',
  presentacion: '',
  precio_costo: 0,
  precio_distribuidor: 0,
  precio_venta_publico: 0,
})

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const cargarProductos = async () => {
  loading.value = true
  try {
    const [{ data }, resInv] = await Promise.all([
      api.get('/productos?per_page=100'),
      api.get('/inventario', { params: { limit: 500 } }),
    ])
    productos.value = data.data ?? data
    inventario.value = resInv.data.data ?? resInv.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const cargarInactivos = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/productos/inactivos?per_page=100')
    productosInactivos.value = data.data ?? data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(cargarProductos)

const toggleVista = async () => {
  vistaInactivos.value = !vistaInactivos.value
  paginaActual.value = 1
  busqueda.value = ''
  filtroCategoria.value = ''

  if (vistaInactivos.value) {
    await cargarInactivos()
  }
}

const paramsExportProductos = computed(() => ({
  buscar: busqueda.value || null,
  categoria: filtroCategoria.value || null,
}))

const exportarProductosPDF = async () => {
  try {
    const response = await api.get('/reportes/productos/pdf', {
      responseType: 'blob',
      params: paramsExportProductos.value,
    })

    descargarPdfBlob(response.data, 'reporte_productos.pdf')
  } catch (e) {
    console.error('Error al exportar PDF:', e)
  }
}

const imprimirProductos = async () => {
  try {
    const response = await api.get('/reportes/productos/pdf', {
      responseType: 'blob',
      params: paramsExportProductos.value,
    })

    imprimirPdfBlob(response.data, 'reporte_productos')
  } catch (e) {
    console.error('Error al imprimir:', e)
  }
}

const exportarProductosExcel = async () => {
  try {
    const response = await api.get('/reportes/productos/excel', {
      responseType: 'blob',
      params: paramsExportProductos.value,
    })

    descargarBlob(response.data, 'reporte_productos.xlsx')
  } catch (e) {
    console.error('Error al exportar Excel:', e)
  }
}

// ── Filtros y paginación ──────────────────────────────────
const listaActual = computed(() =>
  vistaInactivos.value ? productosInactivos.value : productos.value
)

const productosFiltrados = computed(() => {
  let lista = listaActual.value
  if (busqueda.value) {
    const q = busqueda.value.toLowerCase()
    lista = lista.filter(p =>
      p.nombre?.toLowerCase().includes(q) ||
      p.codigo?.toLowerCase().includes(q) ||
      p.categoria?.toLowerCase().includes(q)
    )
  }
  if (filtroCategoria.value) {
    lista = lista.filter(p => p.categoria === filtroCategoria.value)
  }
  if (vistaTipo.value === 'insumos') {
    lista = lista.filter(p => ['Materia Prima', 'Insumo'].includes(p.tipo_producto))
  }
  if (vistaTipo.value === 'terminados') {
    lista = lista.filter(p => p.tipo_producto === 'Producto Terminado')
  }
  return lista
})

const totalPaginas = computed(() =>
  Math.ceil(productosFiltrados.value.length / porPagina)
)

const productosPaginados = computed(() => {
  const inicio = (paginaActual.value - 1) * porPagina
  return productosFiltrados.value.slice(inicio, inicio + porPagina)
})

// ── Modal formulario ──────────────────────────────────────
const abrirCrear = () => {
  editando.value = false
  modalPaso.value = 'producto'
  errores.value = {}
  form.value = formProductoVacio()
  recetaOriginal.value = []
  cantidadProduccion.value = 100
  receta.value = [{ materia_prima_id: '', cantidad_por_unidad: 1, unidad: '' }]
  showModal.value = true
}

const abrirEditar = (p) => {
  editando.value = true
  modalPaso.value = 'producto'
  errores.value = {}
  form.value = { ...formProductoVacio(), ...p }
  recetaOriginal.value = p.receta ?? []
  cantidadProduccion.value = p.receta?.[0]?.unidades_producidas ?? 100
  receta.value = p.receta?.length
    ? p.receta.map(r => ({
      materia_prima_id: r.materia_prima_id,
      cantidad_por_unidad: Number(r.cantidad_por_unidad || 1),
      unidad: r.unidad || r.materia_prima?.presentacion || '',
    }))
    : [{ materia_prima_id: '', cantidad_por_unidad: 1, unidad: '' }]
  showModal.value = true
}

const cerrarModal = () => { showModal.value = false }

const esProductoTerminado = computed(() => form.value.tipo_producto === 'Producto Terminado')

const materiasPrimas = computed(() =>
  productos.value.filter(p => p.tipo_producto === 'Materia Prima')
)

const stockPorProducto = computed(() => {
  const mapa = {}
  inventario.value.forEach(m => {
    const id = m.producto_id || m.producto?.id_producto
    if (id && mapa[id] === undefined) mapa[id] = Number(m.stock || 0)
  })
  return mapa
})

const recetaAnteriorPorMateria = computed(() => {
  const mapa = {}
  recetaOriginal.value.forEach(r => {
    mapa[r.materia_prima_id] = (mapa[r.materia_prima_id] || 0) + Number(r.cantidad_total || 0)
  })
  return mapa
})

const filasRecetaValidas = computed(() =>
  receta.value
    .filter(r => r.materia_prima_id && Number(r.cantidad_por_unidad) > 0)
    .map(r => {
      const materia = productos.value.find(p => p.id_producto === r.materia_prima_id)
      const necesaria = Number(r.cantidad_por_unidad || 0) * Number(cantidadProduccion.value || 1)
      const stock = Number(stockPorProducto.value[r.materia_prima_id] || 0) + Number(recetaAnteriorPorMateria.value[r.materia_prima_id] || 0)
      return {
        ...r,
        materia,
        unidad: r.unidad || materia?.presentacion || 'unid.',
        necesaria,
        stock,
        restante: stock - necesaria,
      }
    })
)

const stockInsuficiente = computed(() => filasRecetaValidas.value.find(f => f.restante < 0))
const puedeGuardarReceta = computed(() => filasRecetaValidas.value.length > 0 && !stockInsuficiente.value)

const formatoNumero = (valor) => {
  const numero = Number(valor || 0)
  return Number.isInteger(numero) ? numero.toString() : numero.toFixed(3).replace(/0+$/, '').replace(/\.$/, '')
}

const formatoMoneda = (valor) => `${Number(valor || 0).toFixed(2)} Bs`

const nombreMateria = (id) => productos.value.find(p => p.id_producto === id)?.nombre || 'Materia prima'

const actualizarUnidadReceta = (fila) => {
  const materia = productos.value.find(p => p.id_producto === fila.materia_prima_id)
  fila.unidad = materia?.presentacion || fila.unidad || ''
}

const agregarInsumo = () => {
  receta.value.push({ materia_prima_id: '', cantidad_por_unidad: 1, unidad: '' })
}

const quitarInsumo = (index) => {
  if (receta.value.length === 1) {
    receta.value = [{ materia_prima_id: '', cantidad_por_unidad: 1, unidad: '' }]
    return
  }
  receta.value.splice(index, 1)
}

const irAReceta = () => {
  errores.value = {}
  if (!form.value.nombre || !form.value.codigo) {
    errores.value = {
      ...(form.value.nombre ? {} : { nombre: ['El nombre es obligatorio.'] }),
      ...(form.value.codigo ? {} : { codigo: ['El codigo es obligatorio.'] }),
    }
    return
  }
  modalPaso.value = 'receta'
}

const payloadReceta = () => filasRecetaValidas.value.map(f => ({
  materia_prima_id: f.materia_prima_id,
  cantidad_por_unidad: Number(f.cantidad_por_unidad),
  cantidad_total: Number(f.necesaria.toFixed(3)),
  unidades_producidas: Number(cantidadProduccion.value),
  unidad: f.unidad,
}))

// ── CRUD ──────────────────────────────────────────────────
const guardar = async () => {
  errores.value = {}
  if (esProductoTerminado.value && modalPaso.value === 'producto') {
    irAReceta()
    return
  }
  if (esProductoTerminado.value && !puedeGuardarReceta.value) return
  submitting.value = true
  try {
    const payload = {
      ...form.value,
      ...(esProductoTerminado.value ? { receta: payloadReceta() } : {}),
    }
    if (editando.value) {
      await api.put(`/productos/${form.value.id_producto}`, payload)
    } else {
      await api.post('/productos', payload)
    }
    await cargarProductos()
    cerrarModal()
  } catch (e) {
    if (e.response?.status === 422) errores.value = e.response.data.errors ?? {}
  } finally {
    submitting.value = false
  }
}

// Soft-delete
const eliminar = async (p) => {
  const ok = await openConfirm({
    title: 'Desactivar producto',
    message: `¿Desactivar "${p.nombre}"?`,
    detail: 'El producto pasará a la sección de Inactivos y podrá ser reactivado en cualquier momento.',
    type: 'warning',
    confirmLabel: 'Sí, desactivar',
  })
  if (!ok) return
  try {
    await api.delete(`/productos/${p.id_producto}`)
    await cargarProductos()
  } catch (e) {
    console.error(e)
  }
}

// Reactivar
const activar = async (p) => {
  const ok = await openConfirm({
    title: 'Reactivar producto',
    message: `¿Reactivar "${p.nombre}"?`,
    detail: 'El producto volverá a aparecer en la lista de productos activos.',
    type: 'warning',
    confirmLabel: 'Sí, reactivar',
  })
  if (!ok) return
  try {
    await api.patch(`/productos/${p.id_producto}/activar`)
    // BUG FIX: recarga ambas listas
    await Promise.all([cargarInactivos(), cargarProductos()])
  } catch (e) {
    console.error(e)
  }
}

// Eliminar permanente
const eliminarDefinitivo = async (p) => {
  const ok = await openConfirm({
    title: 'Eliminar permanentemente',
    message: `¿Eliminar "${p.nombre}" para siempre?`,
    detail: '⚠️ Esta acción es irreversible. El producto será borrado definitivamente de la base de datos.',
    type: 'danger',
    confirmLabel: 'Sí, eliminar para siempre',
  })
  if (!ok) return
  try {
    await api.delete(`/productos/${p.id_producto}/definitivo`)
    await cargarInactivos()
  } catch (e) {
    console.error(e)
  }
}

// ── Helpers ───────────────────────────────────────────────
const colorCategoria = (cat) => {
  const mapa = {
    'Lácteos': 'bg-blue-100 text-blue-700',
    'Cárnicos': 'bg-red-100 text-red-700',
    'Bebidas': 'bg-cyan-100 text-cyan-700',
    'Conservas': 'bg-yellow-100 text-yellow-700',
    'Cereales': 'bg-orange-100 text-orange-700',
    'Snacks': 'bg-purple-100 text-purple-700',
    'Congelados': 'bg-indigo-100 text-indigo-700',
    'Otros': 'bg-gray-100 text-gray-600',
  }
  return mapa[cat] ?? 'bg-gray-100 text-gray-600'
}

const iconoTipo = (tipo) => {
  const mapa = {
    'Sólido': 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'Líquido': 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
    'Semilíquido': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'Polvo': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    'Congelado': 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707',
  }
  return mapa[tipo] ?? 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
}

const categoriasMapa = computed(() => {
  const c = {}
  productos.value.forEach(p => { c[p.categoria] = (c[p.categoria] || 0) + 1 })
  return c
})
const categoriaMasComun = computed(() => {
  const e = Object.entries(categoriasMapa.value)
  if (!e.length) return '—'
  return e.sort((a, b) => b[1] - a[1])[0][0]
})
</script>

<template>
  <div class="space-y-6">

    <!-- ── Encabezado ─────────────────────────────────────── -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">
          {{ vistaInactivos ? 'Productos Inactivos' : 'Gestión de Productos' }}
        </h2>
        <p class="text-sm text-gray-500 mt-0.5">
          {{ vistaInactivos ? 'Productos desactivados del sistema' : 'Administra el catálogo completo de productos' }}
        </p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Botón toggle activos/inactivos -->
        <button @click="toggleVista"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition shadow-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          {{ vistaInactivos ? 'Ver Activos' : 'Ver Inactivos' }}
        </button>
        <!-- Botón nuevo producto solo en vista activos -->

        <ReportExportMenu
          v-if="!vistaInactivos"
          @excel="exportarProductosExcel"
          @pdf="exportarProductosPDF"
          @print="imprimirProductos"
        />
        <button v-if="!vistaInactivos" @click="abrirCrear"
          class="flex items-center gap-2 bg-[#2B2353] hover:bg-[#3d3275] text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-md transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Producto
        </button>
      </div>
    </div>

    <div v-if="!vistaInactivos" class="flex gap-3 border-b border-gray-200">
      <button @click="vistaTipo = 'todos'; paginaActual = 1"
        class="pb-3 px-2 text-sm font-semibold transition-colors border-b-2"
        :class="vistaTipo === 'todos' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400 hover:text-gray-600'">
        Todos
      </button>
      <button @click="vistaTipo = 'insumos'; paginaActual = 1"
        class="pb-3 px-2 text-sm font-semibold transition-colors border-b-2"
        :class="vistaTipo === 'insumos' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400 hover:text-gray-600'">
        Materia prima
      </button>
      <button @click="vistaTipo = 'terminados'; paginaActual = 1"
        class="pb-3 px-2 text-sm font-semibold transition-colors border-b-2"
        :class="vistaTipo === 'terminados' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400 hover:text-gray-600'">
        Producto terminado
      </button>
    </div>

    <!-- ── Tarjetas resumen (solo vista activos) ──────────── -->
    <div v-if="!vistaInactivos" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#2B2353]/10 flex items-center justify-center">
          <svg class="w-6 h-6 text-[#2B2353]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Productos</p>
          <p class="text-2xl font-bold text-gray-800">{{ productos.length }}</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
          <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Categorías</p>
          <p class="text-2xl font-bold text-gray-800">{{ Object.keys(categoriasMapa).length }}</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center">
          <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Más común</p>
          <p class="text-lg font-bold text-gray-800 truncate">{{ categoriaMasComun }}</p>
        </div>
      </div>
    </div>

    <!-- Banner inactivos -->
    <div v-if="vistaInactivos"
      class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium px-4 py-3 rounded-xl">
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Estos productos fueron desactivados. Puedes reactivarlos o eliminarlos permanentemente.
    </div>

    <!-- ── Filtros ─────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-center">
      <div class="relative flex-1 min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="busqueda" type="text" placeholder="Buscar por nombre, código o categoría..."
          class="w-full pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353]"
          @input="paginaActual = 1" />
      </div>
      <select v-model="filtroCategoria"
        class="text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] bg-white"
        @change="paginaActual = 1">
        <option value="">Todas las categorías</option>
        <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
      </select>
      <span class="text-sm text-gray-400 ml-auto">{{ productosFiltrados.length }} resultado(s)</span>
    </div>

    <!-- ── Tabla ──────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
          <p class="text-sm text-gray-400">Cargando productos...</p>
        </div>
      </div>

      <div v-else-if="productosFiltrados.length === 0" class="flex flex-col items-center justify-center py-20 gap-3">
        <svg class="w-14 h-14 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <p class="text-gray-400 font-medium">
          {{ vistaInactivos ? 'No hay productos inactivos' : 'No se encontraron productos' }}
        </p>
        <button v-if="!vistaInactivos" @click="abrirCrear" class="text-sm text-[#2B2353] hover:underline font-semibold">
          + Agregar el primero
        </button>
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Categoría
            </th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Presentación
            </th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="(p, idx) in productosPaginados" :key="p.id_producto"
            class="hover:bg-[#F4F7FE]/60 transition-colors" :class="vistaInactivos ? 'opacity-70' : ''">
            <td class="px-6 py-4 text-gray-400 text-xs">{{ (paginaActual - 1) * porPagina + idx + 1 }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                  style="background-color: rgba(43,35,83,0.07);">
                  <svg class="w-4 h-4 text-[#2B2353]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="iconoTipo(p.tipo_producto)" />
                  </svg>
                </div>
                <div>
                  <p class="font-semibold text-gray-800">{{ p.nombre }}</p>
                  <p v-if="p.descripcion" class="text-xs text-gray-400 truncate max-w-[180px]">{{ p.descripcion }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">{{ p.codigo }}</span>
            </td>
            <td class="px-6 py-4">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="colorCategoria(p.categoria)">
                {{ p.categoria || '—' }}
              </span>
            </td>
            <td class="px-6 py-4 text-gray-600">{{ p.tipo_producto || '—' }}</td>
            <td class="px-6 py-4 text-gray-600">{{ p.presentacion || '—' }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">

                <!-- Vista ACTIVOS: editar + desactivar -->
                <template v-if="!vistaInactivos">
                  <button @click="abrirEditar(p)"
                    class="p-2 rounded-lg text-gray-400 hover:text-[#2B2353] hover:bg-[#2B2353]/10 transition-colors"
                    title="Editar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="eliminar(p)"
                    class="p-2 rounded-lg text-gray-400 hover:text-orange-500 hover:bg-orange-50 transition-colors"
                    title="Desactivar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                  </button>
                </template>

                <!-- Vista INACTIVOS: activar + eliminar para siempre -->
                <template v-else>
                  <button @click="activar(p)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors"
                    title="Reactivar">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Activar
                  </button>
                  <button @click="eliminarDefinitivo(p)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors"
                    title="Eliminar para siempre">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar para siempre
                  </button>
                </template>

              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Paginación -->
      <div v-if="totalPaginas > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-400">Página {{ paginaActual }} de {{ totalPaginas }}</p>
        <div class="flex gap-1">
          <button v-for="p in totalPaginas" :key="p" @click="paginaActual = p"
            class="w-8 h-8 rounded-lg text-xs font-semibold transition-colors"
            :class="paginaActual === p ? 'bg-[#2B2353] text-white' : 'text-gray-500 hover:bg-gray-100'">{{ p }}</button>
        </div>
      </div>
    </div>

    <!-- ── Modal Crear / Editar ───────────────────────────── -->
    <Teleport to="body">
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-[1300] flex items-center justify-center p-4 modal-backdrop"
        @click.self="cerrarModal">
        <Transition name="slide-up">
          <div v-if="showModal" class="bg-white rounded-2xl shadow-2xl w-full overflow-hidden"
            :class="modalPaso === 'receta' ? 'max-w-3xl' : 'max-w-lg'">
            <div class="bg-[#2B2353] px-6 py-5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-white font-bold text-base">
                    {{ modalPaso === 'receta' ? 'Receta de produccion' : (editando ? 'Editar Producto' : 'Nuevo Producto') }}
                  </h3>
                  <p class="text-white/60 text-xs">
                    {{ modalPaso === 'receta' ? 'Selecciona la materia prima que se descontara del inventario' : (editando ? 'Modifica los datos del producto' : 'Completa los campos requeridos') }}
                  </p>
                </div>
              </div>
              <button @click="cerrarModal" class="text-white/60 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div v-if="modalPaso === 'producto'" class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre <span
                    class="text-red-400">*</span></label>
                <input v-model="form.nombre" type="text" placeholder="Ej: Queso Tarro"
                  class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition"
                  :class="errores.nombre ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                <p v-if="errores.nombre" class="text-xs text-red-500 mt-1">{{ errores.nombre[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Código <span
                    class="text-red-400">*</span></label>
                <input v-model="form.codigo" type="text" placeholder="Ej: QSO-100G"
                  class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition font-mono"
                  :class="errores.codigo ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                <p v-if="errores.codigo" class="text-xs text-red-500 mt-1">{{ errores.codigo[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Descripción</label>
                <textarea v-model="form.descripcion" rows="2" placeholder="Descripción opcional del producto..."
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition resize-none" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Categoría</label>
                  <select v-model="form.categoria"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] bg-white transition">
                    <option value="">Seleccionar...</option>
                    <option v-for="c in categorias" :key="c" :value="c">{{ c }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tipo de producto</label>
                  <select v-model="form.tipo_producto"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] bg-white transition">
                    <option value="">Seleccionar...</option>
                    <option v-for="t in tiposProducto" :key="t" :value="t">{{ t }}</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Presentación</label>
                <select v-model="form.presentacion"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] bg-white transition">
                  <option value="">Seleccionar...</option>
                  <option v-for="pr in presentaciones" :key="pr" :value="pr">{{ pr }}</option>
                </select>
              </div>
            </div>
            <div v-else class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre del producto</label>
                  <input :value="form.nombre" readonly
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 text-gray-700" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Categoria</label>
                  <input :value="form.categoria" readonly
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 text-gray-700" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Codigo / SKU</label>
                  <input :value="form.codigo" readonly
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 text-gray-700 font-mono" />
                </div>
              </div>

              <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-[#F4F7FE] to-white p-4">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <h4 class="font-bold text-sm text-gray-800">Precios comerciales</h4>
                    <p class="text-xs text-gray-500">Costo para inventario, precio de factura y precio sugerido al cliente final.</p>
                  </div>
                  <span class="px-3 py-1 rounded-full bg-white text-xs font-bold text-[#2B2353] border border-indigo-100">
                    Margen {{ formatoMoneda(Number(form.precio_distribuidor || 0) - Number(form.precio_costo || 0)) }}
                  </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Costo de produccion</label>
                    <input v-model.number="form.precio_costo" type="number" min="0" step="0.01"
                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" />
                    <p class="text-[11px] text-gray-400 mt-1">Valora el inventario.</p>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Precio distribuidor</label>
                    <input v-model.number="form.precio_distribuidor" type="number" min="0" step="0.01"
                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" />
                    <p class="text-[11px] text-gray-400 mt-1">Se usa en la factura.</p>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Precio sugerido tienda</label>
                    <input v-model.number="form.precio_venta_publico" type="number" min="0" step="0.01"
                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" />
                    <p class="text-[11px] text-gray-400 mt-1">Referencia para PVP.</p>
                  </div>
                </div>
              </div>

              <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                  <div>
                    <h4 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                      <span class="text-emerald-600">⚗</span>
                      Receta de produccion
                    </h4>
                    <p class="text-xs text-gray-500">Cuanto insumo usa 1 unidad?</p>
                  </div>
                  <div class="w-40">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Unidades a producir</label>
                    <input v-model.number="cantidadProduccion" type="number" min="1"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" />
                  </div>
                </div>

                <div class="grid grid-cols-[1fr_110px_110px_42px] gap-2 text-xs font-semibold text-gray-500 mb-1 px-1">
                  <span>Materia prima</span>
                  <span>Cantidad</span>
                  <span>Unidad</span>
                  <span></span>
                </div>
                <div class="space-y-2">
                  <div v-for="(fila, index) in receta" :key="index" class="grid grid-cols-[1fr_110px_110px_42px] gap-2">
                    <select v-model="fila.materia_prima_id" @change="actualizarUnidadReceta(fila)"
                      class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30">
                      <option value="">Seleccionar materia prima...</option>
                      <option v-for="mp in materiasPrimas" :key="mp.id_producto" :value="mp.id_producto">
                        {{ mp.nombre }}
                      </option>
                    </select>
                    <input v-model.number="fila.cantidad_por_unidad" type="number" min="0.001" step="0.001"
                      class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 text-right" />
                    <input v-model="fila.unidad" type="text"
                      class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" />
                    <button @click="quitarInsumo(index)" type="button"
                      class="rounded-xl border border-gray-200 text-gray-500 hover:text-red-500 hover:bg-red-50 transition">
                      <svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                <button @click="agregarInsumo" type="button"
                  class="mt-2 w-full px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition text-left">
                  + Agregar otro insumo
                </button>
              </div>

              <div class="border-t border-gray-200 pt-4">
                <h4 class="font-bold text-sm text-gray-800 mb-3">Vista previa - producir {{ cantidadProduccion || 0 }} unidades</h4>
                <div class="rounded-xl border p-3"
                  :class="stockInsuficiente ? 'border-red-200 bg-red-50' : 'border-emerald-100 bg-emerald-50/50'">
                  <div v-if="!filasRecetaValidas.length" class="text-sm text-gray-500 py-2">
                    Selecciona al menos una materia prima para ver el calculo.
                  </div>
                  <div v-for="fila in filasRecetaValidas" :key="fila.materia_prima_id"
                    class="flex items-center justify-between gap-3 py-1.5 border-b border-black/10 last:border-b-0 text-sm">
                    <span class="text-gray-700">{{ fila.materia?.nombre || 'Materia prima' }} necesaria</span>
                    <span class="font-semibold text-right">
                      {{ formatoNumero(fila.necesaria) }} {{ fila.unidad }}
                      <span :class="fila.restante < 0 ? 'text-red-600' : 'text-emerald-700'" class="font-medium ml-2">
                        Stock: {{ formatoNumero(fila.stock) }} {{ fila.unidad }}
                        {{ fila.restante < 0 ? '- faltan ' + formatoNumero(Math.abs(fila.restante)) : '-> quedan ' + formatoNumero(fila.restante) }}
                        {{ fila.unidad }}
                      </span>
                    </span>
                  </div>
                  <div class="flex items-center justify-between mt-2 text-sm font-semibold">
                    <span>Se puede producir?</span>
                    <span v-if="stockInsuficiente" class="px-3 py-1 rounded-full bg-red-100 text-red-700">No, stock insuficiente</span>
                    <span v-else class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">Si, stock suficiente</span>
                  </div>
                  <p v-if="stockInsuficiente" class="mt-3 px-3 py-2 rounded-lg bg-orange-100 text-orange-800 text-xs font-medium">
                    Puedes producir maximo {{ Math.floor(stockInsuficiente.stock / Number(stockInsuficiente.cantidad_por_unidad || 1)) }} unidades con el stock actual.
                    Falta reponer {{ formatoNumero(Math.abs(stockInsuficiente.restante)) }} {{ stockInsuficiente.unidad }} de {{ nombreMateria(stockInsuficiente.materia_prima_id) }}.
                  </p>
                </div>
                <p v-if="errores.receta" class="text-xs text-red-500 mt-2">{{ errores.receta[0] }}</p>
              </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3 justify-end">
              <button @click="modalPaso === 'receta' ? modalPaso = 'producto' : cerrarModal()"
                class="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition">
                {{ modalPaso === 'receta' ? 'Atras' : 'Cancelar' }}
              </button>
              <button @click="guardar" :disabled="submitting"
                class="px-5 py-2.5 text-sm font-semibold text-white bg-[#2B2353] rounded-xl hover:bg-[#3d3275] transition disabled:opacity-60 flex items-center gap-2">
                <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ submitting ? 'Guardando...' : (modalPaso === 'producto' && esProductoTerminado ? 'Siguiente' : (editando ? 'Actualizar' : 'Guardar Producto')) }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- ── Modal Confirmación ─────────────────────────────── -->
    <Transition name="fade">
      <div v-if="confirmModal.open" class="fixed inset-0 z-[1400] flex items-center justify-center p-4 modal-backdrop"
        @click.self="handleConfirm(false)">
        <Transition name="slide-up">
          <div v-if="confirmModal.open" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">

            <!-- Ícono -->
            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
              :class="confirmModal.type === 'danger' ? 'bg-red-50' : 'bg-orange-50'">
              <svg v-if="confirmModal.type === 'danger'" class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <svg v-else class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>

            <h3 class="text-lg font-bold text-gray-800 mb-1">{{ confirmModal.title }}</h3>
            <p class="text-sm font-medium text-gray-700 mb-1">{{ confirmModal.message }}</p>
            <p v-if="confirmModal.detail" class="text-xs text-gray-500 mb-6 leading-relaxed">{{ confirmModal.detail }}
            </p>

            <div class="flex gap-3">
              <button @click="handleConfirm(false)"
                class="flex-1 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                Cancelar
              </button>
              <button @click="handleConfirm(true)"
                class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition"
                :class="confirmModal.type === 'danger' ? 'bg-red-500 hover:bg-red-600' : 'bg-orange-500 hover:bg-orange-600'">
                {{ confirmModal.confirmLabel }}
              </button>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.modal-backdrop {
  background: rgba(17, 24, 39, 0.34);
  backdrop-filter: blur(12px) saturate(0.82);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-up-leave-active {
  transition: all 0.2s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.97);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
