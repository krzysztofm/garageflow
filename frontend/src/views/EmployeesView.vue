<script setup lang="ts">
import { onMounted, ref } from 'vue'

import PaginationControls from '@/components/PaginationControls.vue'
import EmployeeEditForm from '@/components/EmployeeEditForm.vue'

import { api } from '@/lib/api'
import type { Employee, PaginatedResponse } from '@/types/api'

const employees = ref<PaginatedResponse<Employee> | null>(null)
const loading = ref(false)
const errorMessage = ref('')

async function loadEmployees(page = 1): Promise<void> {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get<PaginatedResponse<Employee>>('/api/employees', {
      params: { page },
    })

    employees.value = response.data
  } catch {
    errorMessage.value = 'Unable to load employees. Please try again.'
  } finally {
    loading.value = false
  }
}

const editing = ref<Employee | null>(null)
const successMessage = ref('')

function handleSaved(employee: Employee): void {
  if (employees.value) {
    employees.value.data = employees.value.data.map((item) =>
      item.id === employee.id ? employee : item,
    )
  }

  editing.value = null
  successMessage.value = 'Employee profile saved successfully.'
}

function openEdit(employee: Employee): void {
  if (loading.value || editing.value !== null) return

  editing.value = employee
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => loadEmployees())
</script>

<template>
  <div class="page-heading">
    <div>
      <h1>Employees</h1>
      <p class="muted">Employee profiles available on your account.</p>
    </div>

    <button
      type="button"
      class="secondary"
      :disabled="loading || editing !== null"
      @click="loadEmployees(employees?.meta.current_page ?? 1)"
    >
      {{ loading ? 'Loading…' : 'Refresh' }}
    </button>
  </div>

  <p v-if="successMessage" class="message success" role="status">
    {{ successMessage }}
  </p>

  <EmployeeEditForm
    v-if="editing"
    :key="editing.id"
    :employee="editing"
    @saved="handleSaved"
    @cancel="editing = null"
  />

  <p v-if="loading" role="status">Loading data…</p>

  <p v-if="errorMessage" class="message error" role="alert">
    {{ errorMessage }}
  </p>

  <section v-if="employees" class="card">
    <p v-if="employees.data.length === 0">No employees to display.</p>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th scope="col">Employee</th>
            <th scope="col">Department</th>
            <th scope="col">Position</th>
            <th scope="col">Manager</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="employee in employees.data" :key="employee.id">
            <td>
              <strong>{{ employee.user.name }}</strong>
              <div class="muted">{{ employee.user.email }}</div>
            </td>
            <td>{{ employee.department }}</td>
            <td>{{ employee.position }}</td>
            <td>{{ employee.manager?.name ?? '—' }}</td>
            <td>
              <button
                v-if="employee.can_update"
                type="button"
                class="secondary"
                :disabled="loading || editing !== null"
                @click="openEdit(employee)"
              >
                Edit
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationControls :meta="employees.meta" :disabled="loading" @change="loadEmployees" />
  </section>
</template>
