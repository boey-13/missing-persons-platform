<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ToastContainer from '../Components/ToastContainer.vue'

const page = usePage()
const currentUrl = computed(() => page.url)

const mobileOpen = ref(false)

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
    <!-- Mobile top bar -->
    <div class="lg:hidden fixed top-0 inset-x-0 bg-[#335a3b] text-white z-40">
      <div class="px-4 py-3 flex items-center justify-between">
        <button @click="mobileOpen = true" class="inline-flex items-center gap-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span class="font-semibold">Menu</span>
        </button>
        <h2 class="text-base font-bold">Admin Panel</h2>
      </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div v-if="mobileOpen" class="lg:hidden">
      <div class="fixed inset-0 bg-black/50 z-40" @click="mobileOpen = false"></div>
      <aside class="fixed inset-y-0 left-0 w-64 bg-[#335a3b] text-white p-6 z-50 overflow-y-auto">
        <h2 class="text-2xl font-extrabold mb-6">Admin Panel</h2>
        <nav class="space-y-2">
          <Link :class="linkClasses('/admin/dashboard')" href="/admin/dashboard" @click="mobileOpen = false">Dashboard Home</Link>
          <Link :class="linkClasses('/admin/missing-reports')" href="/admin/missing-reports" @click="mobileOpen = false">Manage Missing Persons</Link>
          <Link :class="linkClasses('/admin/sighting-reports')" href="/admin/sighting-reports" @click="mobileOpen = false">Manage Sighting Reports</Link>
          <Link :class="linkClasses('/admin/volunteers')" href="/admin/volunteers" @click="mobileOpen = false">Manage Volunteers</Link>
          <Link :class="linkClasses('/admin/community-projects')" href="/admin/community-projects" @click="mobileOpen = false">Manage Community Project</Link>
          <Link :class="linkClasses('/admin/rewards')" href="/admin/rewards" @click="mobileOpen = false">Manage Rewards</Link>
          <Link :class="linkClasses('/admin/users')" href="/admin/users" @click="mobileOpen = false">Manage Users</Link>
          <Link :class="linkClasses('/admin/contact-messages')" href="/admin/contact-messages" @click="mobileOpen = false">Contact Messages</Link>
          <Link :class="linkClasses('/admin/logs')" href="/admin/logs" @click="mobileOpen = false">System Logs</Link>
          <Link :class="linkClasses('/dashboard', 'mt-4')" href="/" @click="mobileOpen = false">Back To Main Dashboard</Link>
        </nav>
      </aside>
    </div>

    <aside class="hidden lg:block w-64 bg-[#335a3b] text-white p-6 sticky top-0 h-screen overflow-y-auto">
      <h2 class="text-2xl font-extrabold mb-6">Admin Panel</h2>
      <nav class="space-y-2">
        <Link :class="linkClasses('/admin/dashboard')" href="/admin/dashboard">Dashboard Home</Link>
        <Link :class="linkClasses('/admin/missing-reports')" href="/admin/missing-reports">Manage Missing Persons</Link>
        <Link :class="linkClasses('/admin/sighting-reports')" href="/admin/sighting-reports">Manage Sighting Reports</Link>
        <Link :class="linkClasses('/admin/volunteers')" href="/admin/volunteers">Manage Volunteers</Link>
        <Link :class="linkClasses('/admin/community-projects')" href="/admin/community-projects">Manage Community Project</Link>
        <Link :class="linkClasses('/admin/rewards')" href="/admin/rewards">Manage Rewards</Link>
        <Link :class="linkClasses('/admin/users')" href="/admin/users">Manage Users</Link>
        <Link :class="linkClasses('/admin/contact-messages')" href="/admin/contact-messages">Contact Messages</Link>
        <Link :class="linkClasses('/admin/logs')" href="/admin/logs">System Logs</Link>
        <Link :class="linkClasses('/dashboard', 'mt-4')" href="/">Back To Main Dashboard</Link>
      </nav>
    </aside>
    <main class="flex-1 p-8 overflow-y-auto pt-16 lg:pt-8">
      <slot />
    </main>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>


