<script setup>
import { ref, computed, nextTick, onBeforeUnmount, onMounted, watch } from 'vue'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const destinos = ref([])
const inactivos = ref([])
const loading = ref(false)
const submitting = ref(false)
const error = ref(null)
const showModal = ref(false)
const showConfirm = ref(false)
const confirmData = ref({ title: '', message: '', action: null, type: 'danger' })
const editando = ref(null)

const busqueda = ref('')
const vistaInactivos = ref(false)
const mapaDestino = ref(null)
const busquedaLugar = ref('')
const resultadosLugar = ref([])
const buscandoLugar = ref(false)
const ubicacionSeleccionada = ref(null)

let mapa = null
let marcador = null
let clickMapaTimer = null
let reverseRequestId = 0
let searchRequestId = 0

const centroInicial = [-16.5, -68.15]
const marcadorIcono = L.divIcon({
  className: '',
  html: '<div class="destino-marker"><span class="destino-marker-pin"></span></div>',
  iconSize: [34, 44],
  iconAnchor: [17, 44],
})

const tipos = ['Sucursal', 'Almacen', 'Distribuidor', 'Cliente', 'Otro']

const form = ref({
  nombre: '',
  ciudad: '',
  tipo: '',
  direccion: '',
  cliente: {
    razon_social: '',
    nit_ci: '',
    nombre_encargado: '',
    telefono: '',
    correo: '',
    direccion: '',
  },
})

const listaActual = computed(() =>
  vistaInactivos.value ? inactivos.value : destinos.value
)

const destinosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()

  if (!q) return listaActual.value

  return listaActual.value.filter(d =>
    d.nombre?.toLowerCase().includes(q) ||
    d.ciudad?.toLowerCase().includes(q) ||
    d.tipo?.toLowerCase().includes(q) ||
    d.direccion?.toLowerCase().includes(q)
  )
})

const ciudadesActivas = computed(() =>
  [...new Set(destinos.value.map(d => d.ciudad).filter(Boolean))].length
)

const paramsExportDestinos = computed(() => ({
  buscar: busqueda.value || null,
}))

const resetForm = () => {
  form.value = {
    nombre: '',
    ciudad: '',
    tipo: '',
    direccion: '',
    cliente: {
      razon_social: '',
      nit_ci: '',
      nombre_encargado: '',
      telefono: '',
      correo: '',
      direccion: '',
    },
  }
  editando.value = null
  busquedaLugar.value = ''
  resultadosLugar.value = []
  ubicacionSeleccionada.value = null
}

const cargar = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/destinos')
    destinos.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch (e) {
    error.value = 'Error al cargar destinos'
  } finally {
    loading.value = false
  }
}

const cargarInactivos = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/destinos/inactivos')
    inactivos.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch (e) {
    error.value = 'Error al cargar destinos archivados'
  } finally {
    loading.value = false
  }
}

const toggleVista = async () => {
  vistaInactivos.value = !vistaInactivos.value
  if (vistaInactivos.value && inactivos.value.length === 0) {
    await cargarInactivos()
  }
}

const abrirNuevo = () => {
  resetForm()
  showModal.value = true
}

const abrirEditar = (d) => {
  editando.value = d.id_destino
  form.value = {
    nombre: d.nombre,
    ciudad: d.ciudad || '',
    tipo: d.tipo || '',
    direccion: d.direccion || '',
    cliente: {
      razon_social: d.cliente?.razon_social || d.nombre || '',
      nit_ci: d.cliente?.nit_ci || '',
      nombre_encargado: d.cliente?.nombre_encargado || '',
      telefono: d.cliente?.telefono || '',
      correo: d.cliente?.correo || '',
      direccion: d.cliente?.direccion || d.direccion || '',
    },
  }
  busquedaLugar.value = d.direccion || ''
  resultadosLugar.value = []
  ubicacionSeleccionada.value = null
  showModal.value = true
}

