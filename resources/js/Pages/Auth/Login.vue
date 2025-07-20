<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login'))
}

const showForgotPasswordModal = ref(false)

const resetForm = useForm({
  email: '',
})

function submitForgotPassword() {
  resetForm.post(route('password.email'), {
    onSuccess: () => {
      toast.success('Reset link sent to your email!')
      showForgotPasswordModal.value = false
    },
    onError: () => {
      toast.error('Failed to send reset link. Please check your email.')
    }
  })
}



</script>

<template>
  <div
    class="relative min-h-screen bg-gradient-to-b from-[#cbb279] via-[#d6c69a] to-white flex items-center justify-center">

    <div class="absolute top-6 right-6">
      <Link href="/register"
        class="border border-white px-4 py-2 rounded-full text-white hover:bg-white hover:text-black transition">
      SIGN UP
      </Link>
    </div>

    <div class="bg-white p-10 rounded-xl shadow-xl w-full max-w-md">
      <h1 class="text-2xl font-bold text-center mb-6">Log In to FindMe</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label for="email" class="block mb-1">Email Address</label>
          <input v-model="form.email" type="email" id="email" class="w-full border px-4 py-2 rounded" required />
        </div>

        <div class="mb-4">
          <label for="password" class="block mb-1">Password</label>
          <input v-model="form.password" type="password" id="password" class="w-full border px-4 py-2 rounded"
            required />
        </div>

        <div class="flex justify-between items-center text-sm mb-4">
          <label class="flex items-center space-x-2">
            <input type="checkbox" v-model="form.remember" />
            <span>Remember Me</span>
          </label>

          <a href="#" @click.prevent="showForgotPasswordModal = true" class="text-blue-500">Forgot Password?</a>
        </div>

        <button type="submit" class="bg-black text-white w-full py-2 rounded font-bold">
          PROCEED
        </button>
      </form>
    </div>

    <div v-if="showForgotPasswordModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-sm text-center relative">
        <h2 class="text-xl font-bold mb-6">Reset Password</h2>

        <form @submit.prevent="submitForgotPassword">
          <label for="resetEmail" class="block text-left mb-1 text-sm font-semibold">EMAIL ADDRESS</label>
          <input id="resetEmail" type="email" v-model="resetForm.email" class="w-full border px-4 py-2 rounded mb-6"
            required />
          <button type="submit" class="bg-[#704c34] text-white w-full py-2 rounded-full font-bold">
            PROCEED
          </button>
        </form>

        <button @click="showForgotPasswordModal = false"
          class="absolute top-3 right-4 text-gray-400 text-xl font-bold hover:text-gray-700">×</button>
      </div>
    </div>
  </div>
</template>
