import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { isAxiosError } from 'axios'

import App from './App.vue'
import router from './router'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)

app.use(createPinia())

const auth = useAuthStore()

api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (
      isAxiosError(error) &&
      [401, 419].includes(error.response?.status ?? 0) &&
      error.config?.url?.startsWith('/api/') &&
      error.config.url !== '/api/login' &&
      auth.isAuthenticated
    ) {
      auth.clearSession()
      void router.replace({
        name: 'login',
        query: { reason: 'expired' },
      })
    }

    return Promise.reject(error)
  },
)

app.use(router)
app.mount('#app')
