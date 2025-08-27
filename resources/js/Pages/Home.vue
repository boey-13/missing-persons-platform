<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  recent: { type: Array, default: () => [] }
})

const $page = usePage()
const showStatus = ref(false)

// Watch for status messages and auto-hide after 3 seconds
watch(() => $page.props.status, (newVal) => {
  console.log('Status changed:', newVal) // Debug log
  if (newVal) {
    showStatus.value = true
    setTimeout(() => {
      showStatus.value = false
    }, 3000)
  } else {
    showStatus.value = false
  }
}, { immediate: true })

const currentIndex = ref(0)

// 手动导航
const goToPrevious = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

const goToNext = () => {
  if (currentIndex.value < props.recent.length - 3) {
    currentIndex.value++
  }
}

// 计算显示的案例
const visibleCases = computed(() => {
  if (props.recent.length <= 3) return props.recent
  return props.recent.slice(currentIndex.value, currentIndex.value + 3)
})
</script>

<template>
  <div>
    <!-- Status Messages -->
    <div v-if="showStatus && $page.props.status"
         class="fixed top-0 left-0 w-full flex justify-center z-50 pointer-events-none">
      <div class="mt-6 w-full max-w-md bg-green-100 border border-green-300 text-green-800 rounded-lg shadow px-5 py-3 text-center font-semibold">
        {{ $page.props.status }}
      </div>
    </div>

    <!-- Hero -->
    <section class="relative min-h-[50vh] bg-cover bg-center bg-no-repeat"
      style="background-image: url('/banner.png');">
      <!-- Overlay for better text readability -->
      <div class="absolute inset-0 bg-black/40"></div>
      <div
        class="relative max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-8 items-center min-h-[90vh]">
        <div class="text-white">
          <h1 class="text-3xl md:text-4xl font-extrabold leading-tight">Helping Families Reunite, One Step at a Time.
          </h1>
          <p class="mt-4 text-white/90">Report, search, and share missing persons with the community.</p>
          <div class="mt-6 flex gap-3">
            <Link href="/missing-persons"
              class="px-5 py-2 rounded bg-white text-[#5C4033] font-semibold hover:bg-gray-100 transition-colors">View
            Case</Link>
            <Link :href="route('missing-persons.report')"
              class="px-5 py-2 rounded bg-[#E67E22] text-white font-semibold hover:bg-[#D35400] transition-colors">
            Report Case</Link>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent cases -->
    <section class="relative max-w-7xl mx-auto px-6 py-14">
      <!-- heading -->
      <div class="text-center mb-10">
        <div class="text-xs uppercase tracking-[0.25em] text-gray-500 mb-2">Latest Reports</div>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">Recent Cases</h2>
        <div
          class="mt-3 h-[3px] w-24 mx-auto rounded-full bg-[#5C4033] opacity-70">
        </div>
      </div>

      <div class="relative">
        <!-- 渐隐遮罩 -->
        <div
          class="pointer-events-none absolute left-0 top-0 h-full w-10 bg-gradient-to-r from-white to-transparent z-[5]">
        </div>
        <div
          class="pointer-events-none absolute right-0 top-0 h-full w-10 bg-gradient-to-l from-white to-transparent z-[5]">
        </div>

        <!-- 左箭头 -->
        <button v-if="props.recent.length > 3" @click="goToPrevious"
          class="absolute -left-6 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/85 backdrop-blur border border-gray-200 shadow-lg
             flex items-center justify-center hover:bg-white hover:shadow-xl transition-all disabled:opacity-40 disabled:cursor-not-allowed" :disabled="currentIndex === 0"
          aria-label="Previous">
          <svg class="w-5 h-5 text-gray-800" viewBox="0 0 24 24" fill="none">
            <path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>

        <!-- 右箭头 -->
        <button v-if="props.recent.length > 3" @click="goToNext"
          class="absolute -right-6 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white/85 backdrop-blur border border-gray-200 shadow-lg
             flex items-center justify-center hover:bg-white hover:shadow-xl transition-all disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentIndex >= props.recent.length - 3" aria-label="Next">
          <svg class="w-5 h-5 text-gray-800" viewBox="0 0 24 24" fill="none">
            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>

        <!-- 展示区 -->
        <div class="flex justify-center">
          <div class="flex gap-8 transition-all duration-300 ease-out">
            <div v-for="item in visibleCases" :key="item.id" class="group w-[320px] bg-white rounded-2xl ring-1 ring-gray-200/70 shadow-sm hover:shadow-xl hover:-translate-y-0.5 hover:ring-[#5C4033]/25
                 transition-all duration-200 overflow-hidden">
              <!-- 封面：固定纵横比，防跳动 -->
              <div class="relative aspect-[3/4] bg-gray-50">
                <img
                  :src="(item.photo_paths && item.photo_paths.length) ? ('/storage/' + item.photo_paths[0]) : '/default-avatar.jpg'"
                  alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy" />
                <span v-if="item.age"
                  class="absolute top-3 right-3 text-[11px] px-2 py-0.5 rounded-full bg-white/90 backdrop-blur border border-gray-200 text-gray-800">
                  Age {{ item.age }}
                </span>
              </div>

              <!-- 信息 -->
              <div class="p-4">
                <h3 class="font-semibold text-gray-900 leading-tight line-clamp-1" :title="item.full_name">
                  {{ item.full_name }}
                </h3>

                <div class="mt-1 text-sm text-gray-600 flex items-start gap-2">
                  <svg class="w-4 h-4 text-gray-400 mt-0.5" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"
                      stroke="currentColor" stroke-width="1.5" />
                    <circle cx="12" cy="9" r="2.5" fill="currentColor" class="text-gray-300" />
                  </svg>
                  <span class="line-clamp-1" :title="item.last_seen_location || '-'">
                    {{ item.last_seen_location || '-' }}
                  </span>
                </div>

                <Link :href="route('missing-persons.show', item.id)" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#5C4033] text-white py-2 font-semibold
                     hover:bg-[#4c352b] transition">
                View
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- 空状态 -->
        <div v-if="visibleCases.length === 0" class="text-center py-10 text-gray-500">
          <p class="font-medium">No recent cases available</p>
          <p class="text-sm mt-1">Recent count: {{ props.recent.length }}</p>
        </div>

        <!-- 指示器 -->
        <div v-if="props.recent.length > 3" class="flex justify-center mt-8 gap-2">
          <button v-for="(_, index) in Math.ceil(props.recent.length / 3)" :key="index"
            @click="currentIndex = index * 3" class="h-2 rounded-full transition-all duration-200"
            :class="Math.floor(currentIndex / 3) === index ? 'bg-[#5C4033] w-8' : 'bg-gray-300 w-2 hover:bg-gray-400'"
            :aria-label="`Go to set ${index + 1}`" />
        </div>

        <!-- View more -->
        <div class="mt-10 text-center">
          <Link href="/missing-persons" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#5C4033] text-white font-semibold shadow-sm
               hover:bg-[#4c352b] hover:shadow-md transition">
          View More
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
          </Link>
        </div>
      </div>
    </section>




    <!-- How to report -->
    <section class="bg-[#fbf8f3] py-12">
      <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-2xl md:text-3xl font-extrabold mb-8">How To Report?</h2>
        <div class="grid md:grid-cols-3 gap-8 text-sm">
          <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-4">
              <div class="flex-shrink-0 flex items-center justify-center">
                <img src="../assets/step1.png" alt="Step 1" class="w-20 h-20 object-cover rounded-lg" />
              </div>
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">1</div>
                <h3 class="font-semibold mt-2">Reach out to family and friends</h3>
                <p class="mt-2 text-gray-600">Confirm the situation and gather important information.</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-4">
              <div class="flex-shrink-0 flex items-center justify-center">
                <img src="../assets/step2.png" alt="Step 2" class="w-20 h-20 object-cover rounded-lg" />
              </div>
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">2</div>
                <h3 class="font-semibold mt-2">File a police report</h3>
                <p class="mt-2 text-gray-600">Visit the nearest police station and obtain a report number.</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-4">
              <div class="flex-shrink-0 flex items-center justify-center">
                <img src="../assets/step3.png" alt="Step 3" class="w-20 h-20 object-cover rounded-lg" />
              </div>
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">3</div>
                <h3 class="font-semibold mt-2">Submit on FindMe</h3>
                <p class="mt-2 text-gray-600">Provide details in our platform to expand community reach.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer CTA -->
    <section class="max-w-7xl mx-auto px-6 py-12 grid md:grid-cols-2 gap-8 items-center">
      <img src="/volunteer.png" class="rounded-xl shadow object-cover w-full h-70 md:h-82" />
      <div>
        <h3 class="text-2xl font-extrabold">Join Our Community of Volunteers</h3>
        <p class="mt-3 text-gray-700">Make a meaningful impact by becoming part of our volunteer network.</p>
        <Link href="/volunteer/apply" class="mt-5 inline-block px-5 py-2 rounded bg-[#5C4033] text-white font-semibold">
        Become A Volunteer</Link>
      </div>
    </section>
  </div>
</template>