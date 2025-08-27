
<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Hero Section -->
    <section class="relative min-h-[30vh] bg-cover bg-center bg-no-repeat"
    style="background-image: url('/about.jpeg');">
             <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative max-w-7xl mx-auto px-6 py-20 text-center">
        <h1 class="text-4xl md:text-6xl text-white/90 font-extrabold mb-6">About FindMe</h1>
        <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
          Connecting communities, reuniting families, and making a difference in the lives of missing persons and their loved ones.
        </p>
      </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Our Mission</h2>
            <p class="text-lg text-gray-700 leading-relaxed mb-6">
              To provide a comprehensive platform that empowers communities to actively participate in finding missing persons, 
              offering hope and support to families during their most difficult times.
            </p>
            <p class="text-lg text-gray-700 leading-relaxed">
              We believe that every person deserves to be found, and every family deserves closure. Through technology, 
              community engagement, and dedicated volunteers, we're making this vision a reality.
            </p>
          </div>
          <div class="bg-white rounded-2xl p-8 shadow-lg">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Vision</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              A world where no missing person goes unnoticed, where communities are actively engaged in search efforts, 
              and where families can find hope and support in their darkest hours.
            </p>
            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-[#5C4033] rounded-full"></div>
                <span class="text-gray-700">Community-driven search efforts</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-[#5C4033] rounded-full"></div>
                <span class="text-gray-700">Advanced technology integration</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-[#5C4033] rounded-full"></div>
                <span class="text-gray-700">Global collaboration network</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- What We Do -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">What We Do</h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Our comprehensive platform provides multiple ways for communities to get involved in finding missing persons.
          </p>
        </div>
        
                 <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
           <div class="text-center p-6">
             <div class="w-16 h-16 bg-[#5C4033] rounded-full flex items-center justify-center mx-auto mb-4">
               <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>
             </div>
             <h3 class="text-xl font-semibold text-gray-900 mb-3">Missing Person Reports</h3>
             <p class="text-gray-600">
               Enable families and authorities to quickly report missing persons with detailed information, 
               photos, and last known locations.
             </p>
           </div>
           
           <div class="text-center p-6">
             <div class="w-16 h-16 bg-[#E67E22] rounded-full flex items-center justify-center mx-auto mb-4">
               <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
               </svg>
             </div>
             <h3 class="text-xl font-semibold text-gray-900 mb-3">Sighting Reports</h3>
             <p class="text-gray-600">
               Allow community members to report potential sightings, helping to track the movements 
               and whereabouts of missing persons.
             </p>
           </div>
           
           <div class="text-center p-6">
             <div class="w-16 h-16 bg-[#27AE60] rounded-full flex items-center justify-center mx-auto mb-4">
               <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
               </svg>
             </div>
             <h3 class="text-xl font-semibold text-gray-900 mb-3">Community Projects</h3>
             <p class="text-gray-600">
               Organize volunteer-led search efforts, awareness campaigns, and community outreach 
               programs to maximize search effectiveness.
             </p>
           </div>

           <div class="text-center p-6">
             <div class="w-16 h-16 bg-[#8E44AD] rounded-full flex items-center justify-center mx-auto mb-4">
               <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
               </svg>
             </div>
             <h3 class="text-xl font-semibold text-gray-900 mb-3">Rewards System</h3>
             <p class="text-gray-600">
               Earn points through community participation and redeem them for rewards, 
               creating a sustainable ecosystem of engagement and support.
             </p>
           </div>
         </div>
      </div>
    </section>

    <!-- Our Values -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Our Values</h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            The principles that guide everything we do and every decision we make.
          </p>
        </div>
        
                 <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-8">
           <div class="text-center">
             <div class="w-12 h-12 bg-[#5C4033] rounded-lg flex items-center justify-center mx-auto mb-4">
               <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
               </svg>
             </div>
             <h3 class="text-lg font-semibold text-gray-900 mb-2">Compassion</h3>
             <p class="text-gray-600 text-sm">
               We approach every case with empathy and understanding for the families involved.
             </p>
           </div>
           
           <div class="text-center">
             <div class="w-12 h-12 bg-[#E67E22] rounded-lg flex items-center justify-center mx-auto mb-4">
               <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>
             </div>
             <h3 class="text-lg font-semibold text-gray-900 mb-2">Reliability</h3>
             <p class="text-gray-600 text-sm">
               Families can trust us to provide accurate, up-to-date information and support.
             </p>
           </div>
           
           <div class="text-center">
             <div class="w-12 h-12 bg-[#27AE60] rounded-lg flex items-center justify-center mx-auto mb-4">
               <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
               </svg>
             </div>
             <h3 class="text-lg font-semibold text-gray-900 mb-2">Community</h3>
             <p class="text-gray-600 text-sm">
               We believe in the power of people working together for a common cause.
             </p>
           </div>
           
           <div class="text-center">
             <div class="w-12 h-12 bg-[#8E44AD] rounded-lg flex items-center justify-center mx-auto mb-4">
               <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
               </svg>
             </div>
             <h3 class="text-lg font-semibold text-gray-900 mb-2">Innovation</h3>
             <p class="text-gray-600 text-sm">
               We continuously improve our platform with the latest technology and best practices.
             </p>
           </div>

           <div class="text-center">
             <div class="w-12 h-12 bg-[#F39C12] rounded-lg flex items-center justify-center mx-auto mb-4">
               <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
               </svg>
             </div>
             <h3 class="text-lg font-semibold text-gray-900 mb-2">Recognition</h3>
             <p class="text-gray-600 text-sm">
               We recognize and reward community contributions through our points-based system.
             </p>
           </div>
         </div>
      </div>
    </section>

    <!-- Get Involved -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Get Involved</h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
          Join our community of volunteers and make a real difference in the lives of missing persons and their families.
        </p>
                 <div class="flex flex-col sm:flex-row gap-4 justify-center">
           <Link 
             href="/volunteer/apply"
             class="inline-flex items-center justify-center px-8 py-3 bg-[#5C4033] text-white font-semibold rounded-lg hover:bg-[#4c352b] transition-colors"
           >
             Become a Volunteer
           </Link>
           <Link 
             href="/missing-persons/report"
             class="inline-flex items-center justify-center px-8 py-3 border-2 border-[#5C4033] text-[#5C4033] font-semibold rounded-lg hover:bg-[#5C4033] hover:text-white transition-colors"
           >
             Report Missing Person
           </Link>
           <Link 
             href="/rewards"
             class="inline-flex items-center justify-center px-8 py-3 border-2 border-[#F39C12] text-[#F39C12] font-semibold rounded-lg hover:bg-[#F39C12] hover:text-white transition-colors"
           >
             View Rewards
           </Link>
         </div>
      </div>
    </section>
  </div>
</template>
