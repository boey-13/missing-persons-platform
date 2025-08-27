<script setup>
// --- Import Inertia.js form helpers and Vue hooks ---
import { usePage, router, useForm } from '@inertiajs/vue3'
import { ref, onMounted, watch, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
defineOptions({ layout: MainLayout })

// --- Form state ---
const form = useForm({
  full_name: '',
  nickname: '',
  ic_number: '',
  age: '',
  gender: '',
  height_cm: '',
  weight_kg: '',
  physical_description: '',
  last_seen_date: '',
  last_seen_location: '',
  last_seen_clothing: '',
  photos: [], // array of files
  police_report: null,
  reporter_name: '',
  reporter_relationship: '',
  reporter_phone: '',
  reporter_email: '',
  additional_notes: '',
})

const errors = ref({})
const photoPreviews = ref([])

// --- Handle photo upload & preview---
function onPhotosChange(e) {
  const files = Array.from(e.target.files)
  form.photos = files
  photoPreviews.value = []
  files.forEach(file => {
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = evt => {
        photoPreviews.value.push(evt.target.result)
      }
      reader.readAsDataURL(file)
    }
  })
}

// --- Police report file preview ---
const policeReportPreview = ref(null)
const policeReportType = ref(null)
const policeReportName = ref('')
function onPoliceReportChange(e) {
  const file = e.target.files[0]
  form.police_report = file
  policeReportName.value = file ? file.name : ''
  if (!file) {
    policeReportPreview.value = null
    policeReportType.value = null
    return
  }
  policeReportType.value = file.type
  const reader = new FileReader()
  reader.onload = evt => {
    policeReportPreview.value = evt.target.result
  }
  if (file.type.startsWith('image/') || file.type === 'application/pdf') {
    reader.readAsDataURL(file)
  } else {
    policeReportPreview.value = null
  }
}

// --- Google Maps integration for last seen location ---
const mapDiv = ref(null)
const marker = ref(null)
const map = ref(null)
const mapLat = ref(3.139)
const mapLng = ref(101.6869)
const mapZoom = ref(12)
let geocoder = null
onMounted(() => {
  const interval = setInterval(() => {
    if (window.google && window.google.maps && window.google.maps.places) {
      clearInterval(interval)
      geocoder = new window.google.maps.Geocoder()
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
  setTimeout(showMap, 1200)
})

// --- Display map with marker ---
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
      draggable: true,
    })

    // Drag marker to change location
    marker.value.addListener('dragend', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      reverseGeocode(pos.lat(), pos.lng())
    })

    // Click map to set marker
    map.value.addListener('click', (e) => {
      const pos = e.latLng
      mapLat.value = pos.lat()
      mapLng.value = pos.lng()
      marker.value.setPosition(pos)
      reverseGeocode(pos.lat(), pos.lng())
    })
  } else {
    map.value.setCenter({ lat: mapLat.value, lng: mapLng.value })
    marker.value.setPosition({ lat: mapLat.value, lng: mapLng.value })
  }
}
watch([mapLat, mapLng], showMap)

const uploading = ref(false)

// --- Frontend validation ---
function validateForm() {
  errors.value = {}

  if (!/^[A-Za-z\s]+$/.test(form.full_name)) {
    errors.value.full_name = "Full name must only contain alphabets and spaces."
  }
  if (form.nickname && !/^[A-Za-z\s]*$/.test(form.nickname)) {
    errors.value.nickname = "Nickname must only contain alphabets and spaces."
  }
  if (!/^\d{12}$/.test(form.ic_number)) {
    errors.value.ic_number = "IC number must be exactly 12 digits."
  }
  if (form.age && !/^\d+$/.test(form.age)) {
    errors.value.age = "Age must be a valid number."
  }
  if (form.height_cm && !/^\d+$/.test(form.height_cm)) {
    errors.value.height_cm = "Height must be a number."
  }
  if (form.weight_kg && !/^\d+$/.test(form.weight_kg)) {
    errors.value.weight_kg = "Weight must be a number."
  }
  if (!/^[A-Za-z\s]+$/.test(form.reporter_name)) {
    errors.value.reporter_name = "Name must only contain alphabets and spaces."
  }
  if (!/^\d{10,11}$/.test(form.reporter_phone)) {
    errors.value.reporter_phone = "Phone number must be 10 or 11 digits."
  }
  if (form.reporter_email && !/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/.test(form.reporter_email)) {
    errors.value.reporter_email = "Invalid email address."
  }
  if (!form.gender) errors.value.gender = "Please select gender."
  if (!form.reporter_relationship) errors.value.reporter_relationship = "Please select relationship."
  if (!form.last_seen_date) errors.value.last_seen_date = "Please select last seen date."
  if (!form.last_seen_location) errors.value.last_seen_location = "Please select last seen location."
  // ...more validation as needed...

  return Object.keys(errors.value).length === 0
}

