<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Distribuciones y Revisiones</h2>
        <p class="text-sm text-gray-500 mt-0.5">Gestiona los ingresos de proveedores y los despachos.</p>
      </div>
      <div class="flex gap-3">
        <ReportExportMenu
          @excel="exportarDistribucionesExcel"
          @pdf="exportarDistribucionesPDF"
          @print="imprimirDistribuciones"
        />
        <button
          v-if="tabActiva === 'ingresos'"
          @click="abrirModalIngreso"
          class="flex items-center gap-2 bg-[#2B2353] hover:bg-[#3d3275] text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-md"
        >
          + Registrar Ingreso
        </button>
        <button
          v-if="tabActiva === 'salidas'"
          @click="abrirModalSalida"
          class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-md"
        >
          Registrar Salida
        </button>
      </div>
    </div>

    <div class="flex gap-4 border-b border-gray-200">
      <button
        @click="tabActiva = 'ingresos'"
        class="pb-3 px-2 text-sm font-semibold border-b-2"
        :class="tabActiva === 'ingresos' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400'"
      >
        Ingresos (Revisiones)
      </button>
      <button
        @click="tabActiva = 'salidas'"
        class="pb-3 px-2 text-sm font-semibold border-b-2"
        :class="tabActiva === 'salidas' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400'"
      >
        Salidas (Despachos)
      </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
      <input
        v-model="busquedaDistribucion"
        class="md:col-span-2 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2B2353]/30"
        placeholder="Buscar por nombre, destino o producto..."
      />
      <select v-model="filtroExportacion" class="px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white">
        <option value="todos">Exportar ingresos y salidas</option>
        <option value="ingresos">Solo ingresos</option>
        <option value="salidas">Solo salidas</option>
      </select>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="flex justify-center py-20">
        <div class="w-10 h-10 border-4 border-[#2B2353]/20 border-t-[#2B2353] rounded-full animate-spin"></div>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr v-if="tabActiva === 'ingresos'">
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">#</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Fecha / Turno</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Entregado por</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Recibido por</th>
            <th class="text-center px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Aprobados</th>
            <th class="text-center px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Rechazados</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
          </tr>
          <tr v-else>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">#</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Fecha / Turno</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Destino</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Despachado por</th>
            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Distribuidor</th>
            <th class="text-center px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Total</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Factura</th>
            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <template v-if="tabActiva === 'ingresos'">
            <tr v-for="ing in ingresosFiltrados" :key="ing.id_ingreso" class="hover:bg-[#F4F7FE]/60">
              <td class="px-6 py-4 font-mono text-xs text-gray-500">#{{ String(ing.id_ingreso).padStart(3, '0') }}</td>
              <td class="px-6 py-4">
                <p class="font-semibold text-gray-800">{{ formatFecha(ing.fecha_hora) }}</p>
                <p class="text-xs text-gray-400">{{ ing.turno || '-' }}</p>
              </td>
              <td class="px-6 py-4 text-gray-700 font-medium">{{ ing.proveedor?.nombre || ing.entregado_por?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-gray-600">{{ ing.recibido_por?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-center font-bold text-emerald-600">{{ totalAprobados(ing) }}</td>
              <td class="px-6 py-4 text-center font-bold text-red-500">{{ totalRechazados(ing) }}</td>
              <td class="px-6 py-4 text-right">
                <button @click="eliminarIngreso(ing)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200">Eliminar</button>
              </td>
            </tr>
            <tr v-if="!ingresosFiltrados.length">
              <td colspan="7" class="text-center py-10 text-gray-400">No hay ingresos registrados</td>
            </tr>
          </template>

          <template v-else>
            <tr v-for="sal in salidasFiltradas" :key="sal.id_salida" class="hover:bg-emerald-50/50">
              <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ sal.nro_informe || '#' + String(sal.id_salida).padStart(3, '0') }}</td>
              <td class="px-6 py-4">
                <p class="font-semibold text-gray-800">{{ formatFecha(sal.fecha_hora) }}</p>
                <p class="text-xs text-gray-400">{{ sal.turno || '-' }}</p>
              </td>
              <td class="px-6 py-4 font-semibold text-gray-800">{{ sal.destino?.nombre || 'Sin destino' }}</td>
              <td class="px-6 py-4 text-gray-700 font-medium">{{ sal.despachado_por?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-gray-600">{{ sal.distribuidor?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-center font-bold text-[#2B2353]">{{ totalSalida(sal) }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex flex-col items-end gap-1">
                  <span class="font-bold text-emerald-700">{{ formatoMoneda(totalFacturaSalida(sal)) }}</span>
                  <span class="text-[11px] text-gray-400">{{ sal.factura ? 'Factura #' + sal.factura.numero_factura : 'Sin factura' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                  <button @click="descargarFacturaSalida(sal, 'pdf')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200">PDF</button>
                  <button @click="descargarFacturaSalida(sal, 'ticket')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-[#2B2353] bg-[#2B2353]/10 border border-[#2B2353]/20">Ticket</button>
                  <button @click="enviarFacturaSalida(sal)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200">Correo</button>
                <button @click="eliminarSalida(sal)" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200">Eliminar</button>
                </div>
              </td>
            </tr>
            <tr v-if="!salidasFiltradas.length">
              <td colspan="8" class="text-center py-10 text-gray-400">No hay salidas registradas</td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
    <Transition name="fade">
      <div v-if="showModalIngreso" class="fixed inset-0 z-[1300] flex items-center justify-center p-4 modal-backdrop">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[92vh] overflow-hidden">
          <div class="bg-[#2B2353] px-6 py-5 flex items-center justify-between">
            <div>
              <h3 class="text-white font-bold text-base">Registrar Ingreso de Productos</h3>
              <p class="text-white/60 text-xs mt-0.5">Turno actual: <span class="font-semibold text-white">{{ formIngreso.turno }}</span></p>
            </div>
            <button @click="showModalIngreso = false" class="text-white/70 text-xl">x</button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 space-y-5">
            <div class="flex gap-2 border-b border-gray-200">
              <button @click="modoIngreso = 'manual'" class="px-3 pb-2 text-sm font-semibold border-b-2" :class="modoIngreso === 'manual' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400'">Registro manual</button>
              <button @click="modoIngreso = 'vision'" class="px-3 pb-2 text-sm font-semibold border-b-2" :class="modoIngreso === 'vision' ? 'border-[#2B2353] text-[#2B2353]' : 'border-transparent text-gray-400'">Inspección con IA</button>
            </div>
            <template v-if="modoIngreso === 'manual'">
            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Entregado por (Proveedor) *</label>
                <select v-model="formIngreso.proveedor_id" class="field">
                  <option value="" disabled>Seleccione proveedor...</option>
                  <option v-for="proveedor in proveedores" :key="proveedor.id_proveedor" :value="proveedor.id_proveedor">{{ proveedor.nombre }}{{ proveedor.nit ? ' - NIT ' + proveedor.nit : '' }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Recibido por</label>
                <div class="field bg-gray-100 text-gray-600">{{ usuarioActual?.name || 'Usuario actual' }} <span class="float-right text-xs text-gray-400">(tu)</span></div>
              </div>
            </div>
            <DetalleTabla
              tipo="ingreso"
              :filas="formIngreso.detalles"
              :productos="productosIngreso"
              @agregar="agregarFilaIngreso"
              @remover="removerFilaIngreso"
              @recalcular="calcularRechazados"
            />
            </template>
            <div v-else class="space-y-4">
              <div class="rounded-xl border border-dashed border-[#2B2353]/35 bg-[#F7F5FF] p-5">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <label class="block text-sm font-bold text-gray-800">Inspección visual de la caja</label>
                    <p class="mt-1 text-xs text-gray-500">Carga una foto para que YOLOv8 revise daños o aperturas.</p>
                  </div>
                  <span class="rounded-full bg-[#2B2353]/10 px-3 py-1 text-xs font-bold text-[#2B2353]">IA · YOLOv8</span>
                </div>
                <input id="imagen-inspeccion" type="file" accept="image/jpeg,image/png,image/webp" @change="seleccionarImagenDeteccion" class="sr-only" />
                <div v-if="vistaPreviaDeteccion" class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_210px]">
                  <div class="overflow-hidden rounded-xl border border-[#2B2353]/15 bg-slate-900">
                    <img :src="vistaPreviaDeteccion" alt="Vista previa de la caja a inspeccionar" class="max-h-[340px] w-full object-contain" />
                  </div>
                  <div class="flex flex-col justify-center gap-3 rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-gray-800">Imagen lista</p>
                    <p class="text-xs leading-relaxed text-gray-500">La foto se analizará y las zonas encontradas se marcarán en amarillo.</p>
                    <label for="imagen-inspeccion" class="cursor-pointer rounded-lg border border-[#2B2353]/20 px-3 py-2 text-center text-xs font-bold text-[#2B2353] hover:bg-[#2B2353]/5">Cambiar imagen</label>
                    <button @click="detectarDanio" :disabled="detectando" class="rounded-xl bg-[#2B2353] px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">
                      {{ detectando ? 'Analizando imagen...' : 'Analizar con IA' }}
                    </button>
                  </div>
                </div>
                <label v-else for="imagen-inspeccion" class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#2B2353]/25 bg-white px-6 py-10 text-center hover:bg-[#2B2353]/[0.03]">
                  <span class="text-3xl">📷</span>
                  <span class="mt-2 text-sm font-bold text-[#2B2353]">Seleccionar imagen de la caja</span>
                  <span class="mt-1 text-xs text-gray-500">JPG, PNG o WEBP</span>
                </label>
                <p v-if="errorDeteccion" class="mt-3 text-sm font-semibold text-red-600">{{ errorDeteccion }}</p>
              </div>
              <div v-if="resultadoDeteccion" class="overflow-hidden rounded-xl border" :class="resultadoDeteccion.damaged ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                  <div>
                    <p class="font-bold" :class="resumenInspeccion.rechazar ? 'text-red-800' : resultadoDeteccion.damaged ? 'text-amber-800' : 'text-emerald-800'">{{ resumenInspeccion.decision }}</p>
                    <p class="text-xs" :class="resultadoDeteccion.damaged ? 'text-amber-700' : 'text-emerald-700'">{{ resumenInspeccion.descripcion }}</p>
                  </div>
                  <span class="rounded-full px-3 py-1 text-xs font-bold" :class="resumenInspeccion.rechazar ? 'bg-red-200 text-red-900' : resultadoDeteccion.damaged ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900'">{{ resumenInspeccion.rechazar ? 'RECHAZAR' : resultadoDeteccion.damaged ? 'REVISAR' : 'CONFORME' }}</span>
                </div>
                <img :src="resultadoDeteccion.annotated_image" alt="Resultado de la detección de daño" class="max-h-[440px] w-full object-contain bg-slate-900" />
                <div class="grid gap-3 border-t border-black/5 bg-white/70 p-4 sm:grid-cols-3">
                  <div class="rounded-xl bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Zonas detectadas</p>
                    <p class="mt-1 text-2xl font-black text-[#2B2353]">{{ resumenInspeccion.total }}</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-[#2B2353]" :style="{ width: `${resumenInspeccion.total === 0 ? 0 : Math.min(100, resumenInspeccion.total * 25)}%` }"></div></div>
                  </div>
                  <div class="rounded-xl bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Tipos encontrados</p>
                    <div class="mt-2 space-y-2 text-xs font-semibold text-gray-700">
                      <div class="flex justify-between"><span>Daño</span><span class="text-amber-700">{{ resumenInspeccion.damage }}</span></div>
                      <div class="h-1.5 overflow-hidden rounded-full bg-amber-100"><div class="h-full bg-amber-400" :style="{ width: `${resumenInspeccion.damagePercent}%` }"></div></div>
                      <div class="flex justify-between"><span>Apertura</span><span class="text-blue-700">{{ resumenInspeccion.open }}</span></div>
                      <div class="h-1.5 overflow-hidden rounded-full bg-blue-100"><div class="h-full bg-blue-400" :style="{ width: `${resumenInspeccion.openPercent}%` }"></div></div>
                    </div>
                  </div>
                  <div class="rounded-xl bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Confianza promedio</p>
                    <p class="mt-1 text-2xl font-black" :class="resultadoDeteccion.damaged ? 'text-[#2B2353]' : 'text-emerald-600'">{{ resumenInspeccion.confianza }}%</p>
                    <p class="mt-2 text-xs leading-relaxed text-gray-500">Regla: rechazar si se detectan más de 2 zonas dañadas.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
            <button @click="showModalIngreso = false" class="btn-secondary">Cancelar</button>
            <button @click="guardarIngreso" :disabled="submitting" class="btn-primary bg-[#2B2353] hover:bg-[#3d3275]">Guardar Ingreso</button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="showModalSalida" class="fixed inset-0 z-[1300] flex items-center justify-center p-4 modal-backdrop">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[92vh] overflow-hidden">
          <div class="bg-emerald-600 px-6 py-5 flex items-center justify-between">
            <div>
              <h3 class="text-white font-bold text-base">Registrar Salida / Despacho</h3>
              <p class="text-white/60 text-xs mt-0.5">Turno actual: <span class="font-semibold text-white">{{ formSalida.turno }}</span></p>
            </div>
            <button @click="showModalSalida = false" class="text-white/70 text-xl">x</button>
          </div>
          <div class="p-6 overflow-y-auto flex-1 space-y-5">
            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ esSupervisor ? 'Despachado por (Supervisor)' : 'Autorizado por' }}</label>
                <div class="field bg-gray-100 text-gray-600">{{ usuarioActual?.name || 'Usuario actual' }} <span class="float-right text-xs text-gray-400">(tu)</span></div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ esSupervisor ? 'Autorizado por' : 'Despachado por (Supervisor)' }} *</label>
                <select v-if="esSupervisor" v-model="formSalida.autorizado_por_id" class="field">
                  <option value="" disabled>Seleccione administrador...</option>
                  <option v-for="admin in administradores" :key="admin.id" :value="admin.id">{{ admin.name }}</option>
                </select>
                <select v-else v-model="formSalida.despachado_por_id" class="field">
                  <option value="" disabled>Seleccione supervisor...</option>
                  <option v-for="sup in supervisores" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Distribuidor *</label>
                <select v-model="formSalida.distribuidor_id" class="field">
                  <option value="" disabled>Seleccione distribuidor...</option>
                  <option v-for="dist in distribuidores" :key="dist.id" :value="dist.id">{{ dist.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Destino *</label>
                <select v-model="formSalida.destino_id" class="field">
                  <option value="" disabled>Seleccione destino...</option>
                  <option v-for="dest in destinos" :key="dest.id_destino" :value="dest.id_destino">{{ dest.nombre }}{{ dest.ciudad ? ' - ' + dest.ciudad : '' }}</option>
                </select>
              </div>
            </div>
            <DetalleTabla
              tipo="salida"
              :filas="formSalida.detalles"
              :productos="productosSalida"
              :lotes="lotesSalida"
              @agregar="agregarFilaSalida"
              @remover="removerFilaSalida"
            />
            <div class="bg-emerald-50 rounded-xl p-3 text-center border border-emerald-100">
              <p class="text-lg font-bold text-emerald-700">{{ totalFormSalida }}</p>
              <p class="text-xs text-gray-500">Total a despachar</p>
              <p class="text-sm font-bold text-[#2B2353] mt-1">{{ formatoMoneda(totalFormSalidaBs) }}</p>
              <p class="text-[11px] text-gray-500">Total estimado para factura</p>
            </div>
          </div>
          <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
            <button @click="showModalSalida = false" class="btn-secondary">Cancelar</button>
            <button @click="guardarSalida" :disabled="submitting" class="btn-primary bg-emerald-600 hover:bg-emerald-700">Guardar Salida</button>
          </div>
        </div>
      </div>
    </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import ReportExportMenu from '@/components/ReportExportMenu.vue'
import { descargarBlob, descargarPdfBlob, imprimirPdfBlob } from '@/utils/reportDownloads'

const DetalleTabla = defineComponent({
  props: {
    tipo: { type: String, required: true },
    filas: { type: Array, required: true },
    productos: { type: Array, required: true },
    lotes: { type: Array, default: () => [] },
  },
  emits: ['agregar', 'remover', 'recalcular'],
  setup(props, { emit }) {
    const lotesPorProducto = (productoId) => props.lotes.filter((lote) =>
      String(lote.producto_id) === String(productoId) &&
      Number(lote.cantidad || 0) > 0 &&
      ['activo', 'por vencer'].includes(lote.estado)
    )

    const nombreProducto = (productoId) =>
      props.productos.find((p) => String(p.id_producto) === String(productoId))?.nombre || 'Producto'

    const textoLote = (lote) => {
      const vence = lote.fecha_fin ? String(lote.fecha_fin).split('T')[0].split(' ')[0] : 'Sin vencimiento'
      return `${lote.codigo_lote} - stock ${Number(lote.cantidad || 0)} - vence ${vence}`
    }

    return () => h('div', [
      h('div', { class: 'flex justify-between items-center mb-3' }, [
        h('h4', { class: 'font-bold text-sm text-gray-700' }, props.tipo === 'ingreso' ? 'Detalle de productos a revisar' : 'Detalle de productos a despachar'),
        h('button', { class: 'text-xs font-semibold text-emerald-600 hover:underline', onClick: () => emit('agregar') }, '+ Agregar fila'),
      ]),
      h('div', { class: 'border border-gray-200 rounded-xl overflow-hidden' }, [
        h('table', { class: 'w-full text-sm text-left' }, [
          h('thead', { class: 'bg-gray-50 text-gray-500 text-xs' }, [
            h('tr', [
              h('th', { class: 'p-3' }, 'Producto / Presentacion'),
              props.tipo === 'salida' ? h('th', { class: 'p-3 min-w-[220px]' }, 'Lote disponible') : null,
              h('th', { class: 'p-3 w-28 text-center' }, props.tipo === 'ingreso' ? 'Total' : 'Cantidad'),
              props.tipo === 'ingreso' ? h('th', { class: 'p-3 w-28 text-center' }, 'Aprobados') : null,
              props.tipo === 'ingreso' ? h('th', { class: 'p-3 w-28 text-center' }, 'Rechazados') : null,
              h('th', { class: 'p-3 w-10' }),
            ]),
          ]),
          h('tbody', { class: 'divide-y divide-gray-100' }, props.filas.map((fila, idx) => h('tr', { key: idx }, [
            h('td', { class: 'p-2' }, h('select', {
              class: 'w-full p-2 border border-gray-200 rounded-lg text-sm',
              value: fila.producto_id,
              onChange: (event) => {
                fila.producto_id = event.target.value
                if (props.tipo === 'salida') fila.lote_id = ''
              },
            }, [
              h('option', { value: '', disabled: true }, 'Seleccione producto...'),
              ...props.productos.map((p) => h('option', { value: p.id_producto }, `${p.nombre}${p.presentacion ? ' - ' + p.presentacion : ''}`)),
            ])),
            props.tipo === 'salida'
              ? h('td', { class: 'p-2' }, h('select', {
                class: 'w-full p-2 border border-gray-200 rounded-lg text-sm bg-white',
                value: fila.lote_id || '',
                disabled: !fila.producto_id,
                onChange: (event) => {
                  fila.lote_id = event.target.value
                  const lote = props.lotes.find((item) => String(item.id_lote) === String(event.target.value))
                  if (lote && Number(fila.cantidad || 0) > Number(lote.cantidad || 0)) {
                    fila.cantidad = Number(lote.cantidad || 0)
                  }
                },
              }, [
                h('option', { value: '', disabled: true }, fila.producto_id ? `Seleccione lote de ${nombreProducto(fila.producto_id)}...` : 'Primero seleccione producto...'),
                ...lotesPorProducto(fila.producto_id).map((lote) => h('option', { value: lote.id_lote }, textoLote(lote))),
              ]))
              : null,
            h('td', { class: 'p-2' }, h('input', {
              class: 'w-full p-2 border border-gray-200 rounded-lg text-sm text-center font-bold',
              type: 'number',
              min: props.tipo === 'ingreso' ? 0 : 1,
              max: props.tipo === 'salida'
                ? (props.lotes.find((item) => String(item.id_lote) === String(fila.lote_id))?.cantidad || undefined)
                : undefined,
              value: fila.cantidad,
              onInput: (event) => {
                const lote = props.lotes.find((item) => String(item.id_lote) === String(fila.lote_id))
                const cantidad = Number(event.target.value)
                fila.cantidad = props.tipo === 'salida' && lote
                  ? Math.min(cantidad, Number(lote.cantidad || 0))
                  : cantidad
                emit('recalcular', fila)
              },
            })),
            props.tipo === 'ingreso' ? h('td', { class: 'p-2' }, h('input', {
              class: 'w-full p-2 border border-emerald-200 bg-emerald-50 text-emerald-700 font-bold rounded-lg text-sm text-center',
              type: 'number',
              min: 0,
              value: fila.cantidad_aprobada,
              onInput: (event) => {
                fila.cantidad_aprobada = Number(event.target.value)
                emit('recalcular', fila)
              },
            })) : null,
            props.tipo === 'ingreso' ? h('td', { class: 'p-2 text-center font-bold text-red-500' }, fila.cantidad_rechazada || 0) : null,
            h('td', { class: 'p-2 text-center' }, h('button', { class: 'text-red-400 hover:text-red-600', onClick: () => emit('remover', idx) }, '🗑')),
          ]))),
        ]),
      ]),
    ])
  },
})

const authStore = useAuthStore()
const usuarioActual = computed(() => authStore.user)
const esSupervisor = computed(() => Number(usuarioActual.value?.id_rol) === 2)

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
})

const detectorApi = axios.create({
  baseURL: import.meta.env.VITE_CAMERA_SERVICE_URL || 'http://localhost:5001',
  timeout: 30000,
})

const obtenerTurnoActual = () => {
  const h = new Date().getHours()
  if (h >= 6 && h < 14) return 'Mañana'
  if (h >= 14 && h < 22) return 'Tarde'
  return 'Noche'
}

const tabActiva = ref('ingresos')
const loading = ref(false)
const submitting = ref(false)
const busquedaDistribucion = ref('')
const filtroExportacion = ref('todos')
const ingresos = ref([])
const salidas = ref([])
const administradores = ref([])
const supervisores = ref([])
const distribuidores = ref([])
const productosDisponibles = ref([])
const lotesDisponibles = ref([])
const destinos = ref([])
const proveedores = ref([])
const catalogosCargados = ref(false)
const showModalIngreso = ref(false)
const showModalSalida = ref(false)
const modoIngreso = ref('manual')
const imagenDeteccion = ref(null)
const vistaPreviaDeteccion = ref('')
const detectando = ref(false)
const resultadoDeteccion = ref(null)
const errorDeteccion = ref('')

const nuevoDetalleIngreso = () => ({ producto_id: '', lote_id: null, cantidad: 0, cantidad_aprobada: 0, cantidad_rechazada: 0 })
const nuevoDetalleSalida = () => ({ producto_id: '', lote_id: '', cantidad: 1 })
const formIngreso = ref({ proveedor_id: '', turno: obtenerTurnoActual(), detalles: [] })
const formSalida = ref({ despachado_por_id: '', autorizado_por_id: '', distribuidor_id: '', destino_id: '', turno: obtenerTurnoActual(), detalles: [] })

const productosIngreso = computed(() => productosDisponibles.value.filter(p => p.tipo_producto !== 'Producto Terminado'))
const productosSalida = computed(() => productosDisponibles.value.filter(p => p.tipo_producto === 'Producto Terminado'))
const lotesSalida = computed(() => lotesDisponibles.value.filter((lote) =>
  Number(lote.cantidad || 0) > 0 &&
  ['activo', 'por vencer'].includes(lote.estado) &&
  productosSalida.value.some((producto) => String(producto.id_producto) === String(lote.producto_id))
))
const totalFormSalida = computed(() => formSalida.value.detalles.reduce((s, d) => s + Number(d.cantidad || 0), 0))
const totalFormSalidaBs = computed(() => formSalida.value.detalles.reduce((s, d) => {
  const producto = productosSalida.value.find((p) => String(p.id_producto) === String(d.producto_id))
  return s + (Number(d.cantidad || 0) * Number(producto?.precio_distribuidor || 0))
}, 0))

const resumenInspeccion = computed(() => {
  const detecciones = resultadoDeteccion.value?.detections || []
  const total = detecciones.length
  const damage = detecciones.filter(item => item.class === 'damage').length
  const open = detecciones.filter(item => item.class === 'open').length
  const confianza = total
    ? Math.round((detecciones.reduce((sum, item) => sum + Number(item.confidence || 0), 0) / total) * 100)
    : 0
  const rechazar = total > 2

  return {
    total,
    damage,
    open,
    confianza,
    rechazar,
    damagePercent: total ? Math.round((damage / total) * 100) : 0,
    openPercent: total ? Math.round((open / total) * 100) : 0,
    decision: rechazar
      ? 'No recibir esta caja'
      : total
        ? 'Caja con observaciones: requiere revisión'
        : 'Caja conforme para recepción',
    descripcion: rechazar
      ? `Se detectaron ${total} zonas. Supera el límite permitido de 2 zonas dañadas.`
      : total
        ? `Se detectaron ${total} zona(s). Está dentro del límite, pero debe revisarse visualmente.`
        : 'No se detectaron zonas de daño ni aperturas.',
  }
})

const extraer = (res, keys = []) => {
  const d = res?.data
  if (!d) return []
  if (Array.isArray(d)) return d
  for (const k of keys) if (Array.isArray(d[k])) return d[k]
  if (Array.isArray(d.data)) return d.data
  return []
}

const coincideBusqueda = (texto) =>
  !busquedaDistribucion.value || String(texto || '').toLowerCase().includes(busquedaDistribucion.value.toLowerCase())

const ingresosFiltrados = computed(() => ingresos.value.filter(ing =>
  coincideBusqueda(ing.proveedor?.nombre) ||
  coincideBusqueda(ing.entregado_por?.name) ||
  coincideBusqueda(ing.recibido_por?.name) ||
  coincideBusqueda(ing.turno) ||
  ing.detalles?.some(d => coincideBusqueda(d.producto?.nombre))
))

const salidasFiltradas = computed(() => salidas.value.filter(sal =>
  coincideBusqueda(sal.nro_informe) ||
  coincideBusqueda(sal.destino?.nombre) ||
  coincideBusqueda(sal.despachado_por?.name) ||
  coincideBusqueda(sal.distribuidor?.name) ||
  sal.detalles?.some(d => coincideBusqueda(d.producto?.nombre))
))

const paramsExportDistribuciones = computed(() => ({
  buscar: busquedaDistribucion.value || null,
  tipo: filtroExportacion.value === 'todos' ? null : filtroExportacion.value,
}))

const formatFecha = (fechaHora) => fechaHora ? fechaHora.toString().split('T')[0].split(' ')[0] : '-'
const formatoMoneda = (valor) => `${Number(valor || 0).toFixed(2)} Bs`
const totalAprobados = (ing) => ing.detalles?.reduce((s, d) => s + Number(d.cantidad_aprobada || 0), 0) || 0
const totalRechazados = (ing) => ing.detalles?.reduce((s, d) => s + Number(d.cantidad_rechazada || 0), 0) || 0
const totalSalida = (sal) => sal.detalles?.reduce((s, d) => s + Number(d.cantidad || 0), 0) || 0
const totalFacturaSalida = (sal) => sal.factura?.total_pagar ?? (sal.detalles?.reduce((s, d) =>
  s + (Number(d.cantidad || 0) * Number(d.producto?.precio_distribuidor || 0)), 0
) || 0)

const cargarDatosIniciales = async () => {
  loading.value = true
  try {
    const [resIng, resSal] = await Promise.allSettled([api.get('/ingresos'), api.get('/salidas')])
    if (resIng.status === 'fulfilled') ingresos.value = extraer(resIng.value, ['ingresos'])
    if (resSal.status === 'fulfilled') salidas.value = extraer(resSal.value, ['salidas'])
  } finally {
    loading.value = false
  }
}

const cargarCatalogos = async () => {
  if (catalogosCargados.value) return
  const [resUsers, resProd, resDest, resLotes, resProveedores] = await Promise.allSettled([
    api.get('/users'),
    api.get('/productos'),
    api.get('/destinos'),
    api.get('/lotes', { params: { per_page: 200 } }),
    api.get('/proveedores'),
  ])
  if (resUsers.status === 'fulfilled') {
    const todos = extraer(resUsers.value, ['usuarios'])
    administradores.value = todos.filter(u => u.id_rol == 1)
    supervisores.value = todos.filter(u => u.id_rol == 2)
    distribuidores.value = todos.filter(u => u.id_rol == 5)
  }
  if (resProd.status === 'fulfilled') productosDisponibles.value = extraer(resProd.value, ['productos'])
  if (resDest.status === 'fulfilled') destinos.value = extraer(resDest.value, ['destinos'])
  if (resLotes.status === 'fulfilled') lotesDisponibles.value = extraer(resLotes.value, ['lotes'])
  if (resProveedores.status === 'fulfilled') proveedores.value = extraer(resProveedores.value, ['proveedores'])
  catalogosCargados.value = true
}

const abrirModalIngreso = async () => {
  await cargarCatalogos()
  formIngreso.value = { proveedor_id: '', turno: obtenerTurnoActual(), detalles: [nuevoDetalleIngreso()] }
  modoIngreso.value = 'manual'
  imagenDeteccion.value = null
  if (vistaPreviaDeteccion.value) URL.revokeObjectURL(vistaPreviaDeteccion.value)
  vistaPreviaDeteccion.value = ''
  resultadoDeteccion.value = null
  errorDeteccion.value = ''
  showModalIngreso.value = true
}

const seleccionarImagenDeteccion = (event) => {
  const archivo = event.target.files?.[0] || null
  resultadoDeteccion.value = null
  errorDeteccion.value = ''

  if (vistaPreviaDeteccion.value) URL.revokeObjectURL(vistaPreviaDeteccion.value)
  vistaPreviaDeteccion.value = ''

  if (!archivo) {
    imagenDeteccion.value = null
    return
  }

  const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp']
  if (!tiposPermitidos.includes(archivo.type)) {
    imagenDeteccion.value = null
    event.target.value = ''
    errorDeteccion.value = 'Solo se permiten imágenes JPG, PNG o WEBP. No se admiten archivos PDF, Word u otros documentos.'
    return
  }

  imagenDeteccion.value = archivo
  vistaPreviaDeteccion.value = URL.createObjectURL(archivo)
}

const detectarDanio = async () => {
  if (!imagenDeteccion.value) return
  detectando.value = true
  errorDeteccion.value = ''
  resultadoDeteccion.value = null
  try {
    const formData = new FormData()
    formData.append('image', imagenDeteccion.value)
    const { data } = await detectorApi.post('/api/detect', formData)
    resultadoDeteccion.value = data
    // Si la IA recomienda rechazar la caja (3 o más zonas), se rechaza el 20% del ingreso.
    // Ejemplo: 100 cajas recibidas = 20 rechazadas y 80 aprobadas.
    if ((data.detections?.length || 0) > 2) {
      formIngreso.value.detalles.forEach((fila) => {
        const total = Number(fila.cantidad || 0)
        const rechazadas = Math.round(total * 0.20)
        fila.cantidad_rechazada = rechazadas
        fila.cantidad_aprobada = total - rechazadas
      })
    }
  } catch (error) {
    errorDeteccion.value = error.response?.data?.message || 'No se pudo analizar la imagen. Verifica que camera-service esté iniciado.'
  } finally {
    detectando.value = false
  }
}

const abrirModalSalida = async () => {
  await cargarCatalogos()
  formSalida.value = {
    despachado_por_id: esSupervisor.value ? usuarioActual.value?.id : '',
    autorizado_por_id: '',
    distribuidor_id: '',
    destino_id: '',
    turno: obtenerTurnoActual(),
    detalles: [nuevoDetalleSalida()],
  }
  showModalSalida.value = true
}

const agregarFilaIngreso = () => formIngreso.value.detalles.push(nuevoDetalleIngreso())
const removerFilaIngreso = (i) => formIngreso.value.detalles.splice(i, 1)
const agregarFilaSalida = () => formSalida.value.detalles.push(nuevoDetalleSalida())
const removerFilaSalida = (i) => formSalida.value.detalles.splice(i, 1)

const calcularRechazados = (fila) => {
  if (fila.cantidad_aprobada > fila.cantidad) fila.cantidad_aprobada = fila.cantidad
  if (fila.cantidad_aprobada < 0) fila.cantidad_aprobada = 0
  fila.cantidad_rechazada = Number(fila.cantidad || 0) - Number(fila.cantidad_aprobada || 0)
}

const mensajeError = (err) => {
  const errores = err.response?.data?.errors || {}
  return Object.values(errores).flat().join('\n') || err.response?.data?.message || 'No se pudo guardar.'
}

const guardarIngreso = async () => {
  if (!formIngreso.value.proveedor_id) return alert('Seleccione el proveedor que entrego.')
  if (!formIngreso.value.detalles.length) return alert('Agregue al menos un producto.')
  submitting.value = true
  try {
    await api.post('/ingresos', {
      proveedor_id: formIngreso.value.proveedor_id,
      recibido_por_id: usuarioActual.value?.id,
      turno: formIngreso.value.turno,
      detalles: formIngreso.value.detalles,
    })
    showModalIngreso.value = false
    await cargarDatosIniciales()
  } catch (err) {
    alert(mensajeError(err))
  } finally {
    submitting.value = false
  }
}

const guardarSalida = async () => {
  if (esSupervisor.value && !formSalida.value.autorizado_por_id) return alert('Seleccione el administrador que autorizara.')
  if (!esSupervisor.value && !formSalida.value.despachado_por_id) return alert('Seleccione el supervisor autorizante.')
  if (!formSalida.value.distribuidor_id) return alert('Seleccione el distribuidor.')
  if (!formSalida.value.destino_id) return alert('Seleccione el destino.')
  if (!formSalida.value.detalles.length) return alert('Agregue al menos un producto.')
  const detalleIncompleto = formSalida.value.detalles.find((detalle) => !detalle.producto_id || !detalle.lote_id || Number(detalle.cantidad || 0) <= 0)
  if (detalleIncompleto) return alert('Seleccione producto, lote y cantidad valida en cada fila.')
  const detalleSinStock = formSalida.value.detalles.find((detalle) => {
    const lote = lotesSalida.value.find((item) => String(item.id_lote) === String(detalle.lote_id))
    return !lote || Number(detalle.cantidad || 0) > Number(lote.cantidad || 0)
  })
  if (detalleSinStock) return alert('La cantidad de una fila supera el stock disponible del lote seleccionado.')
  const cantidadesPorLote = formSalida.value.detalles.reduce((acc, detalle) => {
    const loteId = String(detalle.lote_id)
    acc[loteId] = (acc[loteId] || 0) + Number(detalle.cantidad || 0)
    return acc
  }, {})
  const loteSobrepasado = lotesSalida.value.find((lote) => cantidadesPorLote[String(lote.id_lote)] > Number(lote.cantidad || 0))
  if (loteSobrepasado) return alert(`La suma de cantidades supera el stock del lote ${loteSobrepasado.codigo_lote}.`)

  submitting.value = true
  try {
    await api.post('/salidas', {
      despachado_por_id: esSupervisor.value ? usuarioActual.value?.id : formSalida.value.despachado_por_id,
      autorizado_por_id: esSupervisor.value ? formSalida.value.autorizado_por_id : usuarioActual.value?.id,
      distribuidor_id: formSalida.value.distribuidor_id,
      destino_id: formSalida.value.destino_id || null,
      turno: formSalida.value.turno,
      detalles: formSalida.value.detalles,
    })
    showModalSalida.value = false
    await cargarDatosIniciales()
  } catch (err) {
    alert(mensajeError(err))
  } finally {
    submitting.value = false
  }
}

const eliminarIngreso = async (ing) => {
  if (!confirm(`Eliminar ingreso #${String(ing.id_ingreso).padStart(3, '0')}?`)) return
  await api.delete(`/ingresos/${ing.id_ingreso}`)
  await cargarDatosIniciales()
}

const eliminarSalida = async (sal) => {
  if (!confirm(`Eliminar salida ${sal.nro_informe || '#' + String(sal.id_salida).padStart(3, '0')}?`)) return
  await api.delete(`/salidas/${sal.id_salida}`)
  await cargarDatosIniciales()
}

const asegurarFacturaSalida = async (sal, extra = {}) => {
  if (sal.factura?.id_factura) return sal.factura
  const { data } = await api.post(`/facturas/desde-salida/${sal.id_salida}`, {
    metodo_pago: 'efectivo',
    estado_pago: 'pendiente',
    ...extra,
  })
  sal.factura = data
  return data
}

const descargarFacturaSalida = async (sal, formato = 'pdf') => {
  try {
    const factura = await asegurarFacturaSalida(sal)
    const response = await api.get(`/facturas/${factura.id_factura}/${formato}`, { responseType: 'blob' })
    const nombre = formato === 'ticket' ? `factura_ticket_${factura.numero_factura}.pdf` : `factura_${factura.numero_factura}.pdf`
    descargarPdfBlob(response.data, nombre)
    await cargarDatosIniciales()
  } catch (err) {
    alert(mensajeError(err))
  }
}

const enviarFacturaSalida = async (sal) => {
  try {
    const factura = await asegurarFacturaSalida(sal)
    const { data } = await api.post(`/facturas/${factura.id_factura}/enviar-correo`)
    alert(data.message || 'Proceso completado.')
    await cargarDatosIniciales()
  } catch (err) {
    alert(mensajeError(err))
  }
}

const exportarDistribucionesPDF = async () => {
  const response = await api.get('/reportes/distribuciones/pdf', { responseType: 'blob', params: paramsExportDistribuciones.value })
  descargarPdfBlob(response.data, 'reporte_distribuciones.pdf')
}

const imprimirDistribuciones = async () => {
  const response = await api.get('/reportes/distribuciones/pdf', { responseType: 'blob', params: paramsExportDistribuciones.value })
  imprimirPdfBlob(response.data, 'reporte_distribuciones')
}

const exportarDistribucionesExcel = async () => {
  const response = await api.get('/reportes/distribuciones/excel', { responseType: 'blob', params: paramsExportDistribuciones.value })
  descargarBlob(response.data, 'reporte_distribuciones.xlsx')
}

onMounted(cargarDatosIniciales)
</script>

<style scoped>
.modal-backdrop {
  background: rgba(17, 24, 39, 0.34);
  backdrop-filter: blur(12px) saturate(0.82);
}

.field {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  background: white;
}
.btn-primary {
  padding: 0.5rem 1.25rem;
  border-radius: 0.75rem;
  color: white;
  font-size: 0.875rem;
  font-weight: 700;
}
.btn-secondary {
  padding: 0.5rem 1.25rem;
  border-radius: 0.75rem;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 700;
}
</style>
