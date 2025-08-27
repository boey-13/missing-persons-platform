<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ToastMessage from '@/Components/ToastMessage.vue'

defineOptions({ layout: MainLayout })

const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: ''
})

const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const submitForm = () => {
  form.post('/contact', {
    onSuccess: (response) => {
      form.reset()
      showToastMessage(response.message || 'Thank you for your message. We will get back to you soon!', 'success')
    },
    onError: (errors) => {
      console.error('Form submission failed:', errors)
      showToastMessage('Failed to send message. Please try again.', 'error')
    }
  })
}

function showToastMessage(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
}
</script>

<template>
  <!-- Toast Message -->
  <ToastMessage v-if="showToast" :message="toastMessage" :type="toastType" />

  <div class="min-h-screen bg-white">
    <!-- Hero Section -->
    <section class="relative min-h-[30vh] bg-cover bg-center bg-no-repeat"
    style="background-image: url('/contact.jpg');">
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative max-w-7xl mx-auto px-6 py-20 text-center">
        <h1 class="text-4xl md:text-6xl text-white/90 font-extrabold mb-6">Contact Us</h1>
        <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto leading-relaxed">
          Get in touch with our team. We're here to help and answer any questions you may have.
        </p>
      </div>
    </section>

    <!-- Contact Information -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Phone -->
          <div class="bg-white rounded-2xl p-8 text-center shadow-lg">
            <div class="w-16 h-16 bg-[#5C4033] rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Phone</h3>
            <p class="text-gray-600 mb-2">Emergency Hotline</p>
            <p class="text-2xl font-bold text-[#5C4033]">011-11223344</p>
            <p class="text-sm text-gray-500 mt-2">24/7 Available</p>
          </div>

          <!-- Email -->
          <div class="bg-white rounded-2xl p-8 text-center shadow-lg">
            <div class="w-16 h-16 bg-[#E67E22] rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Email</h3>
            <p class="text-gray-600 mb-2">General Inquiries</p>
            <p class="text-lg font-semibold text-[#E67E22]">support@findme.com</p>
            <p class="text-sm text-gray-500 mt-2">Response within 24 hours</p>
          </div>

          <!-- Location -->
          <div class="bg-white rounded-2xl p-8 text-center shadow-lg">
            <div class="w-16 h-16 bg-[#27AE60] rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Office</h3>
            <p class="text-gray-600 mb-2">Main Office</p>
            <p class="text-lg font-semibold text-[#27AE60]">Kuala Lumpur, Malaysia</p>
            <p class="text-sm text-gray-500 mt-2">By appointment only</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form & Office Hours -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-12">
          <!-- Contact Form -->
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Send us a Message</h2>
            <form @submit.prevent="submitForm" class="space-y-6">
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
                    placeholder="Enter your full name"
                  />
                  <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                </div>
                
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
                    placeholder="Enter your email"
                  />
                  <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                </div>
              </div>

              <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                <input
                  id="subject"
                  v-model="form.subject"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
                  placeholder="What is this about?"
                />
                <div v-if="form.errors.subject" class="text-red-600 text-sm mt-1">{{ form.errors.subject }}</div>
              </div>

              <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                <textarea
                  id="message"
                  v-model="form.message"
                  rows="6"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5C4033] focus:border-transparent"
                  placeholder="Tell us more about your inquiry..."
                ></textarea>
                <div v-if="form.errors.message" class="text-red-600 text-sm mt-1">{{ form.errors.message }}</div>
              </div>

              <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-[#5C4033] text-white py-3 px-6 rounded-lg font-semibold hover:bg-[#4c352b] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ form.processing ? 'Sending...' : 'Send Message' }}
              </button>
            </form>
          </div>

          <!-- Office Hours & Additional Info -->
          <div class="space-y-8">
            <!-- Office Hours -->
            <div class="bg-gray-50 rounded-2xl p-8">
              <h3 class="text-2xl font-bold text-gray-900 mb-6">Office Hours</h3>
              <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="font-medium text-gray-700">Monday - Friday</span>
                  <span class="text-gray-600">9:00 AM - 6:00 PM</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="font-medium text-gray-700">Saturday</span>
                  <span class="text-gray-600">10:00 AM - 4:00 PM</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                  <span class="font-medium text-gray-700">Sunday</span>
                  <span class="text-gray-600">Closed</span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="font-medium text-gray-700">Public Holidays</span>
                  <span class="text-gray-600">Closed</span>
                </div>
              </div>
            </div>

            <!-- Emergency Information -->
            <div class="bg-red-50 border border-red-200 rounded-2xl p-8">
              <h3 class="text-2xl font-bold text-red-900 mb-4">Emergency Contact</h3>
              <p class="text-red-800 mb-4">
                If you have an urgent missing person case or need immediate assistance:
              </p>
              <div class="space-y-2">
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  <span class="text-red-800 font-semibold">Call: 011-11223344</span>
                </div>
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-red-800">Available 24/7</span>
                </div>
              </div>
            </div>

            <!-- Additional Contact Methods -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-8">
              <h3 class="text-2xl font-bold text-blue-900 mb-4">Other Ways to Reach Us</h3>
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                  </svg>
                  <span class="text-blue-800">Live Chat: Available during office hours</span>
                </div>
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2"/>
                  </svg>
                  <span class="text-blue-800">Support Ticket: Create via email</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-4xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently Asked Questions</h2>
        <div class="space-y-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">How quickly do you respond to missing person reports?</h3>
            <p class="text-gray-600">We prioritize all missing person reports and typically respond within 2-4 hours during business hours. Emergency cases are handled immediately.</p>
          </div>
          
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I report a missing person anonymously?</h3>
            <p class="text-gray-600">Yes, you can submit anonymous reports. However, providing contact information helps us gather additional details that may be crucial to the search.</p>
          </div>
          
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you work with law enforcement?</h3>
            <p class="text-gray-600">Yes, we collaborate closely with local law enforcement agencies and can coordinate search efforts when appropriate.</p>
          </div>
          
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">How can I volunteer to help with search efforts?</h3>
            <p class="text-gray-600">You can apply to become a volunteer through our website. We'll review your application and provide training for search and rescue operations.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
