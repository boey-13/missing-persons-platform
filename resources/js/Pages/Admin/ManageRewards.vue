<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ToastMessage from '@/Components/ToastMessage.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  rewards: Object, // Now it's a paginated object
  categories: Array,
  stats: Object,
  filters: Object
})

// Filter states
const selectedCategoryFilter = ref(props.filters?.category || 'all')
const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || 'all')
const sortBy = ref(props.filters?.sort_by || 'created_at')
const sortOrder = ref(props.filters?.sort_order || 'desc')

// Functions
function applyFilters() {
  router.get('/admin/rewards', {
    category: selectedCategoryFilter.value,
    search: search.value,
    status: statusFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  selectedCategoryFilter.value = 'all'
  search.value = ''
  statusFilter.value = 'all'
  sortBy.value = 'created_at'
  sortOrder.value = 'desc'
  applyFilters()
}

function filterByCategory(categoryId) {
  selectedCategoryFilter.value = categoryId
  applyFilters()
}

function handleSearch() {
  applyFilters()
}

function handleSort() {
  applyFilters()
}

const statuses = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

const sortOptions = [
  { value: 'created_at', label: 'Date Created' },
  { value: 'name', label: 'Name' },
  { value: 'points_required', label: 'Points Required' },
  { value: 'stock_quantity', label: 'Stock Quantity' }
]

function deleteReward(rewardId) {
  if (confirm('Are you sure you want to delete this reward?')) {
    router.delete(`/admin/rewards/${rewardId}`)
  }
}
</script>

<template>
  <div>
    <!-- Success Message -->
    <ToastMessage v-if="$page.props.flash?.success" :message="$page.props.flash.success" type="success" />

    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-extrabold">Manage Rewards</h1>
      <Link
        href="/admin/rewards/create"
        class="bg-[#5C4033] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
      >
        Add New Reward
      </Link>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Rewards</div>
        <div class="text-3xl font-black text-[#5C4033]">{{ stats?.total_rewards || 0 }}</div>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Active Rewards</div>
        <div class="text-3xl font-black text-green-600">{{ stats?.active_rewards || 0 }}</div>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Redemptions</div>
        <div class="text-3xl font-black text-blue-600">{{ stats?.total_redemptions || 0 }}</div>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Active Vouchers</div>
        <div class="text-3xl font-black text-orange-600">{{ stats?.active_vouchers || 0 }}</div>
      </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-xl shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <input
              v-model="search"
              @keyup.enter="handleSearch"
              type="text"
              placeholder="Search rewards..."
              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
            />
            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>

        <!-- Category Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select
            v-model="selectedCategoryFilter"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
          >
            <option value="all">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="statusFilter"
            @change="applyFilters"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
          >
            <option value="all">All Statuses</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </div>

        <!-- Sort By -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select
            v-model="sortBy"
            @change="handleSort"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
          >
            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Sort Order -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
          <select
            v-model="sortOrder"
            @change="handleSort"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
          >
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select>
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
        <div class="text-sm text-gray-600">
          Showing {{ props.rewards.from || 0 }} to {{ props.rewards.to || 0 }} of {{ props.rewards.total || 0 }} rewards
        </div>
        <div class="flex space-x-2">
          <button
            @click="handleSearch"
            class="bg-[#5C4033] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#4A3329] transition-colors"
          >
            Apply Filters
          </button>
          <button
            @click="clearFilters"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
          >
            Clear All
          </button>
        </div>
      </div>
    </div>

    <!-- Rewards Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reward
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Points Required
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Stock
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="reward in rewards.data" :key="reward.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <img
                      v-if="reward.image_url"
                      :src="reward.image_url"
                      :alt="reward.name"
                      class="h-10 w-10 rounded-lg object-cover"
                    />
                    <div v-else class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                      <span class="text-gray-400">🎁</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ reward.name }}</div>
                    <div v-if="reward.description" class="text-sm text-gray-500 truncate max-w-xs">
                      {{ reward.description }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                  {{ reward.category?.name }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ reward.points_required }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-if="reward.stock_quantity === 0" class="text-green-600">Unlimited</span>
                <span v-else class="text-gray-900">
                  {{ reward.redeemed_count }}/{{ reward.stock_quantity }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    reward.status === 'active' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ reward.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <Link
                    :href="`/admin/rewards/${reward.id}/edit`"
                    class="text-[#5C4033] hover:text-[#4A3329]"
                  >
                    Edit
                  </Link>
                  <button
                    @click="deleteReward(reward.id)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="rewards.data && rewards.data.length > 0" class="mt-8">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ rewards.from || 0 }} to {{ rewards.to || 0 }} of {{ rewards.total || 0 }} results
          </div>
          <div class="flex items-center space-x-2">
            <!-- Previous Page -->
            <Link
              v-if="rewards.prev_page_url"
              :href="rewards.prev_page_url"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
              preserve-scroll
            >
              Previous
            </Link>
            <span
              v-else
              class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed"
            >
              Previous
            </span>

            <!-- Page Numbers -->
            <template v-for="(link, index) in rewards.links" :key="index">
              <Link
                v-if="link.url && !link.active && link.label !== '...'"
                :href="link.url"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
                preserve-scroll
              >
                {{ link.label }}
              </Link>
              <span
                v-else-if="link.active"
                class="px-3 py-2 text-sm font-medium text-white bg-[#5C4033] border border-[#5C4033] rounded-lg"
              >
                {{ link.label }}
              </span>
              <span
                v-else-if="link.label === '...'"
                class="px-3 py-2 text-sm font-medium text-gray-500"
              >
                {{ link.label }}
              </span>
            </template>

            <!-- Next Page -->
            <Link
              v-if="rewards.next_page_url"
              :href="rewards.next_page_url"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
              preserve-scroll
            >
              Next
            </Link>
            <span
              v-else
              class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed"
            >
              Next
            </span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!rewards.data || rewards.data.length === 0" class="text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">🎁</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No rewards found</h3>
        <p class="text-gray-600 mb-6">Get started by creating your first reward.</p>
        <Link
          href="/admin/rewards/create"
          class="inline-block bg-[#5C4033] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
        >
          Create Reward
        </Link>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 flex space-x-4">
      <Link
        href="/admin/rewards/categories"
        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
      >
        Manage Categories
      </Link>
      <Link
        href="/admin/rewards/stats"
        class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"
      >
        View Statistics
      </Link>
    </div>
  </div>
</template>


