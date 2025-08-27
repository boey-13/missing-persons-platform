<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'
import ToastMessage from '@/Components/ToastMessage.vue'

defineOptions({ layout: MainLayout })

const props = defineProps({ report: Object })

const form = useForm({
  location: props.report?.last_seen_location || '',
  description: '',
  sighted_at: '',
  reporter_name: '',
  reporter_phone: '',
  reporter_email: '',
  photos: []
})

const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// Google Maps + Places Autocomplete (same pattern as ReportMissingPerson.vue)
const mapDiv = ref(null)
const map = ref(null)
const marker = ref(null)
const mapLat = ref(3.139)
const mapLng = ref(101.6869)
const mapZoom = ref(13)
let geocoder = null

onMounted(() => {
  const interval = setInterval(() => {
    if (window.google && window.google.maps && window.google.maps.places) {
      clearInterval(interval)
      geocoder = new window.google.maps.Geocoder()
      const input = document.getElementById('sighting-autocomplete')
      if (!input) return
      const autocomplete = new window.google.maps.places.Autocomplete(input)
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place.geometry) {
          const lat = place.geometry.location.lat()
          const lng = place.geometry.location.lng()
          form.location = place.formatted_address || place.name
          mapLat.value = lat
          mapLng.value = lng
          showMap()
        } else if (place.formatted_address) {
          form.location = place.formatted_address
        } else if (place.name) {
          form.location = place.name
        }
      })
    }
  }, 300)
  setTimeout(showMap, 1000)
})

function showMap() {
  if (!mapDiv.value || !window.google || !window.google.maps) return
  const center = { lat: mapLat.value, lng: mapLng.value }
  if (!map.value) {
    map.value = new window.google.maps.Map(mapDiv.value, {
      center,
      zoom: mapZoom.value,
    })
    marker.value = new window.google.maps.Marker({ position: center, map: map.value, draggable: true })

    // Drag to move marker and reverse geocode
    marker.value.addListener('dragend', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      reverseGeocode(pos.lat(), pos.lng())
    })

    // Click on map to set marker
    map.value.addListener('click', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      marker.value.setPosition(pos)
      reverseGeocode(pos.lat(), pos.lng())
    })
  } else {
    map.value.setCenter(center)
    marker.value.setPosition(center)
  }
}
watch([mapLat, mapLng], showMap)

function onPhotosChange(e) {
  form.photos = Array.from(e.target.files)
}

function submit() {
  // Basic client-side validation
  if (!form.location.trim()) {
    showToastMessage('Please enter a location.', 'error')
    return
  }
  if (!form.reporter_name.trim()) {
    showToastMessage('Please enter your name.', 'error')
    return
  }
  if (!form.reporter_phone.trim()) {
    showToastMessage('Please enter your phone number.', 'error')
    return
  }
  
  form.post(`/missing-persons/${props.report.id}/sightings`, { 
    forceFormData: true,
    onSuccess: () => {
      // The backend will redirect to the missing person details page
      // No need for additional alert as the success message will be shown on the redirected page
    },
    onError: (errors) => {
      console.error('Submission errors:', errors)
      showToastMessage('There was an error submitting your report. Please try again.', 'error')
    }
  })
}

function showToastMessage(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
}

function reverseGeocode(lat, lng) {
  if (!geocoder) return
  geocoder.geocode({ location: { lat, lng } }, (results, status) => {
    if (status === 'OK' && results && results.length) {
      form.location = results[0].formatted_address
    } else {
      form.location = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
    }
  })
}
</script>

<template>
  <Head title="Report Sighting" />

  <!-- Toast Message -->
  <ToastMessage v-if="showToast" :message="toastMessage" :type="toastType" />

  <div class="min-h-screen bg-white font-['Poppins'] text-[#333]">
    <!-- 顶部标题 -->
    <header class="max-w-3xl mx-auto px-4 sm:px-6 pt-10 pb-6 text-center">
      <h1 class="text-3xl font-extrabold tracking-tight">Report a Sighting</h1>
      <p class="text-gray-600 mt-2">
        Case: <span class="font-semibold">{{ props.report.full_name }}</span>
      </p>
    </header>

    <!-- 表单主体（无卡片，仅留白+分割线） -->
    <form
      @submit.prevent="submit"
      enctype="multipart/form-data"
      class="max-w-3xl mx-auto px-4 sm:px-6 pb-14 space-y-10"
    >
      <!-- Sighting Details -->
      <section>
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Sighting details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Location -->
          <div>
            <label for="sighting-autocomplete" class="block text-sm text-gray-700 mb-1">
              Location <span class="text-red-500">*</span>
            </label>
            <input
              id="sighting-autocomplete"
              v-model="form.location"
              type="text"
              autocomplete="off"
              placeholder="Enter location and select a suggestion"
              class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
            />
            <div v-if="form.errors.location" class="text-red-600 text-sm mt-1">
              {{ form.errors.location }}
            </div>
          </div>

          <!-- Datetime -->
          <div>
            <label class="block text-sm text-gray-700 mb-1">Sighted at</label>
            <input
              v-model="form.sighted_at"
              type="datetime-local"
              class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
            />
          </div>
        </div>

        <!-- Map -->
        <div class="mt-5">
          <div
            ref="mapDiv"
            class="w-full h-64 border border-gray-200 rounded-md"
          ></div>
          <p class="text-xs text-gray-500 mt-2">
            Drag the pin or click map to adjust exact location.
          </p>
        </div>

        <!-- Description -->
        <div class="mt-5">
          <label class="block text-sm text-gray-700 mb-1">Description</label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="Clothing, behavior, direction…"
            class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
          ></textarea>
        </div>

        <!-- 分割线 -->
        <div class="border-t border-gray-200 mt-8"></div>
      </section>

      <!-- Your Information -->
      <section>
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Your information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Name -->
          <div>
            <label class="block text-sm text-gray-700 mb-1">
              Full name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.reporter_name"
              type="text"
              class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
            />
            <div v-if="form.errors.reporter_name" class="text-red-600 text-sm mt-1">
              {{ form.errors.reporter_name }}
            </div>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm text-gray-700 mb-1">
              Phone <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.reporter_phone"
              type="text"
              class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
            />
            <div v-if="form.errors.reporter_phone" class="text-red-600 text-sm mt-1">
              {{ form.errors.reporter_phone }}
            </div>
          </div>
        </div>

        <!-- Email -->
        <div class="mt-4">
          <label class="block text-sm text-gray-700 mb-1">Email (optional)</label>
          <input
            v-model="form.reporter_email"
            type="email"
            class="w-full border border-gray-300 px-3 py-2 rounded-md focus:ring-2 focus:ring-black focus:border-black outline-none"
          />
          <p class="text-xs text-gray-500 mt-2">
            We’ll only use your contact to follow up on this sighting if needed.
          </p>
        </div>

        <!-- 分割线 -->
        <div class="border-t border-gray-200 mt-8"></div>
      </section>

      <!-- Photos -->
      <section>
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Photos (optional)</h2>
        <input
          type="file"
          multiple
          accept="image/*"
          @change="onPhotosChange"
          class="block w-full text-sm text-gray-700 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-gray-900 file:text-white hover:file:bg-black"
        />
        <p class="text-xs text-gray-500 mt-2">JPEG/PNG, up to a few photos.</p>
      </section>

      <!-- Submit -->
      <div class="pt-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-black text-white py-3 rounded-md font-semibold hover:bg-[#111] disabled:opacity-60"
        >
          {{ form.processing ? 'Submitting…' : 'Submit Sighting' }}
        </button>
      </div>
    </form>
  </div>
</template>



