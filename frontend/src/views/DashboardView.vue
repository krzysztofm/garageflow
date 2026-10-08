<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import type { ApiResponse, DashboardData } from '@/types/api'

const auth = useAuthStore()

const dashboard = ref<DashboardData | null>(null)
const loading = ref(false)
const errorMessage = ref('')

const stats = [
  { key: 'employees', label: 'Employees' },
  { key: 'pending_leaves', label: 'Pending Leave Requests' },
  { key: 'planned_trips', label: 'Planned Business Trips' },
  { key: 'absent_today', label: 'Absent Today' },
] as const

async function loadDashboard(): Promise<void> {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ''
  dashboard.value = null

  try {
    const response = await api.get<ApiResponse<DashboardData>>('/api/dashboard')
    dashboard.value = response.data.data
  } catch {
    errorMessage.value = 'Unable to load the dashboard. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="page-heading">
    <div>
      <h1>Overview</h1>
      <p class="muted">Welcome, {{ auth.user?.name }}</p>
    </div>

    <button type="button" class="secondary" :disabled="loading" @click="loadDashboard">
      {{ loading ? 'Loading…' : 'Refresh' }}
    </button>
  </div>

  <p v-if="loading" role="status">Loading data…</p>

  <p v-if="errorMessage" class="message error" role="alert">
    {{ errorMessage }}
  </p>

  <template v-if="dashboard">
    <div class="stats-grid">
      <article v-for="stat in stats" :key="stat.key" class="card stat-card">
        <p class="muted">{{ stat.label }}</p>
        <strong class="stat-value">
          {{ dashboard.counts[stat.key] }}
        </strong>
      </article>
    </div>

    <section class="card">
      <h2>Absent Today</h2>
      <p class="muted">{{ dashboard.date }}</p>

      <p v-if="dashboard.absent_employees.length === 0">No employees are absent today.</p>

      <template v-else>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th scope="col">Employee</th>
                <th scope="col">Department</th>
                <th scope="col">Position</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="employee in dashboard.absent_employees" :key="employee.id">
                <td>{{ employee.name }}</td>
                <td>{{ employee.department }}</td>
                <td>{{ employee.position }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-if="dashboard.counts.absent_today > dashboard.absent_employees.length" class="muted">
          Showing {{ dashboard.absent_employees.length }} of
          {{ dashboard.counts.absent_today }} employees.
        </p>
      </template>
    </section>
  </template>
</template>
