<template>
  <div>
    <!-- Header -->
    <header class="bg-[#6B4C3B] text-white px-8 py-3 flex items-center justify-between">
      <div class="flex items-center">
        <img src="/logo-brown.png" alt="FindMe Logo" class="h-8 mr-2" />
        <span class="font-bold italic text-2xl">FindMe</span>
      </div>
      <!-- 这里加导航栏 -->
      <nav class="flex gap-8 text-xl font-semibold">
        <a href="/">Home</a>
        <div class="relative group">
          <button>Missing Person ▼</button>
          <!-- 这里可以做 dropdown，下一步讲 -->
        </div>
        <div class="relative group">
          <button>Volunteer ▼</button>
        </div>
        <a href="#">About Us</a>
        <a href="#">Contact Us</a>
        <div class="relative group">
          <button>UserName ▼</button>
        </div>
      </nav>
    </header>

    <!-- 页面内容 -->
    <main class="min-h-[60vh]">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-[#f8ecd8] text-[#333] py-8 border-t mt-16">
      <div class="max-w-6xl mx-auto grid grid-cols-4 gap-8 px-8">
        <div>
          <div class="font-bold mb-2">About FindMe</div>
          <div class="text-sm text-[#666]">
            FindMe is a secure platform that connects families, volunteers, and the community to help locate missing persons.
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
