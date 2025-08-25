<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Debug Authentication</h1>
    
    <div class="space-y-4">
      <div class="border p-4 rounded">
        <h2 class="font-semibold mb-2">User Information</h2>
        <div><strong>Authenticated:</strong> {{ $page.props.auth ? 'Yes' : 'No' }}</div>
        <div v-if="$page.props.auth"><strong>Name:</strong> {{ $page.props.auth.user.name }}</div>
        <div v-if="$page.props.auth"><strong>Email:</strong> {{ $page.props.auth.user.email }}</div>
        <div v-if="$page.props.auth"><strong>Role:</strong> {{ $page.props.auth.user.role }}</div>
      </div>
      
      <div class="border p-4 rounded">
        <h2 class="font-semibold mb-2">CSRF Token</h2>
        <div><strong>Token:</strong> {{ csrfToken }}</div>
      </div>
      
      <button @click="testApi" class="bg-blue-500 text-white px-4 py-2 rounded">
        Test API Call
      </button>
      
      <div v-if="apiResult" class="border p-4 rounded">
        <h2 class="font-semibold mb-2">API Result</h2>
        <pre>{{ JSON.stringify(apiResult, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const csrfToken = ref('')
const apiResult = ref(null)

onMounted(() => {
  const tokenElement = document.querySelector('meta[name="csrf-token"]')
  csrfToken.value = tokenElement ? tokenElement.getAttribute('content') : 'Not found'
})

function testApi() {
  fetch('/admin/community-projects/applications', {
    headers: {
      'X-CSRF-TOKEN': csrfToken.value,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    credentials: 'same-origin'
  })
    .then(response => {
      apiResult.value = {
        status: response.status,
        statusText: response.statusText,
        ok: response.ok
      }
      return response.json()
    })
    .then(data => {
      apiResult.value.data = data
    })
    .catch(error => {
      apiResult.value = {
        error: error.message
      }
    })
}
</script>
