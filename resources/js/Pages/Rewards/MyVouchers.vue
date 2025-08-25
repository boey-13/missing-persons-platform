<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  vouchers: Array,
  currentPoints: Number,
  selectedStatus: String,
})

// Modal states
const showVoucherModal = ref(false)
const selectedVoucher = ref(null)
const qrCodeData = ref(null)

// Filter states
const selectedStatusFilter = ref(props.selectedStatus || '')

// Functions
function openVoucherModal(voucher) {
  selectedVoucher.value = voucher
  showVoucherModal.value = true
  
  // Load QR code data
  loadQrCodeData(voucher.id)
}

function closeVoucherModal() {
  showVoucherModal.value = false
  selectedVoucher.value = null
  qrCodeData.value = null
}

async function loadQrCodeData(voucherId) {
  try {
    const response = await axios.get(`/vouchers/${voucherId}/qr-code`)
    qrCodeData.value = response.data
  } catch (error) {
    console.error('Failed to load QR code data:', error)
  }
}

function filterByStatus(status) {
  selectedStatusFilter.value = status
  router.get('/rewards/my-vouchers', { status }, { 
    preserveState: true,
    replace: true 
  })
}

function clearFilter() {
  selectedStatusFilter.value = ''
  router.get('/rewards/my-vouchers', {}, { 
    preserveState: true,
    replace: true 
  })
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function getStatusColor(status) {
  const colors = {
    active: 'bg-green-100 text-green-800',
    used: 'bg-blue-100 text-blue-800',
    expired: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getStatusText(status) {
  const texts = {
    active: 'Active',
    used: 'Used',
    expired: 'Expired',
  }
  return texts[status] || status
}
</script>

<template>
  <div>
    <!-- Back Link -->
    <div class="max-w-7xl mx-auto px-6 py-4">
      <Link href="/rewards" class="text-[#5C4033] font-medium hover:underline">
        ← BACK TO REWARDS
      </Link>
    </div>

    <!-- Header -->
    <div class="max-w-7xl mx-auto px-6 py-8 text-center">
      <h1 class="text-4xl font-extrabold text-[#5C4033] mb-2">My Vouchers</h1>
      <div class="w-24 h-1 bg-[#5C4033] mx-auto mb-4"></div>
      <p class="text-[#5C4033] text-lg">Your redeemed rewards and vouchers</p>
      
      <!-- Points Display -->
      <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 inline-block px-6 py-3">
        <div class="text-2xl font-bold text-[#5C4033]">{{ currentPoints }}</div>
        <div class="text-gray-600 text-sm">Available Points</div>
      </div>
    </div>

    <!-- Status Filter -->
    <div class="max-w-7xl mx-auto px-6 mb-8">
      <div class="flex flex-wrap gap-3 justify-center">
        <button
          @click="clearFilter"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            !selectedStatusFilter 
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          All Vouchers
        </button>
        <button
          @click="filterByStatus('active')"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedStatusFilter === 'active'
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          Active
        </button>
        <button
          @click="filterByStatus('used')"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedStatusFilter === 'used'
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          Used
        </button>
        <button
          @click="filterByStatus('expired')"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedStatusFilter === 'expired'
              ? 'bg-[#5C4033] text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          Expired
        </button>
      </div>
    </div>

    <!-- Vouchers Grid -->
    <div class="max-w-7xl mx-auto px-6 pb-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="voucher in vouchers"
          :key="voucher.id"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
          @click="openVoucherModal(voucher)"
        >
          <!-- Voucher Image -->
          <div class="h-48 bg-gray-100 flex items-center justify-center">
            <img
              v-if="voucher.reward?.image_url"
              :src="voucher.reward.image_url"
              :alt="voucher.reward.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="text-gray-400 text-4xl">
              🎁
            </div>
          </div>

          <!-- Voucher Info -->
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ voucher.reward?.name }}</h3>
            
            <!-- Status Badge -->
            <div class="mb-4">
              <span :class="`inline-block px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(voucher.status)}`">
                {{ getStatusText(voucher.status) }}
              </span>
            </div>

            <!-- Voucher Details -->
            <div class="space-y-2 mb-4 text-sm text-gray-600">
              <div class="flex justify-between">
                <span>Redeemed:</span>
                <span>{{ formatDate(voucher.redeemed_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Expires:</span>
                <span>{{ formatDate(voucher.expires_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Points Spent:</span>
                <span class="font-semibold text-[#5C4033]">{{ voucher.points_spent }}</span>
              </div>
            </div>

            <!-- Voucher Code -->
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <div class="text-xs text-gray-500 mb-1">Voucher Code</div>
              <div class="font-mono text-sm font-semibold text-gray-900">{{ voucher.voucher_code }}</div>
            </div>

            <!-- Action Button -->
            <button
              class="w-full bg-[#5C4033] text-white py-3 px-4 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
              :disabled="voucher.status !== 'active'"
              :class="{ 'opacity-50 cursor-not-allowed': voucher.status !== 'active' }"
            >
              {{ voucher.status === 'active' ? 'VIEW VOUCHER' : voucher.status === 'used' ? 'ALREADY USED' : 'EXPIRED' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="vouchers.length === 0" class="text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">🎫</div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No vouchers found</h3>
        <p class="text-gray-600 mb-6">
          {{ selectedStatusFilter 
            ? `You don't have any ${selectedStatusFilter} vouchers.` 
            : "You haven't redeemed any rewards yet." 
          }}
        </p>
        <Link 
          href="/rewards" 
          class="inline-block bg-[#5C4033] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
        >
          Browse Rewards
        </Link>
      </div>
    </div>

    <!-- Voucher Detail Modal -->
    <div v-if="showVoucherModal && selectedVoucher" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <!-- Close Button -->
          <div class="flex justify-end mb-4">
            <button @click="closeVoucherModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Voucher Image -->
          <div class="h-48 bg-gray-100 rounded-lg mb-4 flex items-center justify-center">
            <img
              v-if="selectedVoucher.reward?.image_url"
              :src="selectedVoucher.reward.image_url"
              :alt="selectedVoucher.reward.name"
              class="w-full h-full object-cover rounded-lg"
            />
            <div v-else class="text-gray-400 text-6xl">
              🎁
            </div>
          </div>

          <!-- Voucher Details -->
          <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ selectedVoucher.reward?.name }}</h2>
          <p v-if="selectedVoucher.reward?.description" class="text-gray-600 mb-4">
            {{ selectedVoucher.reward.description }}
          </p>

          <!-- Status -->
          <div class="mb-4">
            <span :class="`inline-block px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(selectedVoucher.status)}`">
              {{ getStatusText(selectedVoucher.status) }}
            </span>
          </div>

          <!-- Voucher Information -->
          <div class="space-y-3 mb-6">
            <div class="flex justify-between">
              <span class="text-gray-600">Voucher Code:</span>
              <span class="font-mono font-semibold text-gray-900">{{ selectedVoucher.voucher_code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Redeemed:</span>
              <span class="font-semibold">{{ formatDate(selectedVoucher.redeemed_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Expires:</span>
              <span class="font-semibold" :class="selectedVoucher.is_expired ? 'text-red-600' : 'text-gray-900'">
                {{ formatDate(selectedVoucher.expires_at) }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Points Spent:</span>
              <span class="font-semibold text-[#5C4033]">{{ selectedVoucher.points_spent }}</span>
            </div>
            <div v-if="selectedVoucher.used_at" class="flex justify-between">
              <span class="text-gray-600">Used On:</span>
              <span class="font-semibold">{{ formatDate(selectedVoucher.used_at) }}</span>
            </div>
          </div>

          <!-- QR Code (for active vouchers) -->
          <div v-if="selectedVoucher.status === 'active' && qrCodeData" class="mb-6">
            <div class="text-center">
              <div class="text-sm text-gray-600 mb-2">Scan this QR code to use your voucher</div>
              <div class="bg-white border border-gray-200 rounded-lg p-4 inline-block">
                <img 
                  v-if="qrCodeData.qrCodeImage" 
                  :src="qrCodeData.qrCodeImage" 
                  alt="Voucher QR Code"
                  class="w-32 h-32"
                />
                <div v-else class="w-32 h-32 bg-gray-100 flex items-center justify-center">
                  <div class="text-gray-400 text-xs text-center">
                    Loading QR Code...
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Expired/Used Notice -->
          <div v-if="selectedVoucher.status !== 'active'" class="mb-6">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <div class="flex">
                <div class="text-gray-400 mr-3">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="text-sm text-gray-600">
                  <p v-if="selectedVoucher.status === 'expired'">
                    This voucher has expired and can no longer be used.
                  </p>
                  <p v-else-if="selectedVoucher.status === 'used'">
                    This voucher has already been used.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Button -->
          <button
            @click="closeVoucherModal"
            class="w-full bg-[#5C4033] text-white py-3 px-4 rounded-lg font-semibold hover:bg-[#4A3329] transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
