<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({
  stats: Object,
  recentActivities: Array,
  recentMissingReports: Array,
  recentSightings: Array,
})
</script>

<template>
  <div class="max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-extrabold text-gray-900">Admin Dashboard</h1>
      <p class="text-gray-600 mt-2">Welcome back! Here's what's happening with your platform.</p>
    </div>

    <!-- Main Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-1">
            <div class="text-gray-500 text-sm font-medium">Total Missing Cases</div>
            <div class="text-3xl font-bold text-gray-900">{{ stats?.totalMissingCases || 0 }}</div>
            <div class="text-green-600 text-sm mt-1">Active cases</div>
          </div>
          <div class="text-blue-500 text-2xl">👥</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-1">
            <div class="text-gray-500 text-sm font-medium">Pending Sightings</div>
            <div class="text-3xl font-bold text-gray-900">{{ stats?.pendingSightings || 0 }}</div>
            <div class="text-orange-600 text-sm mt-1">Awaiting review</div>
          </div>
          <div class="text-orange-500 text-2xl">🔍</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-1">
            <div class="text-gray-500 text-sm font-medium">Total Users</div>
            <div class="text-3xl font-bold text-gray-900">{{ stats?.totalUsers || 0 }}</div>
            <div class="text-blue-600 text-sm mt-1">Registered users</div>
          </div>
          <div class="text-green-500 text-2xl">👤</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-1">
            <div class="text-gray-500 text-sm font-medium">Active Rewards</div>
            <div class="text-3xl font-bold text-gray-900">{{ stats?.activeRewards || 0 }}</div>
            <div class="text-purple-600 text-sm mt-1">Available rewards</div>
          </div>
          <div class="text-purple-500 text-2xl">🎁</div>
        </div>
      </div>
    </div>

    <!-- Additional Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-gray-500 text-sm font-medium mb-2">Community Projects</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats?.totalProjects || 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Active projects</div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-gray-500 text-sm font-medium mb-2">Volunteer Applications</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats?.pendingVolunteers || 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Pending approval</div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-gray-500 text-sm font-medium mb-2">Total Redemptions</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats?.totalRedemptions || 0 }}</div>
        <div class="text-sm text-gray-500 mt-1">Rewards claimed</div>
      </div>
    </div>

    <!-- Recent Activities & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Recent Missing Reports -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Recent Missing Reports</h2>
            <Link href="/admin/missing-reports" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              View All →
            </Link>
          </div>
          
          <div v-if="recentMissingReports && recentMissingReports.length > 0" class="space-y-4">
            <div v-for="report in recentMissingReports.slice(0, 5)" :key="report.id" 
                 class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div class="flex-1">
                <div class="font-medium text-gray-900">{{ report.missing_person_name }}</div>
                <div class="text-sm text-gray-500">{{ report.location }}</div>
                <div class="text-xs text-gray-400">{{ new Date(report.created_at).toLocaleDateString() }}</div>
              </div>
              <div class="flex items-center space-x-2">
                <span :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  report.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  report.status === 'active' ? 'bg-green-100 text-green-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ report.status }}
                </span>
                <Link :href="`/admin/missing-reports/${report.id}`" 
                      class="text-blue-600 hover:text-blue-800 text-sm">
                  View
                </Link>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <div class="text-4xl mb-2">📋</div>
            <p>No recent missing reports</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
          <div class="space-y-3">
            <Link href="/admin/missing-reports/create" 
                  class="block w-full bg-blue-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-blue-700 transition-colors">
              Create Missing Report
            </Link>
            <Link href="/admin/rewards/create" 
                  class="block w-full bg-green-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-green-700 transition-colors">
              Add New Reward
            </Link>
            <Link href="/admin/volunteers" 
                  class="block w-full bg-purple-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-purple-700 transition-colors">
              Review Volunteers
            </Link>
            <Link href="/admin/sighting-reports" 
                  class="block w-full bg-orange-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-orange-700 transition-colors">
              Review Sightings
            </Link>
          </div>
        </div>

        <!-- Recent Sightings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">Recent Sightings</h2>
            <Link href="/admin/sighting-reports" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
              View All →
            </Link>
          </div>
          
          <div v-if="recentSightings && recentSightings.length > 0" class="space-y-3">
            <div v-for="sighting in recentSightings.slice(0, 3)" :key="sighting.id" 
                 class="p-3 bg-gray-50 rounded-lg">
              <div class="font-medium text-gray-900 text-sm">{{ sighting.missing_person_name }}</div>
              <div class="text-xs text-gray-500">{{ sighting.location }}</div>
              <div class="text-xs text-gray-400">{{ new Date(sighting.created_at).toLocaleDateString() }}</div>
            </div>
          </div>
          
          <div v-else class="text-center py-4 text-gray-500">
            <div class="text-2xl mb-1">👁️</div>
            <p class="text-sm">No recent sightings</p>
          </div>
        </div>
      </div>
    </div>

    <!-- System Status -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4">System Status</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span class="text-sm text-gray-700">Platform Online</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span class="text-sm text-gray-700">Database Connected</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span class="text-sm text-gray-700">File Storage Active</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span class="text-sm text-gray-700">Email Service Ready</span>
        </div>
      </div>
    </div>
  </div>
</template>


