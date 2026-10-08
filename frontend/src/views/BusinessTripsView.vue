<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import BusinessTripForm from '@/components/BusinessTripForm.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import { api } from '@/lib/api'
import { getErrorMessage } from '@/lib/errors'
import type { BusinessTrip, PaginatedResponse, TripStatus } from '@/types/api'

const trips = ref<PaginatedResponse<BusinessTrip> | null>(null)
const status = ref<TripStatus | ''>('')
const loading = ref(false)
const showForm = ref(false)
const editing = ref<BusinessTrip | null>(null)
const errorMessage = ref('')
const successMessage = ref('')

const controlsDisabled = computed(() => loading.value || showForm.value)

const statusLabels: Record<TripStatus, string> = {
  planned: 'Planned',
  completed: 'Completed',
  cancelled: 'Cancelled',
}

async function loadTrips(page = 1): Promise<void> {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ''
  trips.value = null

  try {
    const response = await api.get<PaginatedResponse<BusinessTrip>>('/api/business-trips', {
      params: {
        page,
        status: status.value || undefined,
      },
    })

    trips.value = response.data
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    loading.value = false
  }
}

function openForm(trip: BusinessTrip | null = null): void {
  if (controlsDisabled.value) return

  editing.value = trip
  showForm.value = true
  successMessage.value = ''
  errorMessage.value = ''
}

function closeForm(): void {
  showForm.value = false
  editing.value = null
}

async function handleSaved(): Promise<void> {
  const wasEditing = editing.value !== null

  closeForm()

  successMessage.value = wasEditing
    ? 'Business trip updated successfully.'
    : 'Business trip added successfully.'

  if (!wasEditing) {
    status.value = ''
  }

  await loadTrips()
}

watch(status, () => {
  void loadTrips()
})

onMounted(() => loadTrips())
</script>

<template>
  <div class="page-heading">
    <div>
      <h1>Business Trips</h1>
      <p class="muted">Business trips available on your account.</p>
    </div>

    <button v-if="!showForm" type="button" :disabled="controlsDisabled" @click="openForm()">
      New Business Trip
    </button>
  </div>

  <p v-if="successMessage" class="message success" role="status">
    {{ successMessage }}
  </p>

  <BusinessTripForm
    v-if="showForm"
    :key="editing?.id ?? 'new'"
    :trip="editing ?? undefined"
    @saved="handleSaved"
    @cancel="closeForm"
  />

  <div class="toolbar">
    <div>
      <label for="trip-filter-status">Status</label>
      <select id="trip-filter-status" v-model="status" :disabled="controlsDisabled">
        <option value="">All</option>
        <option value="planned">Planned</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <button
      type="button"
      class="secondary"
      :disabled="controlsDisabled"
      @click="loadTrips(trips?.meta.current_page ?? 1)"
    >
      Refresh
    </button>
  </div>

  <p v-if="loading" role="status">Loading data…</p>

  <p v-if="errorMessage" class="message error" role="alert">
    {{ errorMessage }}
  </p>

  <section v-if="trips" class="card">
    <p v-if="trips.data.length === 0">No business trips match the selected criteria.</p>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th scope="col">Employee</th>
            <th scope="col">Dates</th>
            <th scope="col">Destination</th>
            <th scope="col">Description</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="trip in trips.data" :key="trip.id">
            <td>{{ trip.employee.user.name }}</td>

            <td class="date-range">
              {{ trip.start_date }}<br />
              {{ trip.end_date }}
            </td>

            <td class="text-cell">{{ trip.destination }}</td>
            <td class="text-cell">{{ trip.description || '—' }}</td>

            <td>
              <span class="badge" :class="trip.status">
                {{ statusLabels[trip.status] }}
              </span>
            </td>

            <td>
              <button
                v-if="trip.can_update"
                type="button"
                class="secondary"
                :disabled="controlsDisabled"
                @click="openForm(trip)"
              >
                Edit
              </button>

              <span v-else class="muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationControls :meta="trips.meta" :disabled="controlsDisabled" @change="loadTrips" />
  </section>
</template>
