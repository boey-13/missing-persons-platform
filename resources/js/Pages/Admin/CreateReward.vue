<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  categories: Array,
})

const form = useForm({
  category_id: '',
  name: '',
  description: '',
  points_required: '',
  stock_quantity: '',
  image: null,
  voucher_code_prefix: '',
  validity_days: 30,
  status: 'active',
})

const { success, error } = useToast()

function submit() {
  form.post('/admin/rewards', {
    forceFormData: true,
    onSuccess: () => {
      success('Reward created successfully!')
      router.visit('/admin/rewards')
    },
    onError: (errors) => {
      console.error('Create failed:', errors)
      error('Failed to create reward. Please try again.')
    }
  })
}

function handleImageUpload(event) {
  const file = event.target.files[0]
  if (file) {
    form.image = file
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Create New Reward</h1>
          <p class="text-gray-600 mt-2">Add a new reward to the system</p>
        </div>
        <Link
          href="/admin/rewards"
          class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition-colors"
        >
          ← Back to Rewards
        </Link>
      </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <form @submit.prevent="submit" class="p-6 space-y-6">
        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Category *
          </label>
          <select
            v-model="form.category_id"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select a category</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
          <div v-if="form.errors.category_id" class="text-red-600 text-sm mt-1">
            {{ form.errors.category_id }}
          </div>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Reward Name *
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="e.g., RM10 Shopee Voucher"
          />
          <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
            {{ form.errors.name }}
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe the reward..."
          ></textarea>
          <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Points Required -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Points Required *
          </label>
          <input
            v-model="form.points_required"
            type="number"
            required
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="150"
          />
          <div v-if="form.errors.points_required" class="text-red-600 text-sm mt-1">
            {{ form.errors.points_required }}
          </div>
        </div>

        <!-- Stock Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Stock Quantity
          </label>
          <input
            v-model="form.stock_quantity"
            type="number"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="100 (leave empty for unlimited)"
          />
          <p class="text-sm text-gray-500 mt-1">
            Leave empty for unlimited stock
          </p>
          <div v-if="form.errors.stock_quantity" class="text-red-600 text-sm mt-1">
            {{ form.errors.stock_quantity }}
          </div>
        </div>

        <!-- Voucher Code Prefix -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Voucher Code Prefix
          </label>
          <input
            v-model="form.voucher_code_prefix"
            type="text"
            maxlength="10"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="SHOPEE"
          />
          <p class="text-sm text-gray-500 mt-1">
            Prefix for generated voucher codes (optional)
          </p>
          <div v-if="form.errors.voucher_code_prefix" class="text-red-600 text-sm mt-1">
            {{ form.errors.voucher_code_prefix }}
          </div>
        </div>

        <!-- Validity Days -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Validity (Days) *
          </label>
          <input
            v-model="form.validity_days"
            type="number"
            required
            min="1"
            max="365"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="30"
          />
          <div v-if="form.errors.validity_days" class="text-red-600 text-sm mt-1">
            {{ form.errors.validity_days }}
          </div>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Status *
          </label>
          <select
            v-model="form.status"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">
            {{ form.errors.status }}
          </div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Reward Image
          </label>
          <input
            type="file"
            @change="handleImageUpload"
            accept="image/*"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p class="text-sm text-gray-500 mt-1">
            Upload an image for the reward (optional)
          </p>
          <div v-if="form.errors.image" class="text-red-600 text-sm mt-1">
            {{ form.errors.image }}
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3 pt-6 border-t border-gray-200">
          <button
            type="submit"
            :disabled="form.processing"
            class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 relative"
          >
            <span v-if="form.processing" class="absolute inset-0 flex items-center justify-center">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span :class="{ 'opacity-0': form.processing }">
              {{ form.processing ? 'Creating...' : 'Create Reward' }}
            </span>
          </button>
          <Link
            href="/admin/rewards"
            class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-center"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>
