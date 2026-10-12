<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const loading = ref(false)
const movimientos = ref([])
const ingresos = ref([])
const salidas = ref([])
const trazabilidad = ref([])
const productos = ref([])

const normalizar = (data) => {
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  return []
}

const cargar = async () => {
  loading.value = true
  try {
    const [resInv, resIng, resSal, resTraz, resProd] = await Promise.allSettled([
      api.get('/inventario'),
      api.get('/ingresos'),
      api.get('/salidas'),
      api.get('/trazabilidad'),
      api.get('/productos'),
    ])

    if (resInv.status === 'fulfilled') movimientos.value = normalizar(resInv.value.data)
    if (resIng.status === 'fulfilled') ingresos.value = normalizar(resIng.value.data)
    if (resSal.status === 'fulfilled') salidas.value = normalizar(resSal.value.data)
    if (resTraz.status === 'fulfilled') trazabilidad.value = normalizar(resTraz.value.data)
    if (resProd.status === 'fulfilled') productos.value = normalizar(resProd.value.data)
  } catch (error) {
    console.error('Error cargando dashboard:', error)
  } finally {
    loading.value = false
  }
}

const totalMovimientos = computed(() => movimientos.value.length)
const totalIngresos = computed(() => movimientos.value.filter(m => ['ingreso', 'ajuste'].includes(m.clasificacion)).length)
const totalSalidas = computed(() => movimientos.value.filter(m => m.clasificacion === 'salida').length)
const totalRechazados = computed(() => movimientos.value.filter(m => m.clasificacion === 'rechazado').length)
const stockTotal = computed(() => movimientos.value.reduce((s, m) => s + Number(m.stock || 0), 0))
const salidasPendientes = computed(() => salidas.value.filter(s => s.estado_aprobacion === 'pendiente').length)
const entregasCompletadas = computed(() => trazabilidad.value.filter(t => t.estado === 'entregado').length)
const entregasDesignadas = computed(() => trazabilidad.value.filter(t => t.estado === 'designado').length)

const porcentaje = (valor, total) => {
  if (!total) return 0
  return Math.round((Number(valor || 0) / total) * 100)
}

const pctIngresos = computed(() => porcentaje(totalIngresos.value, totalMovimientos.value))
const pctSalidas = computed(() => porcentaje(totalSalidas.value, totalMovimientos.value))
const pctRechazados = computed(() => porcentaje(totalRechazados.value, totalMovimientos.value))
const pctEntregado = computed(() => porcentaje(entregasCompletadas.value, trazabilidad.value.length))
const pctPendiente = computed(() => porcentaje(salidasPendientes.value, Math.max(salidas.value.length, 1)))
const hoveredSegment = ref(null)

const movimientoStats = computed(() => [
  { label: 'Ingresos y ajustes', value: totalIngresos.value, percent: pctIngresos.value, color: 'bg-[#9150F7]' },
  { label: 'Salidas', value: totalSalidas.value, percent: pctSalidas.value, color: 'bg-emerald-500' },
  { label: 'Rechazados', value: totalRechazados.value, percent: pctRechazados.value, color: 'bg-rose-500' },
])

const donutSegments = computed(() => {
  const ingresosPct = pctIngresos.value
  const salidasPct = pctSalidas.value
  const rechazadosPct = pctRechazados.value

  return [
    {
      key: 'ingresos',
      label: 'Ingresos y ajustes',
      percent: ingresosPct,
      count: totalIngresos.value,
      color: '#9150F7',
      offset: 0,
    },
    {
      key: 'salidas',
      label: 'Salidas',
      percent: salidasPct,
      count: totalSalidas.value,
      color: '#34d399',
      offset: ingresosPct,
    },
    {
      key: 'rechazados',
      label: 'Rechazados',
      percent: rechazadosPct,
      count: totalRechazados.value,
      color: '#f43f5e',
      offset: ingresosPct + salidasPct,
    },
  ]
})

