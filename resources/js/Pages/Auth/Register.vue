<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import { ref, computed } from 'vue'
import ToastContainer from '@/Components/ToastContainer.vue'

const { success, error } = useToast()

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

// Password validation
const passwordRequirements = computed(() => {
  const password = form.password
  return {
    length: password.length >= 8,
    uppercase: /[A-Z]/.test(password),
    lowercase: /[a-z]/.test(password),
    number: /\d/.test(password),
    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
  }
})

const isPasswordValid = computed(() => {
  return Object.values(passwordRequirements.value).every(req => req)
})

const isPasswordConfirmed = computed(() => {
  return form.password && form.password === form.password_confirmation
})

// Phone validation
const isPhoneValid = computed(() => {
  const phone = form.phone.replace(/\s/g, '') // Remove spaces
  return phone.length <= 12 && /^[\d\s+\-()]+$/.test(phone)
})

// Email validation
const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(form.email)
})

// Name validation
const isNameValid = computed(() => {
  return form.name.length >= 2 && /^[a-zA-Z\s]+$/.test(form.name)
})

// Overall form validation
const isFormValid = computed(() => {
  return isNameValid.value && 
         isEmailValid.value && 
         isPhoneValid.value && 
         isPasswordValid.value && 
         isPasswordConfirmed.value
})

function submit() {
  // Client-side validation
  if (!isFormValid.value) {
    error('Please fix all validation errors before submitting.')
    return
  }

  form.post(route('register'), {
    onSuccess: () => { 
      // Toast will show briefly before redirect
      success('Registration successful! Please log in with your new account.')
    },
    onError: (errors) => { 
      if (errors.email) {
        error('Email already exists. Please use a different email.')
      } else if (errors.name) {
        error('Username already exists. Please choose a different name.')
      } else {
        error('Registration failed. Please check all fields.')
      }
    }
  })
}
</script>

