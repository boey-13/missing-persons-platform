<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch, nextTick } from 'vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: MainLayout })

const props = defineProps({ report: Object })

const { success, error } = useToast()

const form = useForm({
  location: props.report?.last_seen_location || '',
  description: '',
  sighted_at: '',
  reporter_name: '',
  reporter_phone: '',
  reporter_email: '',
  photos: []
})

const isInitializing = ref(true)
const uploadProgress = ref(0)

/** -------- Google Maps -------- */
const mapDiv = ref(null)
const autocompleteInput = ref(null) // use ref instead of getElementById
const map = ref(null)
const marker = ref(null)
const mapLat = ref(3.1390)
const mapLng = ref(101.6869)
const mapZoom = ref(13)
let geocoder = null

onMounted(async () => {
  // 先结束 loading，让 DOM 渲染出来（mapDiv/输入框必须先存在）
  isInitializing.value = false
  await nextTick()

  // 等待：Google Maps + Places & DOM 都就绪（最长 10s）
  const ok = await waitFor(
    () => window.google?.maps?.places && mapDiv.value && autocompleteInput.value,
    10000
  )
  if (!ok) {
    console.warn('Google Maps not ready within timeout.')
    return
  }

  geocoder = new window.google.maps.Geocoder()
  initMap()
  initAutocomplete()
})

function waitFor(cond, timeout = 10000) {
  return new Promise(resolve => {
    const start = Date.now()
    const t = setInterval(() => {
      if (cond()) { clearInterval(t); resolve(true) }
      else if (Date.now() - start > timeout) { clearInterval(t); resolve(false) }
    }, 100)
  })
}

function initAutocomplete() {
  const ac = new window.google.maps.places.Autocomplete(autocompleteInput.value)
  ac.addListener('place_changed', () => {
    const place = ac.getPlace()
    if (place?.geometry) {
      const lat = place.geometry.location.lat()
      const lng = place.geometry.location.lng()
      form.location = place.formatted_address || place.name
      mapLat.value = lat
      mapLng.value = lng
      showMap()
    } else if (place?.formatted_address) {
      form.location = place.formatted_address
    } else if (place?.name) {
      form.location = place.name
    }
  })
}

function initMap() { showMap() }

async function showMap() {
  await nextTick()
  if (!mapDiv.value || !window.google?.maps) return
  const center = { lat: mapLat.value, lng: mapLng.value }

  if (!map.value) {
    map.value = new window.google.maps.Map(mapDiv.value, { center, zoom: mapZoom.value })
    marker.value = new window.google.maps.Marker({
      position: center, map: map.value, draggable: true
    })

    marker.value.addListener('dragend', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      reverseGeocode(mapLat.value, mapLng.value)
    })

    map.value.addListener('click', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      marker.value.setPosition(pos)
      reverseGeocode(mapLat.value, mapLng.value)
    })
  } else {
    map.value.setCenter(center)
    marker.value.setPosition(center)
    window.google.maps.event.trigger(map.value, 'resize')
  }
}

watch([mapLat, mapLng], showMap)

function reverseGeocode(lat, lng) {
  if (!geocoder) return
  geocoder.geocode({ location: { lat, lng } }, (results, status) => {
    if (status === 'OK' && results?.length) {
      form.location = results[0].formatted_address
    } else {
      form.location = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
    }
  })
}

/** -------- 上传 -------- */
function onPhotosChange(e) {
  const files = Array.from(e.target.files)
  form.photos = files
  uploadProgress.value = 0
  const total = files.length || 1
  let done = 0
  files.forEach(f => {
    if (f && f.type.startsWith('image/')) {
      done++
      uploadProgress.value = Math.round((done / total) * 100)
      if (done === total) setTimeout(() => (uploadProgress.value = 0), 1500)
    }
  })
}

/** -------- submit -------- */
function submit() {
  if (!form.location.trim())  return error('Please enter a location.')
  if (!form.reporter_name.trim())  return error('Please enter your name.')
  if (!form.reporter_phone.trim()) return error('Please enter your phone number.')

  form.post(`/missing-persons/${props.report.id}/sightings`, {
    forceFormData: true,
    onError: () => error('There was an error submitting your report. Please try again.')
  })
}


</script>

