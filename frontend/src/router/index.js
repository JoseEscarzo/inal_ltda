import { createRouter, createWebHistory } from 'vue-router'
import ListaLentes from '@/views/ListaLentes.vue'
import ListaCristales from '@/views/ListaCristales.vue'
import LoginView from '@/views/Login/LoginView.vue'
import { useAuthStore } from '@/stores/auth'

const roleHomePaths = {
  1: '/dashboard',
  2: '/inventario',
  3: '/monitoreo',
  5: '/trazabilidad',
}

const getRoleHomePath = (user) => {
  return roleHomePaths[Number(user?.id_rol)] || '/productos'
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: LoginView
    },

    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/Dashboard/DashboardView.vue'),
      meta: { requiresAuth: true }
    },

    {
      path: '/productos',
      name: 'productos',
      component: () => import('@/views/Producto/ProductoView.vue'),
      meta: { requiresAuth: true }
    },

    {
      path: '/cristales',
      name: 'cristales',
      component: ListaCristales,
      meta: { requiresAuth: true }
    },
    {
      path: '/usuarios',
      name: 'usuarios',
      component: () => import('@/views/ListaUsuarios.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/monitoreo',
      name: 'monitoreo',
      component: () => import('@/views/Monitoreo/MonitoreoView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/distribuciones',
      name: 'distribuciones',
      component: () => import('@/views/Distribuciones/DistribucionesView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/trazabilidad',
      name: 'trazabilidad',
      component: () => import('@/views/Trazabilidad/TrazabilidadView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/lotes',
      name: 'lotes',
      component: () => import('@/views/Lotes/LoteView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/produccion',
      name: 'produccion',
      component: () => import('@/views/LineaProduccion/LineaProduccionView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/inventario',
      name: 'inventario',
      component: () => import('@/views/InventarioProducto/InventarioProductoView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/destinos',
      name: 'destinos',
      component: () => import('@/views/Destinos/DestinosView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/proveedores',
      name: 'proveedores',
      component: () => import('@/views/Proveedores/ProveedoresView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'productos' }
    }
  ],
})

router.beforeEach((to, from) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.token) {
    return { name: 'login' }
  }

  else if (to.name === 'login' && authStore.token) {
    return getRoleHomePath(authStore.user)
  }

})


export default router
