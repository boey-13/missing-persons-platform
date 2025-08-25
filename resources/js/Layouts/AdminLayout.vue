<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentUrl = computed(() => page.url)

function isActive(path) {
  return currentUrl.value?.startsWith(path)
}

const baseClasses = 'block px-3 py-2 rounded transition-colors'
const activeClasses = 'bg-white text-[#335a3b] font-semibold'
const inactiveClasses = 'text-white hover:bg-white/10'

function linkClasses(path, extra = '') {
  return `${baseClasses} ${isActive(path) ? activeClasses : inactiveClasses} ${extra}`
}
</script>

<template>
  <div class="min-h-screen flex bg-[#f6f7f9] text-[#1f2937]">
    <aside class="w-64 bg-[#335a3b] text-white p-6 sticky top-0 h-screen overflow-y-auto">
      <h2 class="text-2xl font-extrabold mb-6">Admin Panel</h2>
      <nav class="space-y-2">
        <Link :class="linkClasses('/admin/dashboard')" href="/admin/dashboard">Dashboard Home</Link>
        <Link :class="linkClasses('/admin/missing-reports')" href="/admin/missing-reports">Manage Missing Persons</Link>
        <Link :class="linkClasses('/admin/sighting-reports')" href="/admin/sighting-reports">Manage Sighting Reports</Link>
        <Link :class="linkClasses('/admin/volunteers')" href="/admin/volunteers">Manage Volunteers</Link>
        <Link :class="linkClasses('/admin/community-projects')" href="/admin/community-projects">Manage Community Project</Link>
        <Link :class="linkClasses('/admin/rewards')" href="/admin/rewards">Manage Rewards</Link>
        <Link :class="linkClasses('/admin/users')" href="/admin/users">Manage Users</Link>
        <Link :class="linkClasses('/admin/logs')" href="/admin/logs">System Logs</Link>
        <Link :class="linkClasses('/dashboard', 'mt-4')" href="/">Back To Main Dashboard</Link>
      </nav>
    </aside>
    <main class="flex-1 p-8 overflow-y-auto">
      <slot />
    </main>
  </div>
</template>