<template>
  <Head title="Report Sighting" />

  <div class="min-h-screen bg-white font-['Poppins'] text-[#333]">
    <!-- Keep original loading (but close immediately in onMounted to avoid blocking DOM) -->
    <div v-if="isInitializing" class="text-center py-16 sm:py-20">
      <div class="inline-flex items-center px-3 sm:px-4 py-2 font-semibold leading-6 text-xs sm:text-sm shadow rounded-md text-white bg-black">
        <svg class="animate-spin -ml-1 mr-2 sm:mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        Loading sighting report form...
      </div>
    </div>

    <div v-else>
      <header class="max-w-3xl mx-auto px-4 sm:px-6 pt-6 sm:pt-10 pb-4 sm:pb-6 text-center">
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Report a Sighting</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1.5 sm:mt-2">
          Case: <span class="font-semibold">{{ props.report.full_name }}</span>
        </p>
      </header>

      <form @submit.prevent="submit" enctype="multipart/form-data" class="max-w-3xl mx-auto px-4 sm:px-6 pb-10 sm:pb-14 space-y-8 sm:space-y-10">
        <!-- Sighting details -->
        <section>
          <h2 class="text-xs sm:text-sm font-semibold text-gray-900 mb-3 sm:mb-4">Sighting details</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label class="block text-xs sm:text-sm text-gray-700 mb-1">
                Location <span class="text-red-500">*</span>
              </label>
              <input
                ref="autocompleteInput"
                v-model="form.location"
                type="text"
                autocomplete="off"
                placeholder="Enter location and select a suggestion"
                class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base"
              />
              <div v-if="form.errors.location" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.location }}</div>
            </div>

            <div>
              <label class="block text-xs sm:text-sm text-gray-700 mb-1">Sighted at</label>
              <input
                v-model="form.sighted_at"
                type="datetime-local"
                class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base"
              />
            </div>
          </div>

          <!-- Map -->
          <div class="mt-4 sm:mt-5">
            <div ref="mapDiv" class="w-full h-48 sm:h-64 border border-gray-200 rounded-md"></div>
            <p class="text-xs text-gray-500 mt-1.5 sm:mt-2">Drag the pin or click map to adjust exact location.</p>
          </div>

          <div class="mt-4 sm:mt-5">
            <label class="block text-xs sm:text-sm text-gray-700 mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Clothing, behavior, direction…"
              class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base"
            />
          </div>

          <div class="border-t border-gray-200 mt-6 sm:mt-8" />
        </section>

        <!-- Your information -->
        <section>
          <h2 class="text-xs sm:text-sm font-semibold text-gray-900 mb-3 sm:mb-4">Your information</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label class="block text-xs sm:text-sm text-gray-700 mb-1">Full name <span class="text-red-500">*</span></label>
              <input v-model="form.reporter_name" type="text" class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base" />
              <div v-if="form.errors.reporter_name" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.reporter_name }}</div>
            </div>

            <div>
              <label class="block text-xs sm:text-sm text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
              <input v-model="form.reporter_phone" type="text" class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base" />
              <div v-if="form.errors.reporter_phone" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.reporter_phone }}</div>
            </div>
          </div>

          <div class="mt-3 sm:mt-4">
            <label class="block text-xs sm:text-sm text-gray-700 mb-1">Email (optional)</label>
            <input v-model="form.reporter_email" type="email" class="w-full border border-gray-300 px-2.5 sm:px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none text-sm sm:text-base" />
            <p class="text-xs text-gray-500 mt-1.5 sm:mt-2">We'll only use your contact to follow up on this sighting if needed.</p>
          </div>

          <div class="border-t border-gray-200 mt-6 sm:mt-8" />
        </section>

        <!-- Photos -->
        <section>
          <h2 class="text-xs sm:text-sm font-semibold text-gray-900 mb-3 sm:mb-4">Photos (optional)</h2>
          <input type="file" multiple accept="image/*" @change="onPhotosChange" class="block w-full text-xs sm:text-sm text-gray-700 file:mr-3 file:px-2.5 sm:file:px-3 file:py-1 sm:file:py-1.5 file:rounded-md file:border-0 file:bg-gray-900 file:text-white hover:file:bg-black" />
          <p class="text-xs text-gray-500 mt-1.5 sm:mt-2">JPEG/PNG, up to a few photos.</p>

          <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-3">
            <div class="flex items-center space-x-2 mb-2">
              <svg class="animate-spin h-3 w-3 sm:h-4 sm:w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <span class="text-xs sm:text-sm text-gray-600">Processing photos...</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 sm:h-2">
              <div class="bg-black h-1.5 sm:h-2 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }" />
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ uploadProgress }}% complete</p>
          </div>
        </section>

        <div class="pt-2">
          <button type="submit" :disabled="form.processing" class="w-full bg-black text-white py-2.5 sm:py-3 rounded-md font-semibold hover:bg-[#111] disabled:opacity-60 relative text-sm sm:text-base">
            <span v-if="form.processing" class="absolute inset-0 flex items-center justify-center">
              <svg class="animate-spin h-3 w-3 sm:h-4 sm:w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
            </span>
            <span :class="{ 'opacity-0': form.processing }">{{ form.processing ? 'Submitting…' : 'Submit Sighting' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
