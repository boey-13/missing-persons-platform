<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  rewards: Array,
  pagination: Object,
  categories: Array,
  currentPoints: Number,
  selectedCategory: String,
  showRedeemableOnly: Boolean,
})

// Modal states
const showRewardModal = ref(false)
const showRedeemModal = ref(false)
const selectedReward = ref(null)
const loading = ref(false)

// Filter states
const selectedCategoryFilter = ref(props.selectedCategory || '')
const showRedeemableOnly = ref(props.showRedeemableOnly || false)

// Functions
function openRewardModal(reward) {
  selectedReward.value = reward
  showRewardModal.value = true
}

function closeRewardModal() {
  showRewardModal.value = false
  selectedReward.value = null
}

function openRedeemModal() {
  showRedeemModal.value = true
  showRewardModal.value = false
}

function closeRedeemModal() {
  showRedeemModal.value = false
  selectedReward.value = null
}

async function redeemReward() {
  if (!selectedReward.value) return

  loading.value = true
  try {
    const response = await axios.post(`/rewards/${selectedReward.value.id}/redeem`)
    
    if (response.data.success) {
      // Update current points
      router.reload({ only: ['currentPoints'] })
      
      // Show success message
      alert(response.data.message)
      
      // Close modal and redirect to my vouchers
      closeRedeemModal()
      router.visit('/rewards/my-vouchers')
    }
  } catch (error) {
    if (error.response?.data?.message) {
      alert(error.response.data.message)
    } else {
      alert('Failed to redeem reward. Please try again.')
    }
  } finally {
    loading.value = false
  }
}

function filterByCategory(categoryId) {
  selectedCategoryFilter.value = categoryId
  const params = { 
    category: categoryId,
    redeemable_only: showRedeemableOnly.value ? '1' : '0'
  }
  router.get('/rewards', params, { 
    preserveState: true,
    replace: true 
  })
}

function clearFilter() {
  selectedCategoryFilter.value = ''
  const params = { 
    redeemable_only: showRedeemableOnly.value ? '1' : '0'
  }
  router.get('/rewards', params, { 
    preserveState: true,
    replace: true 
  })
}

function toggleRedeemableOnly() {
  showRedeemableOnly.value = !showRedeemableOnly.value
  const params = { 
    category: selectedCategoryFilter.value,
    redeemable_only: showRedeemableOnly.value ? '1' : '0'
  }
  router.get('/rewards', params, { 
    preserveState: true,
    replace: true 
  })
}

function goToPage(page) {
  const params = { 
    category: selectedCategoryFilter.value,
    redeemable_only: showRedeemableOnly.value ? '1' : '0',
    page: page
  }
  router.get('/rewards', params, { 
    preserveState: true,
    replace: true 
  })
}
</script>

