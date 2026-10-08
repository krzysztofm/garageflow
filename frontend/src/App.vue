<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const loggingOut = ref(false)
const logoutError = ref('')

async function logout(): Promise<void> {
  if (loggingOut.value) return

  loggingOut.value = true
  logoutError.value = ''

  try {
    await auth.logout()
    await router.replace({ name: 'login' })
  } catch {
    logoutError.value = 'Unable to log out. Please try again.'
  } finally {
    loggingOut.value = false
  }
}
</script>

<template>
  <header v-if="auth.isAuthenticated" class="header">
    <RouterLink to="/" class="brand">GarageFlow</RouterLink>

    <nav class="navigation" aria-label="Main navigation">
      <RouterLink to="/" exact-active-class="active">Overview</RouterLink>
      <RouterLink to="/employees" active-class="active">Employees</RouterLink>
      <RouterLink to="/leave-requests" active-class="active">Leave Requests</RouterLink>
      <RouterLink to="/business-trips" active-class="active">Business Trips</RouterLink>
    </nav>

    <div class="header-actions">
      <span>{{ auth.user?.name }}</span>

      <button type="button" class="secondary" :disabled="loggingOut" @click="logout">
        {{ loggingOut ? 'Logging Out…' : 'Log Out' }}
      </button>
    </div>
  </header>

  <main class="container">
    <p v-if="logoutError" class="message error" role="alert">
      {{ logoutError }}
    </p>

    <RouterView />
  </main>
</template>
