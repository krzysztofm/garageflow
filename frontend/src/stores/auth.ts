import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { isAxiosError } from 'axios'

import { api } from '@/lib/api'
import type { ApiResponse, User } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const initialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  let initialization: Promise<void> | null = null

  function clearSession(): void {
    user.value = null
    initialized.value = true
  }

  async function fetchUser(): Promise<void> {
    try {
      const response = await api.get<ApiResponse<User>>('/api/me')

      user.value = response.data.data
      initialized.value = true
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 401) {
        clearSession()
        return
      }

      throw error
    }
  }

  async function initialize(): Promise<void> {
    if (initialized.value) {
      return
    }

    if (!initialization) {
      initialization = fetchUser().finally(() => {
        initialization = null
      })
    }

    await initialization
  }

  async function login(email: string, password: string): Promise<void> {
    await api.get('/sanctum/csrf-cookie')

    const response = await api.post<ApiResponse<User>>('/api/login', {
      email,
      password,
    })

    user.value = response.data.data
    initialized.value = true
  }

  async function logout(): Promise<void> {
    try {
      await api.get('/sanctum/csrf-cookie')
      await api.post('/api/logout')
    } catch (error) {
      if (!isAxiosError(error) || error.response?.status !== 401) {
        throw error
      }
    }

    clearSession()
  }

  return {
    user,
    initialized,
    isAuthenticated,
    initialize,
    fetchUser,
    login,
    logout,
    clearSession,
  }
})
