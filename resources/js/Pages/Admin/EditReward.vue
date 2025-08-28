<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  reward: Object,
  categories: Array,
})

/**
 * 重要说明：
 * - 这里保留 forceFormData: true（便于图片/文件上传）
 * - 通过 transform 注入 _method: 'PUT'，让浏览器真正发 POST
 *   -> Laravel 仍按 PUT 路由命中，但能正确解析 multipart 表单
 */
const form = useForm({
  // 注意把数值转成字符串给 <select>/<input>，避免受控组件警告
  category_id: props.reward.category_id?.toString() ?? '',
  name: props.reward.name ?? '',
  description: props.reward.description ?? '',
  points_required: props.reward.points_required?.toString() ?? '',
  stock_quantity: props.reward.stock_quantity?.toString() ?? '', // 允许留空代表无限库存
  validity_days: props.reward.validity_days?.toString() ?? '',
  status: (props.reward.status ?? 'active').toString(), // active / inactive
  voucher_code_prefix: props.reward.voucher_code_prefix ?? '',
  image: null, // 可选：编辑时若不换图就保持 null
})

const imagePreview = ref(null)
const { success, error } = useToast()

function onImageChange(e) {
  const file = e.target.files?.[0]
  form.image = file ?? null
  if (file) {
    const reader = new FileReader()
    reader.onload = () => (imagePreview.value = reader.result)
    reader.readAsDataURL(file)
  } else {
    imagePreview.value = null
  }
}

function submit() {
  // 将空字符串转换为 null（便于后端验证：nullable|integer 等）
  form
    .transform((data) => ({
      ...data,
      stock_quantity: data.stock_quantity === '' ? null : data.stock_quantity,
      validity_days: data.validity_days === '' ? null : data.validity_days,
      points_required: data.points_required === '' ? null : data.points_required,
      _method: 'PUT',
    }))
    .post(`/admin/rewards/${props.reward.id}`, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        success('Reward updated successfully!')
        router.visit('/admin/rewards', {
          method: 'get',
          preserveState: false,
          preserveScroll: false,
        })
      },
      onError: (errors) => {
        console.error('Update failed:', errors)
        error('Failed to update reward. Please try again.')
      },
    })
}
</script>

<template>
  <div class="max-w-5xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Edit Reward</h1>
          <p class="text-gray-600 mt-2">Update reward details</p>
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
    <div class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Category *
          </label>
          <select
            v-model="form.category_id"
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
          >
            <option value="" disabled>Select a category</option>
            <option
              v-for="c in categories"
              :key="c.id"
              :value="String(c.id)"
            >
              {{ c.name }}
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
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="e.g. RM20 E-commerce Voucher"
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
            rows="4"
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="Short details about the reward"
          />
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
            min="0"
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="e.g. 100"
          />
          <div
            v-if="form.errors.points_required"
            class="text-red-600 text-sm mt-1"
          >
            {{ form.errors.points_required }}
          </div>
        </div>

        <!-- Stock Quantity (optional) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Stock Quantity
          </label>
          <input
            v-model="form.stock_quantity"
            type="number"
            min="0"
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="Leave empty for unlimited stock"
          />
          <p class="text-sm text-gray-500 mt-1">
            Leave empty for unlimited stock
          </p>
          <div v-if="form.errors.stock_quantity" class="text-red-600 text-sm mt-1">
            {{ form.errors.stock_quantity }}
          </div>
        </div>

        <!-- Validity Days (optional) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Validity (days)
          </label>
          <input
            v-model="form.validity_days"
            type="number"
            min="0"
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="e.g. 30"
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
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">
            {{ form.errors.status }}
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
            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
            placeholder="e.g. EC"
          />
          <div
            v-if="form.errors.voucher_code_prefix"
            class="text-red-600 text-sm mt-1"
          >
            {{ form.errors.voucher_code_prefix }}
          </div>
        </div>

                 <!-- Image (optional) -->
         <div>
           <label class="block text-sm font-medium text-gray-700 mb-2">
             Image
           </label>
           
           <!-- Current Image Display -->
           <div v-if="props.reward.image_path" class="mb-4">
             <p class="text-sm text-gray-600 mb-2">Current Image:</p>
             <div class="flex items-center space-x-4">
               <img 
                 :src="`/storage/${props.reward.image_path}`" 
                 :alt="props.reward.name"
                 class="h-32 w-32 rounded-lg border object-cover"
               />
               <div class="text-sm text-gray-500">
                 <p><strong>Current:</strong> {{ props.reward.image_path.split('/').pop() }}</p>
                 <p class="text-xs">Upload a new image to replace this one</p>
               </div>
             </div>
           </div>
           
           <!-- New Image Upload -->
           <div>
             <p class="text-sm text-gray-600 mb-2">
               {{ props.reward.image_path ? 'Upload New Image:' : 'Upload Image:' }}
             </p>
             <input
               type="file"
               accept="image/*"
               @change="onImageChange"
               class="block w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 hover:file:bg-gray-200"
             />
             
             <!-- New Image Preview -->
             <div v-if="imagePreview" class="mt-3">
               <p class="text-sm text-gray-600 mb-2">New Image Preview:</p>
               <img :src="imagePreview" alt="Preview" class="h-32 w-32 rounded-lg border object-cover" />
             </div>
           </div>
           
           <div v-if="form.errors.image" class="text-red-600 text-sm mt-1">
             {{ form.errors.image }}
           </div>
         </div>

        <!-- Actions -->
        <div class="flex gap-4 pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="flex-1 bg-gray-900 text-white py-3 px-4 rounded-lg font-medium hover:bg-black disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors relative"
          >
            <span v-if="form.processing" class="absolute inset-0 flex items-center justify-center">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span :class="{ 'opacity-0': form.processing }">
              {{ form.processing ? 'Updating...' : 'Update Reward' }}
            </span>
          </button>

          <Link
            href="/admin/rewards"
            class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-center"
          >
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>
