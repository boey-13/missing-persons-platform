<script setup>
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
defineOptions({ layout: MainLayout })

// --- flash + local toast (原样保留) ---
const page = usePage()
const successMsg = computed(() => page.props.flash?.success || '')
const errorMsg   = computed(() => page.props.flash?.error || '')
const localMsg  = ref('')
const localType = ref('success')
const displayMsg  = computed(() => errorMsg.value || successMsg.value || localMsg.value)
const displayType = computed(() => errorMsg.value ? 'error' : (successMsg.value ? 'success' : localType.value))
const showFlash = ref(false)
let hideTimer = null
function showToast(message, type = 'success') {
  localMsg.value  = message
  localType.value = type
  showFlash.value = true
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => { showFlash.value = false; localMsg.value = '' }, 3000)
}
watch([successMsg, errorMsg, localMsg], ([s, e, l]) => {
  clearTimeout(hideTimer)
  showFlash.value = !!(s || e || l)
  if (showFlash.value) {
    hideTimer = setTimeout(() => { showFlash.value = false; localMsg.value = '' }, 3000)
  }
}, { immediate: true })
onBeforeUnmount(() => clearTimeout(hideTimer))

// --- props + 表单（原样保留） ---
const props = defineProps({ profile: Object })
const step = ref(1)
const totalSteps = 3

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
    if (validateCurrentStep()) step.value++
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
      if (!form.motivation || form.motivation.length < 10) { showToast('Motivation must be at least 10 characters long.', 'error'); isValid = false }
      if (!form.emergency_contact_name || form.emergency_contact_name.trim() === '') { showToast('Emergency contact name is required.', 'error'); isValid = false }
      if (!form.emergency_contact_phone || form.emergency_contact_phone.trim() === '') { showToast('Emergency contact phone is required.', 'error'); isValid = false }
      if (form.skills.length === 0) { showToast('Please select at least one skill.', 'error'); isValid = false }
      if (form.languages.length === 0) { showToast('Please select at least one language.', 'error'); isValid = false }
      if (form.availability.length === 0) { showToast('Please select at least one available day.', 'error'); isValid = false }
      if (form.preferred_roles.length === 0) { showToast('Please select at least one preferred role.', 'error'); isValid = false }
      if (!form.transport_mode || form.transport_mode.trim() === '') { showToast('Please select your mode of transport.', 'error'); isValid = false }
      break
    case 3:
      if (!form.termsAccepted) { showToast('You must accept the Terms & Conditions and Code of Conduct.', 'error'); isValid = false }
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

function onFilesChange(e) { form.supporting_documents = Array.from(e.target.files) }

function submit() {
  if (!validateCurrentStep()) return
  form.post(route('volunteer.apply.store'), {
    forceFormData: true,
    onSuccess: () => {
      showToast('Volunteer application submitted successfully! Please wait for admin approval.', 'success')
      setTimeout(() => { router.visit(route('volunteer.application-pending')) }, 2000)
    },
    onError: (errors) => {
      if (Object.keys(errors).length > 0) showToast('Please fix the errors in your application form.', 'error')
      else showToast('Failed to submit application. Please try again.', 'error')
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
  <div class="max-w-4xl mx-auto py-10">
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
          <button v-if="step<3" type="button" class="px-4 py-2 rounded bg-[#335a3b] text-white" @click="nextStep">Next</button>
          <button v-else type="submit" :disabled="form.processing" class="px-4 py-2 rounded bg-[#335a3b] text-white disabled:opacity-50">
            {{ form.processing ? 'Submitting...' : 'Submit Application' }}
          </button>
        </div>
      </div>
    </form>
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
