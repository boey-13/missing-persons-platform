<script setup>
import { ref } from 'vue'
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

// Modals
const showRewardModal = ref(false)
const showRedeemModal = ref(false)
const selectedReward = ref(null)
const loading = ref(false)

// Filters (沿用原有逻辑)
const selectedCategoryFilter = ref(props.selectedCategory || '')
const showRedeemableOnly = ref(props.showRedeemableOnly || false)

// —— methods（逻辑完全不动） ——
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
      router.reload({ only: ['currentPoints'] })
      alert(response.data.message)
      closeRedeemModal()
      router.visit('/rewards/my-vouchers')
    }
  } catch (error) {
    if (error.response?.data?.message) alert(error.response.data.message)
    else alert('Failed to redeem reward. Please try again.')
  } finally {
    loading.value = false
  }
}
function filterByCategory(categoryId) {
  selectedCategoryFilter.value = categoryId
  const params = { 
    category: categoryId || '',
    redeemable_only: showRedeemableOnly.value ? '1' : '0'
  }
  router.get('/rewards', params, { preserveState: true, replace: true })
}
function clearFilter() {
  selectedCategoryFilter.value = ''
  const params = { redeemable_only: showRedeemableOnly.value ? '1' : '0' }
  router.get('/rewards', params, { preserveState: true, replace: true })
}
function toggleRedeemableOnly(event) {
  showRedeemableOnly.value = event.target.checked
  const params = { 
    category: selectedCategoryFilter.value,
    redeemable_only: showRedeemableOnly.value ? '1' : '0'
  }
  router.get('/rewards', params, { preserveState: true, replace: true })
}
function goToPage(page) {
  const params = { 
    category: selectedCategoryFilter.value,
    redeemable_only: showRedeemableOnly.value ? '1' : '0',
    page
  }
  router.get('/rewards', params, { preserveState: true, replace: true })
}

function goBack() {
  // 如果历史记录只有一页，说明是直接访问的，默认回到Profile
  if (window.history.length <= 1) {
    router.visit('/profile')
  } else {
    window.history.back()
  }
}
</script>

