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
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-col items-center hover:shadow-md transition h-80">
    <!-- 更大的头像区域 -->
    <div class="w-32 h-32 bg-[#B3D4FC] rounded-xl flex items-center justify-center mb-3 overflow-hidden">
      <img v-if="person.photo_url" :src="person.photo_url" alt="Photo" class="w-full h-full rounded object-cover" />
      <div v-else class="w-20 h-20 bg-[#87CEEB] rounded-full"></div>
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

