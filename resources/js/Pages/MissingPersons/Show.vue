<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { ref, onMounted, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })

const props = defineProps({
  report: Object,
  flash: Object
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

// Social share functions with points tracking
async function shareToSocial(platform) {
  try {
    // Record the share for points
    const response = await fetch('/api/social-share', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        report_id: props.report.id,
        platform: platform
      })
    })

    const result = await response.json()
    
    if (result.success) {
      // Show success message
      alert(`Shared successfully! You earned ${result.pointsAwarded} point!`)
    } else {
      // Show message if already shared
      alert(result.message)
    }

    // Open social media share URL
    const shareUrls = {
      facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`,
      twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=Help%20find%20this%20missing%20person!`,
      whatsapp: `https://wa.me/?text=${encodeURIComponent(currentUrl)}`,
      instagram: 'https://www.instagram.com/'
    }

    if (shareUrls[platform]) {
      window.open(shareUrls[platform], '_blank')
    }

  } catch (error) {
    console.error('Error recording social share:', error)
    alert('Error recording share. Please try again.')
  }
}

function reportSighting() {
  router.visit(`/sighting-reports/report?report_id=${props.report.id}`)
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function getStatusColor(status) {
  const colors = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Approved': 'bg-green-100 text-green-800',
    'Missing': 'bg-red-100 text-red-800',
    'Found': 'bg-blue-100 text-blue-800',
    'Rejected': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="min-h-screen bg-white font-['Poppins'] text-[#222]">
    <!-- Photo Modal -->
    <teleport to="body">
      <div v-if="showPhotoModal" class="fixed inset-0 z-50 grid place-items-center bg-black/80">
        <div class="relative">
          <img :src="modalPhotoUrl" alt="Full Size Photo"
               class="max-h-[90vh] max-w-[95vw] rounded-2xl shadow-2xl border-4 border-white bg-white"
               @click="closePhotoModal" style="cursor:zoom-out;" />
          <button @click="closePhotoModal"
                  class="absolute top-2 right-2 text-white bg-black/70 rounded-full px-3 py-1 text-lg font-bold shadow hover:bg-black/90">×</button>
        </div>
      </div>

      <!-- Share Modal -->
      <div v-if="showShareModal" class="fixed inset-0 z-50 grid place-items-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-[90%] max-w-sm text-center">
          <h2 class="text-lg font-semibold mb-4">Share to Social Media</h2>
          <div class="flex justify-around mb-4 text-3xl">
            <button @click="shareToSocial('instagram')" class="hover:scale-110 transition">
              <i class="fab fa-instagram text-[#E4405F]"></i>
            </button>
            <button @click="shareToSocial('facebook')" class="hover:scale-110 transition">
              <i class="fab fa-facebook text-[#1877F2]"></i>
            </button>
            <button @click="shareToSocial('whatsapp')" class="hover:scale-110 transition">
              <i class="fab fa-whatsapp text-[#25D366]"></i>
            </button>
            <button @click="shareToSocial('twitter')" class="hover:scale-110 transition">
              <i class="fab fa-twitter text-[#1DA1F2]"></i>
            </button>
          </div>
          <button @click="showShareModal = false" class="text-sm text-gray-600 hover:text-black">Close</button>
        </div>
      </div>
    </teleport>

    <!-- Hero / Header -->
    <header class="border-b border-[#ebebeb] bg-white/70 backdrop-blur">
      <div class="max-w-6xl mx-auto px-5 py-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="space-y-1">
            <h1 class="text-2xl font-extrabold tracking-tight">Missing Person Details</h1>
            <p class="text-sm text-gray-600">Help us reunite families by sharing or reporting any information you may have.</p>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="report.case_status"
                  :class="`inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium capitalize ${getStatusColor(report.case_status)}`">
              <span class="inline-block h-2 w-2 rounded-full bg-current/70"></span>
              {{ report.case_status }}
            </span>
            <button @click="showShareModal = true"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">
              <i class="fas fa-share-alt text-orange-500"></i>
              Share
            </button>
          </div>
        </div>

        <!-- Meta line -->
        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-600">
          <div class="inline-flex items-center gap-2">
            <i class="far fa-calendar"></i>
            <span>Last seen: {{ formatDate(report.last_seen_date) }}</span>
          </div>
          <div class="inline-flex items-center gap-2">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ report.last_seen_location }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-5 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Carousel -->
        <section class="lg:col-span-4">
          <div class="relative group">
            <div class="aspect-[3/4] w-full overflow-hidden rounded-2xl border border-[#f2e5d5] bg-[#fffaf6] shadow">
              <template v-if="photos.length">
                <img
                  :src="photoUrl(photos[currentPhotoIndex])"
                  alt="Missing Person Photo"
                  class="h-full w-full object-contain bg-white transition"
                  @click="openPhotoModal"
                  style="cursor:zoom-in;"
                />

                <!-- arrows -->
                <button @click="prevPhoto"
                        :disabled="currentPhotoIndex === 0"
                        class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white disabled:opacity-40"
                        aria-label="Previous photo">
                  <i class="fas fa-chevron-left text-lg"></i>
                </button>
                <button @click="nextPhoto"
                        :disabled="currentPhotoIndex === photos.length - 1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white disabled:opacity-40"
                        aria-label="Next photo">
                  <i class="fas fa-chevron-right text-lg"></i>
                </button>

                <!-- index -->
                <div class="absolute bottom-2 right-3 rounded-full bg-black/60 px-3 py-1 text-xs text-white">
                  {{ currentPhotoIndex + 1 }} / {{ photos.length }}
                </div>
              </template>

                             <div v-else class="h-full grid place-items-center">
                 <img src="../../assets/default-avatar.jpg" alt="Default Avatar" class="max-h-full max-w-full object-contain" />
               </div>
            </div>

            <!-- thumbs -->
            <div v-if="photos.length" class="mt-3 flex gap-2 overflow-x-auto pb-1">
              <img v-for="(p, i) in photos" :key="i" :src="photoUrl(p)" @click="goPhoto(i)"
                   :alt="'Thumbnail ' + (i+1)"
                   class="h-14 w-14 flex-none rounded-md object-cover border transition"
                   :class="i === currentPhotoIndex ? 'border-orange-400 ring-2 ring-orange-300' : 'border-gray-300 hover:border-sky-400'"/>
            </div>
          </div>
        </section>

        <!-- Info -->
        <section class="lg:col-span-8">
          <div class="rounded-2xl border border-[#eee] bg-white shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Person Information</h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
              <div class="flex items-start gap-3">
                <i class="far fa-id-badge mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Name</dt>
                  <dd class="font-medium text-gray-900">{{ report.full_name }}</dd>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <i class="fas fa-venus-mars mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Gender</dt>
                  <dd class="font-medium text-gray-900">{{ report.gender }}</dd>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <i class="far fa-clock mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Age</dt>
                  <dd class="font-medium text-gray-900">{{ report.age }}</dd>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <i class="fas fa-ruler-vertical mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Height</dt>
                  <dd class="font-medium text-gray-900">{{ report.height_cm }} cm</dd>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <i class="fas fa-weight mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Weight</dt>
                  <dd class="font-medium text-gray-900">{{ report.weight_kg }} kg</dd>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <i class="fas fa-tshirt mt-1 text-gray-500"></i>
                <div>
                  <dt class="text-sm text-gray-500">Clothing (Last Seen)</dt>
                  <dd class="font-medium text-gray-900">{{ report.last_seen_clothing || '—' }}</dd>
                </div>
              </div>
            </dl>
          </div>

          <div class="rounded-2xl border border-[#eee] bg-white shadow p-6 mt-6">
            <h2 class="text-xl font-semibold mb-3">Case Information</h2>
            <dl class="space-y-3">
              <div class="flex gap-3">
                <dt class="w-48 text-sm text-gray-500">Physical Description</dt>
                <dd class="flex-1 text-gray-800">{{ report.physical_description || '—' }}</dd>
              </div>
              <div class="flex gap-3">
                <dt class="w-48 text-sm text-gray-500">Other Notes</dt>
                <dd class="flex-1 text-gray-800">{{ report.additional_notes || '—' }}</dd>
              </div>
            </dl>
          </div>

          <!-- Actions -->
          <div class="mt-6 flex flex-wrap gap-4">
            <Link :href="`/missing-persons/${report.id}/preview-poster`"
                  class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-sky-600 text-white hover:bg-sky-700 font-medium shadow">
              <i class="fas fa-file-download"></i>
              Download Poster
            </Link>
            <Link :href="`/missing-persons/${report.id}/report-sighting`"
                  class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-600 text-white hover:bg-orange-700 font-medium shadow">
              <i class="fas fa-binoculars"></i>
              Submit Sighting
            </Link>
          </div>

          <!-- Map -->
          <div class="mt-8">
            <h2 class="text-xl font-semibold mb-3">Last Seen Location</h2>
            <div ref="mapDiv" class="w-full h-64 rounded-xl border shadow-inner ring-1 ring-gray-100"></div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
::-webkit-scrollbar { height: 6px; }
::-webkit-scrollbar-thumb { background: #d7d7d7; border-radius: 999px; }
::-webkit-scrollbar-track { background: transparent; }
</style>
