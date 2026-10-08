<script setup lang="ts">
import { reactive, ref } from 'vue'

import { api } from '@/lib/api'
import { getErrorMessage, getValidationErrors } from '@/lib/errors'
import type { ApiResponse, BusinessTrip, TripStatus } from '@/types/api'

const props = defineProps<{
  trip?: BusinessTrip
}>()

const emit = defineEmits<{
  saved: [trip: BusinessTrip]
  cancel: []
}>()

const form = reactive({
  start_date: props.trip?.start_date ?? '',
  end_date: props.trip?.end_date ?? '',
  destination: props.trip?.destination ?? '',
  description: props.trip?.description ?? '',
  status: (props.trip?.status ?? 'planned') as TripStatus,
})

const saving = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function save(): Promise<void> {
  if (saving.value) return

  saving.value = true
  errorMessage.value = ''
  fieldErrors.value = {}

  const payload = {
    start_date: form.start_date,
    end_date: form.end_date,
    destination: form.destination.trim(),
    description: form.description.trim() || null,
  }

  try {
    const response = props.trip
      ? await api.patch<ApiResponse<BusinessTrip>>(`/api/business-trips/${props.trip.id}`, {
          ...payload,
          status: form.status,
        })
      : await api.post<ApiResponse<BusinessTrip>>('/api/business-trips', payload)

    emit('saved', response.data.data)
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
    <h2>{{ trip ? 'Edit Business Trip' : 'New Business Trip' }}</h2>
    <p v-if="trip" class="muted">{{ trip.employee.user.name }}</p>

    <p v-if="errorMessage" class="message error" role="alert">
      {{ errorMessage }}
    </p>

    <form @submit.prevent="save">
      <div class="form-grid">
        <div class="field">
          <label for="trip-start">Start Date</label>
          <input
            id="trip-start"
            v-model="form.start_date"
            type="date"
            required
            :disabled="saving"
            :aria-invalid="Boolean(fieldErrors.start_date?.length)"
            aria-describedby="trip-start-error"
          />
          <p id="trip-start-error" class="field-error">
            {{ fieldErrors.start_date?.[0] }}
          </p>
        </div>

        <div class="field">
          <label for="trip-end">End Date</label>
          <input
            id="trip-end"
            v-model="form.end_date"
            type="date"
            required
            :min="form.start_date || undefined"
            :disabled="saving"
            :aria-invalid="Boolean(fieldErrors.end_date?.length)"
            aria-describedby="trip-end-error"
          />
          <p id="trip-end-error" class="field-error">
            {{ fieldErrors.end_date?.[0] }}
          </p>
        </div>
      </div>

      <div class="field">
        <label for="trip-destination">Destination</label>
        <input
          id="trip-destination"
          v-model="form.destination"
          required
          maxlength="150"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.destination?.length)"
          aria-describedby="trip-destination-error"
        />
        <p id="trip-destination-error" class="field-error">
          {{ fieldErrors.destination?.[0] }}
        </p>
      </div>

      <div class="field">
        <label for="trip-description">Description (optional)</label>
        <textarea
          id="trip-description"
          v-model="form.description"
          rows="3"
          maxlength="2000"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.description?.length)"
          aria-describedby="trip-description-error"
        ></textarea>
        <p id="trip-description-error" class="field-error">
          {{ fieldErrors.description?.[0] }}
        </p>
      </div>

      <div v-if="trip" class="field">
        <label for="trip-form-status">Status</label>
        <select
          id="trip-form-status"
          v-model="form.status"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.status?.length)"
          aria-describedby="trip-status-error"
        >
          <option value="planned">Planned</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <p id="trip-status-error" class="field-error">
          {{ fieldErrors.status?.[0] }}
        </p>
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="saving">
          {{ saving ? 'Saving…' : 'Save' }}
        </button>

        <button type="button" class="secondary" :disabled="saving" @click="emit('cancel')">
          Cancel
        </button>
      </div>
    </form>
  </section>
</template>
