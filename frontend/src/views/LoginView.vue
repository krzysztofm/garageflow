<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { isAxiosError } from 'axios'

import { useAuthStore } from '@/stores/auth'

interface ValidationError {
  message?: string
  errors?: Record<string, string[]>
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('employee@garageflow.test')
const password = ref('password')
const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

const notice = computed(() => {
  if (errorMessage.value) return errorMessage.value

  if (route.query.reason === 'expired') {
    return 'Your session has expired. Please log in again.'
  }

  if (route.query.reason === 'connection') {
    return 'Unable to verify your session. Please check your server connection.'
  }

  return ''
})

async function submit(): Promise<void> {
  if (submitting.value) return

  submitting.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  try {
    await auth.login(email.value.trim(), password.value)
    await router.replace({ name: 'dashboard' })
  } catch (error) {
    if (isAxiosError<ValidationError>(error)) {
      const status = error.response?.status

      if (status === 422) {
        fieldErrors.value = error.response?.data.errors ?? {}
        errorMessage.value = 'Please check your email address and password.'
      } else if (status === 429) {
        errorMessage.value = 'Too many login attempts. Please wait a moment.'
      } else if (status === 419) {
        errorMessage.value = 'Your login session has expired. Please try again.'
      } else {
        errorMessage.value = 'Unable to log in. Please try again.'
      }
    } else {
      errorMessage.value = 'Something went wrong. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="card login-card">
    <p class="eyebrow">GarageFlow</p>
    <h1>Sign In</h1>
    <p class="muted">Manage employees, leave requests, and business trips in one place.</p>

    <p v-if="notice" class="message error" role="alert">
      {{ notice }}
    </p>

    <form @submit.prevent="submit">
      <div class="field">
        <label for="email">Email Address</label>
        <input
          id="email"
          v-model="email"
          type="email"
          list="demo-accounts"
          autocomplete="username"
          required
          :disabled="submitting"
          :aria-invalid="Boolean(fieldErrors.email?.length)"
          aria-describedby="email-error"
        />

        <datalist id="demo-accounts">
          <option value="employee@garageflow.test"></option>
          <option value="manager@garageflow.test"></option>
          <option value="admin@garageflow.test"></option>
        </datalist>

        <p id="email-error" class="field-error">
          {{ fieldErrors.email?.[0] }}
        </p>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          autocomplete="current-password"
          required
          :disabled="submitting"
          :aria-invalid="Boolean(fieldErrors.password?.length)"
          aria-describedby="password-error"
        />

        <p id="password-error" class="field-error">
          {{ fieldErrors.password?.[0] }}
        </p>
      </div>

      <button type="submit" :disabled="submitting">
        {{ submitting ? 'Signing In…' : 'Sign In' }}
      </button>
    </form>

    <div class="demo-info">
      <strong>Demo Accounts</strong>
      <p>employee@garageflow.test</p>
      <p>manager@garageflow.test</p>
      <p>admin@garageflow.test</p>
      <p>Password for all accounts: <code>password</code></p>
    </div>
  </section>
</template>
