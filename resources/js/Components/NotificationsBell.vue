<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const open = ref(false)
const items = ref([])
let timerId = null

async function fetchFeed() {
  const res = await fetch(`/notifications?t=${Date.now()}` , {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' },
    cache: 'no-store'
  })
  items.value = await res.json()
}

async function markAllRead() {
  const unreadIds = items.value.filter(n => !n.read_at).map(n => n.id)
  if (!unreadIds.length) return
  await fetch('/notifications/read', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ ids: unreadIds })
  })
  const now = new Date().toISOString()
  items.value.forEach(n => { if (!n.read_at) n.read_at = now })
}

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await fetchFeed()
    await markAllRead()
  }
}

const unreadCount = computed(() => items.value.filter(n => !n.read_at).length)

function timeAgo(iso) {
  if (!iso) return ''
  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'just now'
  if (diff < 3600) return Math.floor(diff/60) + 'm ago'
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago'
  return Math.floor(diff/86400) + 'd ago'
}

function onItemClick(n) {
  if (n.data && n.data.reason && n.title?.includes('Rejected')) {
    alert(`Your volunteer application was rejected. Reason: ${n.data.reason}`)
    window.location.href = '/volunteer/apply'
  } else if (n.title?.includes('Approved')) {
    window.location.href = '/volunteer/projects'
  }
}

onMounted(async () => {
  await fetchFeed()
  timerId = setInterval(fetchFeed, 5000)
  document.addEventListener('visibilitychange', onVisibility)
})

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId)
  document.removeEventListener('visibilitychange', onVisibility)
})

async function onVisibility() {
  if (document.visibilityState === 'visible') {
    await fetchFeed()
  }
}
</script>

<template>
  <div class="relative">
    <button class="relative inline-flex items-center justify-center h-7 w-7 align-middle hover:opacity-90 transition" @click="toggle" aria-label="Notifications">
      <!-- Inline SVG bell icon (no external font needed) -->
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
        <path d="M12 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 14h12a1 1 0 00.707-1.707L18 11.586V8a6 6 0 00-6-6z"/>
        <path d="M8 16a4 4 0 008 0H8z"/>
      </svg>
      <span v-if="unreadCount" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] rounded-full px-1.5 min-w-[16px] leading-4 text-center">{{ unreadCount }}</span>
    </button>
    <div v-if="open" class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-xl z-50 ring-1 ring-black/5 overflow-hidden">
      <div class="p-3 bg-gradient-to-r from-[#f7f3ee] to-white border-b font-semibold">Notifications</div>
      <div class="max-h-96 overflow-auto divide-y">
        <div v-if="!items.length" class="p-5 text-gray-500 text-sm">No notifications</div>
        <div v-for="n in items" :key="n.id" @click="onItemClick(n)" class="p-4 text-sm cursor-pointer hover:bg-gray-50 flex gap-3">
          <div class="mt-0.5">
            <svg v-if="n.title?.includes('Volunteer')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-[#6B4C3B]" fill="currentColor"><path d="M12 2a5 5 0 00-5 5v3H6a2 2 0 000 4h12a2 2 0 000-4h-1V7a5 5 0 00-5-5z"/><path d="M8 17a4 4 0 108 0H8z"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-gray-400" fill="currentColor"><path d="M5 4h14v2H5z"/><path d="M5 8h14v12H5z"/></svg>
          </div>
          <div class="flex-1">
            <div class="font-medium text-gray-900" v-text="n.title"></div>
            <div class="text-gray-600" v-text="n.message"></div>
            <div class="text-[11px] text-gray-400 mt-1">{{ timeAgo(n.created_at) }}</div>
          </div>
          <span v-if="!n.read_at" class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
        </div>
      </div>
    </div>
  </div>
  
</template>

<script>
export default {
  methods: {
    async markRead(n) {
      try {
        await fetch('/notifications/read', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ ids: [n.id] })
        })
        n.read_at = new Date().toISOString()
      } catch (e) {}
    }
  }
}
</script>


