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
            <a href="/volunteer/become" class="block px-4 py-2 hover:bg-[#e7d6c3]">Become Volunteer</a>
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
    <main class="flex-1 max-w-6xl w-full mx-auto min-h-[60vh] pt-3">
      <slot />
    </main>

    <!--  FOOTER  -->
    <footer class="bg-[#ede7e0] text-[#333] py-14 border-t mt-4">
      <div class="max-w-6xl mx-auto grid grid-cols-4 gap-8 px-8">
        <!-- About Section -->
        <div>
          <div class="font-bold mb-2">About FindMe</div>
          <div class="text-sm text-[#666]">
            FindMe is a secure platform that connects families, volunteers, and the community to help locate missing
            persons.
          </div>
        </div>
        <!-- Quick Links -->
        <div>
          <div class="font-bold mb-2">FindMe</div>
          <a href="#" class="block">About</a>
          <a href="/cases" class="block">View Case</a>
          <a href="/missing-persons/report" class="block">Report Case</a>
          <a href="/volunteer/projects" class="block">Volunteer</a>
        </div>
        <!-- Help Section -->
        <div>
          <div class="font-bold mb-2">Help</div>
          <a href="#" class="block">Contact Us</a>
          <a href="#" class="block">Terms & Conditions</a>
          <a href="#" class="block">Privacy Policy</a>
        </div>
        <!-- Social Media Section -->
        <div>
          <div class="font-bold mb-2">Social Media</div>
          <div class="flex gap-3 text-xl mt-2 mb-1">
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
          </div>
          <div class="text-sm mt-1">Twitter Facebook Instagram</div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

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
