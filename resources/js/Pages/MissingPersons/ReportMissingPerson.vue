<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch } from 'vue'

const form = useForm({
  full_name: '',
  nickname: '',
  age: '',
  gender: '',
  height_cm: '',
  weight_kg: '',
  physical_description: '',
  last_seen_date: '',
  last_seen_location: '',
  last_seen_clothing: '',
  photo: null,
  police_report: null,
  reporter_name: '',
  reporter_relationship: '',
  reporter_phone: '',
  reporter_email: '',
  additional_notes: '',
})

const uploading = ref(false)

function submit() {
  uploading.value = true
  form.post(route('missing-persons.store'), {
    forceFormData: true,
    onFinish: () => uploading.value = false,
    onSuccess: () => {
      form.reset()
      alert('Report submitted successfully!')
    }
  })
}

// Google Maps integration
const mapDiv = ref(null)
const marker = ref(null)
const map = ref(null)
const mapLat = ref(3.139)  // default to Kuala Lumpur
const mapLng = ref(101.6869)
const mapZoom = ref(12)

onMounted(() => {
  // wait for Google Maps to load
  const interval = setInterval(() => {
    if (window.google && window.google.maps && window.google.maps.places) {
      clearInterval(interval)
      const input = document.getElementById('autocomplete')
      if (!input) return
      const autocomplete = new window.google.maps.places.Autocomplete(input)
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace()
        if (place.geometry) {
          const lat = place.geometry.location.lat()
          const lng = place.geometry.location.lng()
          form.last_seen_location = place.formatted_address || place.name
          mapLat.value = lat
          mapLng.value = lng
          showMap()
        } else if (place.formatted_address) {
          form.last_seen_location = place.formatted_address
        } else if (place.name) {
          form.last_seen_location = place.name
        }
      })
    }
  }, 300)
  // initially show the map
  setTimeout(showMap, 1200)
})

function showMap() {
  if (!mapDiv.value || !window.google || !window.google.maps) return
  if (!map.value) {
    map.value = new window.google.maps.Map(mapDiv.value, {
      center: { lat: mapLat.value, lng: mapLng.value },
      zoom: mapZoom.value,
    })
    marker.value = new window.google.maps.Marker({
      position: { lat: mapLat.value, lng: mapLng.value },
      map: map.value,
    })
  } else {
    map.value.setCenter({ lat: mapLat.value, lng: mapLng.value })
    marker.value.setPosition({ lat: mapLat.value, lng: mapLng.value })
  }
}

// if the map coordinates change, update the map
watch([mapLat, mapLng], () => {
  showMap()
})
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-[#f8ecd8] via-[#e2c799] to-[#a78244]">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-xl p-8">
      <h1 class="text-2xl font-bold text-center mb-6">Report a Missing Person</h1>
      <form @submit.prevent="submit" enctype="multipart/form-data">

        <!-- Basic Information -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Basic Information</h2>
          <div class="mb-4">
            <label class="block mb-1">Full Name</label>
            <input v-model="form.full_name" type="text" class="w-full border px-4 py-2 rounded" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Nickname (Optional)</label>
            <input v-model="form.nickname" type="text" class="w-full border px-4 py-2 rounded" />
          </div>
        </div>

        <!-- Personal Details -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Personal Details</h2>
          <div class="mb-4">
            <label class="block mb-1">Age</label>
            <input v-model="form.age" type="number" min="0" class="w-full border px-4 py-2 rounded" />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Gender</label>
            <select v-model="form.gender" class="w-full border px-4 py-2 rounded">
              <option value="">Select...</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
          </div>
          <div class="mb-4 flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1">Height (cm)</label>
              <input v-model="form.height_cm" type="number" min="0" class="w-full border px-4 py-2 rounded" />
            </div>
            <div class="w-1/2">
              <label class="block mb-1">Weight (kg)</label>
              <input v-model="form.weight_kg" type="number" min="0" class="w-full border px-4 py-2 rounded" />
            </div>
          </div>
          <div class="mb-4">
            <label class="block mb-1">Physical Description</label>
            <textarea v-model="form.physical_description" class="w-full border px-4 py-2 rounded" placeholder="Hair color, body marks, etc."></textarea>
          </div>
        </div>

        <!-- Last Seen Information -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Last Seen Information</h2>
          <div class="mb-4">
            <label class="block mb-1">Last Seen Date</label>
            <input v-model="form.last_seen_date" type="date" class="w-full border px-4 py-2 rounded" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Last Seen Location</label>
            <input
              id="autocomplete"
              v-model="form.last_seen_location"
              type="text"
              class="w-full border px-4 py-2 rounded"
              placeholder="Enter location and select suggestion"
              autocomplete="off"
              required
            />
          </div>
          <!-- Map Display -->
          <div class="mb-4">
            <div ref="mapDiv" style="width:100%;height:270px;border-radius:12px;box-shadow:0 2px 8px #ddd"></div>
          </div>
          <div class="mb-4">
            <label class="block mb-1">Last Seen Clothing Description</label>
            <textarea v-model="form.last_seen_clothing" class="w-full border px-4 py-2 rounded" placeholder="Clothing details"></textarea>
          </div>
        </div>

        <!-- Photo Upload -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Upload Photo</h2>
          <input type="file" @change="e => form.photo = e.target.files[0]" class="w-full" accept="image/*" />
        </div>

        <!-- Police Report Upload (Optional) -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Upload Polis Report (Optional)</h2>
          <input type="file" @change="e => form.police_report = e.target.files[0]" class="w-full" accept=".pdf,image/*" />
          <small class="block mt-1 text-gray-500">Supported formats: .pdf, .jpg, .png (Max: 5MB)</small>
        </div>

        <!-- Contact Information -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Contact Information</h2>
          <div class="mb-4">
            <label class="block mb-1">Your Name</label>
            <input v-model="form.reporter_name" type="text" class="w-full border px-4 py-2 rounded" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Relationship to Missing Person</label>
            <input v-model="form.reporter_relationship" type="text" class="w-full border px-4 py-2 rounded" />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Phone Number</label>
            <input v-model="form.reporter_phone" type="text" class="w-full border px-4 py-2 rounded" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1">Email Address (Optional)</label>
            <input v-model="form.reporter_email" type="email" class="w-full border px-4 py-2 rounded" />
          </div>
        </div>

        <!-- Additional Notes -->
        <div class="mb-6">
          <h2 class="font-bold mb-2">Additional Notes (Optional)</h2>
          <textarea v-model="form.additional_notes" class="w-full border px-4 py-2 rounded" placeholder="Any other information"></textarea>
        </div>

        <button
          :disabled="uploading"
          type="submit"
          class="bg-black text-white w-full py-2 rounded font-bold flex items-center justify-center"
        >
          <span v-if="uploading" class="animate-spin mr-2">⏳</span>
          Submit Report
        </button>
      </form>
    </div>
  </div>
</template>
