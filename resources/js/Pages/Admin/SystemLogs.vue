<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ 
  logs: Array, 
  pagination: Object,
  filters: Object 
})

const filters = ref({
  user_id: props.filters.current.user_id || '',
  action: props.filters.current.action || '',
  date_from: props.filters.current.date_from || '',
  date_to: props.filters.current.date_to || '',
  search: props.filters.current.search || '',
})

function applyFilters() {
  router.get('/admin/logs', { ...filters.value, page: 1 }, { preserveState: true })
}

function clearFilters() {
  filters.value = {
    user_id: '',
    action: '',
    date_from: '',
    date_to: '',
    search: '',
  }
  applyFilters()
}

function getActionColor(action) {
  const colors = {
    'user_login': 'bg-green-100 text-green-800',
    'user_logout': 'bg-blue-100 text-blue-800',
    'login_failed': 'bg-red-100 text-red-800',
    'report_created': 'bg-purple-100 text-purple-800',
    'sighting_submitted': 'bg-orange-100 text-orange-800',
    'sighting_approved': 'bg-green-100 text-green-800',
    'sighting_rejected': 'bg-red-100 text-red-800',
    'user_role_changed': 'bg-yellow-100 text-yellow-800',
    'user_deleted': 'bg-red-100 text-red-800',
  }
  return colors[action] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">System Logs</h1>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Filters</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Search</label>
          <input v-model="filters.search" type="text" placeholder="Search in descriptions..." 
                 class="w-full border rounded px-3 py-2" @keyup.enter="applyFilters" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Action Type</label>
          <select v-model="filters.action" class="w-full border rounded px-3 py-2">
            <option value="">All Actions</option>
            <option v-for="action in props.filters.actions" :key="action" :value="action">
              {{ action.replace('_', ' ').toUpperCase() }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Date From</label>
          <input v-model="filters.date_from" type="date" class="w-full border rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Date To</label>
          <input v-model="filters.date_to" type="date" class="w-full border rounded px-3 py-2" />
        </div>
        <div class="flex items-end gap-2">
          <button @click="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Apply Filters
          </button>
          <button @click="clearFilters" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">Time</th>
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left">Action</th>
            <th class="px-4 py-3 text-left">Description</th>
            <th class="px-4 py-3 text-left">IP Address</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in props.logs" :key="log.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-600">{{ new Date(log.created_at).toLocaleString() }}</td>
            <td class="px-4 py-3">
              <div v-if="log.user">
                <div class="font-medium">{{ log.user.name }}</div>
                <div class="text-xs text-gray-500">{{ log.user.email }}</div>
                <span class="inline-block px-2 py-1 text-xs rounded bg-gray-100">{{ log.user.role }}</span>
              </div>
              <span v-else class="text-gray-400">Guest</span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs" :class="getActionColor(log.action)">
                {{ log.action.replace('_', ' ').toUpperCase() }}
              </span>
            </td>
            <td class="px-4 py-3 max-w-md">
              <div class="text-gray-800">{{ log.description }}</div>
              <div v-if="log.metadata" class="text-xs text-gray-500 mt-1">
                {{ JSON.stringify(log.metadata) }}
              </div>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ log.ip_address || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <div class="flex items-center space-x-4">
        <button 
          :disabled="pagination.current_page <= 1" 
          @click="router.get('/admin/logs', { ...filters, page: pagination.current_page - 1 }, { preserveState: true })" 
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          &lt;
        </button>
        
        <span class="text-sm text-gray-600">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </span>
        
        <button 
          :disabled="pagination.current_page >= pagination.last_page" 
          @click="router.get('/admin/logs', { ...filters, page: pagination.current_page + 1 }, { preserveState: true })" 
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          &gt;
        </button>
      </div>
    </div>
  </div>
</template>


