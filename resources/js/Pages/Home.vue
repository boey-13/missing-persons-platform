<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: MainLayout })

const props = defineProps({
  recent: { type: Array, default: () => [] }
})

/** flash -> toast **/
const { success, error } = useToast()
const $page = usePage()
watch(
  () => $page.props.flash,
  (flash) => {
    if (flash?.success) success(flash.success)
    if (flash?.error) error(flash.error)
  },
  { immediate: true }
)

/** pagination state **/
const currentIndex = ref(0)              // 当前页起始下标
const perPage = ref(3)                   // 每页数量：小屏=2，大屏=3

const setPerPage = () => {
  // Tailwind sm 断点：<=640px 视为小屏
  perPage.value = window.matchMedia('(max-width: 640px)').matches ? 2 : 3
  clampIndex()
}

const clampIndex = () => {
  // 防止当前索引超界
  const maxStart = Math.max(0, (props.recent?.length || 0) - perPage.value)
  currentIndex.value = Math.min(currentIndex.value, maxStart)
}

onMounted(() => {
  setPerPage()
  window.addEventListener('resize', setPerPage)
})
onUnmounted(() => {
  window.removeEventListener('resize', setPerPage)
})

// 当数据数量或每页数量变化时，修正 currentIndex
watch(() => props.recent.length, clampIndex)
watch(perPage, clampIndex)

/** 手动导航：按“页”为单位移动 **/
const goToPrevious = () => {
  currentIndex.value = Math.max(0, currentIndex.value - perPage.value)
}
const goToNext = () => {
  const maxStart = Math.max(0, props.recent.length - perPage.value)
  currentIndex.value = Math.min(maxStart, currentIndex.value + perPage.value)
}

/** 本页可见数据 **/
const visibleCases = computed(() =>
  props.recent.slice(currentIndex.value, currentIndex.value + perPage.value)
)
</script>