// --- Submit form logic ---
function submit() {
  if (!user) {
    showLoginModal.value = true
    alert("You must log in to submit a report.")
    return
  }
  
  // 验证当前步骤（最后一步）
  if (!validateCurrentStep()) return
  
  // 最终验证所有字段
  if (!validateForm()) return
  
  uploading.value = true
  form.post(route('missing-persons.store'), {
    forceFormData: true,
    onFinish: () => uploading.value = false,
    onError: (e) => {
      errors.value = e
    },
    onSuccess: () => {
      form.reset()
      photoPreviews.value = []
      policeReportPreview.value = null
      alert('Report submitted successfully!')
    }
  })
}

// --- Auth user check for login modal ---
const user = usePage().props.auth.user
const showLoginModal = ref(false)
onMounted(() => {
  if (!user) {
    showLoginModal.value = true
  }
})
function goToLogin() {
  router.visit('/login')
}
function goToHome() {
  router.visit('/')
}

function reverseGeocode(lat, lng) {
  if (!geocoder) return
  geocoder.geocode({ location: { lat, lng } }, (results, status) => {
    if (status === 'OK' && results && results.length) {
      form.last_seen_location = results[0].formatted_address
    } else {
      form.last_seen_location = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
    }
  })
}

/* -------------------- UI-only: Stepper (no business logic changed) -------------------- */
const steps = [
  { key: 'basic', label: 'Basic Info' },
  { key: 'personal', label: 'Personal Details' },
  { key: 'lastseen', label: 'Last Seen' },
  { key: 'uploads', label: 'Uploads' },
  { key: 'contact', label: 'Contact & Notes' },
]
const currentStep = ref(0)
const isFirst = computed(() => currentStep.value === 0)
const isLast  = computed(() => currentStep.value === steps.length - 1)
const progress = computed(() => Math.round(((currentStep.value + 1) / steps.length) * 100))

function goStep(idx) {
  if (idx < 0 || idx > steps.length - 1) return
  currentStep.value = idx
}
function nextStep() { 
  if (!isLast.value && validateCurrentStep()) {
    currentStep.value++ 
  }
}
function prevStep() { if (!isFirst.value) currentStep.value-- }

