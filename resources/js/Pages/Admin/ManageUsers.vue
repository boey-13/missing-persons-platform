<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
defineOptions({ layout: AdminLayout })

const props = defineProps({ users: Array })

function changeRole(userId, role) {
  router.post(`/admin/users/${userId}/role`, { role })
}

function deleteUser(userId) {
  if (confirm('Delete this user?')) {
    router.delete(`/admin/users/${userId}`)
  }
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Manage Users</h1>
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in props.users" :key="u.id" class="border-t">
            <td class="px-4 py-3">{{ u.id }}</td>
            <td class="px-4 py-3">{{ u.name }}</td>
            <td class="px-4 py-3">{{ u.email }}</td>
            <td class="px-4 py-3">
              <select :value="u.role" @change="e => changeRole(u.id, e.target.value)" class="border rounded px-2 py-1">
                <option value="user">User</option>
                <option value="volunteer">Volunteer</option>
                <option value="admin">Admin</option>
              </select>
            </td>
            <td class="px-4 py-3">
              <button class="px-3 py-1 text-white bg-red-600 rounded" @click="() => deleteUser(u.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>


