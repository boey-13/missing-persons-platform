<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  user: Object,
  stats: Object,
  recentMissingReports: Array,
  userPoints: Number,
  userRewards: Array,
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <div class="mb-8">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold">Welcome back, {{ user?.name }}!</h1>
                                <p class="text-blue-100 mt-1">Thank you for helping make our community safer.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-gray-500 text-sm font-medium">Your Points</div>
                                <div class="text-3xl font-bold text-gray-900">{{ userPoints || 0 }}</div>
                                <div class="text-green-600 text-sm mt-1">Available points</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-gray-500 text-sm font-medium">Rewards Claimed</div>
                                <div class="text-3xl font-bold text-gray-900">{{ userRewards?.length || 0 }}</div>
                                <div class="text-purple-600 text-sm mt-1">Total rewards</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <div class="text-gray-500 text-sm font-medium">Reports Filed</div>
                                <div class="text-3xl font-bold text-gray-900">{{ stats?.userReports || 0 }}</div>
                                <div class="text-blue-600 text-sm mt-1">Your contributions</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Link href="/missing-persons/report" 
                                      class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    <div>
                                        <div class="font-medium text-gray-900">Report Missing Person</div>
                                        <div class="text-sm text-gray-500">Help find someone</div>
                                    </div>
                                </Link>

                                <Link href="/sighting-reports/report" 
                                      class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                    <div>
                                        <div class="font-medium text-gray-900">Report Sighting</div>
                                        <div class="text-sm text-gray-500">Share what you saw</div>
                                    </div>
                                </Link>

                                <Link href="/rewards" 
                                      class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                    <div>
                                        <div class="font-medium text-gray-900">Browse Rewards</div>
                                        <div class="text-sm text-gray-500">Redeem your points</div>
                                    </div>
                                </Link>

                                <Link href="/volunteer" 
                                      class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                                    <div>
                                        <div class="font-medium text-gray-900">Become Volunteer</div>
                                        <div class="text-sm text-gray-500">Join our community</div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Missing Reports -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Recent Cases</h2>
                            <Link href="/missing-persons" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All →
                            </Link>
                        </div>
                        
                        <div v-if="recentMissingReports && recentMissingReports.length > 0" class="space-y-3">
                            <div v-for="report in recentMissingReports.slice(0, 3)" :key="report.id" 
                                 class="p-3 bg-gray-50 rounded-lg">
                                <div class="font-medium text-gray-900 text-sm">{{ report.missing_person_name }}</div>
                                <div class="text-xs text-gray-500">{{ report.location }}</div>
                                <div class="text-xs text-gray-400">{{ new Date(report.created_at).toLocaleDateString() }}</div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-4 text-gray-500">
                            <p class="text-sm">No recent cases</p>
                        </div>
                    </div>
                </div>

                <!-- User Rewards Section -->
                <div v-if="userRewards && userRewards.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Your Recent Rewards</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="reward in userRewards.slice(0, 3)" :key="reward.id" 
                             class="p-4 bg-gray-50 rounded-lg">
                            <div class="font-medium text-gray-900">{{ reward.reward_name }}</div>
                            <div class="text-sm text-gray-500">Redeemed on {{ new Date(reward.created_at).toLocaleDateString() }}</div>
                            <div class="text-xs text-gray-400">Voucher: {{ reward.voucher_code }}</div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <Link href="/rewards/my-vouchers" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Your Rewards →
                        </Link>
                    </div>
                </div>

                <!-- Community Impact -->
                <div class="mt-8 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Your Community Impact</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ stats?.totalCases || 0 }}</div>
                            <div class="text-sm text-gray-600">Total Cases Active</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-blue-600">{{ stats?.totalSightings || 0 }}</div>
                            <div class="text-sm text-gray-600">Sightings Reported</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-purple-600">{{ stats?.totalUsers || 0 }}</div>
                            <div class="text-sm text-gray-600">Community Members</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