// 验证当前步骤的字段
function validateCurrentStep() {
  errors.value = {}
  let isValid = true
  
  switch (currentStep.value) {
    case 0: // Basic Information
      if (!/^[A-Za-z\s]+$/.test(form.full_name)) {
        errors.value.full_name = "Full name must only contain alphabets and spaces."
        isValid = false
      }
      if (form.nickname && !/^[A-Za-z\s]*$/.test(form.nickname)) {
        errors.value.nickname = "Nickname must only contain alphabets and spaces."
        isValid = false
      }
      if (!/^\d{12}$/.test(form.ic_number)) {
        errors.value.ic_number = "IC number must be exactly 12 digits."
        isValid = false
      }
      break
      
    case 1: // Personal Details
      if (form.age && !/^\d+$/.test(form.age)) {
        errors.value.age = "Age must be a valid number."
        isValid = false
      }
      if (form.height_cm && !/^\d+$/.test(form.height_cm)) {
        errors.value.height_cm = "Height must be a number."
        isValid = false
      }
      if (form.weight_kg && !/^\d+$/.test(form.weight_kg)) {
        errors.value.weight_kg = "Weight must be a number."
        isValid = false
      }
      if (!form.gender) {
        errors.value.gender = "Please select gender."
        isValid = false
      }
      break
      
    case 2: // Last Seen Information
      if (!form.last_seen_date) {
        errors.value.last_seen_date = "Please select last seen date."
        isValid = false
      }
      if (!form.last_seen_location) {
        errors.value.last_seen_location = "Please select last seen location."
        isValid = false
      }
      break
      
    case 3: // Uploads (optional, so no validation needed)
      break
      
    case 4: // Contact Information
      if (!/^[A-Za-z\s]+$/.test(form.reporter_name)) {
        errors.value.reporter_name = "Name must only contain alphabets and spaces."
        isValid = false
      }
      if (!/^\d{10,11}$/.test(form.reporter_phone)) {
        errors.value.reporter_phone = "Phone number must be 10 or 11 digits."
        isValid = false
      }
      if (form.reporter_email && !/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/.test(form.reporter_email)) {
        errors.value.reporter_email = "Invalid email address."
        isValid = false
      }
      if (!form.reporter_relationship) {
        errors.value.reporter_relationship = "Please select relationship."
        isValid = false
      }
      break
  }
  
  return isValid
}
</script>

