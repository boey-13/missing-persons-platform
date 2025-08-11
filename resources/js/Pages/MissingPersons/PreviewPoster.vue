<script setup>
import { ref } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
defineOptions({ layout: MainLayout })

// Props: missing person detail data
const props = defineProps({ report: Object })

// Helper to get photo file url
function photoUrl(filename) {
    return '/storage/' + filename
}

const showShareModal = ref(false)
const copied = ref(false)

const url = window.location.href
const fbShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
const waShareUrl = `https://wa.me/?text=${encodeURIComponent(url)}`
const twShareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=Help%20find%20this%20missing%20person!`
const igGuideUrl = 'https://www.instagram.com/' // IG 

function copyLink() {
  navigator.clipboard.writeText(url)
  copied.value = true
  setTimeout(() => copied.value = false, 1600)
}

</script>

<template>
    <div class="min-h-screen bg-[#f5f3f0] flex flex-col items-center py-8 font-['Poppins'] text-[#333]">
        <!-- Page header area-->
        <div class="w-full flex items-center justify-between px-6 mb-2">
            <a :href="`/missing-persons/${props.report.id}`" class="text-base text-gray-600 hover:text-black">&lt;
                BACK</a>
            <button @click="showShareModal = true" class="text-orange-400 hover:text-orange-600 text-2xl">
                <i class="fas fa-share-alt"></i>
            </button>

            <teleport to="body">
                <div v-if="showShareModal"
                    class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-xl p-7 w-[95vw] max-w-xs text-center relative">
                        <button @click="showShareModal = false"
                            class="absolute top-3 right-4 text-xl text-gray-400 hover:text-black">×</button>
                        <h2 class="text-lg font-bold mb-5">Share to Social Media</h2>
                        <div class="flex justify-center gap-5 mb-4 text-2xl">
                            <a :href="fbShareUrl" target="_blank" title="Facebook"><i
                                    class="fab fa-facebook text-[#1877F2]"></i></a>
                            <a :href="waShareUrl" target="_blank" title="WhatsApp"><i
                                    class="fab fa-whatsapp text-[#25D366]"></i></a>
                            <a :href="twShareUrl" target="_blank" title="Twitter"><i
                                    class="fab fa-twitter text-[#1DA1F2]"></i></a>
                            <a :href="igGuideUrl" target="_blank" title="Instagram"><i
                                    class="fab fa-instagram text-[#E4405F]"></i></a>
                        </div>
                        <div class="mb-2">
                            <button @click="copyLink" class="text-sky-700 hover:underline text-sm">Copy Link</button>
                        </div>
                        <span v-if="copied" class="text-green-600 text-xs">Copied!</span>
                    </div>
                </div>
            </teleport>
        </div>
        <!-- Page title -->
        <h2 class="text-3xl font-extrabold text-center mb-4 tracking-tight">Preview Poster</h2>
        <!-- Download button -->
        <div class="flex justify-center mb-6">
            <a :href="`/missing-persons/${props.report.id}/download-poster`" download
                class="px-7 py-2 rounded bg-sky-500 text-white hover:bg-sky-600 text-lg font-bold shadow transition">
                Download Poster
            </a>


        </div>
        <!-- Responsive Poster Card -->
        <div class="bg-white rounded-none shadow-xl border-2 border-[#ebebeb] flex flex-col items-center relative
                w-[95vw] max-w-[1000px] min-w-[320px] sm:w-[90vw] md:w-[85vw] lg:w-[900px] xl:w-[1000px] py-14 px-8"
            style="box-sizing:border-box;">
            <!-- Top: urgent flag + case number -->
            <div class="flex justify-between items-center w-full mb-3">
                <span class="bg-[#b12a1a] text-white font-black px-5 py-1 rounded text-xl tracking-widest shadow"
                    style="font-family:'Arial Black',Arial,sans-serif;">URGENT</span>
                <span class="text-gray-500 text-base font-bold">Case No: {{ props.report.id }}</span>
            </div>
            <!-- Big red title -->
            <h1 class="text-[56px] font-black text-[#b12a1a] text-center leading-[62px] tracking-wider mb-9"
                style="font-family:'Arial Black',Arial,sans-serif;">
                MISSING PERSON
            </h1>
            <!-- Section 1: Basic info (photo centered and larger) -->
            <div
                class="w-full border-2 border-[#e6d8d8] rounded-xl bg-[#fff8f5] flex flex-col items-center gap-4 md:gap-6 mb-7 py-6 px-6 shadow">
                <!-- Photo -->
                <div
                    class="w-[280px] h-[360px] md:w-[320px] md:h-[420px] rounded-lg bg-gray-200 border-2 border-gray-400 flex items-center justify-center overflow-hidden shadow-inner">
                    <template v-if="props.report.photo_paths && props.report.photo_paths.length">
                        <img :src="photoUrl(props.report.photo_paths[0])" alt="Photo"
                            class="w-full h-full object-cover object-center" />
                    </template>
                    <template v-else>
                        <i class="fas fa-user text-8xl text-white"></i>
                    </template>
                </div>
                <!-- Big centered name -->
                <div class="text-3xl md:text-4xl font-extrabold tracking-wide text-center">{{ props.report.full_name }}</div>

                <!-- Basic info -->
                <div class="w-full max-w-[680px] text-center leading-snug text-[23px] font-sans">
                    <div class="mb-1.5"><span class="font-bold text-gray-800">Gender: </span>
                        <span class="font-medium">{{ props.report.gender }}</span>
                    </div>
                    <div class="mb-1.5"><span class="font-bold text-gray-800">Age: </span>
                        <span class="font-medium">{{ props.report.age }}</span>
                    </div>
                    <div class="mb-1.5"><span class="font-bold text-gray-800">Height: </span>
                        <span class="font-medium">{{ props.report.height_cm }} cm</span>
                    </div>
                    <div class="mb-1.5"><span class="font-bold text-gray-800">Weight: </span>
                        <span class="font-medium">{{ props.report.weight_kg }} kg</span>
                    </div>

                </div>
            </div>
            <!-- Last seen -->
            <div class="w-full border-l-4 border-[#b12a1a] pl-4 mb-7">
                <div class="mb-2 text-[22px]"><span class="font-bold text-[#b12a1a]">Last Seen Location:</span>
                    <span class="font-semibold text-gray-900 text-[24px] md:text-[26px]">{{ props.report.last_seen_location }}</span>
                </div>
                <div class="mb-1 text-[22px]"><span class="font-bold text-[#b12a1a]">Last Seen Date:</span>
                    <span class="font-semibold text-gray-900 text-[24px] md:text-[26px]">
                        {{ new Date(props.report.last_seen_date).toLocaleDateString('en-GB', {
                            day: 'numeric', month: 'long', year: 'numeric'
                        }) }}
                    </span>
                </div>
            </div>
            <!-- Description -->
            <div class="w-full grid grid-cols-2 gap-8 mb-7">
                <div>
                    <div class="font-bold text-gray-800 mb-1 text-lg">Physical Description:</div>
                    <div class="text-base text-gray-700 font-medium">{{ props.report.physical_description || '—' }}
                    </div>
                </div>
                <div>
                    <div class="font-bold text-gray-800 mb-1 text-lg">Clothing Description:</div>
                    <div class="text-base text-gray-700 font-medium">{{ props.report.last_seen_clothing || '—' }}</div>
                </div>
            </div>
            <div class="w-full mb-7">
                <div class="font-bold text-gray-800 mb-1 text-lg">Other Notes:</div>
                <div class="text-base text-gray-700 font-medium">{{ props.report.additional_notes || '—' }}</div>
            </div>
            <hr class="w-full border-gray-400 mb-6" />
            <!-- Contact -->
            <div class="w-full flex flex-col items-center justify-center mt-2 mb-6">
                <div class="font-black text-[26px] tracking-wider mb-2" style="letter-spacing:2.5px;">
                    CONTACT INFORMATION
                </div>
                <div class="text-[22px] font-medium tracking-wider mb-4">
                    <i class="fas fa-phone-alt mr-2"></i>{{ props.report.contact_number || 'Not Provided' }}
                </div>
                <!-- FindMe Logo-->
                <img src="/images/findme_logo.png" alt="FindMe Logo" class="h-10 mb-1" />
                <div class="text-[#b12a1a] font-extrabold text-lg mb-1">FindMe Platform</div>
                <a href="https://findme.com" target="_blank" class="text-blue-600 underline text-base mb-4">
                    https://findme.com
                </a>
            </div>

            <!-- share -->
            <div class="w-full text-center text-[16px] text-gray-600 font-semibold tracking-wide mt-2">
                Please share this poster to social media and help us find {{ props.report.full_name }}!
            </div>
        </div>
    </div>
</template>
