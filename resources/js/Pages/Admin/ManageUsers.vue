<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  users: Object, // Now it's a paginated object
  filters: Object
})

// Filter states
const search = ref(props.filters?.search || '')
const roleFilter = ref(props.filters?.role || 'all')

// Functions
function applyFilters() {
  router.get('/admin/users', {
    search: search.value,
    role: roleFilter.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  search.value = ''
  roleFilter.value = 'all'
  applyFilters()
}

function handleSearch() {
  applyFilters()
}

function changeRole(userId, role) {
  router.post(`/admin/users/${userId}/role`, {
    role
  }, {
    onSuccess: () => {
      alert('✅ User role updated successfully!')
    },
    onError: (errors) => {
      console.error('Role update failed:', errors)
      alert('Failed to update user role. Please try again.')
    }
  })
}

function deleteUser(userId) {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    router.delete(`/admin/users/${userId}`, {
      onSuccess: () => {
        alert('✅ User deleted successfully!')
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        alert('Failed to delete user. Please try again.')
      }
    })
  }
}

const roles = [
  { value: 'all', label: 'All Roles' },
  { value: 'user', label: 'User' },
  { value: 'volunteer', label: 'Volunteer' },
  { value: 'admin', label: 'Admin' }
]
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-extrabold">Manage Users</h1>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-xl shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <input v-model="search" @keyup.enter="handleSearch" type="text" placeholder="Search by name or email..."
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Role Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select v-model="roleFilter" @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option v-for="role in roles" :key="role.value" :value="role.value">
              {{ role.label }}
            </option>
          </select>
        </div>

        <!-- Filter Actions -->
        <div class="flex items-end space-x-2">
          <button @click="handleSearch"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            Apply Filters
          </button>
          <button @click="clearFilters"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
            Clear All
          </button>
        </div>
      </div>

      <!-- Results Info -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="text-sm text-gray-600">
          Showing {{ props.users.from || 0 }} to {{ props.users.to || 0 }} of {{ props.users.total || 0 }} users
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in props.users.data" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select :value="user.role" @change="e => changeRole(user.id, e.target.value)"
                  class="border border-gray-300 rounded px-3 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="user">User</option>
                  <option value="volunteer">Volunteer</option>
                  <option value="admin">Admin</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(user.created_at).toLocaleDateString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="px-3 py-1 text-white bg-red-600 rounded hover:bg-red-700 transition-colors"
                  @click="() => deleteUser(user.id)">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>



      <!-- Empty State -->
      <div v-if="!props.users.data || props.users.data.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="props.users.data && props.users.data.length > 0" class="mt-8">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ props.users.from || 0 }} to {{ props.users.to || 0 }} of {{ props.users.total || 0 }} results
        </div>
        <div class="flex items-center space-x-2">
          <!-- Previous Page -->
          <Link v-if="props.users.prev_page_url" :href="props.users.prev_page_url"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors flex items-center"
            preserve-scroll>
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Previous
          </Link>
          <span v-else
            class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Previous
          </span>

          <!-- Page Numbers -->
          <template v-for="(link, index) in props.users.links" :key="index">
            <Link v-if="link.url && !link.active && link.label !== '...' && !link.label.includes('&')" :href="link.url"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
              preserve-scroll>
            {{ link.label }}
            </Link>
            <span v-else-if="link.active"
              class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg">
              {{ link.label }}
            </span>
            <span v-else-if="link.label === '...'" class="px-3 py-2 text-sm font-medium text-gray-500">
              {{ link.label }}
            </span>
          </template>

          <!-- Next Page -->
          <Link v-if="props.users.next_page_url" :href="props.users.next_page_url"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors flex items-center"
            preserve-scroll>
          Next
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
          </Link>
          <span v-else
            class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center">
            Next
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
