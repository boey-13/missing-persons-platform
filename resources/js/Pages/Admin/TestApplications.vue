<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Test Applications API</h1>
    
    <button @click="fetchApplications" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">
      Fetch Applications
    </button>
    
    <div v-if="loading" class="text-gray-600">Loading...</div>
    
    <div v-if="error" class="text-red-600 mb-4">{{ error }}</div>
    
    <div v-if="applications.length > 0" class="space-y-4">
      <h2 class="text-lg font-semibold">Applications ({{ applications.length }})</h2>
      <div v-for="app in applications" :key="app.id" class="border p-4 rounded">
        <div><strong>ID:</strong> {{ app.id }}</div>
        <div><strong>Status:</strong> {{ app.status }}</div>
        <div><strong>Volunteer:</strong> {{ app.volunteerName }}</div>
        <div><strong>Project:</strong> {{ app.projectTitle }}</div>
        <div><strong>Created:</strong> {{ app.created_at }}</div>
      </div>
    </div>
    
    <div v-else-if="!loading" class="text-gray-600">
      No applications found
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const applications = ref([])
const loading = ref(false)
const error = ref('')

function fetchApplications() {
  loading.value = true
  error.value = ''
  
  fetch('/admin/community-projects/applications', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    credentials: 'same-origin'
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then(data => {
      applications.value = data
      console.log('Fetched applications:', data)
    })
    .catch(err => {
      error.value = err.message
      console.error('Error fetching applications:', err)
    })
    .finally(() => {
      loading.value = false
    })
}
</script>
