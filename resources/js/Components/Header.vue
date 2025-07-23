<template>
  <header class="bg-[#725644] text-white px-8 py-3 flex items-center justify-between shadow">
    <!-- LOGO -->
    <div class="flex items-center gap-2">
      <img src="/logo/brown include bgd.png" alt="FindMe" class="h-10" />
      <!-- 或者用文字：<span class="text-2xl font-logo font-bold italic">FindMe</span> -->
    </div>
    <!-- navigation -->
    <nav class="flex gap-10 items-center font-[Barlow] text-2xl">
      <a href="/" class="hover:text-[#ffe3c0]">Home</a>
      <div class="relative group">
        <button class="flex items-center gap-1 hover:text-[#ffe3c0]">
          Missing Person <span>▼</span>
        </button>
        <!-- dropdown menu -->
        <div class="hidden group-hover:block absolute bg-white text-[#725644] rounded shadow z-10 mt-1 w-48">
          <a href="/cases" class="block px-4 py-2 hover:bg-[#ffe3c0]">View Case</a>
          <a href="/missing-persons/report" class="block px-4 py-2 hover:bg-[#ffe3c0]">Report Missing Person</a>
        </div>
      </div>
      <div class="relative group">
        <button class="flex items-center gap-1 hover:text-[#ffe3c0]">
          Volunteer <span>▼</span>
        </button>
        <div class="hidden group-hover:block absolute bg-white text-[#725644] rounded shadow z-10 mt-1 w-56">
          <a href="/volunteer/projects" class="block px-4 py-2 hover:bg-[#ffe3c0]">Community Project</a>
          <a href="/volunteer/apply" class="block px-4 py-2 hover:bg-[#ffe3c0]">Become Volunteer</a>
        </div>
      </div>
      <a href="/about" class="hover:text-[#ffe3c0]">About Us</a>
      <a href="/contact" class="hover:text-[#ffe3c0]">Contact Us</a>
      <div class="relative group">
        <button class="flex items-center gap-1 hover:text-[#ffe3c0]">
          UserName <span>▼</span>
        </button>
        <div class="hidden group-hover:block absolute right-0 bg-white text-[#725644] rounded shadow z-10 mt-1 w-56">
          <a href="/profile" class="block px-4 py-2 hover:bg-[#ffe3c0]">User Profile</a>
          <a href="/admin/dashboard" class="block px-4 py-2 hover:bg-[#ffe3c0]">Admin Dashboard</a>
          <a href="/logout" class="block px-4 py-2 hover:bg-[#ffe3c0]">Log Out</a>
        </div>
      </div>
    </nav>
  </header>
</template>
