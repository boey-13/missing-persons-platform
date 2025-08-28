<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  messages: Array,
  pagination: Object,
  filters: Object
})

const { success, error } = useToast()

// Filters
const statusFilter = ref(props.filters?.status || '')
const searchFilter = ref(props.filters?.search || '')

// Functions
function filterByStatus(status) {
  statusFilter.value = status
  applyFilters()
}

function searchMessages() {
  applyFilters()
}

function clearFilters() {
  statusFilter.value = ''
  searchFilter.value = ''
  applyFilters()
}

function applyFilters() {
  const params = {}
  if (statusFilter.value) params.status = statusFilter.value
  if (searchFilter.value) params.search = searchFilter.value
  
  router.get('/admin/contact-messages', params, { preserveState: true })
}

function updateStatus(messageId, newStatus) {
  router.post(`/admin/contact-messages/${messageId}/status`, {
    status: newStatus
  }, {
    onSuccess: () => {
      success('Message status updated successfully')
    },
    onError: () => {
      error('Failed to update message status')
    }
  })
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getStatusColor(status) {
  const colors = {
    unread: 'bg-red-100 text-red-800',
    read: 'bg-blue-100 text-blue-800',
    replied: 'bg-green-100 text-green-800',
    archived: 'bg-gray-100 text-gray-800'
  }
  return colors[status] || colors.unread
}

function goToPage(page) {
  const params = { 
    status: statusFilter.value,
    search: searchFilter.value,
    page
  }
  router.get('/admin/contact-messages', params, { preserveState: true })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Contact Messages</h1>
            <p class="text-gray-600 mt-1">Manage user contact form submissions</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input
              v-model="searchFilter"
              @keyup.enter="searchMessages"
              type="text"
              placeholder="Search by name, email, or subject..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="statusFilter"
              @change="filterByStatus"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">All Status</option>
              <option value="unread">Unread</option>
              <option value="read">Read</option>
              <option value="replied">Replied</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <!-- Clear Filters -->
          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-md font-medium hover:bg-gray-200 transition-colors"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sender
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Subject
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="message in messages" :key="message.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ message.name }}</div>
                    <div class="text-sm text-gray-500">{{ message.email }}</div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 max-w-xs truncate">{{ message.subject }}</div>
                  <div class="text-sm text-gray-500 max-w-xs truncate">{{ message.message }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(message.status)}`">
                    {{ message.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(message.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <button
                      v-if="message.status === 'unread'"
                      @click="updateStatus(message.id, 'read')"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Mark Read
                    </button>
                    <button
                      v-if="message.status === 'read'"
                      @click="updateStatus(message.id, 'replied')"
                      class="text-green-600 hover:text-green-900"
                    >
                      Mark Replied
                    </button>
                    <button
                      @click="updateStatus(message.id, 'archived')"
                      class="text-gray-600 hover:text-gray-900"
                    >
                      Archive
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="messages.length === 0" class="text-center py-12">
          <div class="text-5xl mb-4">📧</div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No contact messages found</h3>
          <p class="text-gray-600">No messages match your current filters.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.total > pagination.per_page" class="mt-6 flex justify-center">
        <nav class="inline-flex items-center gap-1">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-300 rounded-l-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white"
          >
            Prev
          </button>

          <template v-for="page in Math.ceil(pagination.total / pagination.per_page)" :key="page">
            <button
              v-if="page === 1 || page === Math.ceil(pagination.total / pagination.per_page) || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 border border-gray-300 text-sm',
                page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <span
              v-else-if="page === pagination.current_page - 2 || page === pagination.current_page + 2"
              class="px-2 text-gray-400"
            >…</span>
          </template>

          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= Math.ceil(pagination.total / pagination.per_page)"
            class="px-3 py-2 border border-gray-300 rounded-r-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
