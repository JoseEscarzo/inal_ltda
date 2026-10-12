<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const proveedores = ref([])
const inactivos = ref([])
const cargando = ref(false)
const guardando = ref(false)
const mostrarModal = ref(false)
const verArchivados = ref(false)
const editando = ref(null)
const busqueda = ref('')
const error = ref('')
const form = ref({ nombre: '', nit: '', contacto: '', telefono: '', correo: '', direccion: '' })

const lista = computed(() => verArchivados.value ? inactivos.value : proveedores.value)
const filtrados = computed(() => {
  const texto = busqueda.value.trim().toLowerCase()
  if (!texto) return lista.value
  return lista.value.filter((p) => [p.nombre, p.nit, p.contacto, p.telefono, p.correo, p.direccion]
    .some((campo) => String(campo || '').toLowerCase().includes(texto)))
})

const cargar = async () => {
  cargando.value = true
  try {
    const { data } = await api.get('/proveedores')
    proveedores.value = Array.isArray(data) ? data : (data.data || [])
  } catch { error.value = 'No se pudieron cargar los proveedores.' } finally { cargando.value = false }
}

const cargarInactivos = async () => {
  const { data } = await api.get('/proveedores/inactivos')
  inactivos.value = Array.isArray(data) ? data : (data.data || [])
}

const abrirNuevo = () => {
  editando.value = null
  form.value = { nombre: '', nit: '', contacto: '', telefono: '', correo: '', direccion: '' }
  error.value = ''
  mostrarModal.value = true
}

const abrirEditar = (proveedor) => {
  editando.value = proveedor.id_proveedor
  form.value = {
    nombre: proveedor.nombre || '', nit: proveedor.nit || '', contacto: proveedor.contacto || '',
    telefono: proveedor.telefono || '', correo: proveedor.correo || '', direccion: proveedor.direccion || '',
  }
  error.value = ''
  mostrarModal.value = true
}

const guardar = async () => {
  if (!form.value.nombre.trim()) { error.value = 'El nombre del proveedor es obligatorio.'; return }
  guardando.value = true
  try {
    if (editando.value) await api.put(`/proveedores/${editando.value}`, form.value)
    else await api.post('/proveedores', form.value)
    mostrarModal.value = false
    await cargar()
  } catch (e) { error.value = e.response?.data?.message || 'No se pudo guardar el proveedor.' } finally { guardando.value = false }
}

const archivar = async (p) => {
  if (!confirm(`¿Archivar a ${p.nombre}? Ya no aparecerá al registrar un ingreso.`)) return
  await api.patch(`/proveedores/${p.id_proveedor}/archivar`)
  await cargar()
}
const activar = async (p) => { await api.patch(`/proveedores/${p.id_proveedor}/activar`); await Promise.all([cargar(), cargarInactivos()]) }
const eliminar = async (p) => {
  if (!confirm(`¿Eliminar definitivamente a ${p.nombre}? Esta acción no se puede deshacer.`)) return
  await api.delete(`/proveedores/${p.id_proveedor}/definitivo`)
  await cargarInactivos()
}
const cambiarVista = async () => {
  verArchivados.value = !verArchivados.value
  if (verArchivados.value) await cargarInactivos()
}