const destruirMapa = () => {
  if (clickMapaTimer) {
    clearTimeout(clickMapaTimer)
    clickMapaTimer = null
  }
  if (mapa) {
    mapa.remove()
    mapa = null
    marcador = null
  }
}

const moverMarcador = (lat, lon) => {
  const punto = [Number(lat), Number(lon)]
  ubicacionSeleccionada.value = { lat: punto[0], lon: punto[1] }

  if (!mapa) return
  mapa.setView(punto, 16)

  if (marcador) {
    marcador.setLatLng(punto)
  } else {
    marcador = L.marker(punto, { icon: marcadorIcono }).addTo(mapa)
  }
}

const parseCoordenadas = (valor) => {
  const match = String(valor || '').match(/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/)
  if (!match) return null

  const lat = Number(match[1])
  const lon = Number(match[2])
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null
  if (lat < -90 || lat > 90 || lon < -180 || lon > 180) return null

  return { lat, lon }
}

const nombreLugar = (properties = {}) => {
  const partes = [
    properties.name,
    properties.street,
    properties.district,
    properties.city,
    properties.state,
    properties.country,
  ].filter(Boolean)

  return [...new Set(partes)].join(', ')
}

const lugaresDesdePhoton = (data) =>
  (data.features || []).map((feature) => {
    const [lon, lat] = feature.geometry?.coordinates || []
    const displayName = nombreLugar(feature.properties) || `${lat}, ${lon}`

    return {
      place_id: feature.properties?.osm_id || `${lat}-${lon}-${displayName}`,
      lat,
      lon,
      display_name: displayName,
    }
  }).filter((lugar) => Number.isFinite(Number(lugar.lat)) && Number.isFinite(Number(lugar.lon)))

const direccionDesdeCoordenadas = async (lat, lon) => {
  const requestId = ++reverseRequestId
  form.value.direccion = `${Number(lat).toFixed(6)}, ${Number(lon).toFixed(6)}`
  busquedaLugar.value = form.value.direccion

  try {
    const url = new URL('https://photon.komoot.io/reverse')
    url.searchParams.set('lat', lat)
    url.searchParams.set('lon', lon)
    url.searchParams.set('lang', 'es')

    const response = await fetch(url)
    if (!response.ok || requestId !== reverseRequestId) return

    const lugares = lugaresDesdePhoton(await response.json())
    if (lugares[0]) {
      form.value.direccion = lugares[0].display_name
      busquedaLugar.value = lugares[0].display_name
    }
  } catch (e) {
    // Si falla la bÃºsqueda inversa, se conservan las coordenadas.
  }
}

const seleccionarCoordenadas = async (lat, lon) => {
  moverMarcador(lat, lon)
  await direccionDesdeCoordenadas(lat, lon)
}

const ubicarDireccionGuardada = async () => {
  const direccion = form.value.direccion?.trim()
  if (!direccion || ubicacionSeleccionada.value) return

  busquedaLugar.value = direccion

  const coords = parseCoordenadas(direccion)
  if (coords) {
    moverMarcador(coords.lat, coords.lon)
    return
  }

  try {
    const url = new URL('https://photon.komoot.io/api/')
    url.searchParams.set('q', `${direccion}, Bolivia`)
    url.searchParams.set('limit', '1')
    url.searchParams.set('lang', 'es')

    const response = await fetch(url)
    if (!response.ok) return

    const lugares = lugaresDesdePhoton(await response.json())
    const lugar = lugares[0]

    if (lugar) {
      moverMarcador(lugar.lat, lugar.lon)
    }
  } catch (e) {
    // La dirección guardada sigue visible aunque no se pueda ubicar.
  }
}

const iniciarMapa = async () => {
  await nextTick()
  if (!mapaDestino.value || mapa) return

  mapa = L.map(mapaDestino.value, {
    zoomControl: true,
    scrollWheelZoom: true,
    doubleClickZoom: true,
  }).setView(centroInicial, 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
  }).addTo(mapa)

  mapa.on('click', (e) => {
    if (clickMapaTimer) clearTimeout(clickMapaTimer)
    clickMapaTimer = setTimeout(() => {
      seleccionarCoordenadas(e.latlng.lat, e.latlng.lng)
    }, 180)
  })

  if (ubicacionSeleccionada.value) {
    moverMarcador(ubicacionSeleccionada.value.lat, ubicacionSeleccionada.value.lon)
  }

  setTimeout(() => mapa?.invalidateSize(), 100)
  await ubicarDireccionGuardada()
}

