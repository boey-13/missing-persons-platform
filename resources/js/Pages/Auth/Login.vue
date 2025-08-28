<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { useToast } from '@/Composables/useToast'
import ToastContainer from '@/Components/ToastContainer.vue'

const { success, error } = useToast()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login'), {
    onError: (errors) => {
      if (errors.account_locked) {
        showAccountLockedModal.value = true
      } else if (errors.email || errors.password) {
        showLoginErrorModal.value = true
      } else {
        error('Login failed. Please check your credentials.')
      }
    }
  })
}

const showForgotPasswordModal = ref(false)
const showLoginErrorModal = ref(false)
const showAccountLockedModal = ref(false)
const isResettingPassword = ref(false)

const resetForm = useForm({
  email: '',
})

function submitForgotPassword() {
  if (!resetForm.email) {
    error('Please enter your email address')
    return
  }

  isResettingPassword.value = true
  
  resetForm.post(route('password.email'), {
    onSuccess: () => {
      success('Password reset link sent to your email!')
      showForgotPasswordModal.value = false
      resetForm.reset()
    },
    onError: (errors) => {
      if (errors.email) {
        error('Please enter a valid email address')
      } else {
        error('Failed to send reset link. Please try again.')
      }
    },
    onFinish: () => {
      isResettingPassword.value = false
    }
  })
}

function tryAgain() {
  showLoginErrorModal.value = false
  form.password = '' // Clear password field
}

function goToRegister() {
  showLoginErrorModal.value = false
  window.location.href = '/register'
}

function resetPasswordFromLocked() {
  showAccountLockedModal.value = false
  showForgotPasswordModal.value = true
}

function closeLockedModal() {
  showAccountLockedModal.value = false
}

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
</script>

