<script setup lang="ts">
import { reactive, ref } from 'vue'

import { api } from '@/lib/api'
import { getErrorMessage, getValidationErrors } from '@/lib/errors'

const emit = defineEmits<{
  created: []
  cancel: []
}>()

const form = reactive({
  start_date: '',
  end_date: '',
  reason: '',
})

const saving = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function save(): Promise<void> {
  if (saving.value) return

  saving.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  try {
    await api.post('/api/leave-requests', {
      start_date: form.start_date,
      end_date: form.end_date,
      reason: form.reason.trim() || null,
    })

    emit('created')
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
    fieldErrors.value = getValidationErrors(error)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="card edit-card">
    <h2>New Leave Request</h2>

    <p v-if="errorMessage" class="message error" role="alert">
      {{ errorMessage }}
    </p>

    <form @submit.prevent="save">
      <div class="form-grid">
        <div class="field">
          <label for="leave-start">Start Date</label>
          <input
            id="leave-start"
            v-model="form.start_date"
            type="date"
            required
            :disabled="saving"
            :aria-invalid="Boolean(fieldErrors.start_date?.length)"
            aria-describedby="leave-start-error"
          />
          <p id="leave-start-error" class="field-error">
            {{ fieldErrors.start_date?.[0] }}
          </p>
        </div>

        <div class="field">
          <label for="leave-end">End Date</label>
          <input
            id="leave-end"
            v-model="form.end_date"
            type="date"
            required
            :min="form.start_date || undefined"
            :disabled="saving"
            :aria-invalid="Boolean(fieldErrors.end_date?.length)"
            aria-describedby="leave-end-error"
          />
          <p id="leave-end-error" class="field-error">
            {{ fieldErrors.end_date?.[0] }}
          </p>
        </div>
      </div>

      <div class="field">
        <label for="leave-reason">Reason (optional)</label>
        <textarea
          id="leave-reason"
          v-model="form.reason"
          rows="3"
          maxlength="2000"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.reason?.length)"
          aria-describedby="leave-reason-error"
        ></textarea>
        <p id="leave-reason-error" class="field-error">
          {{ fieldErrors.reason?.[0] }}
        </p>
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="saving">
          {{ saving ? 'Submitting…' : 'Submit Request' }}
        </button>

        <button type="button" class="secondary" :disabled="saving" @click="emit('cancel')">
          Cancel
        </button>
      </div>
    </form>
  </section>
</template>