<template>
  <div class="font-['Poppins'] text-[#2b2b2b]">

    <!-- Hero -->
    <!-- Hero -->
    <section class="relative min-h-[50vh] bg-cover bg-center bg-no-repeat" style="background-image: url('/banner.png');"
      aria-label="FindMe — Help families reunite">
      <!-- Keep original black semi-transparent overlay -->
      <div class="absolute inset-0 bg-black/30"></div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12
           grid grid-cols-1 lg:grid-cols-2 gap-6 items-center
           min-h-[70vh] sm:min-h-[90vh]">
        <div class="text-white">
          <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold leading-tight">
            Helping Families Reunite, One Step at a Time.
          </h1>
          <p class="mt-3 sm:mt-4 text-sm sm:text-base text-white/90">
            Report, search, and share missing persons with the community.
          </p>

          <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <Link href="/missing-persons" class="px-4 sm:px-5 py-2.5 sm:py-2 rounded bg-white text-[#5C4033] font-semibold
                 hover:bg-gray-100 transition-colors text-sm sm:text-base text-center">
            View Case
            </Link>
            <Link :href="route('missing-persons.report')" class="px-4 sm:px-5 py-2.5 sm:py-2 rounded bg-[#E67E22] text-white font-semibold
                 hover:bg-[#D35400] transition-colors text-sm sm:text-base text-center">
            Report Case
            </Link>
          </div>
        </div>
      </div>
    </section>


    <!-- Recent cases -->
    <section class="relative max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-14">
      <!-- heading -->
      <div class="text-center mb-8 sm:mb-10">
        <div class="text-[11px] sm:text-xs uppercase tracking-[0.24em] text-gray-500 mb-2">Latest Reports</div>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900">Recent Cases</h2>
        <div class="mt-3 h-[3px] w-24 mx-auto rounded-full bg-[#5C4033]/80"></div>
      </div>

      <div class="relative">
        <!-- Fade overlay (keep original) -->
        <div
          class="pointer-events-none absolute left-0 top-0 h-full w-8 sm:w-12 bg-gradient-to-r from-white to-transparent z-[5]">
        </div>
        <div
          class="pointer-events-none absolute right-0 top-0 h-full w-8 sm:w-12 bg-gradient-to-l from-white to-transparent z-[5]">
        </div>

        <!-- Left arrow (keep original logic) -->
        <button v-if="props.recent.length > 3" @click="goToPrevious"
          class="absolute -left-4 sm:-left-6 top-1/2 -translate-y-1/2 z-10 w-11 sm:w-12 h-11 sm:h-12 rounded-full bg-white/90 backdrop-blur border border-gray-200 shadow-lg
             grid place-items-center hover:bg-white hover:shadow-xl transition disabled:opacity-40 disabled:cursor-not-allowed" :disabled="currentIndex === 0"
          aria-label="Previous">
          <svg class="w-4 sm:w-5 h-4 sm:h-5 text-gray-800" viewBox="0 0 24 24" fill="none">
            <path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>

        <!-- Right arrow (keep original logic) -->
        <button v-if="props.recent.length > 3" @click="goToNext"
          class="absolute -right-4 sm:-right-6 top-1/2 -translate-y-1/2 z-10 w-11 sm:w-12 h-11 sm:h-12 rounded-full bg-white/90 backdrop-blur border border-gray-200 shadow-lg
             grid place-items-center hover:bg-white hover:shadow-xl transition disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentIndex >= props.recent.length - 3" aria-label="Next">
          <svg class="w-4 sm:w-5 h-4 sm:h-5 text-gray-800" viewBox="0 0 24 24" fill="none">
            <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>

        <!-- Display area: mobile = two columns of small cards; ≥sm restore horizontal layout -->
        <div class="overflow-hidden">
          <div
            class="grid grid-cols-2 gap-3 sm:flex sm:gap-6 lg:gap-8 sm:justify-center transition-all duration-300 ease-out">
            <div v-for="item in visibleCases" :key="item.id"
              class="group w-full sm:w-[300px] md:w-[340px] bg-white rounded-xl ring-1 ring-gray-200/70 shadow-sm
                 hover:shadow-2xl hover:-translate-y-1 hover:ring-[#5C4033]/30 transition-all duration-200 overflow-hidden">
              <!-- Cover: mobile height is shorter -->
              <div class="relative aspect-[4/5] sm:aspect-[3/4] bg-gray-50">
                <img
                  :src="(item.photo_paths && item.photo_paths.length) ? ('/storage/' + item.photo_paths[0]) : '/default-avatar.jpg'"
                  :alt="item.full_name || 'case photo'" class="absolute inset-0 w-full h-full object-cover"
                  loading="lazy" />
                <span v-if="item.age"
                  class="absolute top-2 right-2 text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full bg-white/95 backdrop-blur border border-gray-200 text-gray-800 shadow-sm">
                  Age {{ item.age }}
                </span>
              </div>

              <!-- Info: mobile font/spacing is smaller -->     
              <div class="p-3 sm:p-4">
                <h3 class="font-semibold text-gray-900 leading-tight line-clamp-1 text-[13px] sm:text-base"
                  :title="item.full_name">
                  {{ item.full_name }}
                </h3>

                <div class="mt-1 text-[11px] sm:text-sm text-gray-600 flex items-start gap-2">
                  <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400 mt-0.5 flex-shrink-0" viewBox="0 0 24 24"
                    fill="none">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"
                      stroke="currentColor" stroke-width="1.5" />
                    <circle cx="12" cy="9" r="2.5" class="fill-gray-300" />
                  </svg>
                  <span class="line-clamp-1" :title="item.last_seen_location || '-'">
                    {{ item.last_seen_location || '-' }}
                  </span>
                </div>

                <Link :href="route('missing-persons.show', item.id)" class="mt-3 sm:mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#5C4033] text-white py-2
                     text-[13px] sm:text-base font-semibold hover:bg-[#4c352b] active:scale-[0.99] transition">
                View
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="none">
                  <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state (original) -->
        <div v-if="visibleCases.length === 0" class="text-center py-10 text-gray-500">
          <p class="font-medium text-base">No recent cases available</p>
          <p class="text-sm mt-1">Recent count: {{ props.recent.length }}</p>
        </div>

        <!-- Indicators (original logic) -->
        <div v-if="props.recent.length > 3" class="flex justify-center mt-7 sm:mt-8 gap-2">
          <button v-for="(_, index) in Math.ceil(props.recent.length / 3)" :key="index"
            @click="currentIndex = index * 3" class="h-2.5 rounded-full transition-all duration-200"
            :class="Math.floor(currentIndex / 3) === index ? 'bg-[#5C4033] w-8' : 'bg-gray-300 w-2 hover:bg-gray-400'"
            :aria-label="`Go to set ${index + 1}`" />
        </div>

        <!-- View more (original) -->
        <div class="mt-8 sm:mt-10 text-center">
          <Link href="/missing-persons" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#5C4033] text-white font-semibold shadow-sm text-sm sm:text-base
               hover:bg-[#4c352b] hover:shadow-md active:scale-[0.99] transition">
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
    <section class="bg-[#fbf8f3] py-10 sm:py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold mb-6 sm:mb-8">How To Report?</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 text-sm">
          <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm p-5 hover:shadow-md transition">
            <div class="flex items-start gap-4">
              <img src="../assets/step1.png" alt="Step 1"
                class="w-16 sm:w-20 h-16 sm:h-20 object-cover rounded-xl ring-1 ring-gray-200" />
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">1</div>
                <h3 class="font-semibold mt-1 text-sm sm:text-base">Reach out to family and friends</h3>
                <p class="mt-1.5 text-gray-600 text-xs sm:text-sm">Confirm the situation and gather important
                  information.</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm p-5 hover:shadow-md transition">
            <div class="flex items-start gap-4">
              <img src="../assets/step2.png" alt="Step 2"
                class="w-16 sm:w-20 h-16 sm:h-20 object-cover rounded-xl ring-1 ring-gray-200" />
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">2</div>
                <h3 class="font-semibold mt-1 text-sm sm:text-base">File a police report</h3>
                <p class="mt-1.5 text-gray-600 text-xs sm:text-sm">Visit the nearest police station and obtain a report
                  number.</p>
              </div>
            </div>
          </div>

          <div
            class="bg-white rounded-2xl border border-gray-200/70 shadow-sm p-5 hover:shadow-md transition sm:col-span-2 lg:col-span-1">
            <div class="flex items-start gap-4">
              <img src="../assets/step3.png" alt="Step 3"
                class="w-16 sm:w-20 h-16 sm:h-20 object-cover rounded-xl ring-1 ring-gray-200" />
              <div class="flex-1">
                <div class="text-2xl font-extrabold text-[#5C4033]">3</div>
                <h3 class="font-semibold mt-1 text-sm sm:text-base">Submit on FindMe</h3>
                <p class="mt-1.5 text-gray-600 text-xs sm:text-sm">Provide details in our platform to expand community
                  reach.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer CTA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-14 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
      <div class="order-2 lg:order-1">
        <h3 class="text-xl sm:text-2xl font-extrabold">Join Our Community of Volunteers</h3>
        <p class="mt-2 sm:mt-3 text-gray-700 text-sm sm:text-base">
          Make a meaningful impact by becoming part of our volunteer network.
        </p>
        <Link href="/volunteer/apply"
          class="mt-4 sm:mt-5 inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#5C4033] text-white font-semibold text-sm sm:text-base hover:bg-[#4c352b] active:scale-[0.99] transition">
        Become A Volunteer
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
          <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        </Link>
      </div>
      <div class="order-1 lg:order-2">
        <img src="/volunteer.png" alt="volunteer"
          class="rounded-2xl shadow object-cover w-full h-[220px] sm:h-[280px] lg:h-[360px]" />
      </div>
    </section>
  </div>
</template>

<style scoped>
/* 仅样式增强，不改逻辑 */
@media (max-width: 380px) {
  .xs\:w-\[320px\] {
    width: 320px;
  }
}

/* 更柔和的滚动体验（非强制） */
html:focus-within {
  scroll-behavior: smooth;
}
</style>
