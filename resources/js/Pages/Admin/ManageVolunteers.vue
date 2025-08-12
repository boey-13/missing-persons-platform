<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
defineOptions({ layout: AdminLayout })
const props = defineProps({ applications: Object })

function setStatus(id, status) {
  const reason = status === 'Rejected' ? prompt('Reason (optional):') : ''
  router.post(route('admin.volunteers.status', id), { status, reason })
}

const showModal = ref(false)
const detail = ref(null)

function openDetail(app) {
  detail.value = app
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  detail.value = null
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
                <button class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200" @click="openDetail(a)">View</button>
                <button class="px-3 py-1 text-white bg-green-600 rounded hover:bg-green-700" @click="setStatus(a.id, 'Approved')">Approve</button>
                <button class="px-3 py-1 text-white bg-red-600 rounded hover:bg-red-700" @click="setStatus(a.id, 'Rejected')">Reject</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Detail Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-[95%] max-w-3xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Volunteer Application #{{ detail?.id }}</h2>
            <button class="text-gray-500 hover:text-black" @click="closeModal">✕</button>
          </div>

          <div v-if="detail" class="space-y-4 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-gray-500">User</div>
                <div class="font-medium">{{ detail.user?.name }} <span class="text-gray-500 text-xs">({{ detail.user?.email }})</span></div>
              </div>
              <div>
                <div class="text-gray-500">Status</div>
                <div class="font-medium">{{ detail.status }}</div>
                <div v-if="detail.status_reason" class="text-xs text-gray-500 mt-1">Reason: {{ detail.status_reason }}</div>
              </div>
            </div>

            <div>
              <div class="text-gray-500 mb-1">Motivation</div>
              <div class="whitespace-pre-wrap">{{ detail.motivation }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-gray-500 mb-1">Skills</div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="s in (detail.skills || [])" :key="s" class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ s }}</span>
                </div>
              </div>
              <div>
                <div class="text-gray-500 mb-1">Languages</div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="l in (detail.languages || [])" :key="l" class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ l }}</span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-gray-500 mb-1">Availability</div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="d in (detail.availability || [])" :key="d" class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ d }}</span>
                </div>
              </div>
              <div>
                <div class="text-gray-500 mb-1">Preferred Roles</div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="r in (detail.preferred_roles || [])" :key="r" class="px-2 py-0.5 rounded-full text-xs bg-gray-100">{{ r }}</span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-gray-500">Areas Willing to Help</div>
                <div class="font-medium">{{ detail.areas || '-' }}</div>
              </div>
              <div>
                <div class="text-gray-500">Transport Mode</div>
                <div class="font-medium">{{ detail.transport_mode || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-gray-500">Emergency Contact</div>
                <div class="font-medium">{{ detail.emergency_contact_name }} ({{ detail.emergency_contact_phone }})</div>
              </div>
              <div>
                <div class="text-gray-500">Prior Experience</div>
                <div class="font-medium">{{ detail.prior_experience || '-' }}</div>
              </div>
            </div>

            <div>
              <div class="text-gray-500 mb-1">Supporting Documents</div>
              <div v-if="detail.supporting_documents && detail.supporting_documents.length" class="flex flex-wrap gap-2">
                <a v-for="(p,i) in detail.supporting_documents" :key="i" :href="'/storage/' + p" target="_blank" class="text-blue-600 underline">Document {{ i+1 }}</a>
              </div>
              <div v-else class="text-gray-500 text-sm">No documents</div>
            </div>

            <div class="text-xs text-gray-500">Created: {{ detail.created_at }}</div>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>


