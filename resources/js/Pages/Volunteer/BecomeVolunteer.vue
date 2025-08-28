<script setup>
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import { ref, computed, watch, onBeforeUnmount, onMounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useToast } from '@/Composables/useToast'
defineOptions({ layout: MainLayout })

const { success, error } = useToast()

// --- props + 表单（原样保留） ---
const props = defineProps({ profile: Object })
const step = ref(1)
const totalSteps = 3
const isProcessingStep = ref(false)
const isInitializing = ref(true)

const form = useForm({
  motivation: '',
  skills: [],
  languages: [],
  availability: [],
  preferred_roles: [],
  areas: '',
  transport_mode: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  prior_experience: '',
  supporting_documents: [],
  termsAccepted: false,
})

// ✅ UI-only: 步骤元数据（标题/说明），不影响逻辑
const stepsMeta = [
  { num: 1, title: 'Personal Info', desc: 'Loaded from your profile' },
  { num: 2, title: 'Skills & Preferences', desc: 'Tell us how you can help' },
  { num: 3, title: 'Confirm & Submit', desc: 'Agree to terms & send' },
]

// ✅ UI-only: 进度百分比（不改你原来计算方式，只是封装成 computed）
const percent = computed(() => Math.round((step.value / totalSteps) * 100))

// --- 分页控制（逻辑原样保留） ---
function nextStep() {
  if (step.value < totalSteps) {
    isProcessingStep.value = true
    // Simulate validation delay for better UX
    setTimeout(() => {
      if (validateCurrentStep()) {
        step.value++
      }
      isProcessingStep.value = false
    }, 300)
  }
}
function prevStep() { if (step.value > 1) step.value-- }

// ✅ UI-only：可点击的步骤跳转
// - 点击回退：直接跳
// - 点击前进：按原先 nextStep() 逐步推进（会触发你已有的验证逻辑）
function goToStep(idx) {
  if (idx < 1 || idx > totalSteps) return
  if (idx <= step.value) {
    step.value = idx
  } else {
    // 逐步调用 nextStep，保持你的原验证逻辑
    while (step.value < idx) {
      const before = step.value
      nextStep()
      if (step.value === before) break // 验证未通过，停留
    }
  }
}

// --- 校验（原样保留） ---
const currentStepStatus = computed(() => getStepValidationStatus())
function validateCurrentStep() {
  let isValid = true
  switch (step.value) {
    case 1:
      isValid = true
      break
    case 2:
      if (!form.motivation || form.motivation.length < 10) { error('Motivation must be at least 10 characters long.'); isValid = false }
      if (!form.emergency_contact_name || form.emergency_contact_name.trim() === '') { error('Emergency contact name is required.'); isValid = false }
      if (!form.emergency_contact_phone || form.emergency_contact_phone.trim() === '') { error('Emergency contact phone is required.'); isValid = false }
      if (form.skills.length === 0) { error('Please select at least one skill.'); isValid = false }
      if (form.languages.length === 0) { error('Please select at least one language.'); isValid = false }
      if (form.availability.length === 0) { error('Please select at least one available day.'); isValid = false }
      if (form.preferred_roles.length === 0) { error('Please select at least one preferred role.'); isValid = false }
      if (!form.transport_mode || form.transport_mode.trim() === '') { error('Please select your mode of transport.'); isValid = false }
      break
    case 3:
      if (!form.termsAccepted) { error('You must accept the Terms & Conditions and Code of Conduct.'); isValid = false }
      break
  }
  return isValid
}
function getStepValidationStatus() {
  switch (step.value) {
    case 1:
      return { isValid: true, message: 'Personal information loaded from your profile', icon: '✅' }
    case 2:
      const step2Errors = []
      if (!form.motivation || form.motivation.length < 10) step2Errors.push('Motivation (min 10 chars)')
      if (!form.emergency_contact_name) step2Errors.push('Emergency contact name')
      if (!form.emergency_contact_phone) step2Errors.push('Emergency contact phone')
      if (form.skills.length === 0) step2Errors.push('Skills')
      if (form.languages.length === 0) step2Errors.push('Languages')
      if (form.availability.length === 0) step2Errors.push('Availability')
      if (form.preferred_roles.length === 0) step2Errors.push('Preferred roles')
      if (!form.transport_mode) step2Errors.push('Transport mode')
      if (step2Errors.length === 0) return { isValid: true, message: 'All required fields completed', icon: '✅' }
      return { isValid: false, message: `Missing: ${step2Errors.join(', ')}`, icon: '⚠️' }
    case 3:
      if (form.termsAccepted) return { isValid: true, message: 'Ready to submit application', icon: '✅' }
      return { isValid: false, message: 'Please accept terms and conditions', icon: '⚠️' }
    default:
      return { isValid: false, message: 'Unknown step', icon: '❌' }
  }
}

