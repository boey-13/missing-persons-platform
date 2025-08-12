<template>
  <div class="font-['Poppins'] text-[#333] min-h-screen flex flex-col">
    <!--  HEADER  -->
    <header class="bg-[#6B4C3B] text-white px-8 py-3 flex items-center justify-between font-normal">
      <!-- Logo -->
      <div class="flex items-center">
        <img src="/logo-brown.png" alt="FindMe Logo" class="h-8 mr-2" />
        <span class="italic text-2xl">FindMe</span>
      </div>
      <!-- Navigation Bar -->
      <nav class="flex gap-8 text-lg font-normal">
        <a href="/">Home</a>
        <!-- Missing Person Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('missing')" class="flex items-center gap-1">
            Missing Person
            <span :class="{ 'rotate-180': dropdownOpen === 'missing' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'missing'" @click.away="dropdownOpen = null"
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-50">
            <a href="/cases" class="block px-4 py-2 hover:bg-[#e7d6c3]">View Case</a>
            <a href="/missing-persons/report" class="block px-4 py-2 hover:bg-[#e7d6c3]">Report Missing Person</a>
          </div>
        </div>
        <!-- Volunteer Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('volunteer')" class="flex items-center gap-1">
            Volunteer
            <span :class="{ 'rotate-180': dropdownOpen === 'volunteer' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'volunteer'" @click.away="dropdownOpen = null"
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-50">
            <a href="/volunteer/projects" class="block px-4 py-2 hover:bg-[#e7d6c3]">Community Project</a>
            <a href="/volunteer/apply" class="block px-4 py-2 hover:bg-[#e7d6c3]">Become Volunteer</a>
          </div>
        </div>
        <a href="#">About Us</a>
        <a href="#">Contact Us</a>
        <!-- User Dropdown (show name if logged in, Login if not) -->
        <div class="relative">
          <button @click="toggleDropdown('user')" class="flex items-center gap-1">
            {{ currentUser ? currentUser.name : 'Login' }}
            <span :class="{ 'rotate-180': dropdownOpen === 'user' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'user'" @click.away="dropdownOpen = null"
            class="absolute right-0 mt-2 w-52 bg-white text-[#333] rounded shadow z-50">
            <template v-if="currentUser">
              <a href="/profile" class="block px-4 py-2 hover:bg-[#ddc3a5]">User Profile</a>
              <a href="/admin/dashboard" class="block px-4 py-2 hover:bg-[#ddc3a5]">Admin Dashboard</a>
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

      </nav>
    </header>

    <!--  MAIN CONTENT -->
    <main>
      <slot />
    </main>

    <!--  FOOTER  -->
    <footer class="bg-[#ededed] text-[#47312a] pt-16 pb-10 border-t border-[#ede7e0]">
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
            <a href="#" class="hover:text-[#A67B5B]">About</a>
            <a href="/cases" class="hover:text-[#A67B5B]">View Case</a>
            <a href="/missing-persons/report" class="hover:text-[#A67B5B]">Report Case</a>
            <a href="/volunteer/projects" class="hover:text-[#A67B5B]">Volunteer</a>
          </nav>
        </div>
        <!-- Help Section -->
        <div>
          <div class="font-bold text-lg mb-3 tracking-wide">Help</div>
          <nav class="flex flex-col gap-2 text-base">
            <a href="#" class="hover:text-[#A67B5B]">Contact Us</a>
            <a href="#" class="hover:text-[#A67B5B]">Terms & Conditions</a>
            <a href="#" class="hover:text-[#A67B5B]">Privacy Policy</a>
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
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Chatbot from '../Components/Chatbot.vue'


const dropdownOpen = ref(null)
function toggleDropdown(menu) {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu
}

// Get current user from Inertia page props
const page = usePage()
const currentUser = computed(() => page.props.auth?.user)
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
