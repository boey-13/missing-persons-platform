<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })

const props = defineProps({
  recent: { type: Array, default: () => [] }
})
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="relative bg-cover bg-center bg-no-repeat" style="background-image: url('/hero.jpg');">
      <!-- Overlay for better text readability -->
      <div class="absolute inset-0 bg-black/40"></div>
      
      <div class="relative max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <div class="text-white">
          <h1 class="text-3xl md:text-4xl font-extrabold leading-tight">Helping Families Reunite, One Step at a Time.</h1>
          <p class="mt-4 text-white/90">Report, search, and share missing persons with the community.</p>
          <div class="mt-6 flex gap-3">
            <Link href="/missing-persons" class="px-5 py-2 rounded bg-white text-[#5C4033] font-semibold hover:bg-gray-100 transition-colors">View Case</Link>
            <Link :href="route('missing-persons.report')" class="px-5 py-2 rounded bg-[#E67E22] text-white font-semibold hover:bg-[#D35400] transition-colors">Report Case</Link>
          </div>
        </div>
        <div class="aspect-video rounded-xl overflow-hidden bg-white/10 backdrop-blur-sm min-h-[220px] flex items-center justify-center">
          <div class="text-white text-center">
            <div class="text-4xl mb-2">🤝</div>
            <div class="text-lg font-semibold">Community Support</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent cases (horizontal scroll) -->
    <section class="max-w-7xl mx-auto px-6 py-10">
      <h2 class="text-2xl md:text-3xl font-extrabold mb-6">Recently Case</h2>
      <div class="overflow-x-auto pb-2">
        <div class="flex gap-5 min-w-full">
          <div v-for="item in props.recent" :key="item.id" class="min-w-[260px] bg-white rounded-xl shadow p-4">
            <img :src="(item.photo_paths && item.photo_paths.length) ? ('/storage/'+item.photo_paths[0]) : '/placeholder.png'" class="w-full h-40 object-cover rounded"/>
            <div class="mt-3 text-sm">
              <div class="font-semibold">{{ item.full_name }}</div>
              <div class="text-gray-600">Age: {{ item.age ?? '-' }}</div>
              <div class="text-gray-600">Last seen: {{ item.last_seen_location || '-' }}</div>
            </div>
            <Link :href="route('missing-persons.show', item.id)" class="mt-3 inline-block text-[#5C4033] font-semibold">View</Link>
          </div>
        </div>
      </div>
      <div class="mt-4">
        <Link href="/missing-persons" class="px-4 py-2 rounded bg-[#5C4033] text-white">View More</Link>
      </div>
    </section>

    <!-- How to report -->
    <section class="bg-[#fbf8f3] py-12">
      <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-2xl md:text-3xl font-extrabold mb-8">How To Report?</h2>
        <div class="grid md:grid-cols-3 gap-8 text-sm">
          <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl font-extrabold text-[#5C4033]">1</div>
            <h3 class="font-semibold mt-2">Reach out to family and friends</h3>
            <p class="mt-2 text-gray-600">Confirm the situation and gather important information.</p>
          </div>
          <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl font-extrabold text-[#5C4033]">2</div>
            <h3 class="font-semibold mt-2">File a police report</h3>
            <p class="mt-2 text-gray-600">Visit the nearest police station and obtain a report number.</p>
          </div>
          <div class="bg-white rounded-xl shadow p-5">
            <div class="text-2xl font-extrabold text-[#5C4033]">3</div>
            <h3 class="font-semibold mt-2">Submit on FindMe</h3>
            <p class="mt-2 text-gray-600">Provide details in our platform to expand community reach.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer CTA -->
    <section class="max-w-7xl mx-auto px-6 py-12 grid md:grid-cols-2 gap-8 items-center">
      <img src="/volunteer.jpg" class="rounded-xl shadow object-cover w-full h-60 md:h-72"/>
      <div>
        <h3 class="text-2xl font-extrabold">Join Our Community of Volunteers</h3>
        <p class="mt-3 text-gray-700">Make a meaningful impact by becoming part of our volunteer network.</p>
        <Link href="/volunteer/apply" class="mt-5 inline-block px-5 py-2 rounded bg-[#5C4033] text-white font-semibold">Become A Volunteer</Link>
      </div>
    </section>
  </div>
</template>


