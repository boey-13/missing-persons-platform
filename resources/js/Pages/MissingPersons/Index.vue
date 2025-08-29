<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import FilterBar from '@/Components/MissingPerson/FilterBar.vue'
import CaseCard from '@/Components/MissingPerson/CaseCard.vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: MainLayout })

const { success, error } = useToast()

const cases = ref([])                // List of missing person cases
const total = ref(0)                 // Total case count
const filters = ref({                // Filter object
    ageMin: null,
    ageMax: null,
    gender: [],
    location: [],
    reportTime: [],
    weightMin: null,
    weightMax: null,
    heightMin: null,
    heightMax: null,
})
const search = ref('')               // Search keyword
const page = ref(1)                  // Current page
const perPage = 8                    // Cases per page
const loading = ref(false)           // Loading indicator
const showMobileFilters = ref(false) // Mobile filter toggle

// Fetch cases from backend with current filter & search
async function fetchCases() {
    loading.value = true
    try {
        const response = await axios.get('/api/missing-persons', {
            params: {
                ...filters.value,
                search: search.value,
                page: page.value,
                per_page: perPage
            }
        })
        cases.value = response.data.data     // Adjust if your API structure different
        total.value = response.data.total
    } catch (err) {
        error('Failed to load cases. Please try again.')
        console.error('Error fetching cases:', err)
    } finally {
        loading.value = false
    }
}

// Called when user applies filter or search
function onFilterApply(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
    page.value = 1
    fetchCases()
    // Close mobile filters after applying
    showMobileFilters.value = false
}

// Called when user clears filter
function onFilterClear() {
    filters.value = {
        ageMin: null, ageMax: null, gender: [], location: [], reportTime: [],
        weightMin: null, weightMax: null, heightMin: null, heightMax: null,
    }
    page.value = 1
    fetchCases()
}

// Called when search input changes
function onSearch() {
    page.value = 1
    fetchCases()
}

// Called when page changes
function onPageChange(newPage) {
    page.value = newPage
    fetchCases()
}

// Toggle mobile filters
function toggleMobileFilters() {
    showMobileFilters.value = !showMobileFilters.value
}

// Initial load
onMounted(fetchCases)
</script>

<template>
  <div class="flex min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Desktop Filter Bar -->
    <aside class="hidden md:block md:w-72 min-h-screen bg-gradient-to-b from-white to-gray-50 border-r border-gray-200 shadow-sm">
      <div class="sticky top-0 p-8">
        <FilterBar :filters="filters" @apply="onFilterApply" @clear="onFilterClear" />
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col items-center px-4 md:px-12 py-10 bg-white">
      <!-- Title -->
      <h1 class="text-4xl font-extrabold text-gray-900 mb-1 tracking-tight">Missing Person Cases</h1>
      <div class="w-24 h-1 bg-gray-400 rounded-full mx-auto mb-8"></div>
      
      <!-- Mobile Filter Toggle Button -->
      <div class="md:hidden w-full mb-6">
        <button
          @click="toggleMobileFilters"
          class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 flex items-center justify-between shadow-sm hover:bg-gray-50 transition-colors"
        >
          <span class="text-sm font-medium text-gray-700">Search Filters</span>
          <svg 
            class="w-5 h-5 text-gray-500 transition-transform" 
            :class="{ 'rotate-180': showMobileFilters }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
      
      <!-- Mobile Filter Panel -->
      <div v-if="showMobileFilters" class="md:hidden w-full mb-6 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="p-4">
          <FilterBar :filters="filters" @apply="onFilterApply" @clear="onFilterClear" />
        </div>
      </div>
      
      <!-- Search Bar -->
      <div class="mb-10 flex w-full max-w-2xl gap-2 items-center">
        <input
          v-model="search"
          @keyup.enter="onSearch"
          type="text"
          placeholder="Search by name..."
          class="flex-1 rounded-xl border border-gray-300 px-6 py-3 bg-gray-100 text-base focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 shadow-sm"
        />
        <button
          @click="onSearch"
          class="rounded-xl px-5 py-3 bg-gray-800 text-white font-medium shadow hover:bg-gray-700 transition-colors"
        >
          <i class="fas fa-search mr-1"></i>Search
        </button>
      </div>
      
      <!-- Loading Spinner -->
      <div v-if="loading" class="text-center py-16">
        <span class="loading loading-spinner"></span>
      </div>
      
      <!-- Case Grid -->
      <div
        v-if="!loading && cases.length"
        class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 w-full max-w-7xl"
      >
        <CaseCard v-for="item in cases" :key="item.id" :person="item" />
      </div>
      <div v-else-if="!loading" class="text-gray-400 text-center py-20">No cases found</div>
      
      <!-- Pagination -->
      <div class="mt-10 flex justify-center">
        <nav v-if="total > perPage" class="flex items-center space-x-4">
          <button
            :disabled="page <= 1"
            @click="onPageChange(page - 1)"
            class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Previous
          </button>
          
          <button
            v-for="n in Math.ceil(total / perPage)"
            :key="n"
            :class="[
              'text-sm px-3 py-1 rounded transition-colors',
              page === n 
                ? 'bg-gray-800 text-white' 
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            ]"
            @click="onPageChange(n)"
          >
            {{ n }}
          </button>
          
          <button
            :disabled="page >= Math.ceil(total / perPage)"
            @click="onPageChange(page + 1)"
            class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center"
          >
            Next
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </nav>
      </div>
    </main>
  </div>
</template>
