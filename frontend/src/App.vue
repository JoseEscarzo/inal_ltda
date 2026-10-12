<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

const roleNames = {
  1: 'Administrador',
  2: 'Supervisor',
  3: 'Operador',
  4: 'Analista',
  5: 'Distribuidor',
}

// Menú completo
const menuItems = [
  { name: 'Dashboard', path: '/dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Usuarios', path: '/usuarios', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: 'Monitoreo en tiempo real', path: '/monitoreo', icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
 // { name: 'Detecciones', path: '/detecciones', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
 // { name: 'Alertas', path: '/alertas', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', badge: 8 },
  { name: 'Productos', path: '/productos', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
  { name: 'Inventario', path: '/inventario', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
  { name: 'Destinos', path: '/destinos', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z' },
  { name: 'Proveedores', path: '/proveedores', icon: 'M3 7h18M5 7v13h14V7M9 11h6M9 15h6M8 3h8v4H8z' },
  { name: 'Lotes', path: '/lotes', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
  { name: 'Trazabilidad', path: '/trazabilidad', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  { name: 'Distribuciones', path: '/distribuciones', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
  { name: 'Producción', path: '/produccion', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
  { name: 'Cámaras', path: '/camaras', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
 //{ name: 'Modelos de IA', path: '/modelos', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
 //{ name: 'Reportes', path: '/reportes', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
 //{ name: 'Roles y permisos', path: '/roles', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
]

const roleMenuNames = {
  2: [
    'Inventario',
    'Monitoreo en tiempo real',
    'Reportes',
    'Detecciones',
    'Cámaras',
    'Alertas',
    'Productos',
    'Destinos',
    'Proveedores',
    'Distribuciones',
  ],
  3: [
    'Monitoreo en tiempo real',
    'Detecciones',
    'Alertas',
    'Producción',
    'Cámaras',
    'Modelos de IA',
  ],
  5: [
    'Trazabilidad',
  ],
}

const userName = computed(() => authStore.user?.name || 'Jose Angel Mamani Escarzo')
const userRoleId = computed(() => Number(authStore.user?.id_rol))
const userRole = computed(() => authStore.user?.rol?.nombre || authStore.user?.role || roleNames[userRoleId.value] || 'Administrador')
const alertas = ref([])
const showAlertas = ref(false)
const cargandoAlertas = ref(false)
const notificacionModal = ref({ visible: false, titulo: '', mensaje: '', tipo: 'success' })
let alertasTimer = null
const alertasPendientes = computed(() => alertas.value.length)
const esDistribuidor = computed(() => userRoleId.value === 5)
const visibleMenuItems = computed(() => {
  const allowedNames = roleMenuNames[userRoleId.value]

  if (allowedNames) {
    return allowedNames
      .map((name) => menuItems.find((item) => item.name === name))
      .filter(Boolean)
  }

  return menuItems
})

watch(
  [visibleMenuItems, () => route.path, () => authStore.token],
  ([items, currentPath, token]) => {
    if (!token || route.name === 'login' || !items.length) return

    const canSeeCurrentRoute = items.some((item) => item.path === currentPath)
    if (!canSeeCurrentRoute) {
      router.replace(items[0].path)
    }
  },
  { immediate: true }
)

// Lógica de Foto de Perfil
const fileInput = ref(null)
const profileImageUrl = ref(authStore.user?.avatar || null) // Si el backend envía URL del avatar

const userInitials = computed(() => {
  const names = userName.value.split(' ')
  if (names.length >= 2) return `${names[0][0]}${names[1][0]}`.toUpperCase()
  return names[0].substring(0, 2).toUpperCase()
})

const triggerFileUpload = () => {
  fileInput.value.click()
}

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // 1. Mostrar previsualización inmediata en el frontend
  profileImageUrl.value = URL.createObjectURL(file)

  // 2. Aquí deberías agregar la lógica para enviarlo a tu backend en Laravel
  /*
  const formData = new FormData()
  formData.append('avatar', file)
  try {
    await axios.post('/api/user/avatar', formData)
    // authStore.updateUserAvatar(response.data.avatar_url)
  } catch(error) {
    console.error("Error subiendo la imagen", error)
  }
  */
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}

const cargarAlertas = async () => {
  if (!authStore.token) return
  cargandoAlertas.value = true
  try {
    const { data } = await api.get('/alertas')
    alertas.value = data.alertas || data.data || []
  } catch (error) {
    console.error('Error cargando alertas:', error)
  } finally {
    cargandoAlertas.value = false
  }
}

const toggleAlertas = async () => {
  showAlertas.value = !showAlertas.value
  if (showAlertas.value) await cargarAlertas()
}

const aprobarSalida = async (alerta) => {
  await api.post(`/salidas/${alerta.salida_id}/aprobar`)
  await cargarAlertas()
  if (route.name === 'trazabilidad' || route.name === 'distribuciones') router.go(0)
}

const rechazarSalida = async (alerta) => {
  await api.post(`/salidas/${alerta.salida_id}/rechazar`)
  await cargarAlertas()
  if (route.name === 'distribuciones') router.go(0)
}

const mostrarNotificacion = ({ titulo = 'Proceso completado', mensaje = '', tipo = 'success' }) => {
  notificacionModal.value = { visible: true, titulo, mensaje, tipo }
}

const cerrarNotificacion = () => {
  notificacionModal.value.visible = false
}

const fechaRecibo = (alerta) => {
  const fecha = alerta?.fecha_hora || alerta?.salida?.aprobado_en || alerta?.salida?.fecha_hora
  return fecha ? new Date(fecha) : new Date()
}

const fechaParts = (alerta) => {
  const fecha = fechaRecibo(alerta)
  return {
    dia: String(fecha.getDate()).padStart(2, '0'),
    mes: String(fecha.getMonth() + 1).padStart(2, '0'),
    anio: fecha.getFullYear(),
  }
}

const escapeHtml = (valor) => String(valor ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#039;')

const reciboHtml = (alerta) => {
  const salida = alerta?.salida || {}
  const fecha = fechaParts(alerta)
  const detalles = salida.detalles || []
  const filas = detalles.map((detalle) => `
    <tr>
      <td>${escapeHtml(detalle.producto?.nombre || 'Producto')}</td>
      <td>${escapeHtml(detalle.lote?.codigo_lote || '-')}</td>
      <td>${escapeHtml(detalle.cantidad || 0)}</td>
      <td>Aprobado</td>
    </tr>
  `).join('')

  return `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Recibo ${escapeHtml(salida.nro_informe || '')}</title>
  <style>
    @page { size: 216mm 140mm; margin: 10mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: #1f2937; font-size: 11px; }
    .receipt { width: 196mm; min-height: 120mm; border: 2px solid #1f3f99; border-radius: 6px; padding: 7mm; }
    .header { display: flex; justify-content: space-between; gap: 12mm; }
    h1 { margin: 0 0 2mm; color: #1f3f99; font-size: 30px; font-style: italic; font-weight: 900; letter-spacing: 1px; }
    .date { border-collapse: collapse; text-align: center; color: #111827; }
    .date th { background: #1f3f99; color: white; font-size: 9px; padding: 2px 8px; }
    .date td { border: 1px solid #1f3f99; padding: 4px 8px; font-size: 11px; }
    .number { color: #1f3f99; font-weight: 800; margin-top: 2mm; text-align: right; font-size: 12px; }
    .line { margin-top: 3mm; font-size: 13px; }
    .label { font-style: italic; margin-right: 2mm; }
    .value { display: inline-block; min-width: 85mm; border-bottom: 1px dotted #4b5563; padding-left: 2mm; }
    .concept { margin: 3mm 0 2mm; font-size: 13px; font-style: italic; }
    .items { width: 100%; border-collapse: collapse; border: 1px solid #1f3f99; border-radius: 4px; overflow: hidden; }
    .items th { background: #1f3f99; color: white; text-align: left; padding: 4px 8px; font-size: 11px; }
    .items td { border-bottom: 1px solid #d1d5db; padding: 5px 8px; font-size: 11px; }
    .observations { margin-top: 4mm; font-size: 12px; }
    .observations span { display: inline-block; width: 92mm; border-bottom: 1px dotted #4b5563; margin-left: 2mm; }
    .signatures { display: flex; gap: 8mm; margin-top: 13mm; }
    .signature { flex: 1; text-align: center; }
    .signature-line { border-top: 1px solid #111827; padding-top: 2mm; color: #1f3f99; font-weight: 900; letter-spacing: .5px; font-size: 11px; }
    .signature small { display: block; margin-top: 2mm; color: #4b5563; font-size: 9px; }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <div>
        <h1>RECIBO</h1>
        <div>Inal Ltda. Helados Panda</div>
        <div>Carretera a Viacha, Km 8, N° 777 — La Paz</div>
      </div>
      <div>
        <table class="date">
          <tr><th>DIA</th><th>MES</th><th>AÑO</th></tr>
          <tr><td>${fecha.dia}</td><td>${fecha.mes}</td><td>${fecha.anio}</td></tr>
        </table>
        <div class="number">N° ${escapeHtml(salida.nro_informe || '')}</div>
      </div>
    </div>
    <div class="line"><span class="label">Entregué a:</span><span class="value">${escapeHtml(salida.destino?.nombre || 'Destino asignado')}</span></div>
    <div class="line"><span class="label">Distribuidor:</span><span class="value">${escapeHtml(salida.distribuidor?.name || userName.value)}</span></div>
    <div class="concept">Por concepto de entrega de:</div>
    <table class="items">
      <thead><tr><th>Producto</th><th>Lote</th><th>Cantidad</th><th>Estado</th></tr></thead>
      <tbody>${filas}</tbody>
    </table>
    <div class="observations"><em>Observaciones:</em><span>${escapeHtml(salida.observaciones || '')}</span></div>
    <div class="signatures">
      <div class="signature"><div class="signature-line">ENTREGUÉ CONFORME</div><small>${escapeHtml(salida.distribuidor?.name || userName.value)} — Distribuidor</small></div>
      <div class="signature"><div class="signature-line">RECIBÍ CONFORME</div><small>${escapeHtml(salida.destino?.nombre || 'Cliente')} — Cliente</small></div>
    </div>
  </div>
</body>
</html>`
}

const imprimirRecibo = (alerta) => {
  const win = window.open('', '_blank')
  if (!win) return
  win.document.open()
  win.document.write(reciboHtml(alerta))
  win.document.close()
  win.focus()
  win.print()
}

const descargarReciboPDF = async (alerta) => {
  const salidaId = alerta?.salida_id || alerta?.salida?.id_salida
  if (!salidaId) return
  const { data } = await api.get(`/reportes/salidas/${salidaId}/recibo/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(data)
  const link = document.createElement('a')
  link.href = url
  link.download = `${alerta?.salida?.nro_informe || 'recibo'}.pdf`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

const asegurarFacturaAlerta = async (alerta, extra = {}) => {
  const salidaId = alerta?.salida_id || alerta?.salida?.id_salida
  if (!salidaId) return null
  if (alerta.salida?.factura?.id_factura) return alerta.salida.factura
  const { data } = await api.post(`/facturas/desde-salida/${salidaId}`, {
    metodo_pago: 'efectivo',
    estado_pago: 'pendiente',
    ...extra,
  })
  if (alerta.salida) alerta.salida.factura = data
  return data
}

const descargarFacturaTicket = async (alerta) => {
  const factura = await asegurarFacturaAlerta(alerta)
  if (!factura) return
  const { data } = await api.get(`/facturas/${factura.id_factura}/ticket`, { responseType: 'blob' })
  const url = URL.createObjectURL(data)
  const link = document.createElement('a')
  link.href = url
  link.download = `factura_ticket_${factura.numero_factura}.pdf`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

const enviarFacturaCorreo = async (alerta) => {
  const factura = await asegurarFacturaAlerta(alerta)
  if (!factura) return
  const { data } = await api.post(`/facturas/${factura.id_factura}/enviar-correo`, { forzar: true })
  mostrarNotificacion({
    titulo: data.enviado ? 'Factura enviada' : 'No se pudo enviar',
    mensaje: data.message || 'Proceso completado.',
    tipo: data.enviado ? 'success' : 'warning',
  })
  await cargarAlertas()
}

onMounted(() => {
  cargarAlertas()
  alertasTimer = window.setInterval(cargarAlertas, 30000)
})

onUnmounted(() => {
  if (alertasTimer) window.clearInterval(alertasTimer)
})
</script>

<!-- Reemplaza SOLO el <template> y <style scoped>. Tu <script setup> déjalo igual -->

<template>
  <div v-if="route.name !== 'login' && authStore.token" class="fixed inset-0 flex bg-gradient-to-br from-[#627DF2] via-[#9150F7] to-[#E87092] font-sans overflow-hidden">

    <aside class="w-[260px] flex flex-col z-10 py-6 shrink-0">

      <div class="h-16 flex items-center justify-center px-6 mb-4">
        <span class="text-xl font-bold tracking-wider text-center w-full uppercase text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.15)]">{{ userRole }}</span>
      </div>

      <nav class="flex-1 overflow-y-auto scrollbar-hide">
        <ul class="space-y-1">
          <li v-for="item in visibleMenuItems" :key="item.name" class="pl-5 relative">
            <RouterLink
              :to="item.path"
              class="flex items-center px-5 py-3.5 group relative"
              :class="route.path === item.path ? 'active-nav-item' : 'text-white/80 hover:text-white transition-colors duration-200'"
            >
              <svg
                class="w-5 h-5 mr-4 z-20 relative transition-colors duration-200"
                :class="route.path === item.path ? 'text-[#9150F7]' : 'text-white/80 group-hover:text-white'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
              </svg>

              <span class="flex-1 text-sm z-20 relative" :class="route.path === item.path ? 'font-bold text-gray-800' : 'font-medium'">
                {{ item.name }}
              </span>

              <span v-if="item.badge" class="z-20 relative bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full ml-2 shadow-sm">
                {{ item.badge }}
              </span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <div class="px-6 mt-6 flex justify-center">
        <img src="@/assets/panda.png.avif" alt="Panda INAL" class="h-16 w-auto drop-shadow-lg rounded-lg opacity-95 hover:opacity-100 transition-opacity" />
      </div>

    </aside>

    <main class="app-main flex-1 flex flex-col my-6 mr-6 bg-white rounded-[40px] shadow-[0_25px_60px_-15px_rgba(98,125,242,0.4),_0_15px_30px_-10px_rgba(0,0,0,0.05)] border border-white/40 z-0 relative overflow-hidden">

      <header class="h-20 bg-white px-8 flex items-center justify-between border-b border-gray-100 shrink-0 relative z-[1000]">
        <div>
          <h1 class="text-2xl font-bold text-gray-800 capitalize">{{ route.name?.toString().replace(/-/g, ' ') || 'Dashboard' }}</h1>
          <p class="text-sm text-gray-400">Dashboard / {{ route.name }}</p>
        </div>

        <div class="flex items-center space-x-6">

          <div class="relative">
            <button @click="toggleAlertas" class="relative text-gray-400 hover:text-[#9150F7] transition-colors">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
              <span v-if="alertasPendientes" class="absolute -top-1 -right-1 min-w-4 h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold ring-2 ring-white flex items-center justify-center">{{ alertasPendientes }}</span>
            </button>

            <div v-if="showAlertas" class="absolute right-0 top-9 w-96 max-w-[calc(100vw-2rem)] bg-white rounded-2xl shadow-2xl border border-gray-100 z-[1200] overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-bold text-gray-800">{{ esDistribuidor ? 'Recibos designados' : 'Salidas pendientes' }}</p>
                <p class="text-xs text-gray-400">{{ esDistribuidor ? 'Productos aprobados para entregar' : 'Aprueba o rechaza solicitudes de supervisor' }}</p>
              </div>
              <div class="max-h-80 overflow-y-auto">
                <div v-if="cargandoAlertas" class="p-4 text-sm text-gray-400">Cargando...</div>
                <div v-else-if="!alertas.length" class="p-4 text-sm text-gray-400">{{ esDistribuidor ? 'No hay recibos pendientes.' : 'No hay salidas pendientes.' }}</div>
                <div v-for="alerta in alertas" :key="alerta.id_alerta" class="p-4 border-b border-gray-50">
                  <div v-if="!esDistribuidor" class="flex justify-between gap-3">
                    <div>
                      <p class="text-sm font-bold text-gray-800">{{ alerta.salida?.nro_informe || 'Salida pendiente' }}</p>
                      <p class="text-xs text-gray-500 mt-1">{{ alerta.salida?.despachado_por?.name || 'Supervisor' }} solicito despacho a {{ alerta.salida?.destino?.nombre || 'destino' }}</p>
                      <p class="text-xs text-gray-400 mt-1">Distribuidor: {{ alerta.salida?.distribuidor?.name || 'N/A' }}</p>
                    </div>
                  </div>
                  <div v-if="!esDistribuidor" class="flex justify-end gap-2 mt-3">
                    <button @click="rechazarSalida(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100">Rechazar</button>
                    <button @click="aprobarSalida(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700">Aprobar</button>
                  </div>
                  <div v-else class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <p class="text-sm font-bold text-gray-800">{{ alerta.salida?.nro_informe || 'Recibo de salida' }}</p>
                        <p class="text-xs text-gray-500 mt-1">Destino: {{ alerta.salida?.destino?.nombre || 'Destino asignado' }}</p>
                        <p class="text-xs text-gray-400 mt-1">Fecha: {{ fechaParts(alerta).dia }}/{{ fechaParts(alerta).mes }}/{{ fechaParts(alerta).anio }}</p>
                      </div>
                      <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Aprobado</span>
                    </div>

                    <div class="rounded-xl border border-blue-100 overflow-hidden">
                      <table class="w-full text-xs">
                        <thead class="bg-[#1f3f99] text-white">
                          <tr>
                            <th class="text-left px-3 py-2">Producto</th>
                            <th class="text-left px-3 py-2">Lote</th>
                            <th class="text-left px-3 py-2">Cant.</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="detalle in alerta.salida?.detalles || []" :key="detalle.id_detalle_salida" class="border-b border-gray-100 last:border-b-0">
                            <td class="px-3 py-2 text-gray-700">{{ detalle.producto?.nombre || 'Producto' }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ detalle.lote?.codigo_lote || '-' }}</td>
                            <td class="px-3 py-2 font-bold text-gray-800">{{ detalle.cantidad }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <div class="flex justify-end gap-2">
                      <button @click="imprimirRecibo(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1f3f99] bg-blue-50 border border-blue-200 hover:bg-blue-100">Imprimir</button>
                      <button @click="descargarReciboPDF(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-[#1f3f99] hover:bg-[#183276]">Descargar PDF</button>
                      <button @click="descargarFacturaTicket(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-[#2B2353] bg-[#2B2353]/10 border border-[#2B2353]/20 hover:bg-[#2B2353]/15">Factura</button>
                      <button @click="enviarFacturaCorreo(alerta)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100">Enviar</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-3 border-l border-gray-200 pl-6">
            <div class="text-right">
              <p class="text-sm font-bold text-gray-700">{{ userName }}</p>
              <p class="text-xs text-gray-400">{{ userRole }}</p>
            </div>

            <div
              class="relative group cursor-pointer w-10 h-10 rounded-full overflow-hidden border-2 border-gray-200 shadow-sm transition-colors hover:border-[#9150F7]"
              @click="triggerFileUpload"
              title="Cambiar foto de perfil"
            >
              <img v-if="profileImageUrl" :src="profileImageUrl" alt="Perfil" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full bg-indigo-50 flex items-center justify-center text-[#9150F7] font-bold text-sm">
                {{ userInitials }}
              </div>

              <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
            </div>

            <input
              type="file"
              ref="fileInput"
              class="hidden"
              accept="image/*"
              capture="environment"
              @change="handleImageUpload"
            />

            <button @click="handleLogout" class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Cerrar Sesión">
               <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            </button>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-auto p-6 md:p-8 bg-[#FAFBFF]">
        <RouterView />
      </div>

    </main>
  </div>

  <RouterView v-else />

  <Teleport to="body">
    <Transition name="fade">
      <div v-if="notificacionModal.visible" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-950/45 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl border border-white/70">
          <div class="px-6 pt-6 pb-4 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full"
              :class="notificacionModal.tipo === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
              <svg v-if="notificacionModal.tipo === 'success'" class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-extrabold text-gray-900">{{ notificacionModal.titulo }}</h3>
            <p class="mt-2 text-sm leading-6 text-gray-500">{{ notificacionModal.mensaje }}</p>
          </div>
          <div class="bg-gray-50 px-6 py-4 flex justify-center">
            <button @click="cerrarNotificacion" class="rounded-xl bg-[#2B2353] px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#2B2353]/20 hover:bg-[#3d3275] transition">
              Aceptar
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Sincronización del color base de la página con el nuevo tono inicial */
:global(body), :global(html) {
  background-color: #627DF2;
  margin: 0;
  overscroll-behavior-y: none;
}

/* =========================================
   SISTEMA DE CURVAS SIN BUGS DE TRANSICIÓN
   ========================================= */
.active-nav-item {
  background-color: #ffffff;
  border-radius: 30px 0 0 30px;
  position: relative;
  z-index: 10;
  box-shadow: -8px 4px 15px rgba(0, 0, 0, 0.03);
  /* Forzamos a que no haya transición de fondo para evitar el glitch visual */
  transition: none !important;
}

.active-nav-item::before,
.active-nav-item::after {
  content: '';
  position: absolute;
  right: 0;
  width: 30px;
  height: 30px;
  background-color: transparent;
  pointer-events: none;
  /* Z-index 0 asegura que nunca cubra el texto de los otros botones */
  z-index: 0;
}

.active-nav-item::before {
  top: -30px;
  background: radial-gradient(circle at 0 0, transparent 30px, #ffffff 30.5px);
}

.active-nav-item::after {
  bottom: -30px;
  background: radial-gradient(circle at 0 100%, transparent 30px, #ffffff 30.5px);
}
</style>
