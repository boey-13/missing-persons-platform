<template>
  <footer class="bg-[#f8ecd8] border-t border-[#f1e7d9] py-8 text-[#4c3925] mt-8">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- About -->
      <div>
        <div class="font-bold mb-2">About FindMe</div>
        <div class="text-sm">FindMe is a secure platform that connects families, volunteers, and the community to help locate missing persons.</div>
      </div>
      <!-- FindMe -->
      <div>
        <div class="font-bold mb-2">FindMe</div>
        <div class="text-sm flex flex-col gap-1">
          <a href="/about">About</a>
          <a href="/cases">View Case</a>
          <a href="/missing-persons/report">Report Case</a>
          <a href="/volunteer/projects">Volunteer</a>
        </div>
      </div>
      <!-- Help -->
      <div>
        <div class="font-bold mb-2">Help</div>
        <div class="text-sm flex flex-col gap-1">
          <a href="/contact">Contact Us</a>
          <a href="#">Terms & Conditions</a>
          <a href="#">Privacy Policy</a>
        </div>
      </div>
      <!-- Social Media -->
      <div>
        <div class="font-bold mb-2">Social Media</div>
        <div class="flex gap-4 mt-2">
          <a href="#"><i class="fa-brands fa-twitter fa-xl"></i></a>
          <a href="#"><i class="fa-brands fa-facebook fa-xl"></i></a>
          <a href="#"><i class="fa-brands fa-instagram fa-xl"></i></a>
        </div>
      </div>
    </div>
  </footer>
</template>
