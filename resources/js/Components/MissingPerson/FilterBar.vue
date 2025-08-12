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
  <form class="space-y-5 text-[#333]">
    <div>
      <div class="font-bold text-xl text-[#6B4C3B] mb-3">Filter</div>
    </div>
    <div>
      <div class="font-semibold mb-1">Age Range</div>
      <div class="flex gap-2">
        <input type="number" min="0" max="120" v-model="form.ageMin" placeholder="Min" class="w-20 input input-bordered" />
        <span class="self-center">-</span>
        <input type="number" min="0" max="120" v-model="form.ageMax" placeholder="Max" class="w-20 input input-bordered" />
      </div>
    </div>

    <!-- Gender -->
    <div class="mb-4">
      <label class="block font-medium mb-2">Gender</label>
      <div class="flex flex-col gap-1">
        <label v-for="g in genderOptions" :key="g" class="flex items-center gap-2">
          <input type="checkbox" v-model="form.gender" :value="g" /> {{ g }}
        </label>
      </div>
    </div>

    <!-- Location (Malaysia States) -->
    <div class="mb-4">
      <label class="block font-medium mb-2">Location</label>
      <div class="grid grid-cols-2 gap-1 max-h-36 overflow-y-auto">
        <label v-for="loc in states" :key="loc" class="flex items-center gap-2">
          <input type="checkbox" v-model="form.location" :value="loc" /> {{ loc }}
        </label>
      </div>
    </div>

    <!-- Report Time -->
    <div class="mb-4">
      <label class="block font-medium mb-2">Report Time</label>
      <div class="flex flex-col gap-1">
        <label v-for="opt in reportTimeOptions" :key="opt.value" class="flex items-center gap-2">
          <input type="checkbox" v-model="form.reportTime" :value="opt.value" /> {{ opt.label }}
        </label>
      </div>
    </div>

    <!-- Weight Range -->
    <div class="mb-4">
      <label class="block font-medium mb-2">Weight (kg)</label>
      <div class="flex gap-2">
        <input type="number" min="0" max="200" v-model="form.weightMin" placeholder="Min" class="w-20 input input-bordered" />
        <span class="self-center">-</span>
        <input type="number" min="0" max="200" v-model="form.weightMax" placeholder="Max" class="w-20 input input-bordered" />
      </div>
    </div>

    <!-- Height Range -->
    <div class="mb-4">
      <label class="block font-medium mb-2">Height (cm)</label>
      <div class="flex gap-2">
        <input type="number" min="50" max="250" v-model="form.heightMin" placeholder="Min" class="w-20 input input-bordered" />
        <span class="self-center">-</span>
        <input type="number" min="50" max="250" v-model="form.heightMax" placeholder="Max" class="w-20 input input-bordered" />
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-5 mt-5">
      <button type="button" class="rounded-xl px-5 py-2 bg-[#A67B5B] text-white hover:bg-[#876046] shadow" @click="onApply">Apply</button>
      <button type="button" class="rounded-xl px-5 py-2 bg-[#f5e6da] text-[#6B4C3B] hover:bg-[#e7d6c3] border border-[#D9C4B1]" @click="onClear">Clear Filter</button>
    </div>
  </form>
</template>
