<script setup lang="ts">
import type { PaginationMeta } from '@/types/api'

defineProps<{
  meta: PaginationMeta
  disabled: boolean
}>()

const emit = defineEmits<{
  change: [page: number]
}>()
</script>

<template>
  <div class="pagination">
    <span class="muted">
      Page {{ meta.current_page }} of {{ meta.last_page }} · Total: {{ meta.total }}
    </span>

    <div class="pagination-actions">
      <button
        type="button"
        class="secondary"
        :disabled="disabled || meta.current_page <= 1"
        @click="emit('change', meta.current_page - 1)"
      >
        Previous
      </button>

      <button
        type="button"
        class="secondary"
        :disabled="disabled || meta.current_page >= meta.last_page"
        @click="emit('change', meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>