const buscarLugar = async () => {
  const q = busquedaLugar.value.trim()
  if (q.length < 3) {
    resultadosLugar.value = []
    return
  }

  const coords = parseCoordenadas(q)
  if (coords) {
    resultadosLugar.value = []
    moverMarcador(coords.lat, coords.lon)
    form.value.direccion = `${coords.lat}, ${coords.lon}`
    return
  }

  const requestId = ++searchRequestId
  buscandoLugar.value = true
  try {
    const url = new URL('https://photon.komoot.io/api/')
    url.searchParams.set('q', `${q}, Bolivia`)
    url.searchParams.set('limit', '5')

    const response = await fetch(url)
    if (!response.ok || requestId !== searchRequestId) return

    resultadosLugar.value = lugaresDesdePhoton(await response.json())
    if (!resultadosLugar.value.length) {
      error.value = 'No se encontraron lugares con ese texto'
    }
  } catch (e) {
    error.value = 'No se pudo buscar el lugar'
  } finally {
    if (requestId === searchRequestId) {
      buscandoLugar.value = false
    }
  }
}

const seleccionarLugar = (lugar) => {
  form.value.direccion = lugar.display_name
  busquedaLugar.value = lugar.display_name
  resultadosLugar.value = []
  moverMarcador(lugar.lat, lugar.lon)
}

const usarMiUbicacion = () => {
  if (!navigator.geolocation) {
    error.value = 'Tu navegador no permite geolocalizaciÃƒÂ³n'
    return
  }

  navigator.geolocation.getCurrentPosition(
    ({ coords }) => seleccionarCoordenadas(coords.latitude, coords.longitude),
    () => { error.value = 'No se pudo obtener tu ubicaciÃƒÂ³n' },
    { enableHighAccuracy: true, timeout: 10000 },
  )
}

const guardar = async () => {
  if (!form.value.nombre) {
    error.value = 'El nombre es obligatorio'
    return
  }

  submitting.value = true
  try {
    const payload = {
      ...form.value,
      cliente: {
        ...form.value.cliente,
        razon_social: form.value.cliente.razon_social || form.value.nombre,
        direccion: form.value.cliente.direccion || form.value.direccion,
      },
    }
    if (editando.value) {
      await api.put(`/destinos/${editando.value}`, payload)
    } else {
      await api.post('/destinos', payload)
    }

    await cargar()
    if (vistaInactivos.value) await cargarInactivos()
    showModal.value = false
    resetForm()
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al guardar'
  } finally {
    submitting.value = false
  }
}

const confirmar = (title, message, action, type = 'danger') => {
  confirmData.value = { title, message, action, type }
  showConfirm.value = true
}

const archivar = (d) => confirmar(
  'Archivar destino',
  `Archivar "${d.nombre}"? Seguira en el historial pero no aparecera en nuevas salidas.`,
  async () => {
    await api.patch(`/destinos/${d.id_destino}/archivar`)
    await cargar()
    inactivos.value = []
  },
  'warning'
)

const activar = (d) => confirmar(
  'Activar destino',
  `Activar "${d.nombre}"? Volvera a estar disponible para nuevas salidas.`,
  async () => {
    await api.patch(`/destinos/${d.id_destino}/activar`)
    await Promise.all([cargar(), cargarInactivos()])
  },
  'success'
)

const eliminarDefinitivo = (d) => confirmar(
  'Eliminar definitivamente',
  `Â¿Eliminar "${d.nombre}" para siempre? Esta accion no se puede deshacer.`,
  async () => {
    await api.delete(`/destinos/${d.id_destino}/definitivo`)
    await cargarInactivos()
  },
  'danger'
)

