<script setup>
import { ref, computed } from 'vue'

const open = ref(false)
const items = ref([])

async function load() {
  const res = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
  items.value = await res.json()
}

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await load()
    const unreadIds = items.value.filter(n => !n.read_at).map(n => n.id)
    if (unreadIds.length) {
      try {
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
      } catch (e) {}
    }
  }
}
</script>

<template>
  <div class="relative">
    <button class="relative inline-flex items-center justify-center h-7 w-7 align-middle" @click="toggle" aria-label="Notifications">
      <!-- Inline SVG bell icon (no external font needed) -->
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
        <path d="M12 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 14h12a1 1 0 00.707-1.707L18 11.586V8a6 6 0 00-6-6z"/>
        <path d="M8 16a4 4 0 008 0H8z"/>
      </svg>
      <span v-if="items.filter(n => !n.read_at).length" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] rounded-full px-1.5 min-w-[16px] leading-4 text-center">{{ items.filter(n => !n.read_at).length }}</span>
    </button>
    <div v-if="open" class="absolute right-0 mt-2 w-80 bg-white rounded shadow z-50">
      <div class="p-3 border-b font-semibold">Notifications</div>
      <div class="max-h-80 overflow-auto">
        <div v-if="!items.length" class="p-4 text-gray-500 text-sm">No notifications</div>
        <div v-for="n in items" :key="n.id" class="p-3 border-b text-sm flex justify-between items-start">
          <div>
            <div class="font-medium">{{ n.title }}</div>
            <div class="text-gray-600" v-text="n.message"></div>
          </div>
          <button v-if="!n.read_at" class="text-xs text-blue-600 hover:underline" @click="markRead(n)">Mark read</button>
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


