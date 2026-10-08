<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import LeaveRequestForm from '@/components/LeaveRequestForm.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import { api } from '@/lib/api'
import { getErrorMessage } from '@/lib/errors'
import type { LeaveRequest, LeaveStatus, PaginatedResponse } from '@/types/api'

const requests = ref<PaginatedResponse<LeaveRequest> | null>(null)
const status = ref<LeaveStatus | ''>('')
const loading = ref(false)
const reviewing = ref<number | null>(null)
const showForm = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const controlsDisabled = computed(() => loading.value || reviewing.value !== null || showForm.value)

const statusLabels: Record<LeaveStatus, string> = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
}

async function loadRequests(page = 1): Promise<void> {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ''
  requests.value = null

  try {
    const response = await api.get<PaginatedResponse<LeaveRequest>>('/api/leave-requests', {
      params: {
        page,
        status: status.value || undefined,
      },
    })

    requests.value = response.data
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    loading.value = false
  }
}

async function handleCreated(): Promise<void> {
  showForm.value = false
  successMessage.value = 'Leave request submitted successfully.'
  status.value = ''
  await loadRequests()
}

async function review(request: LeaveRequest, decision: 'approved' | 'rejected'): Promise<void> {
  if (controlsDisabled.value) return

  reviewing.value = request.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.patch(`/api/leave-requests/${request.id}/review`, {
      status: decision,
    })

    successMessage.value =
      decision === 'approved' ? 'Leave request approved successfully.' : 'Leave request rejected.'

    await loadRequests()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    reviewing.value = null
  }
}

watch(status, () => {
  void loadRequests()
})

function openCreate(): void {
  if (controlsDisabled.value) return

  showForm.value = true
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => loadRequests())
</script>

<template>
  <div class="page-heading">
    <div>
      <h1>Leave Requests</h1>
      <p class="muted">Leave requests available on your account.</p>
    </div>

    <button v-if="!showForm" type="button" :disabled="controlsDisabled" @click="openCreate">
      New Leave Request
    </button>
  </div>

  <p v-if="successMessage" class="message success" role="status">
    {{ successMessage }}
  </p>

  <LeaveRequestForm v-if="showForm" @created="handleCreated" @cancel="showForm = false" />

  <div class="toolbar">
    <div>
      <label for="leave-status">Status</label>
      <select id="leave-status" v-model="status" :disabled="controlsDisabled">
        <option value="">All</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>

    <button
      type="button"
      class="secondary"
      :disabled="controlsDisabled"
      @click="loadRequests(requests?.meta.current_page ?? 1)"
    >
      Refresh
    </button>
  </div>

  <p v-if="loading" role="status">Loading data…</p>

  <p v-if="errorMessage" class="message error" role="alert">
    {{ errorMessage }}
  </p>

  <section v-if="requests" class="card">
    <p v-if="requests.data.length === 0">No leave requests match the selected criteria.</p>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th scope="col">Employee</th>
            <th scope="col">Dates</th>
            <th scope="col">Reason</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="request in requests.data" :key="request.id">
            <td>{{ request.employee.user.name }}</td>

            <td class="date-range">
              {{ request.start_date }}<br />
              {{ request.end_date }}
            </td>

            <td class="text-cell">{{ request.reason || '—' }}</td>

            <td>
              <span class="badge" :class="request.status">
                {{ statusLabels[request.status] }}
              </span>

              <div v-if="request.reviewer" class="muted">
                {{ request.reviewer.name }}
              </div>
            </td>

            <td>
              <div v-if="request.can_review" class="row-actions">
                <button
                  type="button"
                  :disabled="controlsDisabled"
                  @click="review(request, 'approved')"
                >
                  Approve
                </button>

                <button
                  type="button"
                  class="danger"
                  :disabled="controlsDisabled"
                  @click="review(request, 'rejected')"
                >
                  Reject
                </button>
              </div>

              <span v-else class="muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationControls :meta="requests.meta" :disabled="controlsDisabled" @change="loadRequests" />
  </section>
</template>
