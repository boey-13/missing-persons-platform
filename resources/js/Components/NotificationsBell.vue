<script setup>
import { ref, onMounted } from 'vue'

const open = ref(false)
const items = ref([])

async function load() {
  const res = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
  items.value = await res.json()
}

function toggle() {
  open.value = !open.value
  if (open.value) load()
}
</script>

<template>
  <div class="relative">
    <button class="relative" @click="toggle" aria-label="Notifications">
      <span class="material-icons">notifications</span>
      <span v-if="items.length" class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] rounded-full px-1">{{ items.length }}</span>
    </button>
    <div v-if="open" class="absolute right-0 mt-2 w-80 bg-white rounded shadow z-50">
      <div class="p-3 border-b font-semibold">Notifications</div>
      <div class="max-h-80 overflow-auto">
        <div v-if="!items.length" class="p-4 text-gray-500 text-sm">No notifications</div>
        <div v-for="n in items" :key="n.id" class="p-3 border-b text-sm">
          <div class="font-medium">{{ n.title }}</div>
          <div class="text-gray-600" v-text="n.message"></div>
        </div>
      </div>
    </div>
  </div>
</template>


