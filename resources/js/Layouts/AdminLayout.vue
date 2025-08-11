<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <div class="min-h-screen flex bg-[#f6f7f9] text-[#1f2937]">
    <aside class="w-64 bg-[#335a3b] text-white p-6">
      <h2 class="text-2xl font-extrabold mb-6">Admin Panel</h2>
      <nav class="space-y-2">
        <Link href="/admin/dashboard" class="block px-3 py-2 rounded hover:bg-white/10">Dashboard Home</Link>
        <Link href="/admin/missing-reports" class="block px-3 py-2 rounded hover:bg-white/10">Manage Missing Persons</Link>
        <Link href="/admin/sighting-reports" class="block px-3 py-2 rounded hover:bg-white/10">Manage Sighting Reports</Link>
        <Link href="/admin/community-projects" class="block px-3 py-2 rounded hover:bg-white/10">Manage Community Project</Link>
        <Link href="/admin/rewards" class="block px-3 py-2 rounded hover:bg-white/10">Manage Rewards</Link>
        <Link href="/admin/users" class="block px-3 py-2 rounded hover:bg-white/10">Manage Users</Link>
        <Link href="/admin/logs" class="block px-3 py-2 rounded hover:bg-white/10">System Logs</Link>
        <Link href="/dashboard" class="block px-3 py-2 rounded bg-white text-[#335a3b] font-semibold mt-4">Back To Main Dashboard</Link>
      </nav>
    </aside>
    <main class="flex-1 p-8">
      <slot />
    </main>
  </div>
</template>


