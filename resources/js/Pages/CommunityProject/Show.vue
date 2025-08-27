<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  project: Object,
  userApplication: Object,
  isAdmin: Boolean
})

// Form for updating latest news (admin only)
const newsForm = useForm({
  latest_news: props.project.latest_news || '',
  news_files: []
})

// Form for updating project status (admin only)
const statusForm = useForm({
  status: props.project.status
})

// File upload handling
function handleFileUpload(event) {
  const files = Array.from(event.target.files)
  newsForm.news_files = files
}

// Computed properties
const statusColors = computed(() => ({
  active: 'bg-green-100 text-green-800',
  upcoming: 'bg-blue-100 text-blue-800',
  completed: 'bg-purple-100 text-purple-800',
  cancelled: 'bg-red-100 text-red-800'
}))

const categoryColors = computed(() => ({
  search: 'bg-red-100 text-red-800',
  awareness: 'bg-yellow-100 text-yellow-800',
  training: 'bg-purple-100 text-purple-800'
}))

// Functions
function updateLatestNews() {
  newsForm.post(`/admin/community-projects/${props.project.id}/update-news`, {
    onSuccess: () => {
      // Form will be reset and page will refresh automatically
      console.log('Latest news updated successfully!')
      // Clear the form fields
      newsForm.latest_news = ''
      newsForm.news_files = []
      // Clear the file input
      const fileInput = document.querySelector('input[type="file"]')
      if (fileInput) {
        fileInput.value = ''
      }
    },
    onError: (errors) => {
      console.error('Update failed:', errors)
    }
  })
}

function updateProjectStatus() {
  statusForm.post(`/admin/community-projects/${props.project.id}/status`, {
    onSuccess: () => {
      // Show success message
    }
  })
}

