<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
defineOptions({ layout: AdminLayout })
const props = defineProps({ applications: Object })

function setStatus(id, status) {
  const reason = status === 'Rejected' ? prompt('Reason (optional):') : ''
  router.post(route('admin.volunteers.status', id), { status, reason })
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-6">Volunteer Applications</h1>
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left">Motivation</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in applications.data" :key="a.id" class="border-t">
            <td class="px-4 py-3">{{ a.id }}</td>
            <td class="px-4 py-3">{{ a.user?.name }}<br><span class="text-xs text-gray-500">{{ a.user?.email }}</span></td>
            <td class="px-4 py-3 max-w-md truncate">{{ a.motivation }}</td>
            <td class="px-4 py-3">{{ a.status }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-col gap-2">
                <button class="px-3 py-1 text-white bg-green-600 rounded hover:bg-green-700" @click="setStatus(a.id, 'Approved')">Approve</button>
                <button class="px-3 py-1 text-white bg-red-600 rounded hover:bg-red-700" @click="setStatus(a.id, 'Rejected')">Reject</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>


