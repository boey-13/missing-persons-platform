<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ToastMessage from '@/Components/ToastMessage.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  rewards: Array,
  categories: Array,
  stats: Object,
  selectedCategory: String,
})

// Filter states
const selectedCategoryFilter = ref(props.selectedCategory || '')

// Functions
function filterByCategory(categoryId) {
  selectedCategoryFilter.value = categoryId
  router.get('/admin/rewards', { category: categoryId }, { 
    preserveState: true,
    replace: true 
  })
}

function clearFilter() {
  selectedCategoryFilter.value = ''
  router.get('/admin/rewards', {}, { 
    preserveState: true,
    replace: true 
  })
}

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

    <!-- Category Filter -->
    <div class="mb-6">
      <div class="flex flex-wrap gap-3">
        <button
          @click="clearFilter"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            !selectedCategoryFilter 
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          All Categories
        </button>
        <button
          v-for="category in categories"
          :key="category.id"
          @click="filterByCategory(category.id)"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedCategoryFilter === category.id.toString()
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ category.name }}
        </button>
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
            <tr v-for="reward in rewards" :key="reward.id">
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

      <!-- Empty State -->
      <div v-if="rewards.length === 0" class="text-center py-12">
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


