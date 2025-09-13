<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { useToast } from '@/Composables/useToast'
import ToastContainer from '@/Components/ToastContainer.vue'

const { success, error } = useToast()

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const page = usePage()

// Watch for flash messages from backend
watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    success(flash.success)
  }
  if (flash?.error) {
    error(flash.error)
  }
}, { immediate: true })

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

const isFormValid = computed(() => {
  return isPasswordValid.value && isPasswordConfirmed.value
})

function submit() {
  if (!isFormValid.value) {
    error('Please fix all validation errors before submitting.')
    return
  }

  form.post(route('password.store'), {
    onSuccess: () => {
      success('Password reset successfully! You can now log in with your new password.')
    },
    onError: (errors) => {
      if (errors.token) {
        error('Invalid or expired reset link. Please request a new one.')
      } else if (errors.email) {
        // 检查是否是token错误（Laravel把token错误放在email字段中）
        if (errors.email.includes('token is invalid') || errors.email.includes('invalid')) {
          error('Invalid or expired reset link. Please request a new one.')
        } else {
          error('Invalid email address.')
        }
      } else if (errors.password) {
        error('Please check your password requirements.')
      } else {
        error('Failed to reset password. Please try again.')
      }
    },
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div class="relative min-h-screen bg-white">
    <!-- 双栏布局 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
      <!-- 左侧插画区 -->
      <div class="hidden lg:flex flex-col justify-between bg-[#B9ACA4] p-4 xl:p-6">
        <!-- Logo -->
        <div class="text-white text-2xl xl:text-3xl font-semibold italic select-none">FindMe</div>

        <div class="px-4 xl:px-8">
          <h2 class="text-black text-2xl xl:text-4xl font-extrabold tracking-wide text-center leading-tight">
            Set New Password
          </h2>
          <p class="text-black text-lg xl:text-xl font-bold tracking-wide text-center mt-6 xl:mt-10 leading-relaxed">
            Create a strong password to secure your account.
          </p>
        </div>

        <div class="flex items-end justify-center">
          <img src="/login.png" alt="Illustration"
               class="w-4/5 max-w-[400px] xl:max-w-[460px] drop-shadow-sm select-none" />
        </div>
      </div>


      <div class="relative flex items-center justify-center bg-white px-4 sm:px-6 lg:px-8">

        <div class="absolute top-4 sm:top-6 left-4 sm:left-6">
          <Link href="/login"
                class="px-4 sm:px-5 py-2 text-sm sm:text-base rounded-full border border-gray-300 text-gray-800 hover:bg-gray-900 hover:text-white transition-colors">
            ← Back to Login
          </Link>
        </div>

        <!-- form card -->
        <div class="w-full max-w-sm sm:max-w-md">
          <div class="mx-auto bg-white rounded-2xl border border-gray-100 p-6 sm:p-8 lg:p-10
                      shadow-[0_20px_60px_-20px_rgba(0,0,0,0.15)]">
            <h1 class="text-xl sm:text-2xl font-bold text-center mb-2">Reset Password</h1>
            <p class="text-sm sm:text-base text-gray-600 text-center mb-6 sm:mb-8">
              Enter your new password below.
            </p>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
              <!-- server side errors -->
              <div v-if="form.errors.token" class="text-red-500 text-sm bg-red-50 p-3 rounded-md border border-red-200">
                {{ form.errors.token }}
              </div>
              <div v-if="form.errors.email" class="text-red-500 text-sm bg-red-50 p-3 rounded-md border border-red-200">
                {{ form.errors.email }}
              </div>
              <div v-if="form.errors.password" class="text-red-500 text-sm bg-red-50 p-3 rounded-md border border-red-200">
                {{ form.errors.password }}
              </div>
              <div v-if="form.errors.password_confirmation" class="text-red-500 text-sm bg-red-50 p-3 rounded-md border border-red-200">
                {{ form.errors.password_confirmation }}
              </div>
              
              <!-- email display (readonly) -->
              <div class="relative">
                <label class="absolute -top-2 left-3 bg-white px-1 text-[10px] sm:text-[11px] tracking-widest uppercase text-gray-500">
                  Email Address
                </label>
                <input 
                  type="email" 
                  :value="form.email"
                  readonly
                  class="w-full h-11 sm:h-12 rounded-md border border-gray-300 px-3 sm:px-4 bg-gray-50 text-gray-600 text-sm sm:text-base" 
                />
              </div>

              <!-- new password -->
              <div class="relative">
                <label for="password"
                       class="absolute -top-2 left-3 bg-white px-1 text-[10px] sm:text-[11px] tracking-widest uppercase text-gray-500">
                  New Password
                </label>
                <input 
                  id="password"
                  type="password" 
                  v-model="form.password" 
                  required
                  autocomplete="new-password"
                  :class="`w-full h-11 sm:h-12 rounded-md border px-3 sm:px-4 pr-10 text-sm sm:text-base focus:outline-none focus:border-gray-900 transition-colors ${
                    form.password ? (isPasswordValid ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`"
                />
                <svg viewBox="0 0 24 24"
                     class="absolute right-3 top-1/2 -translate-y-1/2 w-4 sm:w-5 h-4 sm:h-5 text-gray-400 pointer-events-none"
                     fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </div>

              <!-- password requirements -->
              <div v-if="form.password" class="bg-gray-50 p-3 sm:p-4 rounded-md">
                <div class="text-sm font-medium text-gray-700 mb-2">Password Requirements:</div>
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

              <!-- confirm password -->
              <div class="relative">
                <label for="password_confirmation"
                       class="absolute -top-2 left-3 bg-white px-1 text-[10px] sm:text-[11px] tracking-widest uppercase text-gray-500">
                  Confirm New Password
                </label>
                <input 
                  id="password_confirmation"
                  type="password" 
                  v-model="form.password_confirmation" 
                  required
                  autocomplete="new-password"
                  :class="`w-full h-11 sm:h-12 rounded-md border px-3 sm:px-4 pr-10 text-sm sm:text-base focus:outline-none focus:border-gray-900 transition-colors ${
                    form.password_confirmation ? (isPasswordConfirmed ? 'border-green-500' : 'border-red-500') : 'border-gray-300'
                  }`"
                />
                <svg viewBox="0 0 24 24"
                     class="absolute right-3 top-1/2 -translate-y-1/2 w-4 sm:w-5 h-4 sm:h-5 text-gray-400 pointer-events-none"
                     fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div v-if="form.password_confirmation && !isPasswordConfirmed" class="text-red-500 text-xs mt-1">
                  Passwords do not match
                </div>
              </div>

              <!-- submit button -->
              <button 
                type="submit"
                :disabled="form.processing || !isFormValid"
                :class="`w-full h-11 sm:h-12 rounded-md font-semibold tracking-wide transition text-sm sm:text-base ${
                  form.processing || !isFormValid
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    : 'bg-[#704c34] text-white hover:bg-[#5e3f2b] active:bg-[#4a2f1f]'
                }`"
              >
                <span v-if="form.processing" class="inline-flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-4 sm:h-5 w-4 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Resetting Password...
                </span>
                <span v-else>Reset Password</span>
              </button>
            </form>

            <!-- back to login link -->
            <div class="mt-6 sm:mt-8 text-center">
              <Link href="/login" class="text-xs sm:text-sm text-gray-500 hover:text-gray-700 underline transition-colors">
                Remember your password? Sign in
              </Link>
            </div>
          </div>

          <!-- footer copyright -->
          <p class="mt-6 sm:mt-8 text-center text-[10px] sm:text-[11px] text-gray-400 select-none">
            © 2025 All Rights Reserved. FindMe
          </p>
        </div>
      </div>
    </div>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>
