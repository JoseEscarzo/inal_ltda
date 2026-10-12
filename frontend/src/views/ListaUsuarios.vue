<script setup>
import { defineAsyncComponent, ref, onMounted, computed } from 'vue'
import { userService } from '@/services/userService'
import { laravelErrorMessage } from '@/utils/apiErrors'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const FaceCaptureModal = defineAsyncComponent(() => import('@/components/FaceCaptureModal.vue'))

// ── Auth ───────────────────────────────────────────────────────────────
const authStore = useAuthStore()
const router = useRouter()

// ── Estado principal ───────────────────────────────────────────────────
const usuarios = ref([])
const usuariosInactivos = ref([])
const roles = ref([])
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const vistaInactivos = ref(false)
const fieldErrors = ref({})

// ── Buscador ───────────────────────────────────────────────────────────
const busqueda = ref('')

const usuariosFiltrados = computed(() => {
  const lista = vistaInactivos.value ? usuariosInactivos.value : usuarios.value
  if (!busqueda.value.trim()) return lista
  const q = busqueda.value.toLowerCase()
  return lista.filter(u =>
    u.name?.toLowerCase().includes(q) ||
    u.nro_carnet?.toLowerCase().includes(q)
  )
})

const modalOpen = ref(false)
const editingId = ref(null)
const fileInput = ref(null)

