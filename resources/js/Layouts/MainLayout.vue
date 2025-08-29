<template>
  <div class="font-['Poppins'] text-[#333] min-h-screen flex flex-col">
    <!--  HEADER  -->
    <header class="bg-[#6B4C3B] text-white px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between font-normal">
      <!-- Logo -->
      <div class="flex items-center">
        <img src="../assets/white.png" alt="FindMe Logo" class="h-6 sm:h-8 mr-2" />
      </div>

      <!-- Desktop Navigation Bar -->
      <nav class="hidden lg:flex gap-6 xl:gap-8 text-base xl:text-lg font-normal items-center">
        <a href="/" class="hover:text-[#e7d6c3] transition-colors">Home</a>
        <!-- Missing Person Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('missing')" class="flex items-center gap-1 hover:text-[#e7d6c3] transition-colors">
            Missing Person
            <span :class="{ 'rotate-180': dropdownOpen === 'missing' }" class="transition-transform">▼</span>
          </button>
          <div v-if="dropdownOpen === 'missing'" 
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-40">
            <a href="/missing-persons" class="block px-4 py-2 hover:bg-[#e7d6c3]">View Missing Cases</a>
            <!-- Show Report Missing Person only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="block px-4 py-2 hover:bg-[#e7d6c3]">Report Missing Person</a>
          </div>
        </div>
        <!-- Volunteer Dropdown -->
        <div v-if="currentUser" class="relative">
          <button @click="toggleDropdown('volunteer')" class="flex items-center gap-1 hover:text-[#e7d6c3] transition-colors">
            Volunteer
            <span :class="{ 'rotate-180': dropdownOpen === 'volunteer' }" class="transition-transform">▼</span>
          </button>
          <div v-if="dropdownOpen === 'volunteer'" 
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-40">
            <!-- Show Community Projects only for approved volunteers and admins -->
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="block px-4 py-2 hover:bg-[#e7d6c3]">Community Project</a>
            <!-- Show Become Volunteer for users without approved application -->
            <a v-if="showBecomeVolunteer" href="/volunteer/apply" class="block px-4 py-2 hover:bg-[#e7d6c3]">Become Volunteer</a>
          </div>
        </div>
        <a href="/about" class="hover:text-[#e7d6c3] transition-colors">About Us</a>
        <a href="/contact" class="hover:text-[#e7d6c3] transition-colors">Contact Us</a>
        <!-- User Dropdown (show name if logged in, Login if not) -->
        <div class="relative">
          <button @click="toggleDropdown('user')" class="flex items-center gap-1 hover:text-[#e7d6c3] transition-colors">
            {{ currentUser ? currentUser.name : 'Login' }}
            <span :class="{ 'rotate-180': dropdownOpen === 'user' }" class="transition-transform">▼</span>
          </button>
          <div v-if="dropdownOpen === 'user'" 
            class="absolute right-0 mt-2 w-52 bg-white text-[#333] rounded shadow z-40">
            <template v-if="currentUser">
              <a href="/profile" class="block px-4 py-2 hover:bg-[#ddc3a5]">User Profile</a>
              <!-- Show Admin Dashboard only for admins -->
              <a v-if="isAdmin" href="/admin/dashboard" class="block px-4 py-2 hover:bg-[#ddc3a5]">Admin Dashboard</a>
              <Link :href="route('logout')" method="post" as="button"
                class="block px-4 py-2 hover:bg-[#ddc3a5] w-full text-left">
              Log Out
              </Link>
            </template>
            <template v-else>
              <a href="/login" class="block px-4 py-2 hover:bg-[#ddc3a5]">Login</a>
            </template>
          </div>
        </div>
        <NotificationsBell />
      </nav>

      <!-- Mobile Header Actions -->
      <div class="flex items-center gap-3 lg:hidden">
        <!-- Mobile Notifications Bell -->
        <NotificationsBell />
        
        <!-- Mobile Menu Button -->
        <button 
          @click="toggleMobileMenu" 
          class="flex items-center justify-center w-8 h-8 text-white hover:text-[#e7d6c3] transition-colors"
          aria-label="Toggle mobile menu"
        >
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div 
      v-if="mobileMenuOpen" 
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
      @click="closeMobileMenu"
    ></div>

    <!-- Mobile Sidebar Menu -->
    <div 
      v-if="mobileMenuOpen" 
      class="fixed top-0 right-0 h-full w-80 bg-white shadow-xl z-50 lg:hidden transform transition-transform duration-300 ease-in-out"
    >
      <div class="flex flex-col h-full">
        <!-- Mobile Menu Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <div class="flex items-center">
            <img src="../assets/white.png" alt="FindMe Logo" class="h-8 mr-2" />
            <span class="text-[#6B4C3B] font-semibold">Menu</span>
          </div>
          <button 
            @click="closeMobileMenu" 
            class="text-gray-500 hover:text-gray-700 transition-colors"
            aria-label="Close mobile menu"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Mobile Menu Content -->
        <div class="flex-1 overflow-y-auto py-4">
          <nav class="space-y-1">
            <!-- Home -->
            <a href="/" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
              <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              Home
            </a>

            <!-- Missing Person Section -->
            <div class="border-t border-gray-200 pt-4">
              <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Missing Person</div>
              <a href="/missing-persons" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                View Case
              </a>
              <a v-if="currentUser" href="/missing-persons/report" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Report Missing Person
              </a>
            </div>

            <!-- Volunteer Section -->
            <div v-if="currentUser" class="border-t border-gray-200 pt-4">
              <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Volunteer</div>
              <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Community Project
              </a>
              <a v-if="showBecomeVolunteer" href="/volunteer/apply" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Become Volunteer
              </a>
            </div>

            <!-- Other Links -->
            <div class="border-t border-gray-200 pt-4">
              <a href="/about" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                About Us
              </a>
              <a href="/contact" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Contact Us
              </a>
            </div>

            <!-- User Section -->
            <div class="border-t border-gray-200 pt-4">
              <template v-if="currentUser">
                <a href="/profile" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  User Profile
                </a>
                <a v-if="isAdmin" href="/admin/dashboard" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                  Admin Dashboard
                </a>
                <Link :href="route('logout')" method="post" as="button"
                  class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors w-full text-left">
                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                  </svg>
                  Log Out
                </Link>
              </template>
              <template v-else>
                <a href="/login" class="flex items-center px-4 py-3 text-[#333] hover:bg-[#f5f5f5] transition-colors">
                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                  </svg>
                  Login
                </a>
              </template>
            </div>

          </nav>
        </div>
      </div>
    </div>

    <!-- Click outside overlay to close dropdowns -->
    <div 
      v-if="dropdownOpen" 
      class="fixed inset-0 z-30" 
      @click="closeAllDropdowns"
    ></div>

    <!--  MAIN CONTENT -->
    <main class="bg-white">
      <slot />
    </main>

    <!-- Toast Container -->
    <ToastContainer />

    <!--  FOOTER  -->
    <footer class="mt-auto bg-[#ededed] text-[#47312a] pt-12 sm:pt-16 pb-8 sm:pb-10 border-t border-[#ede7e0]">
      <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-12 px-4 sm:px-6 lg:px-10">
        <!-- About Section -->
        <div>
          <div class="font-bold text-base sm:text-lg mb-3 tracking-wide">About FindMe</div>
          <div class="text-sm sm:text-base text-[#7a7a7a] leading-relaxed">
            FindMe is a secure platform that connects families, volunteers, and the community to help locate missing
            persons.
          </div>
        </div>
        <!-- Quick Links -->
        <div>
          <div class="font-bold text-base sm:text-lg mb-3 tracking-wide">Quick Links</div>
          <nav class="flex flex-col gap-2 text-sm sm:text-base">
            <a href="/about" class="hover:text-[#A67B5B] transition-colors">About</a>
            <a href="/missing-persons" class="hover:text-[#A67B5B] transition-colors">View Case</a>
            <!-- Show Report Case only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="hover:text-[#A67B5B] transition-colors">Report Case</a>
            <!-- Show Become Volunteer only for logged in users without approved application -->
            <a v-if="currentUser && !hasApprovedVolunteerApplication" href="/volunteer/apply" class="hover:text-[#A67B5B] transition-colors">Become Volunteer</a>
            <!-- Show Community Projects for approved volunteers and admins -->
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="hover:text-[#A67B5B] transition-colors">Community Projects</a>
            <!-- Show Admin Dashboard for admins -->
            <a v-if="isAdmin" href="/admin/dashboard" class="hover:text-[#A67B5B] transition-colors">Admin Dashboard</a>
            <a href="/contact" class="hover:text-[#A67B5B] transition-colors">Contact Us</a>
          </nav>
        </div>
        <!-- Help Section -->
        <div>
          <div class="font-bold text-base sm:text-lg mb-3 tracking-wide">Help</div>
          <nav class="flex flex-col gap-2 text-sm sm:text-base">
            <a href="/contact" class="hover:text-[#A67B5B] transition-colors">Contact Us</a>
            <a href="/about" class="hover:text-[#A67B5B] transition-colors">About Us</a>
            <a href="/missing-persons" class="hover:text-[#A67B5B] transition-colors">View Cases</a>
            <!-- Show Report Case only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="hover:text-[#A67B5B] transition-colors">Report Case</a>
            <!-- Show Become Volunteer for users without approved application -->
            <a v-if="currentUser && !hasApprovedVolunteerApplication" href="/volunteer/apply" class="hover:text-[#A67B5B] transition-colors">Become Volunteer</a>
            <!-- Show Community Projects for approved volunteers and admins -->
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="hover:text-[#A67B5B] transition-colors">Community Projects</a>
          </nav>
        </div>
        <!-- Social Media Section -->
        <div>
          <div class="font-bold text-base sm:text-lg mb-3 tracking-wide">Connect</div>
          <div class="flex gap-4 sm:gap-5 text-xl sm:text-2xl mb-3 mt-2">
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition-colors"><i
                class="fab fa-twitter"></i></a>
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition-colors"><i
                class="fab fa-facebook"></i></a>
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition-colors"><i
                class="fab fa-instagram"></i></a>
          </div>
          <div class="text-sm sm:text-base mt-2 text-[#a67b5b]">Follow us for updates</div>
        </div>
      </div>
      <!-- 分隔线 -->
      <div class="border-t border-[#ede7e0] mt-8 sm:mt-10 pt-4 sm:pt-6 text-center text-xs sm:text-sm text-[#7a7a7a]">
        © 2025 FindMe. All Rights Reserved.
      </div>
    </footer>

    <Chatbot />
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Chatbot from '../Components/Chatbot.vue'
import NotificationsBell from '../Components/NotificationsBell.vue'
import ToastContainer from '../Components/ToastContainer.vue'

import { useActivityMonitor } from '@/Composables/useActivityMonitor'

const dropdownOpen = ref(null)
const mobileMenuOpen = ref(false)

function toggleDropdown(menu) {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu
}

function closeAllDropdowns() {
  dropdownOpen.value = null
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value
  // Close dropdowns when mobile menu opens
  if (mobileMenuOpen.value) {
    closeAllDropdowns()
  }
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}

// Close dropdown on escape key
function handleEscapeKey(e) {
  if (e.key === 'Escape') {
    if (dropdownOpen.value) {
      closeAllDropdowns()
    }
    if (mobileMenuOpen.value) {
      closeMobileMenu()
    }
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscapeKey)
})