const ejecutarConfirm = async () => {
  try {
    await confirmData.value.action()
  } catch (e) {
    error.value = 'Error en la operacion'
  } finally {
    showConfirm.value = false
  }
}

const exportarDestinosPDF = async () => {
  try {
    const response = await api.get('/reportes/destinos/pdf', {
      responseType: 'blob',
      params: paramsExportDestinos.value,
    })

    descargarPdfBlob(response.data, 'reporte_destinos.pdf')
  } catch (e) {
    console.error('Error al exportar PDF:', e)
    error.value = 'Error al exportar PDF'
  }
}

const imprimirDestinos = async () => {
  try {
    const response = await api.get('/reportes/destinos/pdf', {
      responseType: 'blob',
      params: paramsExportDestinos.value,
    })

    imprimirPdfBlob(response.data, 'reporte_destinos')
  } catch (e) {
    console.error('Error al imprimir:', e)
    error.value = 'Error al imprimir'
  }
}

const exportarDestinosExcel = async () => {
  try {
    const response = await api.get('/reportes/destinos/excel', {
      responseType: 'blob',
      params: paramsExportDestinos.value,
    })

    descargarBlob(response.data, 'reporte_destinos.xlsx')
  } catch (e) {
    console.error('Error al exportar Excel:', e)
    error.value = 'Error al exportar Excel'
  }
}

const tipoBadge = (t) => {
  const map = {
    Sucursal: 'bg-blue-100 text-blue-700',
    'Almacen': 'bg-yellow-100 text-yellow-700',
    Distribuidor: 'bg-purple-100 text-purple-700',
    Cliente: 'bg-green-100 text-green-700',
    Otro: 'bg-gray-100 text-gray-600',
  }

  return map[t] || 'bg-gray-100 text-gray-600'
}

watch(showModal, (open) => {
  if (open) {
    iniciarMapa()
  } else {
    destruirMapa()
  }
})

