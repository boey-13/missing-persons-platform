<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('register'), {
    onSuccess: () => {
      toast.success('Registration successful!')
    },
    onError: () => {
      toast.error('Registration failed. Please check all fields.')
    }
  })
}

</script>

<template>
  <div class="relative min-h-screen bg-gradient-to-br from-indigo-200 via-blue-100 to-white flex items-center justify-center">

    <div class="absolute top-6 right-6">
      <Link
        href="/login"
        class="border border-white px-4 py-2 rounded-full text-white hover:bg-white hover:text-black transition"
      >
        SIGN IN
      </Link>
    </div>


    <div class="bg-white p-10 rounded-xl shadow-xl w-full max-w-md">
      <h1 class="text-2xl font-bold text-center mb-6">Sign up to FindMe</h1>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label for="name" class="block mb-1">Name</label>
          <input id="name" v-model="form.name" type="text" class="w-full border px-4 py-2 rounded" required />
        </div>

        <div class="mb-4">
          <label for="email" class="block mb-1">Email Address</label>
          <input id="email" v-model="form.email" type="email" class="w-full border px-4 py-2 rounded" required />
        </div>

        <div class="mb-4">
          <label for="phone" class="block mb-1">Phone Number</label>
          <input id="phone" v-model="form.phone" type="text" class="w-full border px-4 py-2 rounded" />
        </div>

        <div class="mb-4">
          <label for="password" class="block mb-1">Password</label>
          <input id="password" v-model="form.password" type="password" class="w-full border px-4 py-2 rounded" required />
        </div>

        <div class="mb-6">
          <label for="password_confirmation" class="block mb-1">Confirm Password</label>
          <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full border px-4 py-2 rounded" required />
        </div>

        <button type="submit" class="bg-black text-white w-full py-2 rounded font-bold">
          CREATE AN ACCOUNT
        </button>
      </form>
    </div>
  </div>
</template>