<template>
  <div class="relative min-h-screen bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 min-h-screen">

      <!-- 左侧：注册表单 -->
      <div class="relative flex items-center justify-center bg-white">
        <!-- 左上角 LOGIN 胶囊按钮 -->
        <div class="absolute top-6 left-6">
          <Link href="/login"
            class="px-5 py-2 rounded-full border border-gray-300 text-gray-800 hover:bg-gray-900 hover:text-white transition">
          LOGIN
          </Link>
        </div>

        <div class="w-full max-w-md">
          <div
            class="mx-6 md:mx-0 bg-white rounded-2xl border border-gray-100 p-8 md:p-10 shadow-[0_20px_60px_-20px_rgba(0,0,0,0.15)]">
            <h1 class="text-2xl font-bold text-center mb-7">Sign up to FindMe</h1>

            <form @submit.prevent="submit" class="space-y-5">
              <!-- Server-side errors -->
              <div v-if="form.errors.name" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.name }}
              </div>
              <div v-if="form.errors.email" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.email }}
              </div>
              <div v-if="form.errors.phone" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.phone }}
              </div>
              <div v-if="form.errors.password" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.password }}
              </div>
              <!-- Name -->
              <div class="relative">
                <label for="name"
                  class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">Name</label>
                <input id="name" v-model="form.name" type="text" required
                  :class="`w-full h-12 rounded-md border px-4 focus:outline-none focus:border-gray-900 ${
                    form.name ? (isNameValid ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`" />
                <div v-if="form.name && !isNameValid" class="text-red-500 text-xs mt-1">
                  Name must be at least 2 characters and contain only letters and spaces
                </div>
              </div>

              <!-- Email -->
              <div class="relative">
                <label for="email"
                  class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">Email
                  Address</label>
                <input id="email" v-model="form.email" type="email" required
                  :class="`w-full h-12 rounded-md border px-4 focus:outline-none focus:border-gray-900 ${
                    form.email ? (isEmailValid ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`" />
                <div v-if="form.email && !isEmailValid" class="text-red-500 text-xs mt-1">
                  Please enter a valid email address
                </div>
              </div>

              <!-- Password -->
              <div class="relative">
                <label for="password"
                  class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">Password</label>
                <input id="password" v-model="form.password" type="password" required
                  :class="`w-full h-12 rounded-md border px-4 pr-10 focus:outline-none focus:border-gray-900 ${
                    form.password ? (isPasswordValid ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`" />
                <!-- 右侧装饰小眼睛（纯装饰，不影响逻辑） -->
                <svg viewBox="0 0 24 24"
                  class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
                  fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                
                <!-- Password Requirements -->
                <div v-if="form.password" class="mt-2 p-3 bg-gray-50 rounded-md">
                  <div class="text-xs font-medium text-gray-700 mb-2">Password Requirements:</div>
                  <div class="space-y-1">
                    <div :class="`text-xs ${passwordRequirements.length ? 'text-green-600' : 'text-red-500'}`">
                      ✓ At least 8 characters
                    </div>
                    <div :class="`text-xs ${passwordRequirements.uppercase ? 'text-green-600' : 'text-red-500'}`">
                      ✓ One uppercase letter (A-Z)
                    </div>
                    <div :class="`text-xs ${passwordRequirements.lowercase ? 'text-green-600' : 'text-red-500'}`">
                      ✓ One lowercase letter (a-z)
                    </div>
                    <div :class="`text-xs ${passwordRequirements.number ? 'text-green-600' : 'text-red-500'}`">
                      ✓ One number (0-9)
                    </div>
                    <div :class="`text-xs ${passwordRequirements.special ? 'text-green-600' : 'text-red-500'}`">
                      ✓ One special character (!@#$%^&*)
                    </div>
                  </div>
                </div>
              </div>

              <!-- Confirm Password -->
              <div class="relative">
                <label for="password_confirmation"
                  class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">Confirm
                  Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" required
                  :class="`w-full h-12 rounded-md border px-4 pr-10 focus:outline-none focus:border-gray-900 ${
                    form.password_confirmation ? (isPasswordConfirmed ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`" />
                <svg viewBox="0 0 24 24"
                  class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
                  fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div v-if="form.password_confirmation && !isPasswordConfirmed" class="text-red-500 text-xs mt-1">
                  Passwords do not match
                </div>
              </div>

              <!-- Phone -->
              <div class="relative">
                <label for="phone"
                  class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">Phone
                  Number</label>
                <input id="phone" v-model="form.phone" type="text" placeholder="e.g., +1-234-567-8900"
                  :class="`w-full h-12 rounded-md border px-4 focus:outline-none focus:border-gray-900 ${
                    form.phone ? (isPhoneValid ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`" />
                <div v-if="form.phone && !isPhoneValid" class="text-red-500 text-xs mt-1">
                  Phone number must be maximum 12 digits and contain only numbers, spaces, +, -, and ()
                </div>
              </div>

              <!-- Terms （视觉还原，非必填校验） -->
              <label class="mt-1 flex items-center gap-2 text-[12px] text-gray-600">
                <input type="checkbox" class="h-4 w-4 border-gray-300 rounded-sm" />
                <span>I agree to the Terms of Service and Privacy Policy.</span>
              </label>

              <!-- Submit -->
              <button type="submit"
                :disabled="!isFormValid || form.processing"
                :class="`w-full h-12 rounded-md font-semibold tracking-wide transition mt-2 ${
                  isFormValid && !form.processing
                    ? 'bg-black text-white hover:bg-gray-900 active:bg-black/90'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                }`">
                {{ form.processing ? 'Creating Account...' : 'CREATE AN ACCOUNT' }}
              </button>
            </form>

            <!-- 访客登录链接 -->
            <div class="mt-6 text-center">
              <Link href="/" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Continue as Guest
              </Link>
            </div>
          </div>

          <!-- 页脚 -->
          <p class="mt-8 text-center text-[11px] text-gray-400 select-none">
            © 2025 All Rights Reserved. FindMe
          </p>
        </div>
      </div>

      <!-- 右侧：插画与 Logo -->
      <div class="hidden md:flex flex-col justify-between bg-[#6B5B54] p-6">
        <!-- 右上角 Logo（你会放图） -->
        <div class="flex justify-end">
          <img src="../../assets/white.png" alt="FindMe" class="h-8 object-contain select-none" />
        </div>

        <div class="px-8">
          <h2 class="text-white text-3xl md:text-4xl font-extrabold tracking-wide text-center">
            Bring Families Back Together
          </h2>
          <p class="mt-2 text-white/85 text-sm md:text-base text-center">
            Join community alerts, share verified leads, and help find the missing.
          </p>
        </div>

        <div class="flex items-end justify-center">
          <img src="/signup.png" alt="Volunteers" class="w-4/5 max-w-[520px] drop-shadow-sm select-none" />
        </div>
      </div>
    </div>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>
