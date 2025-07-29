<template>
  <div>
    <!-- Header -->
    <header class="bg-[#6B4C3B] text-white px-8 py-3 flex items-center justify-between font-poppins font-normal">
      <!-- Logo -->
      <div class="flex items-center">
        <img src="/logo-brown.png" alt="FindMe Logo" class="h-8 mr-2" />
        <span class="italic text-2xl">FindMe</span>
      </div>
      <!-- Navigation Bar -->
      <nav class="flex gap-8 text-xl font-normal">
        <a href="/">Home</a>

        <!-- Missing Person Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('missing')" class="flex items-center gap-1">
            Missing Person
            <span :class="{ 'rotate-180': dropdownOpen === 'missing' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'missing'" @click.away="dropdownOpen = null"
            class="absolute left-0 mt-2 w-48 bg-white text-[#333] rounded shadow z-50">
            <a href="/cases" class="block px-4 py-2 hover:bg-[#e2c799]">View Case</a>
            <a href="/missing-persons/report" class="block px-4 py-2 hover:bg-[#e2c799]">Report Missing Person</a>
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
            <a href="/volunteer/projects" class="block px-4 py-2 hover:bg-[#e2c799]">Community Project</a>
            <a href="/volunteer/become" class="block px-4 py-2 hover:bg-[#e2c799]">Become Volunteer</a>
          </div>
        </div>

        <a href="#">About Us</a>
        <a href="#">Contact Us</a>

        <!-- UserName Dropdown -->
        <div class="relative">
          <button @click="toggleDropdown('user')" class="flex items-center gap-1">
            UserName
            <span :class="{ 'rotate-180': dropdownOpen === 'user' }">▼</span>
          </button>
          <div v-if="dropdownOpen === 'user'" @click.away="dropdownOpen = null"
            class="absolute right-0 mt-2 w-52 bg-white text-[#333] rounded shadow z-50">
            <a href="/profile" class="block px-4 py-2 hover:bg-[#e2c799]">User Profile</a>
            <a href="/admin/dashboard" class="block px-4 py-2 hover:bg-[#e2c799]">Admin Dashboard</a>
            <a href="/logout" class="block px-4 py-2 hover:bg-[#e2c799]">Log Out</a>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main content -->
    <main class="min-h-[60vh]">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-[#f8ecd8] text-[#333] py-8 border-t mt-16">
      <div class="max-w-6xl mx-auto grid grid-cols-4 gap-8 px-8">
        <div>
          <div class="font-bold mb-2">About FindMe</div>
          <div class="text-sm text-[#666]">
            FindMe is a secure platform that connects families, volunteers, and the community to help locate missing
            persons.
          </div>
        </div>
        <div>
          <div class="font-bold mb-2">FindMe</div>
          <div>About</div>
          <div>View Case</div>
          <div>Report Case</div>
          <div>Volunteer</div>
        </div>
        <div>
          <div class="font-bold mb-2">Help</div>
          <div>Contact Us</div>
          <div>Terms & Conditions</div>
          <div>Privacy Policy</div>
        </div>
        <div>
          <div class="font-bold mb-2">Social Media</div>
          <div class="flex gap-3 text-xl mt-2">
            <a href="#"><i class="fab fa-twitter"></i>Twitter</a>
            <a href="#"><i class="fab fa-facebook"></i>Facebook</a>
            <a href="#"><i class="fab fa-instagram"></i>Instagram</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const dropdownOpen = ref(null)

function toggleDropdown(menu) {
  dropdownOpen.value = dropdownOpen.value === menu ? null : menu
}
</script>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