<template>
  <div>
    <!-- Back Link -->
    <div class="max-w-7xl mx-auto px-6 py-4">
      <Link href="/profile" class="text-[#5C4033] font-medium hover:underline">
        ← BACK
      </Link>
    </div>

    <!-- Header -->
    <div class="max-w-7xl mx-auto px-6 py-8 text-center">
      <h1 class="text-4xl font-extrabold text-[#5C4033] mb-2">My Rewards</h1>
      <div class="w-24 h-1 bg-[#5C4033] mx-auto mb-4"></div>
      <p class="text-[#5C4033] text-lg">Redeem your points for exciting rewards!</p>
      
      <!-- Points Display -->
      <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 inline-block px-6 py-3">
        <div class="text-2xl font-bold text-[#5C4033]">{{ currentPoints }}</div>
        <div class="text-gray-600 text-sm">Available Points</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-6 mb-8">
      <!-- Category Filter -->
      <div class="flex flex-wrap gap-3 justify-center mb-4">
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

      <!-- Redeemable Only Filter -->
      <div class="flex justify-center">
        <button
          @click="toggleRedeemableOnly"
          :class="[
            'px-6 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2',
            showRedeemableOnly
              ? 'bg-green-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          <svg v-if="showRedeemableOnly" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
          </svg>
          {{ showRedeemableOnly ? 'Showing Redeemable Only' : 'Show All Rewards' }}
        </button>
      </div>
    </div>

    <!-- Rewards Grid -->
    <div class="max-w-7xl mx-auto px-6 pb-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="reward in rewards"
          :key="reward.id"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer flex flex-col h-full"
          @click="openRewardModal(reward)"
        >
          <!-- Reward Image -->
          <div class="h-48 bg-gray-100 flex items-center justify-center flex-shrink-0">
            <img
              v-if="reward.image_url"
              :src="reward.image_url"
              :alt="reward.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="text-gray-400 text-4xl">
              🎁
            </div>
          </div>

          <!-- Reward Info -->
          <div class="p-6 flex flex-col flex-grow">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ reward.name }}</h3>
            <p v-if="reward.description" class="text-gray-600 text-sm mb-4 line-clamp-2">
              {{ reward.description }}
            </p>
            
            <!-- Category Badge -->
            <div class="mb-4">
              <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                {{ reward.category?.name }}
              </span>
            </div>

            <!-- Points Required -->
            <div class="text-lg font-semibold text-[#5C4033] mb-4">
              {{ reward.points_required }} points required
            </div>

            <!-- Spacer to push button to bottom -->
            <div class="flex-grow"></div>

            <!-- Redeem Button -->
            <button
              class="w-full bg-[#5C4033] text-white py-3 px-4 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors mt-auto"
              :disabled="!reward.can_redeem"
              :class="{ 'opacity-50 cursor-not-allowed': !reward.can_redeem }"
            >
              {{ reward.can_redeem ? 'REDEEM NOW' : 'INSUFFICIENT POINTS' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="rewards.length === 0" class="text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">🎁</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No rewards available</h3>
        <p class="text-gray-600">Check back later for new rewards!</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center">
        <div class="flex items-center space-x-4">
          <!-- Previous Page -->
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            &lt;
          </button>

          <!-- Page Numbers -->
          <template v-for="page in pagination.last_page" :key="page">
            <button
              v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
              @click="goToPage(page)"
              :class="[
                'text-sm',
                page === pagination.current_page
                  ? 'underline font-medium'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              {{ page }}
            </button>
            <span
              v-else-if="page === pagination.current_page - 2 || page === pagination.current_page + 2"
              class="text-gray-400"
            >
              ...
            </span>
          </template>

          <!-- Next Page -->
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            &gt;
          </button>
        </div>
      </div>
    </div>

    <!-- Reward Detail Modal -->
    <div v-if="showRewardModal && selectedReward" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <!-- Close Button -->
          <div class="flex justify-end mb-4">
            <button @click="closeRewardModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Reward Image -->
          <div class="h-48 bg-gray-100 rounded-lg mb-4 flex items-center justify-center">
            <img
              v-if="selectedReward.image_url"
              :src="selectedReward.image_url"
              :alt="selectedReward.name"
              class="w-full h-full object-cover rounded-lg"
            />
            <div v-else class="text-gray-400 text-6xl">
              🎁
            </div>
          </div>

          <!-- Reward Details -->
          <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ selectedReward.name }}</h2>
          <p v-if="selectedReward.description" class="text-gray-600 mb-4">
            {{ selectedReward.description }}
          </p>

          <!-- Category -->
          <div class="mb-4">
            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
              {{ selectedReward.category?.name }}
            </span>
          </div>

          <!-- Points and Validity -->
          <div class="space-y-2 mb-6">
            <div class="flex justify-between">
              <span class="text-gray-600">Points Required:</span>
              <span class="font-semibold text-[#5C4033]">{{ selectedReward.points_required }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Your Points:</span>
              <span class="font-semibold" :class="selectedReward.can_redeem ? 'text-green-600' : 'text-red-600'">
                {{ currentPoints }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Valid for:</span>
              <span class="font-semibold">{{ selectedReward.validity_days }} days</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3">
            <button
              @click="closeRewardModal"
              class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Close
            </button>
            <button
              v-if="selectedReward.can_redeem"
              @click="openRedeemModal"
              class="flex-1 bg-[#5C4033] text-white py-3 px-4 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
            >
              Redeem Now
            </button>
            <button
              v-else
              disabled
              class="flex-1 bg-gray-300 text-gray-500 py-3 px-4 rounded-lg font-semibold cursor-not-allowed"
            >
              Insufficient Points
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Redeem Confirmation Modal -->
    <div v-if="showRedeemModal && selectedReward" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Confirm Redemption</h2>
          <p class="text-gray-600 mb-6">
            Are you sure you want to redeem <strong>{{ selectedReward.name }}</strong> for 
            <strong>{{ selectedReward.points_required }} points</strong>?
          </p>
          
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex">
              <div class="text-yellow-400 mr-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div class="text-sm text-yellow-800">
                <p>This action cannot be undone. Your voucher will be available in "My Vouchers" after redemption.</p>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <button
              @click="closeRedeemModal"
              :disabled="loading"
              class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="redeemReward"
              :disabled="loading"
              class="flex-1 bg-[#5C4033] text-white py-3 px-4 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors disabled:opacity-50"
            >
              {{ loading ? 'Processing...' : 'Confirm Redemption' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