onMounted(cargar)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Proveedores</h2>
        <p class="mt-0.5 text-sm text-gray-500">Administra quienes entregan productos para revisión.</p>
      </div>
      <div class="flex gap-3">
        <button @click="cambiarVista" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">
          {{ verArchivados ? 'Ver activos' : 'Archivados' }}
        </button>
        <button v-if="!verArchivados" @click="abrirNuevo" class="rounded-xl bg-[#2B2353] px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-[#3d3275]">+ Nuevo proveedor</button>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
      <input v-model="busqueda" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30" placeholder="Buscar por nombre, NIT, contacto o teléfono...">
    </div>

    <p v-if="error" class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
      <div v-if="cargando" class="py-16 text-center text-sm text-gray-400">Cargando proveedores...</div>
      <table v-else class="w-full text-sm">
        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
          <th class="px-6 py-3.5">Proveedor</th><th class="px-6 py-3.5">NIT</th><th class="px-6 py-3.5">Contacto</th><th class="px-6 py-3.5">Teléfono</th><th class="px-6 py-3.5">Correo</th><th class="px-6 py-3.5 text-right">Acciones</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="p in filtrados" :key="p.id_proveedor" class="hover:bg-[#F4F7FE]/60">
            <td class="px-6 py-4 font-semibold text-gray-800"><p>{{ p.nombre }}</p><p v-if="p.direccion" class="mt-1 text-xs font-normal text-gray-400">{{ p.direccion }}</p></td>
            <td class="px-6 py-4 text-gray-600">{{ p.nit || '-' }}</td><td class="px-6 py-4 text-gray-600">{{ p.contacto || '-' }}</td><td class="px-6 py-4 text-gray-600">{{ p.telefono || '-' }}</td><td class="px-6 py-4 text-gray-600">{{ p.correo || '-' }}</td>
            <td class="px-6 py-4 text-right"><div class="flex justify-end gap-2">
              <button v-if="!verArchivados" @click="abrirEditar(p)" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Editar</button>
              <button v-if="!verArchivados" @click="archivar(p)" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">Archivar</button>
                <button v-if="verArchivados" @click="activar(p)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Activar</button>
                <button v-if="verArchivados" @click="eliminar(p)" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">Eliminar</button>
            </div></td>
          </tr>
          <tr v-if="!filtrados.length"><td colspan="6" class="px-6 py-12 text-center text-gray-400">No hay proveedores {{ verArchivados ? 'archivados' : 'registrados' }}.</td></tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body"><div v-if="mostrarModal" class="fixed inset-0 z-[1300] flex items-center justify-center bg-slate-900/35 p-4 backdrop-blur-sm">
      <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="flex items-center justify-between bg-[#2B2353] px-6 py-5"><h3 class="font-bold text-white">{{ editando ? 'Editar proveedor' : 'Registrar proveedor' }}</h3><button @click="mostrarModal = false" class="text-xl text-white/70">×</button></div>
      <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2"><label class="md:col-span-2"><span class="label">Nombre o razón social *</span><input v-model="form.nombre" class="field" placeholder="Ej. Proveedora Andina SRL"></label><label><span class="label">NIT</span><input v-model="form.nit" class="field"></label><label><span class="label">Persona de contacto</span><input v-model="form.contacto" class="field"></label><label><span class="label">Teléfono</span><input v-model="form.telefono" class="field"></label><label><span class="label">Correo</span><input v-model="form.correo" type="email" class="field"></label><label class="md:col-span-2"><span class="label">Dirección</span><textarea v-model="form.direccion" class="field min-h-20"></textarea></label></div>
      <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 p-4"><button @click="mostrarModal = false" class="btn-secondary">Cancelar</button><button @click="guardar" :disabled="guardando" class="btn-primary bg-[#2B2353] hover:bg-[#3d3275]">{{ guardando ? 'Guardando...' : 'Guardar proveedor' }}</button></div></div>
    </div></Teleport>
  </div>
</template>

<style scoped>
.field { width: 100%; margin-top: .35rem; border: 1px solid #e5e7eb; border-radius: .75rem; padding: .6rem .75rem; font-size: .875rem; outline: none; }
.field:focus { border-color: #2B2353; box-shadow: 0 0 0 2px rgb(43 35 83 / .15); }
.label { font-size: .75rem; font-weight: 600; color: #4b5563; }
.btn-primary, .btn-secondary { border-radius: .75rem; padding: .5rem 1.25rem; font-size: .875rem; font-weight: 700; }
.btn-primary { color: white; }.btn-secondary { color: #6b7280; }
</style>
