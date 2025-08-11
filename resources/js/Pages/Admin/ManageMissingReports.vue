<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ items: Array, pagination: Object })
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Manage Missing Person Reports</h1>
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">Case ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Report By</th>
            <th class="px-4 py-3 text-left">Date & Time</th>
            <th class="px-4 py-3 text-left">Last Seen</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in props.items" :key="row.id" class="border-t">
            <td class="px-4 py-3">{{ row.id }}</td>
            <td class="px-4 py-3">{{ row.name }}</td>
            <td class="px-4 py-3">{{ row.reporter }}</td>
            <td class="px-4 py-3">{{ row.created_at }}</td>
            <td class="px-4 py-3">{{ row.last_seen }}</td>
            <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs" :class="row.status === 'Missing' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">{{ row.status }}</span></td>
            <td class="px-4 py-3 space-x-2">
              <button class="px-3 py-1 text-white bg-green-600 rounded">Approve</button>
              <button class="px-3 py-1 text-white bg-red-600 rounded">Reject</button>
              <button class="px-3 py-1 bg-gray-100 rounded">Request Info</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>