function formatDate(dateString) {
  if (!dateString) return 'Not specified'
  
  // Handle different date formats
  let date
  if (dateString.includes('T')) {
    // ISO format: "2025-08-25T09:00:00.000000Z"
    date = new Date(dateString)
  } else {
    // Simple date format: "2025-08-25"
    date = new Date(dateString)
  }
  
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatTime(timeString) {
  if (!timeString) return ''
  
  let timePart = ''
  if (timeString.includes('T')) {
    // ISO format: "2025-08-25T09:00:00.000000Z"
    timePart = timeString.split('T')[1].substring(0, 5)
  } else if (timeString.includes(' ')) {
    // Format: "2025-08-25 09:00:00"
    timePart = timeString.split(' ')[1].substring(0, 5)
  } else {
    // Simple time format: "09:00"
    timePart = timeString.substring(0, 5)
  }
  
  const [hours, minutes] = timePart.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}

function deleteNews(newsId) {
  if (confirm('Are you sure you want to delete this news update?')) {
    axios.delete(`/admin/community-projects/${props.project.id}/news/${newsId}`)
      .then(response => {
        // Remove the news from the local array
        const index = props.project.news_history.findIndex(news => news.id === newsId)
        if (index > -1) {
          props.project.news_history.splice(index, 1)
        }
        console.log('News deleted successfully!')
        // Show success message
        alert('News deleted successfully!')
      })
      .catch(error => {
        console.error('Error deleting news:', error)
        // Check if the error is actually a success (news was deleted but response format is unexpected)
        if (error.response && error.response.status === 302) {
          // This is likely a redirect response, which means deletion was successful
          const index = props.project.news_history.findIndex(news => news.id === newsId)
          if (index > -1) {
            props.project.news_history.splice(index, 1)
          }
          console.log('News deleted successfully! (redirect response)')
          alert('News deleted successfully!')
        } else {
          alert('Failed to delete news. Please try again.')
        }
      })
  }
}
</script>

<template>
  <Head :title="`${project.title} - Community Project`" />

  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Flash Messages (Top-center, prettier UI) -->
    <teleport to="body">
      <div
        v-if="$page.props.flash?.success || $page.props.flash?.error"
        class="pointer-events-none fixed inset-x-0 top-4 z-[9999] flex justify-center px-4"
      >
        <div class="pointer-events-auto max-w-lg w-full space-y-2">
          <div
            v-if="$page.props.flash?.success"
            class="flex items-start gap-3 rounded-xl bg-green-600/90 text-white px-4 py-3 shadow-lg"
            role="alert"
          >
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a10 10 0 11-20 0 10 10 0 0120 0"/>
            </svg>
            <div class="text-sm font-medium">{{ $page.props.flash.success }}</div>
          </div>
          <div
            v-if="$page.props.flash?.error"
            class="flex items-start gap-3 rounded-xl bg-red-600/90 text-white px-4 py-3 shadow-lg"
            role="alert"
          >
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm font-medium">{{ $page.props.flash.error }}</div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Header -->
    <div class="bg-white/80 backdrop-blur shadow-sm border-b border-gray-200">
      <div class="px-6 py-5 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ project.title }}</h1>
            <p class="text-gray-600 mt-1">Community Project Details</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="`inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium ${statusColors[project.status]}`">
              <span class="inline-block h-2 w-2 rounded-full bg-current/70"></span>
              {{ project.status }}
            </span>
            <span :class="`inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium ${categoryColors[project.category]}`">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 7h18M3 12h18M3 17h18"/>
              </svg>
              {{ project.category }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 max-w-7xl mx-auto p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Project Photos -->
          <section v-if="project.photo_paths && project.photo_paths.length > 0"
                   class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Project Photos</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="(photoPath, index) in project.photo_paths"
                :key="index"
                class="aspect-square overflow-hidden rounded-lg border border-gray-200"
              >
                <img
                  :src="`/storage/${photoPath}`"
                  :alt="`Project photo ${index + 1}`"
                  class="w-full h-full object-cover transition-transform duration-200 hover:scale-[1.03]"
                >
              </div>
            </div>
          </section>

          <!-- Project Description -->
          <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Project Description</h2>
            <p class="text-gray-700 leading-relaxed whitespace-pre-line">
              {{ project.description }}
            </p>
          </section>

          <!-- Latest News & Updates -->
          <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-900">Project News & Updates</h2>
              <div v-if="isAdmin" class="text-sm text-gray-500">
                Total updates: {{ project.news_history ? project.news_history.length : 0 }}
              </div>
            </div>

            <!-- News History Timeline -->
            <div v-if="project.news_history && project.news_history.length > 0"
                 class="max-h-96 overflow-y-auto space-y-4 pr-2">
              <div
                v-for="(news, index) in project.news_history"
                :key="news.id"
                class="relative pl-6"
              >
                <!-- Timeline bullet -->
                <span class="absolute left-0 top-3 h-3 w-3 rounded-full bg-blue-400"></span>

                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <h3 class="text-sm font-semibold text-blue-900">
                        Update #{{ project.news_history.length - index }}
                      </h3>
                      <span class="text-xs text-blue-700/80">by {{ news.creator_name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <span class="text-xs text-blue-700/80">{{ formatDate(news.created_at) }}</span>
                      <!-- Delete button (UI only) -->
                      <button
                        v-if="isAdmin || news.creator_name === $page.props.auth.user.name"
                        @click="deleteNews(news.id)"
                        class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 text-xs font-medium"
                        title="Delete this news"
                      >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4l1 3H9l1-3z"/>
                        </svg>
                        Delete
                      </button>
                    </div>
                  </div>

                  <p class="text-gray-800 whitespace-pre-wrap leading-relaxed">{{ news.content }}</p>

                  <!-- Files -->
                  <div v-if="news.files && news.files.length > 0" class="mt-3">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Attached Files</h4>
                    <div class="grid grid-cols-1 gap-3">
                      <div
                        v-for="(file, fileIndex) in news.files"
                        :key="fileIndex"
                        class="flex items-center justify-between bg-white border border-gray-200 p-3 rounded-lg"
                      >
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                          <div class="h-10 w-10 rounded-md bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg v-if="file.mime_type && file.mime_type.startsWith('image/')"
                                 class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <svg v-else class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                          </div>
                          <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 truncate">
                              {{ file.original_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                              {{ (file.size / 1024 / 1024).toFixed(2) }} MB
                            </p>
                          </div>
                        </div>
                        <a
                          :href="`/storage/${file.path}`"
                          target="_blank"
                          class="text-blue-600 hover:text-blue-800 text-sm font-medium flex-shrink-0 ml-3"
                        >
                          View
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-10">
              <div class="mx-auto h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </div>
              <p class="text-gray-600">No news available yet</p>
            </div>

            <!-- Admin: Add News -->
            <div v-if="isAdmin" class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="text-md font-medium text-gray-900 mb-3">Add New News</h3>
              <form @submit.prevent="updateLatestNews" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">News Content</label>
                  <textarea
                    v-model="newsForm.latest_news"
                    rows="4"
                    placeholder="Enter latest news, meeting point updates, or important announcements..."
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Upload Files</label>
                  <div class="rounded-lg border-2 border-dashed border-gray-300 p-4">
                    <input
                      type="file"
                      @change="handleFileUpload"
                      multiple
                      accept="image/*,.pdf,.doc,.docx,.txt"
                      class="w-full cursor-pointer"
                    />
                    <p class="text-sm text-gray-500 mt-2">
                      Supported: Images (JPG, PNG, GIF), PDF, Word, Text files
                    </p>
                    <p class="text-sm text-gray-500">
                      Max size: 5MB per file
                    </p>
                  </div>

                <!-- Selected files -->
                  <div v-if="newsForm.news_files.length > 0" class="mt-3">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files</h4>
                    <div class="space-y-2">
                      <div
                        v-for="(file, index) in newsForm.news_files"
                        :key="index"
                        class="flex items-center justify-between bg-gray-50 p-2 rounded-md"
                      >
                        <span class="text-sm text-gray-700 truncate">{{ file.name }}</span>
                        <span class="text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end gap-3">
                  <button
                    type="button"
                    @click="newsForm.reset()"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition"
                  >
                    Reset
                  </button>
                  <button
                    type="submit"
                    :disabled="newsForm.processing"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50"
                  >
                    {{ newsForm.processing ? 'Adding...' : 'Add News' }}
                  </button>
                </div>
              </form>
            </div>
          </section>
        </div>

        <!-- Sidebar (sticky on large screens) -->
        <div class="space-y-6 lg:sticky lg:top-6 h-fit">
          <!-- Project Details -->
          <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Project Details</h2>
            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-gray-600">Location</label>
                <p class="text-gray-900 font-medium">{{ project.location }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600">Date & Time</label>
                <p class="text-gray-900 font-medium">{{ formatDate(project.date) }}</p>
                <p class="text-gray-700">{{ formatTime(project.time) }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600">Duration</label>
                <p class="text-gray-900">{{ project.duration }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600">Volunteers</label>
                <div class="flex items-center justify-between">
                  <p class="text-gray-900">
                    {{ project.volunteers_joined }}/{{ project.volunteers_needed }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ Math.round((project.volunteers_joined / project.volunteers_needed) * 100) }}%
                  </p>
                </div>
                <div class="w-full bg-gray-200 h-2 mt-2 rounded-full overflow-hidden">
                  <div
                    class="bg-blue-600 h-2 rounded-full transition-all"
                    :style="`width: ${(project.volunteers_joined / project.volunteers_needed) * 100}%`"
                  ></div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600">Points Reward</label>
                <p class="text-blue-600 font-bold text-lg">{{ project.points_reward }} points</p>
              </div>
            </div>
          </section>

          <!-- Admin Controls -->
          <section v-if="isAdmin" class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Admin Controls</h2>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Status</label>
                <select
                  v-model="statusForm.status"
                  @change="updateProjectStatus"
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="upcoming">Upcoming</option>
                  <option value="active">Active</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Upcoming:</strong> Project is planned but not yet started</p>
                <p><strong>Active:</strong> Project is currently running</p>
                <p><strong>Completed:</strong> Project finished - volunteers get points</p>
                <p><strong>Cancelled:</strong> Project was cancelled</p>
              </div>
            </div>
          </section>

          <!-- User Application Status -->
          <section v-if="userApplication && !isAdmin" class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Application</h2>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-600">Status</label>
                <span :class="`inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[userApplication.status]}`">
                  <span class="inline-block h-1.5 w-1.5 rounded-full bg-current/70"></span>
                  {{ userApplication.status }}
                </span>
              </div>

              <div v-if="userApplication.status === 'approved'">
                <p class="text-green-700 font-medium">You're approved for this project!</p>
                <p class="text-sm text-gray-600 mt-1">Check the latest news above for updates and meeting details.</p>
              </div>

              <div v-if="userApplication.status === 'rejected' && userApplication.rejection_reason">
                <label class="block text-sm font-medium text-gray-600">Rejection Reason</label>
                <p class="text-sm text-gray-800 bg-red-50 p-3 rounded-lg border border-red-200">
                  {{ userApplication.rejection_reason }}
                </p>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>

    <!-- Fixed Bottom Button -->
    <div class="bg-white/80 backdrop-blur border-t border-gray-200 p-6">
      <div class="max-w-7xl mx-auto">
        <Link
          :href="isAdmin ? '/admin/community-projects' : '/volunteer/projects'"
          class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-gray-100 text-gray-800 py-3 px-4 font-medium hover:bg-gray-200 transition"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Back to Projects
        </Link>
      </div>
    </div>
  </div>
</template>
