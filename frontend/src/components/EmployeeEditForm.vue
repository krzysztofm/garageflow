<script setup lang="ts">
import { reactive, ref } from 'vue'

import { api } from '@/lib/api'
import { getErrorMessage, getValidationErrors } from '@/lib/errors'
import type { ApiResponse, Employee } from '@/types/api'

const props = defineProps<{
  employee: Employee
}>()

const emit = defineEmits<{
  saved: [employee: Employee]
  cancel: []
}>()

const form = reactive({
  department: props.employee.department,
  position: props.employee.position,
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
    const response = await api.patch<ApiResponse<Employee>>(`/api/employees/${props.employee.id}`, {
      department: form.department.trim(),
      position: form.position.trim(),
    })

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
    <h2>Edit Employee Profile</h2>
    <p class="muted">{{ employee.user.name }}</p>

    <p v-if="errorMessage" class="message error" role="alert">
      {{ errorMessage }}
    </p>

    <form @submit.prevent="save">
      <div class="field">
        <label for="department">Department</label>
        <input
          id="department"
          v-model="form.department"
          required
          maxlength="100"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.department?.length)"
          aria-describedby="department-error"
        />
        <p id="department-error" class="field-error">
          {{ fieldErrors.department?.[0] }}
        </p>
      </div>

      <div class="field">
        <label for="position">Position</label>
        <input
          id="position"
          v-model="form.position"
          required
          maxlength="100"
          :disabled="saving"
          :aria-invalid="Boolean(fieldErrors.position?.length)"
          aria-describedby="position-error"
        />
        <p id="position-error" class="field-error">
          {{ fieldErrors.position?.[0] }}
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
