<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { ref, onMounted, watch, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })

const props = defineProps({
  report: Object
})

// Google Map setup
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

// Carousel logic
const photos = computed(() => props.report.photo_paths || [])
const currentPhotoIndex = ref(0)
function photoUrl(filename) {
  return '/storage/' + filename
}
function prevPhoto() {
  if (currentPhotoIndex.value > 0) currentPhotoIndex.value--
}
function nextPhoto() {
  if (currentPhotoIndex.value < photos.value.length - 1) currentPhotoIndex.value++
}
function goPhoto(idx) {
  currentPhotoIndex.value = idx
}

const showPhotoModal = ref(false)
const modalPhotoUrl = ref('')     // URL for the photo in modal

function openPhotoModal() {
  if (photos.value.length > 0) {
    modalPhotoUrl.value = photoUrl(photos.value[currentPhotoIndex.value])
    showPhotoModal.value = true
  }
}
function closePhotoModal() {
  showPhotoModal.value = false
}
</script>

<template>
  <div class="flex flex-col min-h-screen bg-[#fcfbf7] font-['Poppins'] text-[#333]">

    <!-- Share Modal -->
    <teleport to="body">
      <div v-if="showPhotoModal" class="fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center">
        <div class="relative">
          <img :src="modalPhotoUrl" alt="Full Size Photo"
            class="max-h-[90vh] max-w-[95vw] rounded-2xl shadow-xl border-4 border-white bg-white"
            @click="closePhotoModal" style="cursor:zoom-out;" />
          <button @click="closePhotoModal"
            class="absolute top-2 right-2 text-white bg-black/70 rounded-full px-3 py-1 text-lg font-bold shadow hover:bg-black/90">×</button>
        </div>
      </div>
      <div v-if="showShareModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg p-6 w-[90%] max-w-sm text-center">
          <h2 class="text-lg font-semibold mb-4">Share to Social Media</h2>
          <div class="flex justify-around mb-4 text-3xl">
            <a :href="`https://www.instagram.com/`" target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-instagram text-[#E4405F]"></i>
            </a>
            <a :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`" target="_blank"
              class="hover:scale-110 transition">
              <i class="fab fa-facebook text-[#1877F2]"></i>
            </a>
            <a :href="`https://wa.me/?text=${encodeURIComponent(currentUrl)}`" target="_blank"
              class="hover:scale-110 transition">
              <i class="fab fa-whatsapp text-[#25D366]"></i>
            </a>
            <a :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=Help%20find%20this%20missing%20person!`"
              target="_blank" class="hover:scale-110 transition">
              <i class="fab fa-twitter text-[#1DA1F2]"></i>
            </a>
          </div>
          <button @click="showShareModal = false" class="text-sm text-gray-600 hover:text-black">Close</button>
        </div>
      </div>
    </teleport>

    <!-- Main Content Area-->
    <main>
      <div class="bg-white rounded-2xl shadow-xl border-2 border-[#ebebeb] p-10">
        <h1 class="text-3xl font-bold text-center mb-2 tracking-tight">Missing Person Details</h1>
        <p class="text-center text-gray-600 mb-10">
          Help us reunite families by sharing or reporting any information you may have.
        </p>

        <div class="flex flex-col lg:flex-row gap-8">
          <!-- Photo Carousel Area -->
          <div
            class="w-full lg:w-[330px] rounded-xl shadow p-4 flex flex-col items-center justify-center border border-[#f2e5d5] bg-[#fffaf6]">
            <template v-if="photos.length > 0">
              <div class="flex items-center justify-center gap-2 mb-3">
                <!-- Left Arrow (outside photo) -->
                <button @click="prevPhoto" :disabled="currentPhotoIndex === 0"
                  class="bg-white hover:bg-orange-100 rounded-full p-2 shadow transition disabled:opacity-30"
                  aria-label="Previous photo">
                  <i class="fas fa-chevron-left text-2xl"></i>
                </button>
                <!-- Main Photo, even bigger -->
                <div
                  class="w-[270px] h-[340px] rounded-2xl overflow-hidden flex items-center justify-center bg-gray-100 shadow">
                  <img :src="photoUrl(photos[currentPhotoIndex])" alt="Missing Person Photo"
                    class="object-contain w-full h-full max-h-[330px] max-w-[255px] transition-all duration-300 bg-white cursor-zoom-in"
                    style="user-select: none; border-radius: 14px;" @click="openPhotoModal">
                </div>
                <!-- Right Arrow (outside photo) -->
                <button @click="nextPhoto" :disabled="currentPhotoIndex === photos.length - 1"
                  class="bg-white hover:bg-orange-100 rounded-full p-2 shadow transition disabled:opacity-30"
                  aria-label="Next photo">
                  <i class="fas fa-chevron-right text-2xl"></i>
                </button>
              </div>
              <!-- Index -->
              <div class="text-gray-600 text-sm mb-1">{{ currentPhotoIndex + 1 }} / {{ photos.length }}</div>
              <!-- Thumbnails -->
              <div class="flex gap-2 justify-center mt-1">
                <img v-for="(p, i) in photos" :key="i" :src="photoUrl(p)" @click="goPhoto(i)" :class="[
                  'rounded-md cursor-pointer object-cover border-2 transition-all duration-200',
                  i === currentPhotoIndex ? 'border-orange-400 ring-2 ring-orange-300 scale-105' : 'border-gray-300 hover:border-sky-400'
                ]" style="width:56px;height:56px; background:#f8fafb;" :alt="'Thumbnail ' + (i + 1)" />
              </div>
            </template>
            <div v-else class="text-gray-400 text-sm text-center mt-10">No photo available</div>
          </div>



          <!-- Person Info Area -->
          <div class="flex-1 bg-white rounded-xl shadow p-8 border border-[#eee]">
            <div class="flex justify-between items-start">
              <div>
                <p class="mb-2"><span class="font-semibold text-gray-800">Name:</span> {{ report.full_name }}</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Gender:</span> {{ report.gender }}</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Age:</span> {{ report.age }}</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Height:</span> {{ report.height_cm }} cm</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Weight:</span> {{ report.weight_kg }} kg</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Last Seen Location:</span> {{
                  report.last_seen_location
                }}</p>
                <p class="mb-2"><span class="font-semibold text-gray-800">Last Seen Date:</span>
                  {{ new Date(report.last_seen_date).toLocaleDateString('en-GB', {
                    day: 'numeric', month: 'long', year:
                      'numeric'
                  }) }}
                </p>
              </div>
              <!-- Share Icon -->
              <button @click="showShareModal = true" class="text-orange-400 hover:text-orange-600 text-2xl mt-2">
                <i class="fas fa-share-alt"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Case Info Section -->
        <div class="mt-8 bg-white rounded-xl shadow p-8 border border-[#eee]">
          <h2 class="text-xl font-semibold mb-3">Case Information</h2>
          <ul class="list-disc pl-7 space-y-1 text-gray-700">
            <li><strong>Physical Description:</strong> {{ report.physical_description || '—' }}</li>
            <li><strong>Clothing Description:</strong> {{ report.last_seen_clothing || '—' }}</li>
            <li><strong>Other Notes:</strong> {{ report.additional_notes || '—' }}</li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-5 justify-center">
          <Link :href="`/missing-persons/${report.id}/preview-poster`"
            class="px-7 py-2 rounded bg-sky-500 text-white hover:bg-sky-600 text-base font-semibold shadow transition">
          Download Poster
          </Link>
          <Link :href="`/missing-persons/${report.id}/report-sighting`"
            class="px-7 py-2 rounded bg-orange-500 text-white hover:bg-orange-600 text-base font-semibold shadow transition">
            Submit Sighting
          </Link>
        </div>

        <!-- Map Section -->
        <div class="mt-12">
          <h2 class="text-xl font-semibold mb-3">Last Seen Location</h2>
          <div ref="mapDiv" class="w-full h-64 rounded shadow border"></div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
h1,
h2,
h3,
h4,
h5,
h6 {
  color: #333;
}
</style>
