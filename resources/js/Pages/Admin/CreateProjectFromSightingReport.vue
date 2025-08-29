<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  sightingReport: Object,
  missingReport: Object,
  prefilledData: Object
})

// Form for new project
const projectForm = useForm({
  title: props.prefilledData.title,
  location: props.prefilledData.location,
  date: props.prefilledData.date,
  time: props.prefilledData.time,
  duration: props.prefilledData.duration,
  volunteers_needed: props.prefilledData.volunteers_needed,
  points_reward: props.prefilledData.points_reward,
  description: props.prefilledData.description,
  category: props.prefilledData.category,
  status: props.prefilledData.status,
  photos: []
})

const categories = [
  { value: 'search', label: 'Search Operations' },
  { value: 'awareness', label: 'Awareness Campaigns' },
  { value: 'training', label: 'Training & Workshops' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

function handlePhotoUpload(event) {
  const files = Array.from(event.target.files)
  projectForm.photos = files
}

function createProject() {
  projectForm.post('/admin/community-projects', {
    forceFormData: true,
    onSuccess: () => {
      router.visit('/admin/community-projects')
    }
  })
}

function goBack() {
  router.visit('/admin/sighting-reports')
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-6 sm:py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Create Community Project</h1>
            <p class="mt-2 text-gray-600 text-sm sm:text-base">Create a community project based on sighting report</p>
          </div>
          <button
            @click="goBack"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
          >
            ← Back to Sighting Reports
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        <!-- Sighting Report Info -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Sighting Report Details</h2>
            
            <!-- Missing Person Photo -->
            <div class="mb-4">
              <div class="w-32 h-32 bg-[#B3D4FC] rounded-xl flex items-center justify-center mx-auto overflow-hidden">
                <img 
                  v-if="missingReport.photo_paths && missingReport.photo_paths.length > 0" 
                  :src="'/storage/' + missingReport.photo_paths[0]" 
                  :alt="missingReport.full_name"
                  class="w-full h-full rounded object-cover"
                />
                <div v-else class="w-20 h-20 bg-[#87CEEB] rounded-full flex items-center justify-center">
                  <span class="text-2xl font-bold text-white">{{ missingReport.full_name?.charAt(0)?.toUpperCase() || 'U' }}</span>
                </div>
              </div>
            </div>

            <!-- Missing Person Details -->
            <div class="space-y-3 mb-6">
              <div>
                <span class="text-sm font-medium text-gray-500">Missing Person:</span>
                <div class="text-gray-900 font-medium">{{ missingReport.full_name }}</div>
              </div>
              <div>
                <span class="text-sm font-medium text-gray-500">Age/Gender:</span>
                <div class="text-gray-900">{{ missingReport.age }}/{{ missingReport.gender }}</div>
              </div>
            </div>

            <!-- Sighting Details -->
            <div class="border-t border-gray-200 pt-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Sighting Information</h3>
              <div class="space-y-3">
                <div>
                  <span class="text-sm font-medium text-gray-500">Sighted At:</span>
                  <div class="text-gray-900">{{ sightingReport.location }}</div>
                  <div class="text-sm text-gray-500">{{ formatDate(sightingReport.sighted_at) }}</div>
                </div>
                <div v-if="sightingReport.description">
                  <span class="text-sm font-medium text-gray-500">Sighting Details:</span>
                  <div class="text-gray-900 text-sm">{{ sightingReport.description }}</div>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-500">Reported By:</span>
                  <div class="text-gray-900">{{ sightingReport.reporter_name }}</div>
                  <div class="text-sm text-gray-500">{{ sightingReport.reporter_phone }}</div>
                  <div v-if="sightingReport.reporter_email" class="text-sm text-gray-500">{{ sightingReport.reporter_email }}</div>
                </div>
              </div>
            </div>

            <!-- Missing Person Additional Info -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Missing Person Details</h3>
              <div class="space-y-3">
                <div>
                  <span class="text-sm font-medium text-gray-500">Last Seen:</span>
                  <div class="text-gray-900">{{ missingReport.last_seen_location }}</div>
                  <div class="text-sm text-gray-500">{{ formatDate(missingReport.last_seen_date) }}</div>
                </div>
                <div v-if="missingReport.physical_description">
                  <span class="text-sm font-medium text-gray-500">Physical Description:</span>
                  <div class="text-gray-900 text-sm">{{ missingReport.physical_description }}</div>
                </div>
                <div v-if="missingReport.additional_notes">
                  <span class="text-sm font-medium text-gray-500">Additional Notes:</span>
                  <div class="text-gray-900 text-sm">{{ missingReport.additional_notes }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Project Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Project Information</h2>
            
            <form @submit.prevent="createProject" class="space-y-6">
              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Title</label>
                <input
                  v-model="projectForm.title"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <!-- Category and Status -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                  <select
                    v-model="projectForm.category"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option v-for="category in categories" :key="category.value" :value="category.value">
                      {{ category.label }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select
                    v-model="projectForm.status"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                      {{ status.label }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Location -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <input
                  v-model="projectForm.location"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <!-- Date and Time -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                  <input
                    v-model="projectForm.date"
                    type="date"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                  <input
                    v-model="projectForm.time"
                    type="time"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                  <input
                    v-model="projectForm.duration"
                    type="text"
                    placeholder="e.g., 6 hours"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Volunteers and Points -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Volunteers Needed</label>
                  <input
                    v-model="projectForm.volunteers_needed"
                    type="number"
                    min="1"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Points Reward</label>
                  <input
                    v-model="projectForm.points_reward"
                    type="number"
                    min="0"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea
                  v-model="projectForm.description"
                  rows="4"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
              </div>

              <!-- Photos -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Photos</label>
                <input
                  type="file"
                  @change="handlePhotoUpload"
                  multiple
                  accept="image/*"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-500 mt-1">Upload project photos (optional)</p>
              </div>

              <!-- Submit Buttons -->
              <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button
                  type="submit"
                  :disabled="projectForm.processing"
                  class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
                >
                  {{ projectForm.processing ? 'Creating...' : 'Create Project' }}
                </button>
                <button
                  type="button"
                  @click="goBack"
                  class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
