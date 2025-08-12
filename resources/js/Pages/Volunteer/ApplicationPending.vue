<script setup>
const props = defineProps({ application: Object })
</script>

<template>
  <div class="max-w-3xl mx-auto py-12 text-center">
    <h1 class="text-3xl font-extrabold mb-4">Application Submitted</h1>
    <p class="text-gray-700">Your volunteer application has been submitted and is now pending review.</p>
    <p class="mt-2 text-sm text-gray-600">Reference ID: {{ application?.id }}</p>
  </div>
</template>


