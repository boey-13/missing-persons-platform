<template>
  <div class="font-['Poppins'] text-[#333] min-h-screen flex flex-col">
    <!--  HEADER  -->
    <header class="bg-[#6B4C3B] text-white px-8 py-3 flex items-center justify-between font-normal">
      <!-- Logo -->
      <div class="flex items-center">
        <img src="../assets/white.png" alt="FindMe Logo" class="h-8 mr-2" />
      </div>
      <!-- Navigation Bar -->
      <nav class="flex gap-8 text-lg font-normal items-center">
        <a href="/">Home</a>
        <!-- Missing Person Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('missing')" class="flex items-center gap-1">
            Missing Person
            <span :class="{ 'rotate-180': dropdownOpen === 'missing' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'missing'" 
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-40">
            <a href="/missing-persons" class="block px-4 py-2 hover:bg-[#e7d6c3]">View Case</a>
            <!-- Show Report Missing Person only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="block px-4 py-2 hover:bg-[#e7d6c3]">Report Missing Person</a>
          </div>
        </div>
        <!-- Volunteer Dropdown -->
        <div v-if="currentUser" class="relative">
          <button @click="toggleDropdown('volunteer')" class="flex items-center gap-1">
            Volunteer
            <span :class="{ 'rotate-180': dropdownOpen === 'volunteer' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'volunteer'" 
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-40">
            <!-- Show Community Projects only for approved volunteers and admins -->
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="block px-4 py-2 hover:bg-[#e7d6c3]">Community Project</a>
            <!-- Show Become Volunteer only for users without approved application -->
            <a v-if="!hasApprovedVolunteerApplication" href="/volunteer/apply" class="block px-4 py-2 hover:bg-[#e7d6c3]">Become Volunteer</a>
            <!-- Show application status for pending/rejected applications -->
            <a v-if="hasVolunteerApplication && !isApprovedVolunteer" href="/volunteer/application-pending" class="block px-4 py-2 hover:bg-[#e7d6c3]">Application Status</a>
          </div>
        </div>
        <a href="/about">About Us</a>
        <a href="/contact">Contact Us</a>
        <!-- User Dropdown (show name if logged in, Login if not) -->
        <div class="relative">
          <button @click="toggleDropdown('user')" class="flex items-center gap-1">
            {{ currentUser ? currentUser.name : 'Login' }}
            <span :class="{ 'rotate-180': dropdownOpen === 'user' }">▼</span>
          </button>
                      <div v-if="dropdownOpen === 'user'" 
            class="absolute right-0 mt-2 w-52 bg-white text-[#333] rounded shadow z-40">
            <template v-if="currentUser">
              <a href="/profile" class="block px-4 py-2 hover:bg-[#ddc3a5]">User Profile</a>
              <!-- Show Admin Dashboard only for admins -->
              <a v-if="isAdmin" href="/admin/dashboard" class="block px-4 py-2 hover:bg-[#ddc3a5]">Admin Dashboard</a>
              <Link href="/logout" method="post" as="button"
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
    </header>

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

    <!--  FOOTER  -->
    <footer class="mt-auto bg-[#ededed] text-[#47312a] pt-16 pb-10 border-t border-[#ede7e0]">
      <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12 px-6 md:px-10">
        <!-- About Section -->
        <div>
          <div class="font-bold text-lg mb-3 tracking-wide">About FindMe</div>
          <div class="text-base text-[#7a7a7a] leading-relaxed">
            FindMe is a secure platform that connects families, volunteers, and the community to help locate missing
            persons.
          </div>
        </div>
        <!-- Quick Links -->
        <div>
          <div class="font-bold text-lg mb-3 tracking-wide">Quick Links</div>
          <nav class="flex flex-col gap-2 text-base">
            <a href="/about" class="hover:text-[#A67B5B]">About</a>
            <a href="/missing-persons" class="hover:text-[#A67B5B]">View Case</a>
            <!-- Show Report Case only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="hover:text-[#A67B5B]">Report Case</a>
            <!-- Show Become Volunteer only for logged in users without approved application -->
            <a v-if="currentUser && !hasApprovedVolunteerApplication" href="/volunteer/apply" class="hover:text-[#A67B5B]">Become Volunteer</a>
            <!-- Show Community Projects for approved volunteers and admins -->
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="hover:text-[#A67B5B]">Community Projects</a>
            <!-- Show Admin Dashboard for admins -->
            <a v-if="isAdmin" href="/admin/dashboard" class="hover:text-[#A67B5B]">Admin Dashboard</a>
            <a href="/contact" class="hover:text-[#A67B5B]">Contact Us</a>
          </nav>
        </div>
        <!-- Help Section -->
        <div>
          <div class="font-bold text-lg mb-3 tracking-wide">Help</div>
          <nav class="flex flex-col gap-2 text-base">
            <a href="/contact" class="hover:text-[#A67B5B]">Contact Us</a>
            <a href="/about" class="hover:text-[#A67B5B]">About Us</a>
            <a href="/missing-persons" class="hover:text-[#A67B5B]">View Cases</a>
            <!-- Show Report Case only for logged in users -->
            <a v-if="currentUser" href="/missing-persons/report" class="hover:text-[#A67B5B]">Report Case</a>
            <!-- Show volunteer-related help for logged in users -->
            <a v-if="currentUser" href="/volunteer/apply" class="hover:text-[#A67B5B]">Become Volunteer</a>
            <a v-if="canAccessVolunteerProjects" href="/volunteer/projects" class="hover:text-[#A67B5B]">Community Projects</a>
          </nav>
        </div>
        <!-- Social Media Section -->
        <div>
          <div class="font-bold text-lg mb-3 tracking-wide">Connect</div>
          <div class="flex gap-5 text-2xl mb-3 mt-2">
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition"><i
                class="fab fa-twitter"></i></a>
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition"><i
                class="fab fa-facebook"></i></a>
            <a href="#" class="bg-white rounded-full p-2 shadow hover:bg-[#ede7e0] transition"><i
                class="fab fa-instagram"></i></a>
          </div>
          <div class="text-base mt-2 text-[#a67b5b]">Follow us for updates</div>
        </div>
      </div>
      <!-- 分隔线 -->
      <div class="border-t border-[#ede7e0] mt-10 pt-6 text-center text-sm text-[#7a7a7a]">
        © 2025 FindMe. All Rights Reserved.
      </div>
    </footer>

    <Chatbot />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Chatbot from '../Components/Chatbot.vue'
import NotificationsBell from '../Components/NotificationsBell.vue'

const dropdownOpen = ref(null)

function toggleDropdown(menu) {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu
}

function closeAllDropdowns() {
  dropdownOpen.value = null
}

// Close dropdown on escape key
function handleEscapeKey(e) {
  if (e.key === 'Escape' && dropdownOpen.value) {
    closeAllDropdowns()
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

// Permission checks
const isAdmin = computed(() => currentUser.value?.role === 'admin')
const hasVolunteerApplication = computed(() => currentUser.value?.volunteer_application !== null)
const isApprovedVolunteer = computed(() => 
  currentUser.value?.volunteer_application?.status === 'Approved'
)
const hasApprovedVolunteerApplication = computed(() => 
  currentUser.value?.volunteer_application?.status === 'Approved'
)
const canAccessVolunteerProjects = computed(() => 
  isAdmin.value || isApprovedVolunteer.value
)
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