<template>
  <div class="bg-white min-h-screen">
         <!-- Top bar -->
     <div class="max-w-[1400px] mx-auto px-6 py-4 flex items-center justify-between">
       <button @click="goBack" class="text-gray-700 hover:text-black text-sm font-medium">← Back</button>
      <Link
        href="/rewards/my-vouchers"
        class="inline-flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-black"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0120 9v10a2 2 0 01-2 2z"/>
        </svg>
        My Vouchers
      </Link>
    </div>

    <!-- Header -->
    <header class="max-w-[1400px] mx-auto px-6 pb-2 text-center">
      <h1 class="text-3xl font-extrabold text-gray-900">Rewards</h1>
      <p class="text-gray-600 mt-2">Redeem your points for available rewards</p>

      <!-- Points summary -->
      <div class="mt-6 inline-flex items-center gap-3 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 shadow-sm rounded-md px-6 py-3">
        <div class="w-8 h-8 rounded-md bg-gray-900 text-white flex items-center justify-center text-sm font-bold">
          P
        </div>
        <div class="text-left">
          <div class="text-2xl font-bold leading-tight text-gray-900">{{ currentPoints }}</div>
          <div class="text-xs text-gray-500 -mt-0.5">Available points</div>
        </div>
      </div>
    </header>

    <!-- Filters -->
    <section class="max-w-[1400px] mx-auto px-6 pt-6 pb-4">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-5 bg-gray-50 rounded-lg">
        <!-- Category chips -->
        <div class="overflow-x-auto hide-scrollbar -mx-1">
          <div class="flex items-center gap-2 px-1">
            <button
              @click="clearFilter"
              :class="[
                'px-3 py-1.5 text-sm border rounded-md',
                selectedCategoryFilter === '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              All
            </button>
                         <button
               v-for="c in categories.slice(0, 4)"
               :key="c.id"
               @click="filterByCategory(c.id)"
               :class="[
                 'px-3 py-1.5 text-sm border rounded-md',
                 String(selectedCategoryFilter) === String(c.id)
                   ? 'bg-gray-900 text-white border-gray-900'
                   : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
               ]"
             >
               {{ c.name }}
             </button>
          </div>
        </div>

        <!-- Redeemable only switch -->
        <label class="inline-flex items-center gap-3 cursor-pointer select-none">
          <span class="text-sm text-gray-700">Redeemable only</span>
          <input
            type="checkbox"
            :checked="showRedeemableOnly"
            @change="toggleRedeemableOnly"
            class="peer sr-only"
          />
          <span
            class="w-10 h-6 inline-flex items-center bg-gray-200 peer-checked:bg-gray-900 rounded-full p-0.5 transition-colors"
          >
            <span
              class="w-5 h-5 bg-white rounded-full shadow transform transition-transform peer-checked:translate-x-4"
            ></span>
          </span>
        </label>
      </div>
    </section>

    <!-- Rewards grid -->
    <main class="max-w-[1400px] mx-auto px-6 pb-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <article
          v-for="reward in rewards"
          :key="reward.id"
          class="group border border-gray-200 rounded-md overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-300 bg-white flex flex-col"
          @click="openRewardModal(reward)"
        >
          <!-- Image -->
          <div class="relative h-48 bg-gray-100 overflow-hidden">
            <img
              :src="reward.image_url || '/voucher.png'"
              :alt="reward.name"
              class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform"
            />
            <span
              v-if="reward.category?.name"
              class="absolute top-3 left-3 text-xs px-2 py-0.5 rounded bg-white/90 border border-gray-200"
            >
              {{ reward.category.name }}
            </span>
          </div>

          <!-- Body -->
          <div class="p-5 flex flex-col gap-3 flex-1">
            <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">{{ reward.name }}</h3>
            <p v-if="reward.description" class="text-sm text-gray-600 line-clamp-2">
              {{ reward.description }}
            </p>

            <div class="flex items-center justify-between mt-1">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded bg-gray-900 text-white text-xs font-bold flex items-center justify-center">P</div>
                <div class="text-sm text-gray-900 font-semibold">
                  {{ reward.points_required }} pts
                </div>
              </div>

              <span
                class="text-xs px-2 py-0.5 rounded border"
                :class="
                  reward.can_redeem 
                    ? 'border-green-200 text-green-700 bg-green-50' 
                    : reward.is_fully_redeemed 
                      ? 'border-red-200 text-red-700 bg-red-50'
                      : 'border-gray-200 text-gray-600 bg-gray-50'
                "
              >
                {{ 
                  reward.can_redeem 
                    ? 'Redeemable' 
                    : reward.is_fully_redeemed 
                      ? 'Full Redeemed'
                      : 'Locked' 
                }}
              </span>
            </div>

            <!-- CTA -->
            <button
              class="mt-auto w-full py-2.5 px-3 rounded-md text-sm font-medium transition-colors"
              :disabled="!reward.can_redeem || reward.is_fully_redeemed"
              :class="
                reward.can_redeem && !reward.is_fully_redeemed
                  ? 'bg-gray-900 text-white hover:bg-black'
                  : 'bg-gray-100 text-gray-500 cursor-not-allowed'
              "
            >
              {{ 
                reward.can_redeem && !reward.is_fully_redeemed
                  ? 'Redeem' 
                  : reward.is_fully_redeemed
                    ? 'Full Redeemed'
                    : 'Insufficient points' 
              }}
            </button>
          </div>
        </article>
      </div>

      <!-- Empty -->
      <div v-if="rewards.length === 0" class="text-center py-16">
        <div class="text-5xl mb-3">🎁</div>
        <h3 class="text-lg font-semibold text-gray-900">No rewards available</h3>
        <p class="text-gray-600 text-sm mt-1">Check back later for new rewards.</p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-10 flex justify-center">
        <nav class="inline-flex items-center gap-1">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-300 rounded-l-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white"
          >
            Prev
          </button>

          <template v-for="page in pagination.last_page" :key="page">
            <button
              v-if="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 border border-gray-300 text-sm',
                page === pagination.current_page ? 'bg-gray-900 text-white' : 'bg-white hover:bg-gray-50'
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
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-r-md text-sm hover:bg-gray-50 disabled:hover:bg-white"
          >
            Next
          </button>
        </nav>
      </div>
    </main>

    <!-- Reward Modal -->
    <div v-if="showRewardModal && selectedReward" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-5">
          <div class="flex justify-end mb-2">
            <button @click="closeRewardModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="h-48 bg-gray-100 rounded mb-4 overflow-hidden">
            <img :src="selectedReward.image_url || '/voucher.png'" :alt="selectedReward.name" class="w-full h-full object-cover" />
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-1">{{ selectedReward.name }}</h2>
          <p v-if="selectedReward.description" class="text-gray-700 text-sm mb-3">{{ selectedReward.description }}</p>

          <div class="mb-4">
            <span class="inline-block px-2 py-0.5 text-xs rounded border border-gray-200 text-gray-700">
              {{ selectedReward.category?.name || 'Uncategorized' }}
            </span>
          </div>

          <div class="space-y-2 text-sm mb-6">
            <div class="flex justify-between">
              <span class="text-gray-600">Points required</span>
              <span class="font-semibold text-gray-900">{{ selectedReward.points_required }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Your points</span>
              <span :class="selectedReward.can_redeem ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'">
                {{ currentPoints }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Validity</span>
              <span class="font-semibold text-gray-900">{{ selectedReward.validity_days }} days</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button
              @click="closeRewardModal"
              class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-md text-sm font-medium hover:bg-gray-200"
            >
              Close
            </button>
            <button
              v-if="selectedReward.can_redeem && !selectedReward.is_fully_redeemed"
              @click="openRedeemModal"
              class="flex-1 bg-gray-900 text-white py-2.5 rounded-md text-sm font-semibold hover:bg-black"
            >
              Redeem now
            </button>
            <button
              v-else
              disabled
              class="flex-1 bg-gray-100 text-gray-400 py-2.5 rounded-md text-sm font-semibold cursor-not-allowed"
            >
              {{ selectedReward.is_fully_redeemed ? 'Full Redeemed' : 'Insufficient points' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Modal -->
    <div v-if="showRedeemModal && selectedReward" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md max-w-md w-full">
        <div class="p-5">
          <h2 class="text-lg font-bold text-gray-900 mb-3">Confirm redemption</h2>
          <p class="text-sm text-gray-700 mb-5">
            Redeem <strong>{{ selectedReward.name }}</strong> for
            <strong>{{ selectedReward.points_required }} points</strong>?
          </p>

          <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm text-yellow-800 mb-5">
            This action cannot be undone. Your voucher will appear in “My Vouchers” after redemption.
          </div>

          <div class="flex gap-3">
            <button
              @click="closeRedeemModal"
              :disabled="loading"
              class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-md text-sm font-medium hover:bg-gray-200 disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="redeemReward"
              :disabled="loading"
              class="flex-1 bg-gray-900 text-white py-2.5 rounded-md text-sm font-semibold hover:bg-black disabled:opacity-50"
            >
              {{ loading ? 'Processing…' : 'Confirm' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
