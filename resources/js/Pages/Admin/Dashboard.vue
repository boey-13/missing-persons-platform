<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({
  stats: Object,
})
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Dashboard Home</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Missing Person</div>
        <div class="text-4xl font-black">{{ props.stats.totalMissingCases }}</div>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Pending Sightings</div>
        <div class="text-4xl font-black">{{ props.stats.pendingSightings }}</div>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Users</div>
        <div class="text-4xl font-black">{{ props.stats.totalUsers }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-2xl font-bold mb-4">System Logs</h2>
        <p class="text-gray-500">Coming soon…</p>
      </div>
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Manage User Account</h2>
        <p class="text-gray-500">Quick access via sidebar.</p>
      </div>
    </div>
  </div>
</template>


