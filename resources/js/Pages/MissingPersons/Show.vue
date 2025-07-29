<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { ref, onMounted, watch } from 'vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  report: Object
})

const mapDiv = ref(null)
const map = ref(null)
const marker = ref(null)
const mapLat = ref(3.139)
const mapLng = ref(101.6869)
const mapZoom = ref(14)

const showShareModal = ref(false)
const currentUrl = window.location.href

function showMap() {
  if (!mapDiv.value || !window.google || !window.google.maps) return

  const latLng = { lat: mapLat.value, lng: mapLng.value }

  if (!map.value) {
    map.value = new window.google.maps.Map(mapDiv.value, {
      center: latLng,
      zoom: mapZoom.value,
    })

    marker.value = new window.google.maps.Marker({
      position: latLng,
      map: map.value,
      title: props.report.full_name
    })
  } else {
    map.value.setCenter(latLng)
    marker.value.setPosition(latLng)
  }
}

onMounted(() => {
  if (window.google && window.google.maps && window.google.maps.Geocoder && props.report.last_seen_location) {
    const geocoder = new google.maps.Geocoder()
    geocoder.geocode({ address: props.report.last_seen_location }, (results, status) => {
      if (status === 'OK' && results[0]) {
        const location = results[0].geometry.location
        mapLat.value = location.lat()
        mapLng.value = location.lng()
      } else {
        console.warn('Geocode failed:', status)
      }
    })

    setTimeout(showMap, 1200)
  }
})

watch([mapLat, mapLng], showMap)
</script>

<template>
  <div class="flex flex-col min-h-screen bg-[#f5f3f0] font-['Poppins'] text-[#333]">
    <!-- Share Modal -->
    <teleport to="body">
      <div v-if="showShareModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg p-6 w-[90%] max-w-sm text-center">
          <h2 class="text-lg font-semibold mb-4">Share to Social Media</h2>
          <div class="flex justify-around mb-4 text-3xl">
            <a :href="`https://www.instagram.com/`" target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-instagram text-[#E4405F]"></i>
            </a>
            <a :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`" target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-facebook text-[#1877F2]"></i>
            </a>
            <a :href="`https://wa.me/?text=${encodeURIComponent(currentUrl)}`" target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-whatsapp text-[#25D366]"></i>
            </a>
            <a :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=Help%20find%20this%20missing%20person!`" target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-twitter text-[#1DA1F2]"></i>
            </a>
          </div>
          <button @click="showShareModal = false" class="text-sm text-gray-600 hover:text-black">Close</button>
        </div>
      </div>
    </teleport>

    <!-- Main Content -->
    <main class="flex-1 p-6 max-w-5xl mx-auto">
      <h1 class="text-3xl font-bold text-center mb-1">Missing Person Details</h1>
      <p class="text-center text-gray-600 mb-8">
        Help us reunite families by sharing or reporting any information you may have.
      </p>

      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Image -->
        <div class="w-full lg:w-1/3 bg-white rounded-xl shadow p-4 flex items-center justify-center">
          <img v-if="report.photo_paths && report.photo_paths.length > 0"
               :src="`/storage/${report.photo_paths[0]}`"
               alt="Missing Person Photo"
               class="rounded-xl object-cover w-full h-72" />
          <div v-else class="text-gray-400 text-sm text-center">No photo available</div>
        </div>

        <!-- Person Info -->
        <div class="flex-1 bg-white rounded-xl shadow p-6">
          <div class="flex justify-between items-start">
            <div>
              <p><span class="font-semibold">Name:</span> {{ report.full_name }}</p>
              <p><span class="font-semibold">Gender:</span> {{ report.gender }}</p>
              <p><span class="font-semibold">Age:</span> {{ report.age }}</p>
              <p><span class="font-semibold">Height:</span> {{ report.height_cm }} cm</p>
              <p><span class="font-semibold">Weight:</span> {{ report.weight_kg }} kg</p>
              <p><span class="font-semibold">Last Seen Location:</span> {{ report.last_seen_location }}</p>
              <p><span class="font-semibold">Last Seen Date:</span>
                {{ new Date(report.last_seen_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>

            <!-- Share Icon -->
            <button @click="showShareModal = true" class="text-orange-400 hover:text-orange-600 text-2xl">
              <i class="fas fa-share-alt"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Case Info -->
      <div class="mt-6 bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-2">Case Information</h2>
        <ul class="list-disc pl-6 space-y-1 text-gray-700">
          <li><strong>Physical Description:</strong> {{ report.physical_description || '—' }}</li>
          <li><strong>Clothing Description:</strong> {{ report.last_seen_clothing || '—' }}</li>
          <li><strong>Other Notes:</strong> {{ report.additional_notes || '—' }}</li>
        </ul>
      </div>

      <!-- Actions -->
      <div class="mt-6 flex gap-4 justify-center">
        <button class="px-6 py-2 rounded bg-sky-500 text-white hover:bg-sky-600 text-sm font-medium shadow">
          Download Poster
        </button>
        <button class="px-6 py-2 rounded bg-orange-500 text-white hover:bg-orange-600 text-sm font-medium shadow">
          Submit Sighting
        </button>
      </div>

      <!-- Map -->
      <div class="mt-10">
        <h2 class="text-xl font-semibold mb-2">Last Seen Location</h2>
        <div ref="mapDiv" class="w-full h-64 rounded shadow border"></div>
      </div>
    </main>
  </div>
</template>

<style scoped>
h1, h2, h3, h4, h5, h6 {
  color: #333;
}
</style>
