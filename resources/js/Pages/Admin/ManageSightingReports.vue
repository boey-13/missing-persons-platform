<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
defineOptions({ layout: AdminLayout })

const props = defineProps({ items: Array, pagination: Object })
const showModal = ref(false)
const modalData = ref(null)

async function openDetail(id) {
  const res = await fetch(`/admin/sighting-reports/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
  modalData.value = await res.json()
  showModal.value = true
}

function changeStatus(id, status) {
  router.post(`/admin/sighting-reports/${id}/status`, { status })
}

</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Manage Sightings Reports</h1>
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Location</th>
            <th class="px-4 py-3 text-left">Sighted At</th>
            <th class="px-4 py-3 text-left">Reporter</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in props.items" :key="row.id" class="border-t">
            <td class="px-4 py-3">{{ row.id }}</td>
            <td class="px-4 py-3">{{ row.location }}</td>
            <td class="px-4 py-3">{{ row.sighted_at || '-' }}</td>
            <td class="px-4 py-3">{{ row.reporter }}</td>
            <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs" :class="row.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : (row.status === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')">{{ row.status }}</span></td>
            <td class="px-4 py-3">
              <div class="flex flex-col gap-2">
                <button class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200" @click="openDetail(row.id)">View</button>
                <button class="px-3 py-1 text-white bg-green-600 rounded hover:bg-green-700" @click="changeStatus(row.id, 'Approved')">Approve</button>
                <button class="px-3 py-1 text-white bg-red-600 rounded hover:bg-red-700" @click="changeStatus(row.id, 'Rejected')">Reject</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Sighting Detail</h2>
            <button class="text-gray-500 hover:text-black" @click="showModal=false">✕</button>
          </div>
          <div v-if="modalData" class="space-y-3">
            <div><strong>Location:</strong> {{ modalData.location }}</div>
            <div><strong>Sighted At:</strong> {{ modalData.sighted_at || '-' }}</div>
            <div><strong>Reporter:</strong> {{ modalData.reporter_name }} ({{ modalData.reporter_phone }})</div>
            <div v-if="modalData.reporter_email"><strong>Email:</strong> {{ modalData.reporter_email }}</div>
            <div><strong>Status:</strong> {{ modalData.status }}</div>
            <div><strong>Description:</strong><br/> <span class="text-gray-700">{{ modalData.description || '-' }}</span></div>
            <div v-if="modalData.photos && modalData.photos.length" class="mt-2 flex gap-2 flex-wrap">
              <img v-for="(p,i) in modalData.photos" :key="i" :src="'/storage/' + p" class="w-28 h-28 object-cover rounded border" />
            </div>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>


