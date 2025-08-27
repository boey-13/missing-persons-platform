<script setup>
import { defineProps } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    person: {
        type: Object,
        required: true
    }
})

const defaultAvatar = '/images/default-avatar.png'

function getStatusColor(status) {
  const colors = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Approved': 'bg-green-100 text-green-800',
    'Rejected': 'bg-red-100 text-red-800',
    'Missing': 'bg-blue-100 text-blue-800',
    'Found': 'bg-green-100 text-green-800',
    'Closed': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getDisplayStatus(status) {
  const displayStatus = {
    'Pending': 'Pending Verify',
    'Approved': 'Verified',
    'Rejected': 'Rejected',
    'Missing': 'Missing',
    'Found': 'Found',
    'Closed': 'Closed'
  }
  return displayStatus[status] || status
}
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-col items-center hover:shadow-md transition h-80">
    <!-- 更大的头像区域 -->
    <div class="w-32 h-32 bg-[#B3D4FC] rounded-xl flex items-center justify-center mb-3 overflow-hidden">
      <img v-if="person.photo_url" :src="person.photo_url" alt="Photo" class="w-full h-full rounded object-cover" />
      <img v-else src="../../assets/default-avatar.jpg" alt="Default Avatar" class="w-full h-full rounded object-cover" />
    </div>
    
    <!-- Status Badge -->
    <div v-if="person.case_status" class="mb-2">
      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(person.case_status)">
        {{ getDisplayStatus(person.case_status) }}
      </span>
    </div>
    
    <div class="text-center flex-1 flex flex-col justify-center">
      <div class="font-medium text-md">{{ person.full_name || 'Name:xx' }}</div>
      <div class="text-sm text-gray-600">AGE: {{ person.age || 'xx' }}</div>
      <div class="text-sm text-gray-600 leading-snug truncate max-w-[200px] mx-auto">{{ person.last_seen_location || 'xx' }}</div>
    </div>
    <Link
      :href="`/missing-persons/${person.id}`"
      class="mt-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
    >
      View Details
    </Link>
  </div>
</template>