onMounted(cargar)
onBeforeUnmount(destruirMapa)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">
          {{ vistaInactivos ? 'Destinos Archivados' : 'Destinos' }}
        </h2>
        <p class="text-sm text-gray-500 mt-1">
          Gestiona los puntos de destino para despachos y salidas
        </p>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <button @click="toggleVista"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition shadow-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
          {{ vistaInactivos ? 'Ver Activos' : 'Ver Archivados' }}
        </button>

        <ReportExportMenu
          v-if="!vistaInactivos"
          @excel="exportarDestinosExcel"
          @pdf="exportarDestinosPDF"
          @print="imprimirDestinos"
        />

        <button v-if="!vistaInactivos" @click="abrirNuevo"
          class="flex items-center gap-2 bg-[#2B2353] hover:bg-[#3d3275] text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-md transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Destino
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex justify-between">
      {{ error }}
      <button @click="error = null" class="font-bold">Destino•</button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#2B2353]/10 flex items-center justify-center">
          <svg class="w-6 h-6 text-[#2B2353]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5h.01" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Activos</p>
          <p class="text-2xl font-bold text-gray-800">{{ destinos.length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Archivados</p>
          <p class="text-2xl font-bold text-gray-800">{{ inactivos.length }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
          <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Ciudades</p>
          <p class="text-2xl font-bold text-gray-800">{{ ciudadesActivas }}</p>
        </div>
      </div>
    </div>

    <!-- Buscador -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-center">
      <div class="relative flex-1 min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="busqueda" type="text" placeholder="Buscar por nombre, ciudad, tipo o direccion..."
          class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353]" />
      </div>
      <span class="text-sm text-gray-400 ml-auto">{{ destinosFiltrados.length }} resultado(s)</span>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-700 text-sm">
          {{ vistaInactivos ? 'Destinos archivados' : 'Destinos activos' }}
        </h3>

        <span class="text-xs text-gray-400">
          {{ destinosFiltrados.length }} resultado(s)
        </span>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
          <p class="text-sm text-gray-400">Cargando destinos...</p>
        </div>
      </div>

      <div v-else-if="destinosFiltrados.length === 0" class="text-center py-12 text-gray-400">
        No se encontraron destinos
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase">Nombre</th>
            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase">Ciudad</th>
            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase">Tipo</th>
            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase">Direccion</th>
            <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-50">
          <tr v-for="d in destinosFiltrados" :key="d.id_destino" class="hover:bg-gray-50 transition-colors"
            :class="vistaInactivos ? 'opacity-70 bg-gray-50/50' : ''">
            <td class="px-5 py-3.5 font-medium text-gray-800">
              {{ d.nombre }}
            </td>

            <td class="px-5 py-3.5 text-gray-600">
              {{ d.ciudad || 'â€”' }}
            </td>

            <td class="px-5 py-3.5">
              <span v-if="d.tipo" :class="['text-xs font-medium px-2.5 py-1 rounded-full', tipoBadge(d.tipo)]">
                {{ d.tipo }}
              </span>
              <span v-else class="text-gray-400">â€”</span>
            </td>

            <td class="px-5 py-3.5 text-gray-500 max-w-xs truncate">
              {{ d.direccion || 'â€”' }}
            </td>

            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-2">
                <template v-if="!vistaInactivos">
                  <button @click="abrirEditar(d)"
                    class="p-2 rounded-lg text-gray-400 hover:text-[#2B2353] hover:bg-[#2B2353]/10 transition-colors"
                    title="Editar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>

                  <button @click="archivar(d)"
                    class="p-2 rounded-lg text-gray-400 hover:text-orange-500 hover:bg-orange-50 transition-colors"
                    title="Archivar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                  </button>
                </template>

                <template v-else>
                  <button @click="activar(d)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors"
                    title="Activar">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Activar
                  </button>

                  <button @click="eliminarDefinitivo(d)"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors"
                    title="Eliminar">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Eliminar
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal crear/editar -->
    <Teleport to="body">
      <div v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5h.01" />
                </svg>
              </div>
              <div>
                <h3 class="text-white font-bold text-base">
                  {{ editando ? 'Editar Destino' : 'Nuevo Destino' }}
                </h3>
                <p class="text-white/60 text-xs">
                  {{ editando ? 'Modifica los datos del destino' : 'Completa los campos requeridos' }}
                </p>
              </div>
            </div>

            <button @click="showModal = false; resetForm()" class="text-white/60 hover:text-white transition-colors">
              Destino
            </button>
          </div>

          <div class="p-6 space-y-4 max-h-[68vh] overflow-y-auto">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                Nombre <span class="text-red-400">*</span>
              </label>
              <input v-model="form.nombre"
                class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition"
                placeholder="Ej: Sucursal Norte" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                  Ciudad
                </label>
                <input v-model="form.ciudad"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition"
                  placeholder="Ej: La Paz" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                  Tipo
                </label>
                <select v-model="form.tipo"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] bg-white transition">
                  <option value="">Seleccionar...</option>
                  <option v-for="t in tipos" :key="t" :value="t">
                    {{ t }}
                  </option>
                </select>
              </div>
            </div>

            <div class="rounded-2xl border border-indigo-100 bg-[#F4F7FE]/70 p-4 space-y-3">
              <div>
                <h4 class="text-sm font-bold text-gray-800">Datos del cliente</h4>
                <p class="text-xs text-gray-500">Estos datos se usan para emitir y enviar la factura.</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Razon social / tienda</label>
                  <input v-model="form.cliente.razon_social"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
                    placeholder="Ej: Tienda Don Hilarion" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">NIT / CI</label>
                  <input v-model="form.cliente.nit_ci"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
                    placeholder="Ej: 1234567" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Encargado</label>
                <input v-model="form.cliente.nombre_encargado"
                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
                  placeholder="Nombre del encargado" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Telefono</label>
                  <input v-model="form.cliente.telefono"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
                    placeholder="Ej: 77777777" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Correo para factura</label>
                  <input v-model="form.cliente.correo" type="email"
                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
                    placeholder="cliente@correo.com" />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                Direccion
              </label>
              <div class="space-y-3">
                <div class="flex gap-2">
                  <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="busquedaLugar" type="text"
                      class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30 focus:border-[#2B2353] transition"
                      placeholder="Buscar lugar, ej: Faro Murillo" @keyup.enter="buscarLugar" />
                  </div>

                  <button type="button" @click="buscarLugar" :disabled="buscandoLugar"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-[#2B2353] rounded-xl hover:bg-[#3d3275] transition disabled:opacity-60 flex items-center gap-2">
                    <svg v-if="buscandoLugar" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ buscandoLugar ? 'Buscando...' : 'Buscar' }}
                  </button>
                </div>

                <div v-if="resultadosLugar.length"
                  class="max-h-36 overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-sm">
                  <button v-for="lugar in resultadosLugar" :key="`${lugar.place_id}-${lugar.lat}`" type="button"
                    @click="seleccionarLugar(lugar)"
                    class="w-full px-3 py-2.5 text-left text-sm text-gray-600 hover:bg-[#F4F7FE] transition border-b border-gray-50 last:border-b-0">
                    {{ lugar.display_name }}
                  </button>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-gray-200">
                  <div ref="mapaDestino" class="h-64 w-full bg-gray-100"></div>
                  <button type="button" @click="usarMiUbicacion"
                    class="absolute right-3 top-3 z-[500] flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 text-[#2B2353]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                      stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 21s7-4.438 7-11a7 7 0 10-14 0c0 6.562 7 11 7 11z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5h.01" />
                    </svg>
                    Mi ubicacion
                  </button>
                </div>

                <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2.5">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Direccion seleccionada</p>
                  <p class="mt-1 text-sm text-gray-600 leading-relaxed">
                    {{ form.direccion || 'Busca un lugar o haz clic en el mapa para seleccionar una direccion.' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button @click="showModal = false; resetForm()"
              class="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition">
              Cancelar
            </button>

            <button @click="guardar" :disabled="submitting"
              class="px-5 py-2.5 text-sm font-semibold text-white bg-[#2B2353] rounded-xl hover:bg-[#3d3275] transition disabled:opacity-60 flex items-center gap-2">
              <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              {{ submitting ? 'Guardando...' : (editando ? 'Guardar cambios' : 'Guardar Destino') }}
            </button>
          </div>
        </div>
      </div>
      <div v-if="showConfirm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center space-y-4">
          <h3 class="text-lg font-bold text-gray-800">
            {{ confirmData.title }}
          </h3>

          <p class="text-sm text-gray-500">
            {{ confirmData.message }}
          </p>

          <div class="flex gap-3 justify-center">
            <button @click="showConfirm = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
              Cancelar
            </button>

            <button @click="ejecutarConfirm" :class="[
              'px-4 py-2 text-sm text-white rounded-lg font-medium',
              confirmData.type === 'danger'
                ? 'bg-red-500 hover:bg-red-600'
                : confirmData.type === 'warning'
                  ? 'bg-yellow-500 hover:bg-yellow-600'
                  : 'bg-green-500 hover:bg-green-600'
            ]">
              Confirmar
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
:deep(.destino-marker) {
  position: relative;
  width: 34px;
  height: 44px;
}

:deep(.destino-marker-pin) {
  position: absolute;
  left: 3px;
  top: 1px;
  width: 28px;
  height: 28px;
  background: #236dcc;
  border: 3px solid #ffffff;
  border-radius: 999px 999px 999px 0;
  box-shadow: 0 4px 10px rgba(30, 41, 59, 0.28);
  transform: rotate(-45deg);
}

:deep(.destino-marker-pin::after) {
  content: '';
  position: absolute;
  width: 8px;
  height: 8px;
  inset: 7px;
  background: #71c0ee;
  border-radius: 999px;
}

:deep(.leaflet-container) {
  font-family: inherit;
  z-index: 0;
}

:deep(.leaflet-control-attribution) {
  font-size: 10px;
}
</style>
