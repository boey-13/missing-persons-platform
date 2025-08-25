<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
defineOptions({ layout: MainLayout })

const props = defineProps({
  profile: Object,
})

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

function nextStep() {
  if (step.value < totalSteps) step.value++
}
function prevStep() {
  if (step.value > 1) step.value--
}

function onFilesChange(e) {
  form.supporting_documents = Array.from(e.target.files)
}

function submit() {
  if (!form.termsAccepted) return alert('You must accept Terms & Code of Conduct')
  form.post(route('volunteer.apply.store'), { forceFormData: true })
}

const skillsOptions = ['沟通', '海报分发', '社媒宣传', '搜寻']
const languagesOptions = ['English', '中文', 'Bahasa', 'Tamil']
const daysOptions = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
const rolesOptions = ['海报分发','社区活动','搜寻队','线上协助']
const transportOptions = ['有车','摩托','公共交通','步行']

function toggle(array, value) {
  const idx = array.indexOf(value)
  if (idx >= 0) array.splice(idx, 1)
  else array.push(value)
}
</script>

<template>
  <div class="max-w-4xl mx-auto py-10">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Become a Volunteer</h1>
      <p class="text-gray-600">Join our community and help find missing persons</p>
    </div>

    <!-- Progress Bar -->
    <div class="mb-8">
      <div class="flex justify-between mb-2">
        <span class="text-sm text-gray-600">Step {{ step }} of {{ totalSteps }}</span>
        <span class="text-sm text-gray-600">{{ Math.round((step / totalSteps) * 100) }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="bg-[#335a3b] h-2 rounded-full transition-all duration-300" :style="{ width: `${(step / totalSteps) * 100}%` }"></div>
      </div>
    </div>

    <form @submit.prevent="submit">
      <!-- Step 1: Personal Info -->
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

      <!-- Step 2: Skills & Preferences -->
      <div v-if="step===2" class="bg-white rounded-xl shadow p-6 space-y-6">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Motivation / Why Volunteer <span class="text-red-500">*</span></label>
          <textarea v-model="form.motivation" rows="4" class="w-full border rounded px-3 py-2" required></textarea>
          <div v-if="form.errors.motivation" class="text-sm text-red-600 mt-1">{{ form.errors.motivation }}</div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Skills / Strengths</label>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in skillsOptions" :key="s" type="button"
                    @click="toggle(form.skills, s)"
                    :class="['px-3 py-1 rounded border', form.skills.includes(s)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ s }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Languages</label>
          <div class="flex flex-wrap gap-2">
            <button v-for="l in languagesOptions" :key="l" type="button"
                    @click="toggle(form.languages, l)"
                    :class="['px-3 py-1 rounded border', form.languages.includes(l)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ l }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Availability (days)</label>
          <div class="flex flex-wrap gap-2">
            <button v-for="d in daysOptions" :key="d" type="button"
                    @click="toggle(form.availability, d)"
                    :class="['px-3 py-1 rounded border', form.availability.includes(d)?'bg-[#335a3b] text-white border-[#335a3b]':'bg-white']">{{ d }}</button>
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-2">Preferred Roles</label>
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
          <label class="block text-sm text-gray-600 mb-1">Mode of Transport</label>
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

      <!-- Step 3: Confirm & Submit -->
      <div v-if="step===3" class="bg-white rounded-xl shadow p-6 space-y-4">
        <div class="flex items-center gap-2">
          <input id="terms" type="checkbox" v-model="form.termsAccepted" required />
          <label for="terms" class="text-sm">I agree to the Terms & Conditions and Code of Conduct</label>
        </div>
        <div class="text-sm text-gray-600">Please review your information before submitting.</div>
      </div>

      <!-- Step Controls -->
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
</template>


