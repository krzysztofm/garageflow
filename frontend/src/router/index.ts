import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/employees',
      name: 'employees',
      component: () => import('@/views/EmployeesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/leave-requests',
      name: 'leave-requests',
      component: () => import('@/views/LeaveRequestsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/business-trips',
      name: 'business-trips',
      component: () => import('@/views/BusinessTripsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  try {
    await auth.initialize()
  } catch {
    if (to.name === 'login') {
      return true
    }

    return {
      name: 'login',
      query: { reason: 'connection' },
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
