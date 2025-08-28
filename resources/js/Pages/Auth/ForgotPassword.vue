<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { useToast } from '@/Composables/useToast'
import ToastContainer from '@/Components/ToastContainer.vue'

const { success, error } = useToast()

const form = useForm({
  email: '',
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

function submit() {
  if (!form.email) {
    error('Please enter your email address')
    return
  }

  form.post(route('password.email'), {
    onSuccess: () => {
      success('Password reset link sent to your email!')
      form.reset()
    },
    onError: (errors) => {
      if (errors.email) {
        error('Please enter a valid email address')
      } else {
        error('Failed to send reset link. Please try again.')
      }
    }
  })
}
</script>

<template>
  <div class="relative min-h-screen bg-white">
    <!-- 双栏布局 -->
    <div class="grid grid-cols-1 md:grid-cols-2 min-h-screen">
      <!-- 左侧插画区 -->
      <div class="hidden md:flex flex-col justify-between bg-[#B9ACA4] p-6">
        <!-- Logo -->
        <div class="text-white text-3xl font-semibold italic select-none">FindMe</div>

        <div class="px-8">
          <h2 class="text-black text-3xl md:text-4xl font-extrabold tracking-wide text-center">
            Reset Your Password
          </h2>
          <p class="text-black text-xl md:text-l font-bold tracking-wide text-center mt-10">
            Don't worry, we'll help you get back to your account safely.
          </p>
        </div>

        <div class="flex items-end justify-center">
          <img src="/login.png" alt="Illustration"
               class="w-4/5 max-w-[460px] drop-shadow-sm select-none" />
        </div>
      </div>

      <!-- 右侧表单区 -->
      <div class="relative flex items-center justify-center bg-white">
        <!-- 左上角 Back to Login 按钮 -->
        <div class="absolute top-6 left-6">
          <Link href="/login"
                class="px-5 py-2 rounded-full border border-gray-300 text-gray-800 hover:bg-gray-900 hover:text-white transition">
            ← Back to Login
          </Link>
        </div>

        <!-- 表单卡片 -->
        <div class="w-full max-w-md">
          <div class="mx-6 md:mx-0 bg-white rounded-2xl border border-gray-100 p-8 md:p-10
                      shadow-[0_20px_60px_-20px_rgba(0,0,0,0.15)]">
            <h1 class="text-2xl font-bold text-center mb-2">Forgot Password?</h1>
            <p class="text-gray-600 text-center mb-8">
              Enter your email address and we'll send you a link to reset your password.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
              <!-- 服务器端错误 -->
              <div v-if="form.errors.email" class="text-red-500 text-sm bg-red-50 p-3 rounded-md border border-red-200">
                {{ form.errors.email }}
              </div>
              
              <!-- 邮箱输入 -->
              <div class="relative">
                <label for="email"
                       class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">
                  Email Address
                </label>
                <input 
                  id="email" 
                  type="email" 
                  v-model="form.email" 
                  required
                  autocomplete="email"
                  class="w-full h-12 rounded-md border border-gray-300 px-4 focus:outline-none focus:border-gray-900" 
                />
              </div>

              <!-- 提交按钮 -->
              <button 
                type="submit"
                :disabled="form.processing || !form.email"
                :class="`w-full h-12 rounded-md font-semibold tracking-wide transition ${
                  form.processing || !form.email
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    : 'bg-[#704c34] text-white hover:bg-[#5e3f2b] active:bg-[#4a2f1f]'
                }`"
              >
                <span v-if="form.processing" class="inline-flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Sending Reset Link...
                </span>
                <span v-else>Send Reset Link</span>
              </button>
            </form>

            <!-- 返回登录链接 -->
            <div class="mt-8 text-center">
              <Link href="/login" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Remember your password? Sign in
              </Link>
            </div>
          </div>

          <!-- 页脚版权 -->
          <p class="mt-8 text-center text-[11px] text-gray-400 select-none">
            © 2025 All Rights Reserved. FindMe
          </p>
        </div>
      </div>
    </div>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>
