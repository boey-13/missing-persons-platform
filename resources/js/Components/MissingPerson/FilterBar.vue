<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'

// All Malaysia states and federal territories
const states = [
  "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang", "Penang",
  "Perak", "Perlis", "Sabah", "Sarawak", "Selangor", "Terengganu",
  "Kuala Lumpur", "Putrajaya", "Labuan"
]

// Gender options
const genderOptions = ["Male", "Female"]

// Report time options
const reportTimeOptions = [
  { label: "Within 7 days", value: "7" },
  { label: "Within 30 days", value: "30" },
  { label: "More than 30 days", value: "more" }
]

// Accept props and events from parent
const props = defineProps({
  filters: Object,
})
const emit = defineEmits(['apply', 'clear'])

// Local form state for controlled inputs
const form = ref({
  ageMin: props.filters.ageMin || "",
  ageMax: props.filters.ageMax || "",
  gender: [...(props.filters.gender || [])],
  location: [...(props.filters.location || [])],
  reportTime: [...(props.filters.reportTime || [])],
  weightMin: props.filters.weightMin || "",
  weightMax: props.filters.weightMax || "",
  heightMin: props.filters.heightMin || "",
  heightMax: props.filters.heightMax || "",
})

// Reset form state when parent filters change
watch(() => props.filters, (val) => {
  form.value = {
    ageMin: val.ageMin || "",
    ageMax: val.ageMax || "",
    gender: [...(val.gender || [])],
    location: [...(val.location || [])],
    reportTime: [...(val.reportTime || [])],
    weightMin: val.weightMin || "",
    weightMax: val.weightMax || "",
    heightMin: val.heightMin || "",
    heightMax: val.heightMax || "",
  }
}, { deep: true })

// When user clicks Apply
function onApply() {
  emit('apply', { ...form.value })
}

// When user clicks Clear
function onClear() {
  // Reset all fields to default
  form.value = {
    ageMin: "",
    ageMax: "",
    gender: [],
    location: [],
    reportTime: [],
    weightMin: "",
    weightMax: "",
    heightMin: "",
    heightMax: "",
  }
  emit('clear')
}
</script>

<template>
  <!-- Header -->
  <div class="mb-4 sm:mb-6">
    <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-1">Search Filters</h3>
    <p class="text-xs sm:text-sm text-gray-500">Refine your search to find specific cases</p>
  </div>

  <form class="space-y-4 sm:space-y-6">
    <!-- Age Range -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Age Range</label>
      <div class="flex items-center gap-2 sm:gap-3">
        <div class="flex-1">
          <input 
            type="number" 
            min="0" 
            max="120" 
            v-model="form.ageMin" 
            placeholder="Min age" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
        <span class="text-gray-400 font-medium text-sm">to</span>
        <div class="flex-1">
          <input 
            type="number" 
            min="0" 
            max="120" 
            v-model="form.ageMax" 
            placeholder="Max age" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Gender -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Gender</label>
      <div class="space-y-1 sm:space-y-2">
        <label v-for="g in genderOptions" :key="g" class="flex items-center p-2 sm:p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-colors shadow-sm">
          <input 
            type="checkbox" 
            v-model="form.gender" 
            :value="g" 
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <span class="ml-2 sm:ml-3 text-xs sm:text-sm font-medium text-gray-700">{{ g }}</span>
        </label>
      </div>
    </div>

    <!-- Location -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Location</label>
      <div class="max-h-32 sm:max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 sm:p-3 space-y-1 sm:space-y-2 bg-white shadow-sm">
        <label v-for="loc in states" :key="loc" class="flex items-center p-1.5 sm:p-2 rounded hover:bg-gray-50 cursor-pointer transition-colors">
          <input 
            type="checkbox" 
            v-model="form.location" 
            :value="loc" 
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <span class="ml-2 sm:ml-3 text-xs sm:text-sm text-gray-700">{{ loc }}</span>
        </label>
      </div>
    </div>

    <!-- Report Time -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Report Time</label>
      <div class="space-y-1 sm:space-y-2">
        <label v-for="opt in reportTimeOptions" :key="opt.value" class="flex items-center p-2 sm:p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 cursor-pointer transition-colors shadow-sm">
          <input 
            type="checkbox" 
            v-model="form.reportTime" 
            :value="opt.value" 
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <span class="ml-2 sm:ml-3 text-xs sm:text-sm font-medium text-gray-700">{{ opt.label }}</span>
        </label>
      </div>
    </div>

    <!-- Weight Range -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Weight (kg)</label>
      <div class="flex items-center gap-2 sm:gap-3">
        <div class="flex-1">
          <input 
            type="number" 
            min="0" 
            max="200" 
            v-model="form.weightMin" 
            placeholder="Min weight" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
        <span class="text-gray-400 font-medium text-sm">to</span>
        <div class="flex-1">
          <input 
            type="number" 
            min="0" 
            max="200" 
            v-model="form.weightMax" 
            placeholder="Max weight" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Height Range -->
    <div class="space-y-2 sm:space-y-3">
      <label class="block text-xs sm:text-sm font-semibold text-gray-700">Height (cm)</label>
      <div class="flex items-center gap-2 sm:gap-3">
        <div class="flex-1">
          <input 
            type="number" 
            min="50" 
            max="250" 
            v-model="form.heightMin" 
            placeholder="Min height" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
        <span class="text-gray-400 font-medium text-sm">to</span>
        <div class="flex-1">
          <input 
            type="number" 
            min="50" 
            max="250" 
            v-model="form.heightMax" 
            placeholder="Max height" 
            class="w-full px-2 sm:px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors shadow-sm text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-2 sm:pt-3 border-t border-gray-200">
      <div class="flex flex-col gap-2 sm:gap-3">
        <!-- Primary Action Button - Apply Filters -->
        <button 
          type="button" 
          @click="onApply"
          class="w-full bg-blue-600 text-white py-2.5 sm:py-3 px-3 sm:px-4 rounded-xl font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg text-xs sm:text-sm min-h-[40px] sm:min-h-[48px] flex items-center justify-center gap-1.5 sm:gap-2"
        >
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
          </svg>
          Apply Filters
        </button>
        
        <!-- Secondary Action Button - Clear All -->
        <button 
          type="button" 
          @click="onClear"
          class="w-full bg-white text-gray-600 py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg font-medium hover:bg-gray-50 hover:text-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 border border-gray-300 shadow-sm hover:shadow-md text-xs sm:text-sm min-h-[36px] sm:min-h-[40px] flex items-center justify-center gap-1.5 sm:gap-2"
        >
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Clear All
        </button>
      </div>
    </div>
  </form>
</template>
