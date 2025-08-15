<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
defineOptions({ layout: AdminLayout })

const props = defineProps({ 
  items: Array, 
  pagination: Object,
  filters: Object
})

const showModal = ref(false)
const modalData = ref(null)

// Filter states
const statusFilter = ref(props.filters?.status || '')
const searchFilter = ref(props.filters?.search || '')
const missingReportFilter = ref(props.filters?.missing_report_id || '')

async function openDetail(id) {
  const res = await fetch(`/admin/sighting-reports/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
  modalData.value = await res.json()
  showModal.value = true
}

function changeStatus(id, status) {
  router.post(`/admin/sighting-reports/${id}/status`, { status })
}

function applyFilters() {
  const params = {}
  if (statusFilter.value) params.status = statusFilter.value
  if (searchFilter.value) params.search = searchFilter.value
  if (missingReportFilter.value) params.missing_report_id = missingReportFilter.value
  
  router.get('/admin/sighting-reports', params)
}

function clearFilters() {
  statusFilter.value = ''
  searchFilter.value = ''
  missingReportFilter.value = ''
  router.get('/admin/sighting-reports')
}

function getStatusColor(status) {
  const colors = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Approved': 'bg-green-100 text-green-800',
    'Rejected': 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Manage Sighting Reports</h1>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select v-model="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search Location</label>
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Search by location..."
            class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div class="flex gap-2">
          <button @click="applyFilters" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Apply Filters
          </button>
          <button @click="clearFilters" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Results Info -->
    <div v-if="filters?.missing_report_id" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-blue-800 font-medium">
          Showing sighting reports for Missing Person ID: {{ filters.missing_report_id }}
        </span>
        <button 
          @click="clearFilters" 
          class="ml-auto text-blue-600 hover:text-blue-800 underline"
        >
          Show All Reports
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Missing Person</th>
            <th class="px-4 py-3 text-left">Location</th>
            <th class="px-4 py-3 text-left">Sighted At</th>
            <th class="px-4 py-3 text-left">Reporter</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in props.items" :key="row.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">#{{ row.id }}</td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ row.missing_person_name }}</div>
              <div class="text-xs text-gray-500">ID: {{ row.missing_report_id }}</div>
            </td>
            <td class="px-4 py-3">{{ row.location }}</td>
            <td class="px-4 py-3">{{ formatDate(row.sighted_at) }}</td>
            <td class="px-4 py-3">{{ row.reporter }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(row.status)">
                {{ row.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs" @click="openDetail(row.id)">
                  View
                </button>
                <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="changeStatus(row.id, 'Approved')">
                  Approve
                </button>
                <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="changeStatus(row.id, 'Rejected')">
                  Reject
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="!props.items || props.items.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No sighting reports found</h3>
        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="props.pagination && props.pagination.total > props.pagination.per_page" class="mt-6 flex justify-center">
      <div class="flex space-x-2">
        <button
          v-for="page in Math.ceil(props.pagination.total / props.pagination.per_page)"
          :key="page"
          @click="router.get('/admin/sighting-reports', { page, ...filters })"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            page === props.pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Sighting Detail</h2>
            <button class="text-gray-500 hover:text-black" @click="showModal=false">✕</button>
          </div>
          <div v-if="modalData" class="space-y-3">
            <div><strong>Location:</strong> {{ modalData.location }}</div>
            <div><strong>Sighted At:</strong> {{ formatDate(modalData.sighted_at) }}</div>
            <div><strong>Reporter:</strong> {{ modalData.reporter_name }} ({{ modalData.reporter_phone }})</div>
            <div v-if="modalData.reporter_email"><strong>Email:</strong> {{ modalData.reporter_email }}</div>
            <div><strong>Status:</strong> {{ modalData.status }}</div>
            <div><strong>Description:</strong><br/> <span class="text-gray-700">{{ modalData.description || '-' }}</span></div>
            <div v-if="modalData.photos && modalData.photos.length" class="mt-2 flex gap-2 flex-wrap">
              <img v-for="(p,i) in modalData.photos" :key="i" :src="'/storage/' + p" class="w-28 h-28 object-cover rounded border" />
            </div>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>