const activeDonutSegment = computed(() => hoveredSegment.value || donutSegments.value[0])

const productoTop = computed(() => {
  const conteo = new Map()
  movimientos.value.forEach((m) => {
    const nombre = m.producto?.nombre || 'Sin producto'
    const actual = conteo.get(nombre) || { nombre, cantidad: 0, stock: 0 }
    actual.cantidad += Math.abs(Number(m.cantidad || 0))
    actual.stock = Math.max(actual.stock, Number(m.stock || 0))
    conteo.set(nombre, actual)
  })
  return [...conteo.values()].sort((a, b) => b.cantidad - a.cantidad).slice(0, 5)
})

const actividadReciente = computed(() => {
  const ingresosRows = ingresos.value.slice(0, 4).map(i => ({
    tipo: 'Ingreso',
    codigo: `#${String(i.id_ingreso).padStart(3, '0')}`,
    responsable: i.entregado_por?.name || i.recibido_por?.name || 'Sin responsable',
    fecha: i.fecha_hora,
    color: 'text-[#6d35d8] bg-violet-50',
  }))

  const salidasRows = salidas.value.slice(0, 4).map(s => ({
    tipo: 'Salida',
    codigo: s.nro_informe || `#${String(s.id_salida).padStart(3, '0')}`,
    responsable: s.despachado_por?.name || s.distribuidor?.name || 'Sin responsable',
    fecha: s.fecha_hora,
    color: 'text-emerald-600 bg-emerald-50',
  }))

  return [...ingresosRows, ...salidasRows]
    .sort((a, b) => new Date(b.fecha || 0) - new Date(a.fecha || 0))
    .slice(0, 6)
})

const formatNumero = (valor) => new Intl.NumberFormat('es-BO').format(Number(valor || 0))
const formatFecha = (fecha) => fecha ? fecha.toString().replace('T', ' ').slice(0, 16) : '-'

onMounted(cargar)
</script>

