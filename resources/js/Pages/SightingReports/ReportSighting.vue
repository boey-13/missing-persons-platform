<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'

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
  form.post(`/missing-persons/${props.report.id}/sightings`, { forceFormData: true })
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
  <div class="min-h-screen flex flex-col items-center py-10 bg-[#f5f3f0] font-['Poppins'] text-[#333]">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border-2 border-[#ebebeb] p-10">
      <h1 class="text-3xl font-extrabold text-center mb-2 tracking-tight">Report a Sighting</h1>
      <p class="text-center text-gray-600 mb-8">Case: <span class="font-semibold">{{ props.report.full_name }}</span></p>

      <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
        <div>
          <h2 class="font-bold text-lg text-[#b12a1a] mb-2">Sighting Details</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1">Location</label>
              <input id="sighting-autocomplete" v-model="form.location" type="text" class="w-full border px-4 py-2 rounded" placeholder="Enter location and select suggestion" autocomplete="off" />
              <div v-if="form.errors.location" class="text-red-600 text-sm mt-1">{{ form.errors.location }}</div>
            </div>
            <div>
              <label class="block mb-1">Sighted At</label>
              <input v-model="form.sighted_at" type="datetime-local" class="w-full border px-4 py-2 rounded" />
            </div>
          </div>
          <div class="mt-4">
            <div ref="mapDiv" style="width:100%;height:270px;border-radius:12px;box-shadow:0 2px 8px #ddd"></div>
          </div>
          <div class="mt-4">
            <label class="block mb-1">Description</label>
            <textarea v-model="form.description" rows="4" class="w-full border px-4 py-2 rounded" placeholder="Clothing, behavior, direction..."></textarea>
          </div>
        </div>

        <div>
          <h2 class="font-bold text-lg text-[#b12a1a] mb-2">Your Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-1">Full Name</label>
              <input v-model="form.reporter_name" type="text" class="w-full border px-4 py-2 rounded" />
              <div v-if="form.errors.reporter_name" class="text-red-600 text-sm mt-1">{{ form.errors.reporter_name }}</div>
            </div>
            <div>
              <label class="block mb-1">Phone</label>
              <input v-model="form.reporter_phone" type="text" class="w-full border px-4 py-2 rounded" />
              <div v-if="form.errors.reporter_phone" class="text-red-600 text-sm mt-1">{{ form.errors.reporter_phone }}</div>
            </div>
          </div>
          <div class="mt-4">
            <label class="block mb-1">Email (optional)</label>
            <input v-model="form.reporter_email" type="email" class="w-full border px-4 py-2 rounded" />
          </div>
        </div>

        <div>
          <h2 class="font-bold text-lg text-[#b12a1a] mb-2">Photos (optional)</h2>
          <input type="file" multiple accept="image/*" @change="onPhotosChange" />
        </div>

        <button type="submit" class="bg-black text-white w-full py-2 rounded font-bold hover:bg-[#b12a1a] transition-colors">
          Submit Sighting
        </button>
      </form>
    </div>
  </div>
</template>


