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

// Modal states for confirmations
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedSightingId = ref(null)

function openApproveModal(id) {
  selectedSightingId.value = id
  showApproveModal.value = true
}

function openRejectModal(id) {
  selectedSightingId.value = id
  showRejectModal.value = true
}

function approveSighting() {
  router.post(`/admin/sighting-reports/${selectedSightingId.value}/status`, { 
    status: 'Approved' 
  }, {
    onSuccess: () => {
      showApproveModal.value = false
      selectedSightingId.value = null
      // Show success message
      alert('✅ Sighting report approved successfully!')
    },
    onError: (errors) => {
      console.error('Approval failed:', errors)
      alert('❌ Failed to approve sighting report. Please try again.')
    }
  })
}

function rejectSighting() {
  router.post(`/admin/sighting-reports/${selectedSightingId.value}/status`, { 
    status: 'Rejected' 
  }, {
    onSuccess: () => {
      showRejectModal.value = false
      selectedSightingId.value = null
      // Show success message
      alert('✅ Sighting report rejected successfully!')
    },
    onError: (errors) => {
      console.error('Rejection failed:', errors)
      alert('❌ Failed to reject sighting report. Please try again.')
    }
  })
}

function createProjectFromSighting(sightingId) {
  router.get(`/admin/sighting-reports/${sightingId}/create-project`)
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
                 
                 <!-- Pending Status Actions -->
                 <template v-if="row.status === 'Pending'">
                   <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(row.id)">
                     Approve
                   </button>
                   <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(row.id)">
                     Reject
                   </button>
                 </template>
                 
                 <!-- Approved Status Actions -->
                 <template v-if="row.status === 'Approved'">
                   <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(row.id)">
                     Approve
                   </button>
                   <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(row.id)">
                     Reject
                   </button>
                   <button 
                     @click="createProjectFromSighting(row.id)"
                     class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition-colors text-xs"
                   >
                     Create Project
                   </button>
                 </template>
                 
                 <!-- Rejected Status Actions -->
                 <template v-if="row.status === 'Rejected'">
                   <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(row.id)">
                  Approve
                </button>
                   <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(row.id)">
                  Reject
                </button>
                 </template>
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
      <div class="flex items-center space-x-4">
        <button
          :disabled="props.pagination.current_page <= 1"
          @click="router.get('/admin/sighting-reports', { page: props.pagination.current_page - 1, ...filters })"
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Previous
        </button>
        
        <button
          v-for="page in Math.ceil(props.pagination.total / props.pagination.per_page)"
          :key="page"
          @click="router.get('/admin/sighting-reports', { page, ...filters })"
          :class="[
            'text-sm px-3 py-1 rounded',
            page === props.pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
          ]"
        >
          {{ page }}
        </button>
        
        <button
          :disabled="props.pagination.current_page >= Math.ceil(props.pagination.total / props.pagination.per_page)"
          @click="router.get('/admin/sighting-reports', { page: props.pagination.current_page + 1, ...filters })"
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          Next
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <teleport to="body">
       <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
         <div class="bg-white rounded-2xl shadow-2xl w-[95%] max-w-6xl max-h-[90vh] overflow-y-auto">
           <!-- Header -->
           <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-t-2xl">
             <div class="flex justify-between items-center">
               <div class="flex items-center space-x-3">
                 <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                 </svg>
                 <h2 class="text-2xl font-bold">Sighting Report Details</h2>
               </div>
               <button 
                 class="text-white hover:text-gray-200 transition-colors p-2 rounded-full hover:bg-white/10"
                 @click="showModal=false"
               >
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                 </svg>
               </button>
             </div>
           </div>

           <div v-if="modalData" class="p-6 space-y-6">
             <!-- Sighting Information Card -->
             <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
               <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                 </svg>
                 Sighting Information
               </h3>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div class="bg-white rounded-lg p-4 border border-blue-200">
                   <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Location</div>
                   <div class="text-gray-900 font-medium">{{ modalData.location }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-blue-200">
                   <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Sighted At</div>
                   <div class="text-gray-900 font-medium">{{ formatDate(modalData.sighted_at) }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-blue-200">
                   <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Status</div>
                   <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getStatusColor(modalData.status)">
                     {{ modalData.status }}
                   </span>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-blue-200">
                   <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Report ID</div>
                   <div class="text-gray-900 font-medium">#{{ modalData.id }}</div>
                 </div>
               </div>
             </div>

             <!-- Reporter Information Card -->
             <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
               <h3 class="text-lg font-semibold text-green-900 mb-4 flex items-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                 </svg>
                 Reporter Information
               </h3>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div class="bg-white rounded-lg p-4 border border-green-200">
                   <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-1">Name</div>
                   <div class="text-gray-900 font-medium">{{ modalData.reporter_name }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-green-200">
                   <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-1">Phone</div>
                   <div class="text-gray-900 font-medium">{{ modalData.reporter_phone }}</div>
                 </div>
                 <div v-if="modalData.reporter_email" class="bg-white rounded-lg p-4 border border-green-200 md:col-span-2">
                   <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-1">Email</div>
                   <div class="text-gray-900 font-medium">{{ modalData.reporter_email }}</div>
                 </div>
               </div>
             </div>

             <!-- Description Card -->
             <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
               <h3 class="text-lg font-semibold text-purple-900 mb-4 flex items-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                 </svg>
                 Sighting Description
               </h3>
               <div class="bg-white rounded-lg p-4 border border-purple-200">
                 <div class="text-xs font-medium text-purple-600 uppercase tracking-wide mb-2">Details</div>
                 <div class="text-gray-900 leading-relaxed">
                   {{ modalData.description || 'No description provided' }}
                 </div>
               </div>
             </div>

             <!-- Photos Card -->
             <div v-if="modalData.photos && modalData.photos.length" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
               <h3 class="text-lg font-semibold text-orange-900 mb-4 flex items-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                 </svg>
                 Sighting Photos
               </h3>
               <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                 <div 
                   v-for="(photo, index) in modalData.photos" 
                   :key="index"
                   class="bg-white rounded-lg p-2 border border-orange-200 hover:shadow-lg transition-shadow"
                 >
                   <img 
                     :src="'/storage/' + photo" 
                     :alt="`Sighting photo ${index + 1}`"
                     class="w-full h-32 object-cover rounded-lg"
                   />
                   <div class="text-xs text-gray-500 text-center mt-2">Photo {{ index + 1 }}</div>
                 </div>
               </div>
             </div>

             <!-- Missing Person Information Card -->
             <div v-if="modalData.missing_person" class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
               <h3 class="text-lg font-semibold text-red-900 mb-4 flex items-center">
                 <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                 </svg>
                 Missing Person Information
               </h3>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div class="bg-white rounded-lg p-4 border border-red-200">
                   <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Name</div>
                   <div class="text-gray-900 font-medium">{{ modalData.missing_person.full_name }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-red-200">
                   <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Age/Gender</div>
                   <div class="text-gray-900 font-medium">{{ modalData.missing_person.age }}/{{ modalData.missing_person.gender }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-red-200">
                   <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Last Seen Location</div>
                   <div class="text-gray-900 font-medium">{{ modalData.missing_person.last_seen_location }}</div>
                 </div>
                 <div class="bg-white rounded-lg p-4 border border-red-200">
                   <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Last Seen Date</div>
                   <div class="text-gray-900 font-medium">{{ formatDate(modalData.missing_person.last_seen_date) }}</div>
                 </div>
               </div>
          </div>

             <!-- Action Buttons -->
             <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
               <button
                 @click="showModal=false"
                 class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
               >
                 Close
               </button>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Approve Confirmation Modal -->
    <div v-if="showApproveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Approve Sighting Report</h2>
            <button @click="showApproveModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <p class="text-gray-600 mb-6">Are you sure you want to approve this sighting report? This will make it visible to all users and help in the search for the missing person.</p>
          
          <div class="flex gap-3">
            <button 
              @click="approveSighting"
              class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors"
            >
              Approve Report
            </button>
            <button 
              @click="showApproveModal = false"
              class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Reject Sighting Report</h2>
            <button @click="showRejectModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <p class="text-gray-600 mb-6">Are you sure you want to reject this sighting report? This will notify the reporter and the report will not be visible to users.</p>
          
          <div class="flex gap-3">
            <button 
              @click="rejectSighting"
              class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors"
            >
              Reject Report
            </button>
            <button 
              @click="showRejectModal = false"
              class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


