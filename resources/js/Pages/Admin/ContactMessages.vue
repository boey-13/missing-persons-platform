<script setup>
import { ref, computed, watch } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  messages: Array,
  pagination: Object,
  filters: Object
})

const { success, error } = useToast()
const page = usePage()

// Watch for flash messages
watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    success(flash.success)
  }
  if (flash?.error) {
    error(flash.error)
  }
}, { immediate: true })

// Filters
const statusFilter = ref(props.filters?.status || '')
const searchFilter = ref(props.filters?.search || '')

// Modal states
const showViewModal = ref(false)
const showReplyModal = ref(false)
const selectedMessage = ref(null)
const replyForm = ref({
  subject: '',
  message: ''
})

// Loading states
const isSendingReply = ref(false)
const isUpdatingStatus = ref({})

// Functions
function filterByStatus() {
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

function viewMessage(message) {
  selectedMessage.value = message
  showViewModal.value = true
}

function openReplyModal(message) {
  selectedMessage.value = message
  replyForm.value.subject = `Re: ${message.subject}`
  replyForm.value.message = ''
  showReplyModal.value = true
}

function sendReply() {
  if (!replyForm.value.subject || !replyForm.value.message) {
    error('Please fill in both subject and message')
    return
  }

  isSendingReply.value = true

  router.post(`/admin/contact-messages/${selectedMessage.value.id}/reply`, {
    subject: replyForm.value.subject,
    message: replyForm.value.message
  }, {
    onSuccess: () => {
      showReplyModal.value = false
      selectedMessage.value = null
      replyForm.value = { subject: '', message: '' }
      isSendingReply.value = false
    },
    onError: () => {
      // Error will be handled by flash message
      isSendingReply.value = false
    }
  })
}

function updateStatus(messageId, newStatus) {
  isUpdatingStatus.value[messageId] = true

  router.post(`/admin/contact-messages/${messageId}/status`, {
    status: newStatus
  }, {
    onSuccess: () => {
      isUpdatingStatus.value[messageId] = false
    },
    onError: () => {
      error('Failed to update message status')
      isUpdatingStatus.value[messageId] = false
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
    closed: 'bg-gray-100 text-gray-800'
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
              <option value="closed">Closed</option>
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
                    <!-- Reply indicator -->
                    <div v-if="message.admin_reply" class="flex items-center text-green-600" title="Replied by {{ message.admin_replied_by?.name || 'Admin' }}">
                      <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                      <span class="text-xs">Replied</span>
                    </div>
                    <button
                      v-if="message.status === 'unread'"
                      @click="updateStatus(message.id, 'read')"
                      :disabled="isUpdatingStatus[message.id]"
                      class="text-blue-600 hover:text-blue-900 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="isUpdatingStatus[message.id]" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating...
                      </span>
                      <span v-else>Mark Read</span>
                    </button>
                    <button
                      @click="viewMessage(message)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      View
                    </button>
                    <button
                      v-if="message.status !== 'replied' && message.status !== 'closed'"
                      @click="openReplyModal(message)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Reply
                    </button>
                    <button
                      @click="updateStatus(message.id, 'closed')"
                      :disabled="isUpdatingStatus[message.id]"
                      class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="isUpdatingStatus[message.id]" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating...
                      </span>
                      <span v-else>Close Case</span>
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

    <!-- View Message Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">View Message</h3>
          <button @click="showViewModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedMessage" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">From:</label>
            <p class="text-sm text-gray-900">{{ selectedMessage.name }} ({{ selectedMessage.email }})</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Subject:</label>
            <p class="text-sm text-gray-900">{{ selectedMessage.subject }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Message:</label>
            <div class="mt-1 p-3 bg-gray-50 rounded-md">
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ selectedMessage.message }}</p>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Date:</label>
            <p class="text-sm text-gray-900">{{ formatDate(selectedMessage.created_at) }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Status:</label>
            <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(selectedMessage.status)}`">
              {{ selectedMessage.status }}
            </span>
          </div>
          
          <!-- Admin Reply Section -->
          <div v-if="selectedMessage.admin_reply" class="border-t pt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Admin Reply</h4>
            <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <span class="text-sm font-medium text-blue-900">Subject:</span>
                  <span class="text-sm text-blue-800 ml-1">{{ selectedMessage.admin_reply_subject }}</span>
                </div>
                <div class="text-xs text-blue-600">
                  {{ formatDate(selectedMessage.admin_replied_at) }}
                </div>
              </div>
              <div class="mb-2">
                <span class="text-sm font-medium text-blue-900">Replied by:</span>
                <span class="text-sm text-blue-800 ml-1">{{ selectedMessage.admin_replied_by?.name || 'Unknown Admin' }}</span>
              </div>
              <div>
                <span class="text-sm font-medium text-blue-900">Message:</span>
                <div class="mt-1 text-sm text-blue-800 whitespace-pre-wrap">{{ selectedMessage.admin_reply }}</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex justify-end mt-6 space-x-3">
          <button
            @click="showViewModal = false"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
          >
            Close
          </button>
          <button
            v-if="selectedMessage && selectedMessage.status !== 'replied' && selectedMessage.status !== 'closed'"
            @click="openReplyModal(selectedMessage)"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
          >
            Reply
          </button>
        </div>
      </div>
    </div>

    <!-- Reply Modal -->
    <div v-if="showReplyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Reply to Message</h3>
          <button @click="showReplyModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        
        <div v-if="selectedMessage" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">To:</label>
            <p class="text-sm text-gray-900">{{ selectedMessage.name }} ({{ selectedMessage.email }})</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Original Subject:</label>
            <p class="text-sm text-gray-900">{{ selectedMessage.subject }}</p>
          </div>
          
          <div>
            <label for="reply-subject" class="block text-sm font-medium text-gray-700">Subject:</label>
            <input
              id="reply-subject"
              v-model="replyForm.subject"
              type="text"
              class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          
          <div>
            <label for="reply-message" class="block text-sm font-medium text-gray-700">Message:</label>
            <textarea
              id="reply-message"
              v-model="replyForm.message"
              rows="6"
              class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Type your reply message here..."
            ></textarea>
          </div>
        </div>
        
        <div class="flex justify-end mt-6 space-x-3">
          <button
            @click="showReplyModal = false"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
          >
            Cancel
          </button>
          <button
            @click="sendReply"
            :disabled="isSendingReply"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSendingReply" class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sending...
            </span>
            <span v-else>Send Reply</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
