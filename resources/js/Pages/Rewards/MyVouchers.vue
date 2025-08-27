<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  vouchers: Array,
  currentPoints: Number,
  selectedStatus: String,
})

// Modal state
const showVoucherModal = ref(false)
const selectedVoucher = ref(null)
const qrCodeData = ref(null)

// Filter state
const selectedStatusFilter = ref(props.selectedStatus || '')

// —— 逻辑保持不变 ——
function openVoucherModal(voucher) {
  selectedVoucher.value = voucher
  showVoucherModal.value = true
  loadQrCodeData(voucher.id)
}
function closeVoucherModal() {
  showVoucherModal.value = false
  selectedVoucher.value = null
  qrCodeData.value = null
}
async function loadQrCodeData(voucherId) {
  try {
    const res = await axios.get(`/vouchers/${voucherId}/qr-code`)
    qrCodeData.value = res.data
  } catch (e) {
    console.error('Failed to load QR code data:', e)
  }
}
function filterByStatus(status) {
  selectedStatusFilter.value = status
  router.get('/rewards/my-vouchers', { status }, { preserveState: true, replace: true })
}
function clearFilter() {
  selectedStatusFilter.value = ''
  router.get('/rewards/my-vouchers', {}, { preserveState: true, replace: true })
}
function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
// 改为与新风格一致的徽章样式（细边+浅底）
function getStatusColor(status) {
  const map = {
    active:  'border-green-200 text-green-700 bg-green-50',
    used:    'border-blue-200 text-blue-700 bg-blue-50',
    expired: 'border-red-200 text-red-700 bg-red-50'
  }
  return map[status] || 'border-gray-200 text-gray-700 bg-gray-50'
}
function getStatusText(status) {
  const m = { active: 'Active', used: 'Used', expired: 'Expired' }
  return m[status] || status
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
     <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
       <button @click="goBack" class="text-gray-700 hover:text-black text-sm font-medium">← Back</button>
     </div>

     <!-- Header -->
     <header class="max-w-7xl mx-auto px-6 pb-2 text-center">
       <h1 class="text-3xl font-extrabold text-gray-900">My Vouchers</h1>
       <p class="text-gray-600 mt-2">Your redeemed rewards and vouchers</p>
       
       <!-- Points Display -->
       <div class="mt-6 inline-flex items-center gap-3 border border-gray-200 rounded-md px-4 py-2">
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
    <section class="max-w-7xl mx-auto px-6 pt-6 pb-4">
      <div class="flex items-center justify-between gap-4 flex-wrap md:flex-nowrap">
        <!-- Status chips -->
        <div class="overflow-x-auto hide-scrollbar -mx-1">
          <div class="flex items-center gap-2 px-1">
            <button
              @click="clearFilter"
              :class="[
                'px-3 py-1.5 text-sm border rounded-md',
                selectedStatusFilter === '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >All</button>

            <button
              @click="filterByStatus('active')"
              :class="[
                'px-3 py-1.5 text-sm border rounded-md',
                selectedStatusFilter === 'active' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >Active</button>

            <button
              @click="filterByStatus('used')"
              :class="[
                'px-3 py-1.5 text-sm border rounded-md',
                selectedStatusFilter === 'used' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >Used</button>

            <button
              @click="filterByStatus('expired')"
              :class="[
                'px-3 py-1.5 text-sm border rounded-md',
                selectedStatusFilter === 'expired' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >Expired</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Grid -->
    <main class="max-w-7xl mx-auto px-6 pb-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <article
          v-for="voucher in vouchers"
          :key="voucher.id"
          class="group border border-gray-200 rounded-md overflow-hidden bg-white hover:shadow-sm transition-shadow cursor-pointer"
          @click="openVoucherModal(voucher)"
        >
          <!-- Image -->
          <div class="relative h-48 bg-gray-100 overflow-hidden">
            <img
              :src="voucher.reward?.image_url || '/voucher.png'"
              :alt="voucher.reward?.name"
              class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform"
            />
            <span
              class="absolute top-3 left-3 text-xs px-2 py-0.5 rounded border"
              :class="getStatusColor(voucher.status)"
            >
              {{ getStatusText(voucher.status) }}
            </span>
          </div>

          <!-- Body -->
          <div class="p-4">
            <h3 class="text-base font-semibold text-gray-900 line-clamp-2 mb-1">
              {{ voucher.reward?.name }}
            </h3>

            <div class="space-y-2 text-sm text-gray-600 my-3">
              <div class="flex justify-between">
                <span>Redeemed</span>
                <span class="font-medium text-gray-900">{{ formatDate(voucher.redeemed_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Expires</span>
                <span class="font-medium" :class="voucher.status === 'expired' ? 'text-red-700' : 'text-gray-900'">
                  {{ formatDate(voucher.expires_at) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span>Points spent</span>
                <span class="font-semibold text-gray-900">{{ voucher.points_spent }}</span>
              </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-4">
              <div class="text-[11px] text-gray-500 mb-1">Voucher code</div>
              <div class="font-mono text-sm font-semibold text-gray-900 break-all">
                {{ voucher.voucher_code }}
              </div>
            </div>

            <button
              class="w-full py-2.5 rounded-md text-sm font-semibold transition-colors"
              :disabled="voucher.status !== 'active'"
              :class="voucher.status === 'active'
                ? 'bg-gray-900 text-white hover:bg-black'
                : 'bg-gray-100 text-gray-500 cursor-not-allowed'"
            >
              {{ voucher.status === 'active' ? 'View voucher' : voucher.status === 'used' ? 'Already used' : 'Expired' }}
            </button>
          </div>
        </article>
      </div>

      <!-- Empty -->
      <div v-if="vouchers.length === 0" class="text-center py-16">
        <div class="text-5xl mb-3">🎫</div>
        <h3 class="text-lg font-semibold text-gray-900">No vouchers found</h3>
        <p class="text-gray-600 text-sm mt-1">
          {{ selectedStatusFilter ? `You don't have any ${selectedStatusFilter} vouchers.` : "You haven't redeemed any rewards yet." }}
        </p>
        <Link
          href="/rewards"
          class="inline-block mt-6 bg-gray-900 text-white px-6 py-3 rounded-md text-sm font-semibold hover:bg-black"
        >
          Browse rewards
        </Link>
      </div>
    </main>

    <!-- Modal -->
    <div v-if="showVoucherModal && selectedVoucher" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-5">
          <div class="flex justify-end mb-2">
            <button @click="closeVoucherModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="h-48 bg-gray-100 rounded mb-4 overflow-hidden">
            <img
              :src="selectedVoucher.reward?.image_url || '/voucher.png'"
              :alt="selectedVoucher.reward?.name"
              class="w-full h-full object-cover"
            />
          </div>

          <h2 class="text-xl font-bold text-gray-900 mb-1">{{ selectedVoucher.reward?.name }}</h2>
          <p v-if="selectedVoucher.reward?.description" class="text-gray-700 text-sm mb-3">
            {{ selectedVoucher.reward.description }}
          </p>

          <div class="mb-3">
            <span
              class="inline-block px-2 py-0.5 text-xs rounded border"
              :class="getStatusColor(selectedVoucher.status)"
            >
              {{ getStatusText(selectedVoucher.status) }}
            </span>
          </div>

          <div class="space-y-2 text-sm mb-6">
            <div class="flex justify-between">
              <span class="text-gray-600">Voucher code</span>
              <span class="font-mono font-semibold text-gray-900">{{ selectedVoucher.voucher_code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Redeemed</span>
              <span class="font-semibold text-gray-900">{{ formatDate(selectedVoucher.redeemed_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Expires</span>
              <span class="font-semibold" :class="selectedVoucher.status === 'expired' ? 'text-red-700' : 'text-gray-900'">
                {{ formatDate(selectedVoucher.expires_at) }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Points spent</span>
              <span class="font-semibold text-gray-900">{{ selectedVoucher.points_spent }}</span>
            </div>
            <div v-if="selectedVoucher.used_at" class="flex justify-between">
              <span class="text-gray-600">Used on</span>
              <span class="font-semibold text-gray-900">{{ formatDate(selectedVoucher.used_at) }}</span>
            </div>
          </div>

          <!-- QR (active only) -->
          <div v-if="selectedVoucher.status === 'active' && qrCodeData" class="mb-6">
            <div class="text-center">
              <div class="text-sm text-gray-600 mb-2">Scan this QR code to use your voucher</div>
              <div class="bg-white border border-gray-200 rounded p-4 inline-block">
                <img
                  v-if="qrCodeData.qrCodeImage"
                  :src="qrCodeData.qrCodeImage"
                  alt="Voucher QR Code"
                  class="w-32 h-32"
                />
                <div v-else class="w-32 h-32 bg-gray-100 flex items-center justify-center">
                  <div class="text-gray-400 text-xs">Loading QR Code...</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notice -->
          <div v-if="selectedVoucher.status !== 'active'" class="mb-6">
            <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700">
              <template v-if="selectedVoucher.status === 'expired'">
                This voucher has expired and can no longer be used.
              </template>
              <template v-else-if="selectedVoucher.status === 'used'">
                This voucher has already been used.
              </template>
            </div>
          </div>

          <button
            @click="closeVoucherModal"
            class="w-full bg-gray-900 text-white py-2.5 rounded-md text-sm font-semibold hover:bg-black"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
