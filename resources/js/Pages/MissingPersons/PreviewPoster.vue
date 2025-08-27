<script setup>
import { ref } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
defineOptions({ layout: MainLayout })

const props = defineProps({ report: Object })

function photoUrl(filename) {
  return '/storage/' + filename
}

const showShareModal = ref(false)
const copied = ref(false)

const url = window.location.href
const fbShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
const waShareUrl = `https://wa.me/?text=${encodeURIComponent(url)}`
const twShareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=Help%20find%20this%20missing%20person!`
const igGuideUrl = 'https://www.instagram.com/'

function copyLink() {
  navigator.clipboard.writeText(url)
  copied.value = true
  setTimeout(() => (copied.value = false), 1600)
}
</script>

<template>
  <div class="min-h-screen bg-[#f5f3f0] flex flex-col items-center py-8 font-['Poppins'] text-[#333]">
    <!-- Header -->
    <div class="w-full flex items-center justify-between px-6 mb-2">
      <a :href="`/missing-persons/${props.report.id}`" class="text-base text-gray-600 hover:text-black">&lt; BACK</a>
      <button @click="showShareModal = true" class="text-orange-400 hover:text-orange-600 text-2xl">
        <i class="fas fa-share-alt"></i>
      </button>

      <!-- Share Modal -->
      <teleport to="body">
        <div v-if="showShareModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div class="bg-white rounded-xl shadow-xl p-7 w-[95vw] max-w-xs text-center relative">
            <button @click="showShareModal = false" class="absolute top-3 right-4 text-xl text-gray-400 hover:text-black">×</button>
            <h2 class="text-lg font-bold mb-5">Share to Social Media</h2>
            <div class="flex justify-center gap-5 mb-4 text-2xl">
              <a :href="fbShareUrl" target="_blank" title="Facebook"><i class="fab fa-facebook text-[#1877F2]"></i></a>
              <a :href="waShareUrl" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp text-[#25D366]"></i></a>
              <a :href="twShareUrl" target="_blank" title="Twitter"><i class="fab fa-twitter text-[#1DA1F2]"></i></a>
              <a :href="igGuideUrl" target="_blank" title="Instagram"><i class="fab fa-instagram text-[#E4405F]"></i></a>
            </div>
            <div class="mb-2">
              <button @click="copyLink" class="text-sky-700 hover:underline text-sm">Copy Link</button>
            </div>
            <span v-if="copied" class="text-green-600 text-xs">Copied!</span>
          </div>
        </div>
      </teleport>
    </div>

    <!-- Title -->
    <h2 class="text-3xl font-extrabold text-center mb-4 tracking-tight">Preview Poster</h2>

    <!-- Download -->
    <div class="flex justify-center mb-6">
      <a :href="`/missing-persons/${props.report.id}/download-poster`" download
         class="px-7 py-2 rounded bg-sky-500 text-white hover:bg-sky-600 text-lg font-bold shadow transition">
        Download Poster
      </a>
    </div>

    <!-- ==================== Poster（与 PDF 模板一致的视觉） ==================== -->
    <div
      class="bg-white w-[95vw] max-w-[1000px] sm:w-[90vw] md:w-[85vw] lg:w-[900px] xl:w-[1000px] px-8 pt-8 pb-10"
      style="box-sizing:border-box;"
    >
      <!-- URGENT / Case No -->
      <div class="flex justify-between items-center w-full mb-2">
        <span class="bg-[#b12a1a] text-white font-black px-5 py-1 rounded text-xl tracking-widest"
              style="font-family:'Arial Black',Arial,sans-serif;">
          URGENT
        </span>
        <span class="text-[#2a5dab] text-base font-extrabold">Case No: {{ props.report.id }}</span>
      </div>

      <!-- Red Main Title with bottom rule -->
      <h1
        class="text-[42px] md:text-[44px] font-black text-[#b12a1a] text-center tracking-wider mb-4 pb-2 border-b border-[#e8e2dc]"
        style="font-family:'Arial Black',Arial,sans-serif;"
      >
        MISSING PERSON
      </h1>

      <!-- Hero：没有外框，只做居中栈（宽度≈160mm） -->
      <div class="w-full mx-auto text-center">
        <div class="w-full mx-auto max-w-[606px]">
          <!-- Photo box（灰边 + 底条） -->
          <div class="relative mx-auto mb-6 w-[264px] h-[304px] rounded-lg bg-gray-200 border border-gray-400
                      flex items-center justify-center overflow-hidden shadow-inner">
            <template v-if="props.report.photo_paths && props.report.photo_paths.length">
              <img :src="photoUrl(props.report.photo_paths[0])" alt="Photo" class="w-full h-full object-cover" />
            </template>
            <template v-else>
              <i class="fas fa-user text-7xl text-white"></i>
            </template>
            <div class="absolute bottom-0 left-0 right-0 h-[6px] bg-gray-300"></div>
          </div>

          <!-- Name + basic info -->
          <div class="text-[17px] sm:text-[18px] leading-relaxed">
            <div class="text-[18px] sm:text-[19px] font-extrabold mb-2">{{ props.report.full_name }}</div>
            <div><span class="font-extrabold">Gender:</span> {{ props.report.gender }}</div>
            <div><span class="font-extrabold">Age:</span> {{ props.report.age }}</div>
            <div><span class="font-extrabold">Height:</span> {{ props.report.height_cm }} cm</div>
            <div><span class="font-extrabold">Weight:</span> {{ props.report.weight_kg }} kg</div>
          </div>
        </div>
      </div>

      <!-- Last Seen（左红竖线 + 红色标签 + 粗体值） -->
      <div class="w-full max-w-[606px] mx-auto mt-7 mb-6 border-l-4 border-[#b12a1a] pl-4 text-left">
        <div class="mb-1 text-[16px] sm:text-[18px]">
          <span class="font-extrabold text-[#b12a1a]">Last Seen Location:</span>
          <span class="last-seen-value font-extrabold text-[#111] text-[17px] sm:text-[18px]">
            {{ props.report.last_seen_location || '—' }}
          </span>
        </div>
        <div class="text-[16px] sm:text-[18px]">
          <span class="font-extrabold text-[#b12a1a]">Last Seen Date:</span>
          <span class="last-seen-value font-extrabold text-[#111] text-[17px] sm:text-[18px]">
            {{ new Date(props.report.last_seen_date).toLocaleDateString('en-GB', { day:'numeric', month:'long', year:'numeric' }) }}
          </span>
        </div>
      </div>

      <!-- Descriptions（黑色粗体小标题） -->
      <div class="w-full max-w-[606px] mx-auto grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div>
          <div class="font-extrabold text-[#222] mb-1">Physical Description:</div>
          <div class="text-[15px] text-gray-700">{{ props.report.physical_description || '—' }}</div>
        </div>
        <div>
          <div class="font-extrabold text-[#222] mb-1">Clothing Description:</div>
          <div class="text-[15px] text-gray-700">{{ props.report.last_seen_clothing || '—' }}</div>
        </div>
      </div>

      <div class="w-full max-w-[606px] mx-auto mb-6">
        <div class="font-extrabold text-[#222] mb-1">Other Notes:</div>
        <div class="text-[15px] text-gray-700">{{ props.report.additional_notes || '—' }}</div>
      </div>

      <!-- Rule -->
      <hr class="w-full max-w-[606px] mx-auto border-t-2 border-gray-300 my-5" />

      <!-- Contact Section（与 PDF 对齐） -->
      <div class="w-full max-w-[606px] mx-auto text-center">
        <div class="text-[16px] sm:text-[17px] font-extrabold tracking-[2px] mb-2">CONTACT INFORMATION</div>
        <div class="text-[16px] font-semibold mb-4">
          <i class="fas fa-phone-alt mr-2"></i>{{ props.report.contact_number || 'Not Provided' }}
        </div>
        <img src="../../assets/brown.png" alt="FindMe Logo" class="h-5 mx-auto mb-1" />
        <div class="text-[#b12a1a] font-extrabold text-[14px] mb-1">FindMe Platform</div>
        <a href="https://findme.com" target="_blank" class="text-blue-600 underline text-[14px]">
          https://findme.com
        </a>
      </div>

      <!-- Share reminder -->
      <div class="w-full max-w-[606px] mx-auto text-center text-[14px] text-gray-600 font-semibold mt-4">
        Please share this poster to social media and help us find {{ props.report.full_name }}!
      </div>
    </div>
    <!-- ==================== /Poster ==================== -->
  </div>
</template>