// Get current user from Inertia page props
const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

// Initialize activity monitor for authenticated users
if (currentUser.value) {
  useActivityMonitor()
}

// Permission checks
const isAdmin = computed(() => currentUser.value?.role === 'admin')
const isVolunteer = computed(() => currentUser.value?.role === 'volunteer')
const hasApprovedVolunteerApplication = computed(() => 
  currentUser.value?.volunteer_application?.status === 'Approved'
)
const hasPendingVolunteerApplication = computed(() => 
  currentUser.value?.volunteer_application?.status === 'Pending'
)
const canAccessVolunteerProjects = computed(() => 
  isAdmin.value || isVolunteer.value
)

// Determine if user should see "Become Volunteer" link
const showBecomeVolunteer = computed(() => 
  currentUser.value && !isVolunteer.value
)

// Get the correct volunteer link destination
const volunteerLinkDestination = computed(() => {
  if (!currentUser.value) return null
  if (hasPendingVolunteerApplication.value) return '/volunteer/application-pending'
  if (isVolunteer.value) return '/volunteer/projects'
  return '/volunteer/apply'
})
</script>

<style scoped>
/* Utility: Flip arrow icon */
.rotate-180 {
  transform: rotate(180deg);
}

/* Use Poppins font for every part (for safety if Tailwind font is not loaded) */
:deep(body) {
  font-family: 'Poppins', Arial, sans-serif !important;
}

</style>