<template>
  <!-- Login required modal (unchanged) -->
  <teleport to="body">
    <div v-if="showLoginModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
      <div class="bg-white rounded-lg p-6 shadow-xl w-[90%] max-w-md text-center">
        <h2 class="text-lg font-semibold mb-4">Login Required</h2>
        <p class="mb-6 text-sm text-gray-600">You must be logged in to submit a report. Would you like to login now?</p>
        <div class="flex justify-center gap-4">
          <button @click="goToHome" class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100 text-sm">
            Continue Without Login
          </button>
          <button @click="goToLogin" class="px-4 py-2 rounded bg-[#6B4C3B] text-white hover:bg-[#5c3f31] text-sm">
            Go to Login
          </button>
        </div>
      </div>
    </div>
  </teleport>

     <!-- Page -->
   <div class="min-h-screen bg-white font-['Poppins'] text-[#333]">
    <div class="max-w-4xl mx-auto px-4 py-10">

      <!-- Title -->
      <h1 class="text-3xl font-extrabold tracking-tight text-center mb-8">Report a Missing Person</h1>

      <!-- Stepper header (no cards) -->
      <div class="mb-6">
        <!-- Progress bar -->
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
          <div class="h-2 bg-[#b12a1a] transition-all" :style="{ width: progress + '%' }"></div>
        </div>
        <!-- Steps -->
        <ol class="mt-3 grid grid-cols-5 gap-2 text-xs sm:text-sm">
          <li v-for="(s, idx) in steps" :key="s.key"
              class="flex items-center gap-2 select-none cursor-pointer"
              @click="goStep(idx)">
            <span
              class="inline-flex h-6 w-6 items-center justify-center rounded-full border"
              :class="idx <= currentStep ? 'bg-[#b12a1a] text-white border-[#b12a1a]' : 'bg-white border-gray-300 text-gray-500'">
              {{ idx + 1 }}
            </span>
            <span :class="idx <= currentStep ? 'text-[#b12a1a] font-medium' : 'text-gray-600'">{{ s.label }}</span>
          </li>
        </ol>
      </div>

      <!-- Form (same logic; only UI paginated) -->
      <form @submit.prevent="submit" enctype="multipart/form-data">

        <!-- STEP 1: Basic Information -->
        <section v-show="currentStep === 0" class="space-y-4">
          <h2 class="font-bold text-lg text-[#b12a1a]">Basic Information</h2>
          <div>
            <label class="block mb-1">Full Name</label>
            <input v-model="form.full_name" type="text" class="w-full border px-4 py-2 rounded" required />
            <span v-if="errors.full_name" class="text-red-500 text-sm">{{ errors.full_name }}</span>
          </div>
          <div>
            <label class="block mb-1">Nickname (Optional)</label>
            <input v-model="form.nickname" type="text" class="w-full border px-4 py-2 rounded" />
            <span v-if="errors.nickname" class="text-red-500 text-sm">{{ errors.nickname }}</span>
          </div>
          <div>
            <label class="block mb-1">IC Number</label>
            <input v-model="form.ic_number" type="text" class="w-full border px-4 py-2 rounded" required maxlength="12" />
            <span v-if="errors.ic_number" class="text-red-500 text-sm">{{ errors.ic_number }}</span>
          </div>
        </section>

        <!-- STEP 2: Personal Details -->
        <section v-show="currentStep === 1" class="space-y-4">
          <h2 class="font-bold text-lg text-[#b12a1a]">Personal Details</h2>
          <div>
            <label class="block mb-1">Age</label>
            <input v-model="form.age" type="number" min="0" class="w-full border px-4 py-2 rounded" />
            <span v-if="errors.age" class="text-red-500 text-sm">{{ errors.age }}</span>
          </div>
          <div>
            <label class="block mb-1">Gender</label>
            <select v-model="form.gender" class="w-full border px-4 py-2 rounded">
              <option value="">Select...</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
            <span v-if="errors.gender" class="text-red-500 text-sm">{{ errors.gender }}</span>
          </div>
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1">Height (cm)</label>
              <input v-model="form.height_cm" type="number" min="0" class="w-full border px-4 py-2 rounded" />
              <span v-if="errors.height_cm" class="text-red-500 text-sm">{{ errors.height_cm }}</span>
            </div>
            <div class="w-1/2">
              <label class="block mb-1">Weight (kg)</label>
              <input v-model="form.weight_kg" type="number" min="0" class="w-full border px-4 py-2 rounded" />
              <span v-if="errors.weight_kg" class="text-red-500 text-sm">{{ errors.weight_kg }}</span>
            </div>
          </div>
          <div>
            <label class="block mb-1">Physical Description</label>
            <textarea v-model="form.physical_description" class="w-full border px-4 py-2 rounded"
                      placeholder="Hair color, body marks, etc."></textarea>
          </div>
        </section>

        <!-- STEP 3: Last Seen -->
        <section v-show="currentStep === 2" class="space-y-4">
          <h2 class="font-bold text-lg text-[#b12a1a]">Last Seen Information</h2>
          <div>
            <label class="block mb-1">Last Seen Date</label>
            <input v-model="form.last_seen_date" type="date" class="w-full border px-4 py-2 rounded" required />
            <span v-if="errors.last_seen_date" class="text-red-500 text-sm">{{ errors.last_seen_date }}</span>
          </div>
          <div>
            <label class="block mb-1">Last Seen Location</label>
            <input id="autocomplete" v-model="form.last_seen_location" type="text"
                   class="w-full border px-4 py-2 rounded"
                   placeholder="Enter location and select suggestion" autocomplete="off" required />
            <span v-if="errors.last_seen_location" class="text-red-500 text-sm">{{ errors.last_seen_location }}</span>
          </div>
          <div>
            <div ref="mapDiv" style="width:100%;height:270px;border-radius:12px;box-shadow:0 2px 8px #ddd"></div>
          </div>
          <div>
            <label class="block mb-1">Last Seen Clothing Description</label>
            <textarea v-model="form.last_seen_clothing" class="w-full border px-4 py-2 rounded"
                      placeholder="Clothing details"></textarea>
          </div>
        </section>

        <!-- STEP 4: Uploads -->
        <section v-show="currentStep === 3" class="space-y-4">
          <h2 class="font-bold text-lg text-[#b12a1a]">Uploads</h2>
          <!-- Photos -->
          <div>
            <label class="block mb-1">Upload Photos</label>
            <input type="file" multiple @change="onPhotosChange" class="w-full" accept="image/*" />
            <div v-if="photoPreviews.length" class="flex gap-2 mt-2 flex-wrap">
              <img v-for="(src, idx) in photoPreviews" :key="idx" :src="src" alt="Preview"
                   class="w-32 h-32 object-cover rounded shadow" />
            </div>
          </div>
          <!-- Police report -->
          <div>
            <label class="block mb-1">Upload Police Report</label>
            <input type="file" @change="onPoliceReportChange" class="w-full" accept=".pdf,image/*" />
            <small class="block mt-1 text-gray-500">Supported formats: .pdf, .jpg, .png (Max: 5MB)</small>
            <div v-if="policeReportPreview" class="mt-2">
              <img v-if="policeReportType && policeReportType.startsWith('image/')"
                   :src="policeReportPreview" alt="Police Report Preview" class="w-40 rounded shadow" />
              <embed v-else-if="policeReportType === 'application/pdf'"
                     :src="policeReportPreview" type="application/pdf"
                     class="w-full h-48 rounded shadow" />
            </div>
            <div v-else-if="policeReportName" class="mt-2 text-gray-600">
              {{ policeReportName }}
            </div>
          </div>
        </section>

        <!-- STEP 5: Contact & Notes -->
        <section v-show="currentStep === 4" class="space-y-4">
          <h2 class="font-bold text-lg text-[#b12a1a]">Contact Information</h2>
          <div>
            <label class="block mb-1">Your Name</label>
            <input v-model="form.reporter_name" type="text" class="w-full border px-4 py-2 rounded" required />
            <span v-if="errors.reporter_name" class="text-red-500 text-sm">{{ errors.reporter_name }}</span>
          </div>
          <div>
            <label class="block mb-1">Relationship to Missing Person</label>
            <select v-model="form.reporter_relationship" class="w-full border px-4 py-2 rounded" required>
              <option value="">Select...</option>
              <option>Parent</option>
              <option>Sibling</option>
              <option>Spouse</option>
              <option>Friend</option>
              <option>Relative</option>
              <option>Other</option>
            </select>
            <span v-if="errors.reporter_relationship" class="text-red-500 text-sm">{{ errors.reporter_relationship }}</span>
          </div>
          <div>
            <label class="block mb-1">Phone Number</label>
            <input v-model="form.reporter_phone" type="text" class="w-full border px-4 py-2 rounded" required />
            <span v-if="errors.reporter_phone" class="text-red-500 text-sm">{{ errors.reporter_phone }}</span>
          </div>
          <div>
            <label class="block mb-1">Email Address (Optional)</label>
            <input v-model="form.reporter_email" type="email" class="w-full border px-4 py-2 rounded" />
            <span v-if="errors.reporter_email" class="text-red-500 text-sm">{{ errors.reporter_email }}</span>
          </div>

          <h2 class="font-bold text-lg text-[#b12a1a] mt-6">Additional Notes (Optional)</h2>
          <textarea v-model="form.additional_notes" class="w-full border px-4 py-2 rounded"
                    placeholder="Any other information"></textarea>
        </section>

        <!-- Divider -->
        <hr class="my-6 border-gray-300/70" />

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between gap-4">
          <button
            type="button"
            :disabled="isFirst"
            @click="prevStep"
            class="px-4 py-2 rounded border border-gray-300 text-gray-800 hover:bg-gray-100 disabled:opacity-40"
          >
            ← Previous
          </button>

          <div class="text-sm text-gray-600">Step {{ currentStep + 1 }} of {{ steps.length }} ({{ progress }}%)</div>

          <button
            v-if="!isLast"
            type="button"
            @click="nextStep"
            class="px-4 py-2 rounded bg-[#b12a1a] text-white hover:bg-[#9c2417]"
          >
            Next →
          </button>

          <button
            v-else
            :disabled="uploading"
            type="submit"
            class="px-4 py-2 rounded bg-black text-white font-semibold flex items-center gap-2 hover:bg-[#b12a1a] disabled:opacity-50"
          >
            <span v-if="uploading" class="animate-spin">⏳</span>
            Submit Report
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
