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
  if (n.data && n.data.action) {
    switch (n.data.action) {
      case 'reapply':
        const reason = n.data?.reason ? `Reason: ${n.data.reason}` : 'You can re-apply with additional info.'
        const go = confirm(`Your volunteer application was rejected.\n${reason}\n\nRe-apply now?`)
        if (go) window.location.href = '/volunteer/apply'
        break
        
      case 'open_projects':
        window.location.href = '/volunteer/projects'
        break
        
      case 'view_report':
        window.location.href = `/missing-persons/${n.data.report_id}`
        break
        
      case 'resubmit_report':
        const reportReason = n.data?.reason ? `Reason: ${n.data.reason}` : 'Please review and resubmit.'
        const resubmit = confirm(`Your missing person report was rejected.\n${reportReason}\n\nSubmit a new report?`)
        if (resubmit) window.location.href = '/missing-persons/report'
        break
        
      case 'view_sighting':
        window.location.href = `/sighting-reports/${n.data.sighting_id}`
        break
        
      case 'resubmit_sighting':
        const sightingReason = n.data?.reason ? `Reason: ${n.data.reason}` : 'Please review and resubmit.'
        const resubmitSighting = confirm(`Your sighting report was rejected.\n${sightingReason}\n\nSubmit a new sighting?`)
        if (resubmitSighting) window.location.href = '/sighting-reports/report'
        break
        
      case 'view_project':
        window.location.href = `/volunteer/projects/${n.data.project_id}`
        break
        
      case 'view_application':
        window.location.href = `/volunteer/my-applications`
        break
        
      case 'reapply_project':
        const projectReason = n.data?.reason ? `Reason: ${n.data.reason}` : 'Please review and reapply.'
        const reapplyProject = confirm(`Your project application was rejected.\n${projectReason}\n\nReapply for this project?`)
        if (reapplyProject) window.location.href = `/volunteer/projects/${n.data.project_id}/apply`
        break
        
      case 'view_profile':
        window.location.href = '/profile'
        break
        
      case 'get_started':
        window.location.href = '/missing-persons/report'
        break
        
      case 'review_report':
        window.location.href = `/admin/missing-reports/${n.data.report_id}`
        break
        
      case 'review_sighting':
        window.location.href = `/admin/sighting-reports/${n.data.sighting_id}`
        break
        
      case 'review_volunteer':
        window.location.href = `/admin/volunteers`
        break
        
      case 'review_project_application':
        window.location.href = `/admin/community-projects/applications`
        break
        
      default:
        // Default action - just mark as read
        break
    }
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
      <div class="p-3 bg-gray-50 border-b border-gray-200">
        <div class="font-semibold text-gray-900">Notifications</div>
      </div>
      <div class="max-h-96 overflow-auto divide-y divide-gray-100">
        <div v-if="!items.length" class="p-5 text-gray-500 text-sm text-center">No notifications</div>
        <div v-for="n in items" :key="n.id" @click="onItemClick(n)" class="p-4 text-sm cursor-pointer hover:bg-gray-50 transition-colors flex gap-3">
          <div class="mt-0.5 flex-shrink-0">
            <!-- Different icons based on notification type -->
            <svg v-if="n.type?.includes('volunteer')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-orange-600" fill="currentColor">
              <path d="M12 2a5 5 0 00-5 5v3H6a2 2 0 000 4h12a2 2 0 000-4h-1V7a5 5 0 00-5-5z"/>
              <path d="M8 17a4 4 0 108 0H8z"/>
            </svg>
            <svg v-else-if="n.type?.includes('missing')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-blue-600" fill="currentColor">
              <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <svg v-else-if="n.type?.includes('sighting')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-green-600" fill="currentColor">
              <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg v-else-if="n.type?.includes('project')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-purple-600" fill="currentColor">
              <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <svg v-else-if="n.type?.includes('points')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-yellow-600" fill="currentColor">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <svg v-else-if="n.type?.includes('welcome')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-green-600" fill="currentColor">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-gray-500" fill="currentColor">
              <path d="M5 4h14v2H5z"/>
              <path d="M5 8h14v12H5z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900 mb-1" v-text="n.title"></div>
            <div class="text-gray-700 text-sm leading-relaxed" v-text="n.message"></div>
            <div class="text-xs text-gray-500 mt-2">{{ timeAgo(n.created_at) }}</div>
          </div>
          <div class="flex-shrink-0">
            <span v-if="!n.read_at" class="w-2 h-2 bg-blue-500 rounded-full"></span>
          </div>
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