<template>
  <div class="relative min-h-screen bg-white">

    <!-- 双栏布局 -->
    <div class="grid grid-cols-1 md:grid-cols-2 min-h-screen">
      <!-- 左侧插画区 -->
      <div class="hidden md:flex flex-col justify-between bg-[#B9ACA4] p-6">
        <!-- Logo（可以换成你的图） -->
        <div class="text-white text-3xl font-semibold italic select-none">FindMe</div>

        <div class="px-8">
          <h2 class="text-black text-3xl md:text-4xl font-extrabold tracking-wide text-center">
            Your share could save a life
          </h2>
          <p class="text-black text-xl md:text-l font-bold tracking-wide text-center mt-10">Join community alerts and share tips securely.</p>
        </div>

        <div class="flex items-end justify-center">
          <img src="/login.png" alt="Illustration"
               class="w-4/5 max-w-[460px] drop-shadow-sm select-none" />
        </div>
      </div>

      <!-- 右侧登录区 -->
      <div class="relative flex items-center justify-center bg-white">
        <!-- 右上角 Sign Up 胶囊按钮 -->
        <div class="absolute top-6 right-6">
          <Link href="/register"
                class="px-5 py-2 rounded-full border border-gray-300 text-gray-800 hover:bg-gray-900 hover:text-white transition">
            SIGN UP
          </Link>
        </div>

        <!-- 登录卡片 -->
        <div class="w-full max-w-md">
          <div class="mx-6 md:mx-0 bg-white rounded-2xl border border-gray-100 p-8 md:p-10
                      shadow-[0_20px_60px_-20px_rgba(0,0,0,0.15)]">
            <h1 class="text-2xl font-bold text-center mb-7">Log In to FindMe</h1>

            <form @submit.prevent="submit" class="space-y-5">
              <!-- 服务器端错误 -->
              <div v-if="form.errors.account_locked" class="text-orange-600 text-sm bg-orange-50 p-3 rounded-md border border-orange-200">
                {{ form.errors.account_locked }}
              </div>
              <div v-if="form.errors.email" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.email }}
              </div>
              <div v-if="form.errors.password" class="text-red-500 text-sm bg-red-50 p-3 rounded-md">
                {{ form.errors.password }}
              </div>
              
              <!-- 邮箱 -->
              <div class="relative">
                <label for="email"
                       class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">
                  Email Address
                </label>
                <input id="email" type="email" v-model="form.email" required
                       class="w-full h-12 rounded-md border border-gray-300 px-4 focus:outline-none focus:border-gray-900" />
              </div>

              <!-- 密码（右侧放个静态眼睛图标以还原视觉） -->
              <div class="relative">
                <label for="password"
                       class="absolute -top-2 left-3 bg-white px-1 text-[11px] tracking-widest uppercase text-gray-500">
                  Password
                </label>
                <input id="password" type="password" v-model="form.password" required
                       class="w-full h-12 rounded-md border border-gray-300 px-4 pr-10 focus:outline-none focus:border-gray-900" />
                <!-- 眼睛图标（装饰，不改逻辑） -->
                <svg viewBox="0 0 24 24"
                     class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
                     fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </div>

              <!-- 记住我 / 忘记密码 -->
              <div class="flex items-center justify-between text-[12px] text-gray-600">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" v-model="form.remember"
                         class="h-4 w-4 border-gray-300 rounded-sm" />
                  <span>Remember Me</span>
                </label>

                <a href="#" @click.prevent="showForgotPasswordModal = true"
                   class="hover:underline">Forgot Password?</a>
              </div>

              <!-- 提交按钮 -->
              <button type="submit"
                      class="w-full h-12 rounded-md bg-black text-white font-semibold tracking-wide hover:bg-gray-900 active:bg-black/90 transition">
                PROCEED
              </button>
            </form>

            <!-- 访客登录链接 -->
            <div class="mt-6 text-center">
              <Link href="/" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Continue as Guest
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

    <!-- 找回密码弹层 -->
    <div v-if="showForgotPasswordModal"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md text-center relative">
        <!-- 关闭按钮 -->
        <button @click="showForgotPasswordModal = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- 图标 -->
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
          </svg>
        </div>

        <!-- 标题和描述 -->
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Reset Your Password</h2>
        <p class="text-gray-600 mb-8">Enter your email address and we'll send you a link to reset your password.</p>

        <form @submit.prevent="submitForgotPassword" class="space-y-6">
          <!-- 邮箱输入 -->
          <div class="text-left">
            <label for="resetEmail" class="block text-sm font-semibold text-gray-700 mb-2">
              Email Address
            </label>
            <input 
              id="resetEmail" 
              type="email" 
              v-model="resetForm.email" 
              required
              :disabled="isResettingPassword"
              :class="[
                'w-full h-12 px-4 border rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500',
                resetForm.errors.email 
                  ? 'border-red-300 focus:border-red-500' 
                  : 'border-gray-300 focus:border-blue-500',
                isResettingPassword ? 'bg-gray-50 cursor-not-allowed' : 'bg-white'
              ]"
              placeholder="Enter your email address"
            />
            <div v-if="resetForm.errors.email" class="mt-1 text-sm text-red-600">
              {{ resetForm.errors.email }}
            </div>
          </div>

          <!-- 提交按钮 -->
          <button 
            type="submit"
            :disabled="isResettingPassword"
            class="w-full h-12 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <svg v-if="isResettingPassword" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isResettingPassword ? 'Sending...' : 'Send Reset Link' }}
          </button>
        </form>

        <!-- 返回登录链接 -->
        <div class="mt-6 text-center">
          <button 
            @click="showForgotPasswordModal = false"
            class="text-sm text-gray-500 hover:text-gray-700 underline transition-colors"
          >
            Back to Login
          </button>
        </div>
      </div>
    </div>

    <!-- 登录错误弹层 -->
    <div v-if="showLoginErrorModal"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm text-center relative">
        <!-- 错误图标 -->
        <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>

        <h2 class="text-xl font-bold text-gray-900 mb-2">Login Failed</h2>
        <p class="text-gray-600 mb-6">Invalid email or password. What would you like to do?</p>

        <div class="space-y-3">
          <button @click="tryAgain"
                  class="w-full h-11 rounded-full bg-gray-800 text-white font-semibold hover:bg-gray-700 transition">
            Try Again
          </button>
          <button @click="goToRegister"
                  class="w-full h-11 rounded-full border-2 border-gray-800 text-gray-800 font-semibold hover:bg-gray-800 hover:text-white transition">
            Create New Account
          </button>
        </div>

        <button @click="showLoginErrorModal = false"
                class="absolute top-3 right-4 text-gray-400 text-xl font-bold hover:text-gray-700">×</button>
      </div>
    </div>

    <!-- 账户锁定弹层 -->
    <div v-if="showAccountLockedModal"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm text-center relative">
        <!-- 锁定图标 -->
        <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>

        <h2 class="text-xl font-bold text-gray-900 mb-2">Account Temporarily Locked</h2>
        <p class="text-gray-600 mb-4">Too many failed login attempts. Your account has been locked for 5 minutes for security reasons.</p>
        
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
          <p class="text-orange-800 text-sm font-medium">Security Recommendation:</p>
          <p class="text-orange-700 text-sm mt-1">Consider resetting your password to ensure account security.</p>
        </div>

        <div class="space-y-3">
          <button @click="resetPasswordFromLocked"
                  class="w-full h-11 rounded-full bg-orange-600 text-white font-semibold hover:bg-orange-700 transition">
            Reset Password
          </button>
          <button @click="closeLockedModal"
                  class="w-full h-11 rounded-full border-2 border-gray-300 text-gray-600 font-semibold hover:bg-gray-50 transition">
            Wait 5 Minutes
          </button>
        </div>

        <button @click="closeLockedModal"
                class="absolute top-3 right-4 text-gray-400 text-xl font-bold hover:text-gray-700">×</button>
      </div>
    </div>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>