// ── Modal de confirmación ──────────────────────────────────────────────
const confirmModal = ref({
  open: false,
  title: '',
  message: '',
  detail: '',
  type: 'warning',
  confirmLabel: 'Confirmar',
  resolve: null
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

// ── Formulario ─────────────────────────────────────────────────────────
const hoy = computed(() => new Date().toISOString().split('T')[0])
const fechaMayorEdad = computed(() => {
  const fecha = new Date()
  fecha.setFullYear(fecha.getFullYear() - 18)
  return fecha.toISOString().split('T')[0]
})
const form = ref({
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  email: '',
  phone: '',
  fecha_nacimiento: '',
  nro_carnet: '',
  password: '',
  password_confirmation: '',
  id_rol: ''
})

// ── Biometría ──────────────────────────────────────────────────────────
const faceModalOpen = ref(false)
const facePhotoBase64 = ref('')
const faceDescriptor = ref(null)
const facePreviewUrl = ref('')
const faceHint = ref('')
const modelsLoading = ref(false)
const modelsReady = ref(false)
const modelsPreloadFailed = ref(false)

let loadFaceModels = null

const resumenUsuarioParaModal = computed(() => {
  return [form.value.nombres, form.value.email, form.value.phone].filter(Boolean).join(' · ')
})

function onFaceCaptured({ dataUrl, descriptor }) {
  facePhotoBase64.value = dataUrl
  faceDescriptor.value = descriptor
  facePreviewUrl.value = dataUrl
  faceModalOpen.value = false
  faceHint.value = '¡Biometría registrada por cámara!'
}

function onPhotoUploaded(event) {
  const file = event.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (e) => {
    facePhotoBase64.value = e.target.result
    facePreviewUrl.value = e.target.result
    faceDescriptor.value = null
    faceHint.value = '¡Foto cargada (solo visual)!'
    if (fileInput.value) fileInput.value.value = ''
  }
  reader.readAsDataURL(file)
}

function resetBiometrics() {
  facePhotoBase64.value = ''
  faceDescriptor.value = null
  facePreviewUrl.value = ''
  faceHint.value = ''
}

function soloDigitos(campo, max = 8) {
  form.value[campo] = String(form.value[campo] || '').replace(/\D/g, '').slice(0, max)
}

function validarFormulario() {
  const errores = {}
  const textoRegex = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  if (!form.value.nombres.trim()) errores.nombres = 'El nombre es obligatorio.'
  else if (!textoRegex.test(form.value.nombres.trim())) errores.nombres = 'El nombre solo puede contener letras y espacios.'

  if (!form.value.apellido_paterno.trim() && !form.value.apellido_materno.trim()) {
    errores.apellido_paterno = 'Debe ingresar al menos un apellido.'
  }
  if (form.value.apellido_paterno.trim() && !textoRegex.test(form.value.apellido_paterno.trim())) {
    errores.apellido_paterno = 'El apellido paterno solo puede contener letras.'
  }
  if (form.value.apellido_materno.trim() && !textoRegex.test(form.value.apellido_materno.trim())) {
    errores.apellido_materno = 'El apellido materno solo puede contener letras.'
  }

  if (!form.value.email.trim()) errores.email = 'El correo es obligatorio.'
  else if (!emailRegex.test(form.value.email.trim())) errores.email = 'Ingrese un correo valido, por ejemplo usuario@correo.com.'

  if (!/^\d{8}$/.test(form.value.phone || '')) errores.phone = 'El celular debe tener exactamente 8 numeros.'
  if (!form.value.nro_carnet) errores.nro_carnet = 'El Nro de carnet es obligatorio.'
  else if (!/^\d{8}$/.test(form.value.nro_carnet || '')) errores.nro_carnet = 'El Nro de carnet debe tener exactamente 8 numeros.'

  if (!form.value.fecha_nacimiento) errores.fecha_nacimiento = 'La fecha de nacimiento es obligatoria.'
  else if (form.value.fecha_nacimiento > fechaMayorEdad.value) {
    errores.fecha_nacimiento = 'El usuario debe ser mayor de 18 anos.'
  }

  if (!editingId.value && !form.value.password) errores.password = 'La contrasena es obligatoria.'
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    errores.password_confirmation = 'La confirmacion de contrasena no coincide.'
  }
  if (!form.value.id_rol) errores.id_rol = 'Debe seleccionar un rol.'
  if (!editingId.value && !facePhotoBase64.value) errores.face = 'La foto es obligatoria.'

  fieldErrors.value = errores
  errorMsg.value = Object.values(errores)[0] || ''
  return Object.keys(errores).length === 0
}

async function prepararCamara() {
  if (modelsReady.value) return true

  modelsLoading.value = true
  modelsPreloadFailed.value = false
  try {
    if (!loadFaceModels) {
      const { useFaceRegistration } = await import('@/composables/useFaceRegistration')
      loadFaceModels = useFaceRegistration().loadModels
    }
    await loadFaceModels()
    modelsReady.value = true
    return true
  } catch (err) {
    modelsPreloadFailed.value = true
    return false
  } finally {
    modelsLoading.value = false
  }
}

async function abrirCamara() {
  if (loading.value) return
  if (await prepararCamara()) {
    faceModalOpen.value = true
  }
}

// ── Carga de datos ─────────────────────────────────────────────────────
async function cargar() {
  loading.value = true
  errorMsg.value = ''
  try {
    usuarios.value = await userService.list()
  } catch (err) {
    const backendErrors = err?.response?.data?.errors
    if (backendErrors && typeof backendErrors === 'object') {
      fieldErrors.value = {
        ...fieldErrors.value,
        ...Object.fromEntries(
          Object.entries(backendErrors).map(([campo, mensajes]) => [campo, Array.isArray(mensajes) ? mensajes[0] : mensajes])
        ),
      }
      if (backendErrors.nro_carnet) fieldErrors.value.nro_carnet = 'Este C.I. ya existe en el sistema.'
      if (backendErrors.fecha_nacimiento) fieldErrors.value.fecha_nacimiento = Array.isArray(backendErrors.fecha_nacimiento)
        ? backendErrors.fecha_nacimiento[0]
        : backendErrors.fecha_nacimiento
    }
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

async function cargarInactivos() {
  loading.value = true
  errorMsg.value = ''
  try {
    usuariosInactivos.value = await userService.listInactivos()
  } catch (err) {
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

async function cargarRoles() {
  try {
    roles.value = await userService.getRoles()
  } catch (err) {
    console.error('Error cargando roles:', err)
  }
}

async function toggleVista() {
  vistaInactivos.value = !vistaInactivos.value
  busqueda.value = ''
  if (vistaInactivos.value) {
    await cargarInactivos()
  }
}

// ── Modal crear / editar ───────────────────────────────────────────────
function abrirCrear() {
  editingId.value = null
  form.value = {
    nombres: '', apellido_paterno: '', apellido_materno: '',
    email: '', phone: '',
    fecha_nacimiento: '', nro_carnet: '',
    password: '', password_confirmation: '', id_rol: ''
  }
  resetBiometrics()
  modalOpen.value = true
  errorMsg.value = ''
  fieldErrors.value = {}
}

async function abrirEditar(u) {
  editingId.value = u.id
  const detalle = await userService.get(u.id).catch(() => u)
  const partes = u.name ? u.name.split(' ') : []
  form.value = {
    nombres:           partes[0] || '',
    apellido_paterno:  partes[1] || '',
    apellido_materno:  partes.slice(2).join(' ') || '',
    email:             u.email,
    phone:             u.phone || '',
    fecha_nacimiento:  u.fecha_nacimiento ? u.fecha_nacimiento.substring(0, 10) : '',
    nro_carnet:        u.nro_carnet || '',
    password: '', password_confirmation: '',
    id_rol:            u.id_rol || ''
  }
  resetBiometrics()
  if (detalle.face_photo_base64) facePreviewUrl.value = detalle.face_photo_base64
  modalOpen.value = true
  errorMsg.value = ''
  fieldErrors.value = {}
}

function cerrarModal() { modalOpen.value = false }

// ── Guardar ────────────────────────────────────────────────────────────
async function guardar() {
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    if (!validarFormulario()) return
    if (!form.value.nombres) {
      errorMsg.value = 'El nombre es obligatorio.'
      return
    }
    if (!form.value.apellido_paterno && !form.value.apellido_materno) {
      errorMsg.value = 'Debe ingresar al menos un apellido.'
      return
    }
    if (!editingId.value) {
      if (!form.value.password) { errorMsg.value = 'La contraseña es obligatoria.'; return }
      if (!facePhotoBase64.value) { errorMsg.value = 'La foto es obligatoria.'; return }
    }
    if (!form.value.id_rol) { errorMsg.value = 'Debe seleccionar un rol.'; return }

    const fullName = `${form.value.nombres} ${form.value.apellido_paterno} ${form.value.apellido_materno}`
      .replace(/\s+/g, ' ').trim()

    const payload = {
      name:             fullName,
      email:            form.value.email,
      phone:            form.value.phone || null,
      id_rol:           form.value.id_rol,
      fecha_nacimiento: form.value.fecha_nacimiento || null,
      nro_carnet:       form.value.nro_carnet || null,
    }

    if (facePhotoBase64.value) {
      payload.face_photo_base64 = facePhotoBase64.value
      payload.face_descriptor = faceDescriptor.value || null
    }

    if (editingId.value) {
      if (form.value.password) {
        payload.password = form.value.password
        payload.password_confirmation = form.value.password_confirmation
      }
      await userService.update(editingId.value, payload)
      successMsg.value = 'Usuario actualizado.'
    } else {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
      await userService.create(payload)
      successMsg.value = 'Usuario creado.'
    }
    cerrarModal()
    await cargar()
  } catch (err) {
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

// ── Desactivar (soft-delete) ───────────────────────────────────────────
async function eliminar(u) {
  const esMiCuenta = u.id === authStore.user?.id

  const ok = await openConfirm({
    title: 'Desactivar usuario',
    message: esMiCuenta
      ? '⚠️ Estás a punto de desactivar tu propia cuenta.'
      : `¿Desactivar a ${u.name}?`,
    detail: esMiCuenta
      ? 'Se cerrará tu sesión inmediatamente y necesitarás que otro administrador reactive tu cuenta para volver a ingresar.'
      : 'El usuario pasará a la sección de Inactivos y podrá ser reactivado en cualquier momento.',
    type: 'warning',
    confirmLabel: esMiCuenta ? 'Sí, desactivar y cerrar sesión' : 'Sí, desactivar'
  })
  if (!ok) return

  loading.value = true
  try {
    const { data } = await userService.remove(u.id)
    successMsg.value = 'Usuario desactivado.'

    // Si era su propia cuenta → cerrar sesión
    if (data?.self) {
      authStore.logout()
      await router.replace('/login')
      return
    }

    await cargar()
  } catch (err) {
    // Aquí llega el 403 si es el único administrador
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

// ── Activar ────────────────────────────────────────────────────────────
async function activar(u) {
  const ok = await openConfirm({
    title: 'Reactivar usuario',
    message: `¿Reactivar a ${u.name}?`,
    detail: 'El usuario volverá a aparecer en la lista de usuarios activos.',
    type: 'warning',
    confirmLabel: 'Sí, reactivar'
  })
  if (!ok) return
  loading.value = true
  try {
    await userService.activar(u.id)
    successMsg.value = 'Usuario reactivado.'
    await Promise.all([cargarInactivos(), cargar()])
  } catch (err) {
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

// ── Eliminar permanente ────────────────────────────────────────────────
async function eliminarDefinitivo(u) {
  const ok = await openConfirm({
    title: 'Eliminar permanentemente',
    message: `¿Eliminar a ${u.name} para siempre?`,
    detail: '⚠️ Esta acción es irreversible. El usuario será borrado definitivamente de la base de datos.',
    type: 'danger',
    confirmLabel: 'Sí, eliminar para siempre'
  })
  if (!ok) return
  loading.value = true
  try {
    await userService.eliminarDefinitivo(u.id)
    successMsg.value = 'Usuario eliminado permanentemente.'
    await cargarInactivos()
  } catch (err) {
    errorMsg.value = laravelErrorMessage(err)
  } finally {
    loading.value = false
  }
}

const paramsExportUsuarios = computed(() => ({
  buscar: busqueda.value || null,
  activo: !vistaInactivos.value,
}))

async function exportarUsuariosPDF() {
  const { data } = await userService.exportPdf(paramsExportUsuarios.value)
  descargarPdfBlob(data, 'reporte_usuarios.pdf')
}

async function imprimirUsuariosReporte() {
  const { data } = await userService.exportPdf(paramsExportUsuarios.value)
  imprimirPdfBlob(data, 'reporte_usuarios')
}

async function exportarUsuariosExcel() {
  const { data } = await userService.exportExcel(paramsExportUsuarios.value)
  descargarBlob(data, 'reporte_usuarios.xlsx')
}

// ── Utilidades ─────────────────────────────────────────────────────────
function formatFecha(fecha) {
  if (!fecha) return '—'
  const d = new Date(fecha)
  return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => {
  cargar()
  cargarRoles()
})
</script>

<template>
  <div class="page-root">
    <div class="page-container">

      <!-- Header -->
      <div class="page-header">
        <div class="header-text">
          <h1 class="page-title">{{ vistaInactivos ? 'Usuarios Inactivos' : 'Usuarios' }}</h1>
          <p class="page-subtitle">{{ vistaInactivos ? 'Usuarios desactivados del sistema' : 'Administración de cuentas del sistema' }}</p>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-toggle" @click="toggleVista">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            {{ vistaInactivos ? 'Ver Activos' : 'Ver Inactivos' }}
          </button>
          <ReportExportMenu
            @excel="exportarUsuariosExcel"
            @pdf="exportarUsuariosPDF"
            @print="imprimirUsuariosReporte"
          />
          <button v-if="!vistaInactivos" type="button" class="btn-primary" @click="abrirCrear">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo usuario
          </button>
        </div>
      </div>

      <!-- Alertas -->
      <transition name="fade">
        <div v-if="successMsg" class="alert alert-success">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ successMsg }}
        </div>
      </transition>
      <transition name="fade">
        <div v-if="errorMsg && !modalOpen" class="alert alert-error">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
          {{ errorMsg }}
        </div>
      </transition>

      <!-- ── Buscador ──────────────────────────────────────────────── -->
      <div class="search-bar">
        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar por nombre o carnet..."
          class="search-input"
        />
        <span class="search-count">{{ usuariosFiltrados.length }} resultado(s)</span>
      </div>

      <!-- ── TABLA ACTIVOS ─────────────────────────────────────────── -->
      <div v-if="!vistaInactivos" class="card">
        <div v-if="loading && !usuarios.length" class="table-loading">
          <div class="spinner"></div>
          <span>Cargando usuarios…</span>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Carnet</th>
              <th>Correo</th>
              <th>Celular</th>
              <th>Nacimiento</th>
              <th>Rol</th>
              <th>Verificado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuariosFiltrados" :key="u.id" class="table-row">
              <td class="td-id">{{ u.id }}</td>
              <td class="td-name">
                <div class="user-avatar-cell">
                  <div class="avatar-circle">{{ u.name?.charAt(0)?.toUpperCase() }}</div>
                  {{ u.name }}
                </div>
              </td>
              <td class="td-carnet">{{ u.nro_carnet || '—' }}</td>
              <td class="td-email">{{ u.email }}</td>
              <td class="td-phone">{{ u.phone || '—' }}</td>
              <td class="td-fecha">{{ formatFecha(u.fecha_nacimiento) }}</td>
              <td><span class="role-badge">{{ u.rol ? u.rol.nombre : 'Sin Rol' }}</span></td>
              <td>
                <span :class="u.email_verified_at ? 'badge-verified' : 'badge-pending'">
                  <span class="badge-dot"></span>
                  {{ u.email_verified_at ? 'Verificado' : 'Pendiente' }}
                </span>
              </td>
              <td class="text-right">
                <div class="action-buttons">
                  <button type="button" class="btn-action btn-edit" @click="abrirEditar(u)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                  </button>
                  <button type="button" class="btn-action btn-delete" @click="eliminar(u)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Desactivar
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && usuariosFiltrados.length === 0">
              <td colspan="9" class="table-empty-row">No se encontraron usuarios</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && !usuarios.length" class="table-empty">
          <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <p>No hay usuarios activos</p>
        </div>
      </div>

      <!-- ── TABLA INACTIVOS ───────────────────────────────────────── -->
      <div v-else class="card">
        <div class="inactive-banner">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Estos usuarios fueron desactivados. Puedes reactivarlos o eliminarlos permanentemente.
        </div>
        <div v-if="loading && !usuariosInactivos.length" class="table-loading">
          <div class="spinner"></div>
          <span>Cargando inactivos…</span>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Carnet</th>
              <th>Correo</th>
              <th>Celular</th>
              <th>Nacimiento</th>
              <th>Rol</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuariosFiltrados" :key="u.id" class="table-row table-row-inactive">
              <td class="td-id">{{ u.id }}</td>
              <td class="td-name">
                <div class="user-avatar-cell">
                  <div class="avatar-circle avatar-inactive">{{ u.name?.charAt(0)?.toUpperCase() }}</div>
                  <span class="text-slate-400">{{ u.name }}</span>
                </div>
              </td>
              <td class="td-carnet text-slate-400">{{ u.nro_carnet || '—' }}</td>
              <td class="td-email text-slate-400">{{ u.email }}</td>
              <td class="td-phone">{{ u.phone || '—' }}</td>
              <td class="td-fecha">{{ formatFecha(u.fecha_nacimiento) }}</td>
              <td><span class="role-badge role-badge-inactive">{{ u.rol ? u.rol.nombre : 'Sin Rol' }}</span></td>
              <td class="text-right">
                <div class="action-buttons">
                  <button type="button" class="btn-action btn-activate" @click="activar(u)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Activar
                  </button>
                  <button type="button" class="btn-action btn-delete-forever" @click="eliminarDefinitivo(u)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Eliminar para siempre
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && usuariosFiltrados.length === 0">
              <td colspan="8" class="table-empty-row">No se encontraron usuarios</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && !usuariosInactivos.length" class="table-empty">
          <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p>No hay usuarios inactivos</p>
        </div>
      </div>

    </div>

    <!-- ── MODAL FORMULARIO ───────────────────────────────────────────── -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="modalOpen" class="modal-overlay" @click.self="cerrarModal">
          <div class="modal-box">

            <div class="modal-header">
              <div>
                <h2 class="modal-title">{{ editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                <p class="modal-subtitle">{{ editingId ? 'Actualiza la información de la cuenta' : 'Completa los datos para crear una cuenta' }}</p>
              </div>
              <button @click="cerrarModal" class="modal-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <div v-if="errorMsg && modalOpen" class="alert alert-error mx-6 mb-0 mt-0">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              {{ errorMsg }}
            </div>

            <div class="modal-body">

              <!-- Foto biométrica -->
              <div class="biometric-col">
                <p class="biometric-label">Foto de identificación</p>
                <div class="photo-frame group">
                  <img v-if="facePreviewUrl" :src="facePreviewUrl" class="photo-img" alt="Foto usuario" />
                  <div v-else class="photo-placeholder">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Sin foto</span>
                  </div>
                  <div class="photo-overlay">
                    <p class="text-white text-xs font-semibold mb-2">Cambiar foto</p>
                    <button type="button" :disabled="loading || modelsLoading" @click.stop="abrirCamara" class="photo-btn">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      Usar Cámara
                    </button>
                    <button type="button" :disabled="loading" @click.stop="fileInput.click()" class="photo-btn">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                      </svg>
                      Subir Archivo
                    </button>
                  </div>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="onPhotoUploaded" />
                <div class="biometric-status">
                  <span v-if="modelsLoading" class="status-badge status-loading">
                    <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Cargando IA...
                  </span>
                  <span v-else-if="modelsReady" class="status-badge status-ready"><span>✦</span> Cámara lista</span>
                  <span v-else-if="modelsPreloadFailed" class="status-badge status-error">Error de cámara</span>
                  <p v-if="faceHint" class="hint-success">{{ faceHint }}</p>
                  <p v-if="!editingId && !facePreviewUrl" class="hint-required">* Foto requerida</p>
                </div>
              </div>

              <!-- Formulario -->
              <div class="form-col">
                <div class="form-section-title">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  Datos personales
                </div>

                <div class="form-group">
                  <label class="form-label">Nombres</label>
                  <input v-model="form.nombres" type="text" placeholder="Ej. Juan Carlos" class="form-input" />
                  <p v-if="fieldErrors.nombres" class="field-error">{{ fieldErrors.nombres }}</p>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Ap. Paterno</label>
                    <input v-model="form.apellido_paterno" type="text" placeholder="Ej. Pérez" class="form-input" />
                    <p v-if="fieldErrors.apellido_paterno" class="field-error">{{ fieldErrors.apellido_paterno }}</p>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Ap. Materno <span class="label-optional"></span></label>
                    <input v-model="form.apellido_materno" type="text" placeholder="Ej. Gómez" class="form-input" />
                    <p v-if="fieldErrors.apellido_materno" class="field-error">{{ fieldErrors.apellido_materno }}</p>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Fecha de Nacimiento</label>
                    <input v-model="form.fecha_nacimiento" type="date" :max="fechaMayorEdad" class="form-input" />
                    <p v-if="fieldErrors.fecha_nacimiento" class="field-error">{{ fieldErrors.fecha_nacimiento }}</p>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Cédula de Identidad (C.I.)<span class="label-optional"></span></label>
                    <input v-model="form.nro_carnet" type="text" maxlength="8" inputmode="numeric" placeholder="Ej. 12345678" class="form-input" @input="soloDigitos('nro_carnet')" />
                    <p v-if="fieldErrors.nro_carnet" class="field-error">{{ fieldErrors.nro_carnet }}</p>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input v-model="form.email" type="email" placeholder="tu@correo.com" class="form-input" />
                    <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</p>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Celular</label>
                    <input v-model="form.phone" type="tel" maxlength="8" inputmode="numeric" placeholder="Ej. 70012345" class="form-input" @input="soloDigitos('phone')" />
                    <p v-if="fieldErrors.phone" class="field-error">{{ fieldErrors.phone }}</p>
                  </div>
                </div>

                <div class="form-divider"></div>

                <div class="form-section-title">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                  Seguridad y acceso
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">{{ editingId ? 'Nueva contraseña' : 'Contraseña' }}</label>
                    <input v-model="form.password" type="password" maxlength="8" class="form-input" />
                  </div>
                  <div class="form-group" v-if="form.password || !editingId">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input v-model="form.password_confirmation" type="password" maxlength="8" class="form-input" />
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Rol del usuario</label>
                  <div class="select-wrapper">
                    <select v-model="form.id_rol" class="form-select">
                      <option value="" disabled>Seleccione un rol...</option>
                      <option v-for="rol in roles" :key="rol.id_rol" :value="rol.id_rol">{{ rol.nombre }}</option>
                    </select>
                    <svg class="select-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="cerrarModal">Cancelar</button>
              <button type="button" :disabled="loading" class="btn-primary" @click="guardar">
                <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                {{ loading ? 'Guardando...' : (editingId ? 'Actualizar usuario' : 'Guardar usuario') }}
              </button>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ── MODAL CONFIRMACIÓN ─────────────────────────────────────────── -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="confirmModal.open" class="modal-overlay confirm-overlay" @click.self="handleConfirm(false)">
          <div class="confirm-box">

            <div :class="['confirm-icon-wrap', confirmModal.type === 'danger' ? 'icon-danger' : 'icon-warning']">
              <svg v-if="confirmModal.type === 'danger'" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
            </div>

            <div class="confirm-content">
              <h3 class="confirm-title">{{ confirmModal.title }}</h3>
              <p class="confirm-message">{{ confirmModal.message }}</p>
              <p v-if="confirmModal.detail" class="confirm-detail">{{ confirmModal.detail }}</p>
            </div>

            <div class="confirm-actions">
              <button type="button" class="btn-secondary confirm-cancel" @click="handleConfirm(false)">
                Cancelar
              </button>
              <button
                type="button"
                :class="['confirm-ok', confirmModal.type === 'danger' ? 'confirm-ok-danger' : 'confirm-ok-warning']"
                @click="handleConfirm(true)"
              >
                {{ confirmModal.confirmLabel }}
              </button>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

    <FaceCaptureModal
      v-if="faceModalOpen"
      class="z-50"
      :show="faceModalOpen"
      :user-resumen="resumenUsuarioParaModal"
      @close="faceModalOpen = false"
      @captured="onFaceCaptured"
    />
  </div>
</template>

<style scoped>
.page-root {
  min-height: 100vh;
  background-color: #f8fafc;
  padding: 2rem 1.5rem;
  font-family: 'DM Sans', 'Nunito', system-ui, sans-serif;
  color: #1e293b;
}
.page-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }

.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.page-title { font-size: 1.875rem; font-weight: 800; color: #0f172a; letter-spacing: -0.03em; margin: 0 0 0.2rem; }
.page-subtitle { font-size: 0.875rem; color: #64748b; margin: 0; }
.header-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: #10b981; color: #fff; font-weight: 700; font-size: 0.875rem;
  padding: 0.7rem 1.4rem; border-radius: 0.75rem; border: none; cursor: pointer;
  box-shadow: 0 4px 14px rgba(16,185,129,0.3);
  transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
}
.btn-primary:hover { background: #059669; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-secondary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: #fff; color: #475569; font-weight: 600; font-size: 0.875rem;
  padding: 0.7rem 1.4rem; border-radius: 0.75rem; border: 1.5px solid #e2e8f0; cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; }

.btn-toggle {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.875rem;
  padding: 0.7rem 1.4rem; border-radius: 0.75rem; border: 1.5px solid #e2e8f0; cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.btn-toggle:hover { background: #e2e8f0; border-color: #cbd5e1; }

.alert { display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1.1rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; }
.alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* Buscador */
.search-bar {
  display: flex; align-items: center; gap: 0.75rem;
  background: #fff; border: 1.5px solid #e2e8f0;
  border-radius: 0.85rem; padding: 0.6rem 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.search-icon { width: 1.1rem; height: 1.1rem; color: #94a3b8; flex-shrink: 0; }
.search-input {
  flex: 1; border: none; outline: none;
  font-size: 0.875rem; font-family: inherit;
  color: #1e293b; background: transparent;
}
.search-input::placeholder { color: #cbd5e1; }
.search-count { font-size: 0.75rem; font-weight: 600; color: #94a3b8; white-space: nowrap; }

.card { background: #fff; border-radius: 1.25rem; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04); }

.inactive-banner {
  display: flex; align-items: center; gap: 0.5rem;
  background: #fffbeb; border-bottom: 1px solid #fde68a;
  color: #92400e; font-size: 0.8rem; font-weight: 500;
  padding: 0.7rem 1.1rem;
}

.table-loading { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 3rem; color: #94a3b8; font-size: 0.9rem; }
.spinner { width: 20px; height: 20px; border: 2.5px solid #e2e8f0; border-top-color: #10b981; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.table-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3.5rem; color: #94a3b8; font-size: 0.9rem; }
.table-empty-row { text-align: center; padding: 2rem; color: #94a3b8; font-size: 0.875rem; }

.data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
.data-table thead tr { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
.data-table thead th { padding: 0.85rem 1.1rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
.table-row { border-bottom: 1px solid #f1f5f9; transition: background 0.12s; }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: #fafbff; }
.table-row-inactive { opacity: 0.75; }
.data-table td { padding: 0.9rem 1.1rem; vertical-align: middle; }

.td-id { color: #cbd5e1; font-size: 0.8rem; font-weight: 600; }
.td-name { font-weight: 600; color: #1e293b; }
.td-email { color: #475569; }
.td-phone { color: #94a3b8; }
.td-carnet { color: #64748b; font-size: 0.82rem; }
.td-fecha { color: #64748b; font-size: 0.82rem; }
.text-right { text-align: right; }
.text-slate-400 { color: #94a3b8; }

.user-avatar-cell { display: flex; align-items: center; gap: 0.65rem; }
.avatar-circle { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; font-size: 0.8rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.avatar-inactive { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #94a3b8; }

.role-badge { display: inline-block; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; border-radius: 99px; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.65rem; }
.role-badge-inactive { background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; }

.badge-verified, .badge-pending { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 600; padding: 0.22rem 0.65rem; border-radius: 99px; }
.badge-verified { background: #f0fdf4; color: #15803d; }
.badge-pending { background: #fffbeb; color: #92400e; }
.badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.action-buttons { display: flex; align-items: center; justify-content: flex-end; gap: 0.4rem; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 0.5rem; border: 1.5px solid transparent; cursor: pointer; transition: all 0.12s; }

.btn-edit { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.btn-edit:hover { background: #dcfce7; border-color: #86efac; }

.btn-delete { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
.btn-delete:hover { background: #ffedd5; border-color: #fdba74; }

.btn-activate { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.btn-activate:hover { background: #dbeafe; border-color: #93c5fd; }

.btn-delete-forever { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.btn-delete-forever:hover { background: #fee2e2; border-color: #fca5a5; }

/* Modal formulario */
.modal-overlay { position: fixed; inset: 0; z-index: 40; display: flex; align-items: center; justify-content: center; padding: 1.25rem; background: rgba(15,23,42,0.45); backdrop-filter: blur(6px); overflow-y: auto; }
.modal-box { position: relative; width: 100%; max-width: 880px; background: #fff; border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 25px 60px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; margin-top: auto; margin-bottom: auto; }
.modal-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 1.75rem 2rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.modal-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; margin: 0 0 0.2rem; }
.modal-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0; }
.modal-close { padding: 0.4rem; background: #f1f5f9; border: none; border-radius: 0.5rem; cursor: pointer; color: #64748b; transition: background 0.12s; flex-shrink: 0; }
.modal-close:hover { background: #e2e8f0; color: #1e293b; }
.modal-body { display: flex; flex-direction: column; gap: 2rem; padding: 1.75rem 2rem; }
@media (min-width: 720px) { .modal-body { flex-direction: row; } }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1.25rem 2rem 1.75rem; border-top: 1px solid #f1f5f9; }

/* Modal confirmación */
.confirm-overlay { z-index: 50; }
.confirm-box {
  background: #fff; border-radius: 1.5rem; border: 1px solid #e2e8f0;
  box-shadow: 0 30px 80px rgba(0,0,0,0.18); width: 100%; max-width: 440px;
  padding: 2rem; display: flex; flex-direction: column; align-items: center; gap: 1.25rem; text-align: center;
}
.confirm-icon-wrap { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.icon-warning { background: #fff7ed; color: #c2410c; }
.icon-danger  { background: #fef2f2; color: #b91c1c; }
.confirm-content { display: flex; flex-direction: column; gap: 0.4rem; }
.confirm-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.confirm-message { font-size: 0.9rem; font-weight: 600; color: #334155; margin: 0; }
.confirm-detail { font-size: 0.8rem; color: #64748b; margin: 0; line-height: 1.5; }
.confirm-actions { display: flex; gap: 0.75rem; width: 100%; }
.confirm-cancel { flex: 1; justify-content: center; padding: 0.75rem 1rem; }
.confirm-ok { flex: 1; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem; padding: 0.75rem 1rem; border-radius: 0.75rem; border: none; cursor: pointer; transition: all 0.15s; }
.confirm-ok-warning { background: #f97316; color: #fff; box-shadow: 0 4px 14px rgba(249,115,22,0.3); }
.confirm-ok-warning:hover { background: #ea580c; }
.confirm-ok-danger { background: #ef4444; color: #fff; box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
.confirm-ok-danger:hover { background: #dc2626; }

/* Biometric col */
.biometric-col { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 100%; }
@media (min-width: 720px) { .biometric-col { width: 200px; } }
.biometric-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin: 0 0 0.75rem; align-self: flex-start; }
.photo-frame { position: relative; width: 180px; height: 180px; border-radius: 1.25rem; background: #f8fafc; border: 2px dashed #e2e8f0; overflow: hidden; cursor: pointer; transition: border-color 0.15s; }
.photo-frame:hover { border-color: #10b981; }
.photo-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.photo-placeholder { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; color: #94a3b8; font-size: 0.8rem; }
.photo-overlay { position: absolute; inset: 0; background: rgba(15,23,42,0.75); backdrop-filter: blur(4px); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; opacity: 0; transition: opacity 0.2s; }
.photo-frame:hover .photo-overlay { opacity: 1; }
.photo-btn { display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(255,255,255,0.12); color: #fff; font-size: 0.72rem; font-weight: 600; padding: 0.45rem 0.9rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.2); cursor: pointer; width: 80%; justify-content: center; transition: background 0.12s; }
.photo-btn:hover:not(:disabled) { background: #10b981; border-color: #10b981; }
.photo-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.biometric-status { margin-top: 0.85rem; width: 100%; display: flex; flex-direction: column; align-items: center; gap: 0.35rem; }
.status-badge { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 600; padding: 0.3rem 0.75rem; border-radius: 99px; }
.status-loading { background: #fffbeb; color: #92400e; }
.status-ready { background: #f0fdf4; color: #15803d; }
.status-error { background: #fef2f2; color: #b91c1c; }
.hint-success { font-size: 0.72rem; font-weight: 600; color: #10b981; text-align: center; margin: 0; }
.hint-required { font-size: 0.68rem; color: #94a3b8; text-align: center; margin: 0; }

/* Form col */
.form-col { flex: 1; display: flex; flex-direction: column; gap: 1rem; }
.form-section-title { display: flex; align-items: center; gap: 0.45rem; font-size: 0.82rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.06em; }
.form-divider { height: 1px; background: #f1f5f9; margin: 0.25rem 0; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.form-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; display: flex; align-items: center; gap: 0.4rem; }
.label-optional { font-weight: 400; text-transform: none; font-size: 0.68rem; color: #94a3b8; letter-spacing: 0; }
.form-input { width: 100%; background: #f8fafc; color: #1e293b; border: 1.5px solid #e2e8f0; border-radius: 0.65rem; padding: 0.7rem 0.9rem; font-size: 0.875rem; font-family: inherit; outline: none; transition: border-color 0.15s, background 0.15s, box-shadow 0.15s; box-sizing: border-box; }
.form-input::placeholder { color: #cbd5e1; }
.form-input:focus { border-color: #10b981; background: #fff; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
.field-error { color: #dc2626; font-size: 0.72rem; font-weight: 600; margin: 0.1rem 0 0; }
.select-wrapper { position: relative; }
.form-select { width: 100%; background: #f8fafc; color: #1e293b; border: 1.5px solid #e2e8f0; border-radius: 0.65rem; padding: 0.7rem 2.2rem 0.7rem 0.9rem; font-size: 0.875rem; font-family: inherit; outline: none; appearance: none; cursor: pointer; transition: border-color 0.15s; box-sizing: border-box; }
.form-select:focus { border-color: #10b981; background: #fff; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
.select-arrow { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8; pointer-events: none; }

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-enter-active { transition: opacity 0.2s, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.modal-leave-active { transition: opacity 0.15s; }
.modal-enter-from { opacity: 0; transform: scale(0.96) translateY(8px); }
.modal-leave-to { opacity: 0; }

.hidden { display: none; }
.animate-spin { animation: spin 1s linear infinite; }
.shrink-0 { flex-shrink: 0; }
</style>