function onFilesChange(e) { 
  form.supporting_documents = Array.from(e.target.files) 
}

// Initialize page after a short delay
onMounted(() => {
  setTimeout(() => {
    isInitializing.value = false
  }, 500)
})

function submit() {
  if (!validateCurrentStep()) return
  form.post(route('volunteer.apply.store'), {
    forceFormData: true,
    onSuccess: () => {
      success('Volunteer application submitted successfully! Please wait for admin approval.')
      setTimeout(() => { router.visit(route('volunteer.application-pending')) }, 2000)
    },
    onError: (errors) => {
      if (Object.keys(errors).length > 0) error('Please fix the errors in your application form.')
      else error('Failed to submit application. Please try again.')
    }
  })
}

const skillsOptions = ['Communication', 'Poster Distribution', 'Social Media Promotion', 'Search Operations']
const languagesOptions = ['English', 'Chinese', 'Bahasa', 'Tamil']
const daysOptions = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
const rolesOptions = ['Poster Distribution','Community Events','Search Team','Online Support']
const transportOptions = ['Car','Motorcycle','Public Transport','Walking']
function toggle(array, value) { const idx = array.indexOf(value); if (idx >= 0) array.splice(idx, 1); else array.push(value) }
</script>

<template>
  <div class="max-w-[1400px] mx-auto py-10">
    <!-- Page Loading State -->
    <div v-if="isInitializing" class="text-center py-20">
      <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-[#335a3b] hover:bg-[#2a4a30] transition ease-in-out duration-150 cursor-not-allowed">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Loading volunteer application form...
      </div>
    </div>
    
    <div v-else>
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Become a Volunteer</h1>
        <p class="text-gray-600">Join our community and help find missing persons</p>
      </div>

    <!-- Stepper Header -->
    <div class="mb-6">
      <!-- Linear progress -->
      <div class="flex justify-between mb-2">
        <span class="text-sm text-gray-600">Step {{ step }} of {{ totalSteps }}</span>
        <span class="text-sm text-gray-600">{{ percent }}%</span>
      </div>
      <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-2 bg-[#335a3b] transition-all duration-300" :style="{ width: percent + '%' }"></div>
      </div>

      <!-- Clickable steps -->
      <ol class="mt-4 grid grid-cols-3 gap-2">
        <li v-for="s in stepsMeta" :key="s.num"
            class="relative flex items-center justify-start gap-3 select-none">
          <!-- connector -->
          <div v-if="s.num !== 1" class="absolute left-[-8px] right-auto w-full h-px bg-gray-300 top-1/2 -z-10"></div>

          <!-- dot -->
          <button type="button"
                  @click="goToStep(s.num)"
                  class="h-8 w-8 rounded-full border flex items-center justify-center transition"
                  :class="[
                    s.num < step ? 'bg-[#335a3b] border-[#335a3b] text-white' :
                    (s.num === step ? 'bg-white border-[#335a3b] text-[#335a3b]' : 'bg-white border-gray-300 text-gray-500')
                  ]">
            <span v-if="s.num < step">✓</span>
            <span v-else>{{ s.num }}</span>
          </button>

          <!-- label -->
          <div class="min-w-0">
            <div :class="s.num <= step ? 'text-sm font-medium text-[#335a3b]' : 'text-sm font-medium text-gray-600'">
              {{ s.title }}
            </div>
            <div class="text-xs text-gray-500 hidden sm:block truncate">{{ s.desc }}</div>
          </div>
        </li>
      </ol>

      <!-- Step Validation Status -->
      <div class="mt-3 text-center">
        <div :class="[
              'inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg',
              currentStepStatus.isValid ? 'text-green-700 bg-green-50 border border-green-200' : 'text-orange-700 bg-orange-50 border border-orange-200'
            ]">
          <span>{{ currentStepStatus.icon }}</span>
          <span>{{ currentStepStatus.message }}</span>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit">
      <!-- Step 1 -->
      <div v-if="step===1" class="bg-white rounded-xl shadow p-6 space-y-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Name</label>
          <input v-model="profile.name" disabled class="w-full border rounded px-3 py-2 bg-gray-50" />
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Email</label>
          <input v-model="profile.email" disabled class="w-full border rounded px-3 py-2 bg-gray-50" />
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Phone</label>
          <input v-model="profile.phone" disabled class="w-full border rounded px-3 py-2 bg-gray-50" />
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">Region</label>
          <input v-model="profile.region" disabled class="w-full border rounded px-3 py-2 bg-gray-50" />
        </div>
      </div>

      <!-- Step 2 -->
      <div v-if="step===2" class="bg-white rounded-xl shadow p-6 space-y-6">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Motivation / Why Volunteer <span class="text-red-500">*</span></label>
          <textarea v-model="form.motivation" rows="4" class="w-full border rounded px-3 py-2" required></textarea>
          <div v-if="form.errors.motivation" class="text-sm text-red-600 mt-1">{{ form.errors.motivation }}</div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Skills / Strengths <span class="text-red-500">*</span></label>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in skillsOptions" :key="s" type="button"
                    @click="toggle(form.skills, s)"
                    :class="['px-3 py-1 rounded border', form.skills.includes(s)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ s }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Languages <span class="text-red-500">*</span></label>
          <div class="flex flex-wrap gap-2">
            <button v-for="l in languagesOptions" :key="l" type="button"
                    @click="toggle(form.languages, l)"
                    :class="['px-3 py-1 rounded border', form.languages.includes(l)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ l }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Availability (days) <span class="text-red-500">*</span></label>
          <div class="flex flex-wrap gap-2">
            <button v-for="d in daysOptions" :key="d" type="button"
                    @click="toggle(form.availability, d)"
                    :class="['px-3 py-1 rounded border', form.availability.includes(d)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ d }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Preferred Roles <span class="text-red-500">*</span></label>
          <div class="flex flex-wrap gap-2">
            <button v-for="r in rolesOptions" :key="r" type="button"
                    @click="toggle(form.preferred_roles, r)"
                    :class="['px-3 py-1 rounded border', form.preferred_roles.includes(r)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ r }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Areas Willing to Help</label>
          <input v-model="form.areas" class="w-full border rounded px-3 py-2" placeholder="e.g., KL, PJ, Setapak" />
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Mode of Transport <span class="text-red-500">*</span></label>
          <select v-model="form.transport_mode" class="w-full border rounded px-3 py-2">
            <option value="">Select...</option>
            <option v-for="t in transportOptions" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Emergency Contact Name <span class="text-red-500">*</span></label>
            <input v-model="form.emergency_contact_name" class="w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">Emergency Contact Phone <span class="text-red-500">*</span></label>
            <input v-model="form.emergency_contact_phone" class="w-full border rounded px-3 py-2" required />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Prior Experience (optional)</label>
          <textarea v-model="form.prior_experience" rows="3" class="w-full border rounded px-3 py-2"></textarea>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Supporting Documents (optional)</label>
          <input type="file" multiple @change="onFilesChange" />
        </div>
      </div>

      <!-- Step 3 -->
      <div v-if="step===3" class="bg-white rounded-xl shadow p-6 space-y-4">
        <div class="flex items-center gap-2">
          <input id="terms" type="checkbox" v-model="form.termsAccepted" required />
          <label for="terms" class="text-sm">I agree to the Terms & Conditions and Code of Conduct</label>
        </div>
        <div class="text-sm text-gray-600">Please review your information before submitting.</div>
      </div>

      <!-- Controls -->
      <div class="flex justify-between mt-6">
        <button type="button" class="px-4 py-2 rounded bg-gray-200" :disabled="step===1" @click="prevStep">Back</button>
        <div class="space-x-2">
          <button v-if="step<3" type="button" 
                  :disabled="isProcessingStep"
                  class="px-4 py-2 rounded bg-[#335a3b] text-white relative disabled:opacity-50 disabled:cursor-not-allowed" 
                  @click="nextStep">
            <span v-if="isProcessingStep" class="absolute inset-0 flex items-center justify-center">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span :class="{ 'opacity-0': isProcessingStep }">
              {{ isProcessingStep ? 'Validating...' : 'Next' }}
            </span>
          </button>
          <button v-else type="submit" 
                  :disabled="form.processing" 
                  class="px-4 py-2 rounded bg-[#335a3b] text-white relative disabled:opacity-50 disabled:cursor-not-allowed">
            <span v-if="form.processing" class="absolute inset-0 flex items-center justify-center">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span :class="{ 'opacity-0': form.processing }">
              {{ form.processing ? 'Submitting...' : 'Submit Application' }}
            </span>
          </button>
        </div>
      </div>
    </form>
      </div>
    </div>

  <!-- Top-centered Flash -->
  <teleport to="body">
    <transition name="fade-down">
      <div v-if="showFlash" class="pointer-events-none fixed inset-x-0 top-4 z-[9999] flex justify-center px-4">
        <div class="pointer-events-auto max-w-md w-full">
          <div role="alert" class="rounded-xl px-4 py-3 text-white text-center shadow-lg"
               :class="displayType === 'error' ? 'bg-red-600/90' : 'bg-green-600/90'">
            {{ displayMsg }}
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
/* 顶部提示动画 */
.fade-down-enter-active,
.fade-down-leave-active { transition: all .18s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