<template>
  <div class="dashboard-shell">
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-0.5">Resumen de movimientos, distribuciones y trazabilidad.</p>
      </div>
      <button
        @click="cargar"
        class="px-4 py-2.5 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-600 hover:text-[#2B2353] hover:border-[#9150F7]/40 shadow-sm"
      >
        Actualizar
      </button>
    </div>

    <div v-if="loading" class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400">
      Cargando metricas...
    </div>

    <template v-else>
      <section class="hero-panel">
        <div class="hero-content">
          <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">Resumen operativo</p>
          <h3 class="text-3xl font-bold text-white mt-2">Movimientos del sistema</h3>
          <p class="text-white/70 text-sm mt-2 max-w-2xl">
            Vista general de entradas, salidas, rechazos y entregas para controlar el flujo de productos.
          </p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
            <div class="hero-stat">
              <span>Movimientos</span>
              <strong>{{ formatNumero(totalMovimientos) }}</strong>
            </div>
            <div class="hero-stat">
              <span>Stock total</span>
              <strong>{{ formatNumero(stockTotal) }}</strong>
            </div>
            <div class="hero-stat">
              <span>Productos</span>
              <strong>{{ formatNumero(productos.length) }}</strong>
            </div>
            <div class="hero-stat">
              <span>Pendientes</span>
              <strong>{{ formatNumero(salidasPendientes) }}</strong>
            </div>
          </div>
        </div>
        <div class="donut-wrap">
          <div class="donut" @mouseleave="hoveredSegment = null">
            <svg class="donut-svg" viewBox="0 0 220 220" aria-hidden="true">
              <circle class="donut-track" cx="110" cy="110" r="84" pathLength="100" />
              <circle
                v-for="segment in donutSegments"
                :key="segment.key"
                class="donut-segment"
                cx="110"
                cy="110"
                r="84"
                pathLength="100"
                :stroke="segment.color"
                :stroke-dasharray="`${segment.percent} ${100 - segment.percent}`"
                :stroke-dashoffset="-segment.offset"
                @mouseenter="hoveredSegment = segment"
                @focus="hoveredSegment = segment"
              />
            </svg>
            <div class="donut-center">
              <span>{{ activeDonutSegment.label }}</span>
              <strong>{{ activeDonutSegment.percent }}%</strong>
              <small>del total</small>
            </div>
            <div
              v-if="hoveredSegment"
              class="donut-tooltip"
              :style="{ '--tooltip-color': hoveredSegment.color }"
            >
              <span class="legend-dot"></span>
              <p>{{ hoveredSegment.label }}</p>
              <strong>{{ hoveredSegment.percent }}%</strong>
              <small>{{ formatNumero(hoveredSegment.count) }} movimientos</small>
            </div>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-icon bg-violet-50 text-[#9150F7]">+</span>
            <p>Entradas</p>
          </div>
          <strong>{{ formatNumero(totalIngresos) }}</strong>
          <div class="bar-track"><span class="bg-[#9150F7]" :style="{ width: `${pctIngresos}%` }"></span></div>
          <small>{{ pctIngresos }}% de movimientos</small>
        </div>

        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-icon bg-emerald-50 text-emerald-600">-</span>
            <p>Salidas</p>
          </div>
          <strong>{{ formatNumero(totalSalidas) }}</strong>
          <div class="bar-track"><span class="bg-emerald-500" :style="{ width: `${pctSalidas}%` }"></span></div>
          <small>{{ pctSalidas }}% de movimientos</small>
        </div>

        <div class="metric-card">
          <div class="metric-head">
            <span class="metric-icon bg-rose-50 text-rose-500">!</span>
            <p>Rechazados</p>
          </div>
          <strong>{{ formatNumero(totalRechazados) }}</strong>
          <div class="bar-track"><span class="bg-rose-500" :style="{ width: `${pctRechazados}%` }"></span></div>
          <small>{{ pctRechazados }}% de movimientos</small>
        </div>
      </section>

      <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="panel xl:col-span-2">
          <div class="panel-title">
            <div>
              <h3>Porcentaje de movimientos</h3>
              <p>Distribucion por tipo de movimiento</p>
            </div>
          </div>
          <div class="space-y-5">
            <div v-for="item in movimientoStats" :key="item.label" class="movement-row">
              <div class="flex items-center justify-between">
                <span>{{ item.label }}</span>
                <strong>{{ item.percent }}%</strong>
              </div>
              <div class="bar-track tall"><span :class="item.color" :style="{ width: `${item.percent}%` }"></span></div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-title">
            <div>
              <h3>Trazabilidad</h3>
              <p>Estado de entregas</p>
            </div>
          </div>
          <div class="delivery-gauge">
            <div class="mini-donut" :style="{ '--p': pctEntregado }">
              <strong>{{ pctEntregado }}%</strong>
              <span>entregado</span>
            </div>
            <div class="space-y-3 flex-1">
              <div class="status-line">
                <span>Entregadas</span>
                <b>{{ entregasCompletadas }}</b>
              </div>
              <div class="status-line">
                <span>Designadas</span>
                <b>{{ entregasDesignadas }}</b>
              </div>
              <div class="status-line">
                <span>Salidas pendientes</span>
                <b>{{ pctPendiente }}%</b>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="panel">
          <div class="panel-title">
            <div>
              <h3>Productos con mas movimiento</h3>
              <p>Segun cantidad registrada</p>
            </div>
          </div>
          <div class="space-y-4">
            <div v-for="producto in productoTop" :key="producto.nombre" class="product-row">
              <div>
                <strong>{{ producto.nombre }}</strong>
                <span>Stock {{ formatNumero(producto.stock) }}</span>
              </div>
              <div class="product-bar">
                <span :style="{ width: `${Math.min(100, porcentaje(producto.cantidad, productoTop[0]?.cantidad || 1))}%` }"></span>
              </div>
              <b>{{ formatNumero(producto.cantidad) }}</b>
            </div>
            <p v-if="!productoTop.length" class="text-sm text-gray-400">Sin movimientos registrados.</p>
          </div>
        </div>

        <div class="panel">
          <div class="panel-title">
            <div>
              <h3>Actividad reciente</h3>
              <p>Ingresos y salidas mas recientes</p>
            </div>
          </div>
          <div class="space-y-3">
            <div v-for="item in actividadReciente" :key="`${item.tipo}-${item.codigo}`" class="activity-row">
              <span :class="item.color">{{ item.tipo }}</span>
              <div>
                <strong>{{ item.codigo }}</strong>
                <p>{{ item.responsable }}</p>
              </div>
              <time>{{ formatFecha(item.fecha) }}</time>
            </div>
            <p v-if="!actividadReciente.length" class="text-sm text-gray-400">Sin actividad reciente.</p>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.dashboard-shell {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.hero-panel {
  position: relative;
  overflow: visible;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 260px;
  gap: 1.5rem;
  border-radius: 24px;
  padding: 1.5rem;
  background:
    radial-gradient(circle at 18% 20%, rgba(255,255,255,.22), transparent 28%),
    linear-gradient(135deg, #2B2353 0%, #627DF2 48%, #E87092 100%);
  box-shadow: 0 24px 48px rgba(43, 35, 83, .18);
}
.hero-content,
.donut-wrap {
  position: relative;
  z-index: 1;
}
.hero-stat {
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.12);
  border-radius: 16px;
  padding: .9rem;
  color: white;
}
.hero-stat span {
  display: block;
  color: rgba(255,255,255,.72);
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
}
.hero-stat strong {
  display: block;
  margin-top: .2rem;
  font-size: 1.55rem;
}
.donut-wrap {
  display: grid;
  place-items: center;
}
.donut,
.mini-donut {
  display: grid;
  place-items: center;
  border-radius: 999px;
}
.donut {
  position: relative;
  width: 210px;
  height: 210px;
}
.donut-svg {
  position: absolute;
  inset: 0;
  overflow: visible;
  transform: rotate(-90deg);
}
.donut-track,
.donut-segment {
  fill: none;
  stroke-width: 42;
}
.donut-track {
  stroke: rgba(255, 255, 255, .16);
}
.donut-segment {
  cursor: pointer;
  pointer-events: stroke;
  transition: stroke-width .18s ease, filter .18s ease, opacity .18s ease;
}
.donut-segment:hover,
.donut-segment:focus {
  stroke-width: 48;
  filter: drop-shadow(0 10px 14px rgba(43, 35, 83, .24));
  outline: none;
}
.donut-center,
.mini-donut {
  width: 126px;
  height: 126px;
  background: white;
  text-align: center;
}
.donut-center {
  position: relative;
  z-index: 2;
  display: grid;
  place-items: center;
  align-content: center;
  border-radius: 999px;
  padding: 1rem;
}
.donut-center span,
.donut-center small,
.mini-donut span {
  color: #6b7280;
  font-size: .75rem;
  font-weight: 700;
}
.donut-center strong {
  color: #2B2353;
  font-size: 2rem;
  line-height: 1.1;
}
.donut-tooltip {
  position: absolute;
  right: calc(100% + 16px);
  top: 50%;
  transform: translateY(-50%);
  min-width: 184px;
  padding: .8rem .9rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, .96);
  border: 1px solid rgba(229, 231, 235, .9);
  box-shadow: 0 18px 36px rgba(43, 35, 83, .18);
  pointer-events: none;
  z-index: 20;
}
.donut-tooltip::before {
  content: '';
  position: absolute;
  top: calc(50% - 7px);
  right: -7px;
  width: 14px;
  height: 14px;
  transform: rotate(45deg);
  background: rgba(255, 255, 255, .96);
  border-right: 1px solid rgba(229, 231, 235, .9);
  border-top: 1px solid rgba(229, 231, 235, .9);
}
.donut-tooltip {
  display: grid;
  grid-template-columns: 12px 1fr auto;
  align-items: center;
  gap: .55rem;
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: var(--tooltip-color);
}
.donut-tooltip p {
  color: #4b5563;
  font-size: .78rem;
  font-weight: 700;
}
.donut-tooltip strong {
  color: #2B2353;
  font-size: .86rem;
}
.donut-tooltip small {
  grid-column: 2 / -1;
  color: #8b95a7;
  font-size: .72rem;
  font-weight: 700;
}
.metric-card,
.panel {
  background: white;
  border: 1px solid #eef0f6;
  border-radius: 20px;
  box-shadow: 0 12px 28px rgba(43, 35, 83, .06);
}
.metric-card {
  padding: 1.1rem;
}
.metric-head {
  display: flex;
  align-items: center;
  gap: .7rem;
  color: #6b7280;
  font-size: .78rem;
  font-weight: 800;
  text-transform: uppercase;
}
.metric-icon {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 12px;
}
.metric-card strong {
  display: block;
  color: #111827;
  font-size: 2rem;
  margin-top: .9rem;
}
.metric-card small {
  display: block;
  color: #8b95a7;
  margin-top: .55rem;
}
.bar-track {
  height: 8px;
  background: #eef1f7;
  border-radius: 999px;
  overflow: hidden;
  margin-top: .9rem;
}
.bar-track.tall {
  height: 12px;
}
.bar-track span {
  display: block;
  height: 100%;
  border-radius: inherit;
}
.panel {
  padding: 1.25rem;
}
.panel-title {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.2rem;
}
.panel-title h3 {
  color: #111827;
  font-weight: 800;
  font-size: 1.05rem;
}
.panel-title p,
.movement-row span,
.product-row span,
.activity-row p,
.activity-row time {
  color: #8b95a7;
  font-size: .8rem;
}
.movement-row strong {
  color: #2B2353;
}
.delivery-gauge {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}
.mini-donut {
  flex: 0 0 auto;
  background: radial-gradient(circle closest-side, #fff 68%, transparent 69%), conic-gradient(#10b981 calc(var(--p) * 1%), #eef1f7 0);
}
.mini-donut strong {
  color: #2B2353;
  font-size: 1.4rem;
}
.status-line {
  display: flex;
  justify-content: space-between;
  padding: .75rem .85rem;
  border-radius: 12px;
  background: #f8fafc;
  color: #6b7280;
  font-size: .85rem;
}
.status-line b {
  color: #2B2353;
}
.product-row {
  display: grid;
  grid-template-columns: minmax(120px, 1fr) minmax(120px, 220px) 48px;
  align-items: center;
  gap: 1rem;
}
.product-row strong,
.activity-row strong {
  display: block;
  color: #111827;
  font-size: .9rem;
}
.product-bar {
  height: 8px;
  border-radius: 999px;
  background: #eef1f7;
  overflow: hidden;
}
.product-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #627DF2, #9150F7, #E87092);
}
.activity-row {
  display: grid;
  grid-template-columns: 78px minmax(0, 1fr) 112px;
  align-items: center;
  gap: .85rem;
  padding: .8rem;
  border-radius: 14px;
  background: #f8fafc;
}
.activity-row > span {
  text-align: center;
  font-size: .72rem;
  font-weight: 800;
  border-radius: 999px;
  padding: .35rem .55rem;
}
@media (max-width: 900px) {
  .hero-panel {
    grid-template-columns: 1fr;
  }
  .donut-tooltip {
    right: 50%;
    top: calc(100% + 12px);
    transform: translateX(50%);
  }
  .donut-tooltip::before {
    top: -7px;
    right: calc(50% - 7px);
    border-left: 1px solid rgba(229, 231, 235, .9);
    border-right: 0;
  }
  .product-row,
  .activity-row {
    grid-template-columns: 1fr;
  }
}
</style>
