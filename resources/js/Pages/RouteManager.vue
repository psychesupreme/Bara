<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-heading font-extrabold text-2xl text-white tracking-tight">Route Planning & Journey Cycles</h2>
          <p class="text-sm text-gray-400">Manage field sales journey plans, visit frequencies, and guided call step sequences.</p>
        </div>

        <button class="px-4 py-2 rounded-xl btn-gradient text-sm font-medium text-white shadow-lg">
          + Create Route Plan
        </button>
      </div>

      <!-- Route Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="route in routes" :key="route.id" class="glass-panel p-6 space-y-4">
          <div class="flex justify-between items-start">
            <div>
              <div class="font-mono text-xs text-indigo-400 font-semibold">{{ route.code }}</div>
              <h3 class="font-heading font-bold text-lg text-white mt-0.5">{{ route.name }}</h3>
              <p class="text-xs text-gray-400 mt-1">Assigned Rep: <span class="text-gray-200 font-medium">{{ route.rep }}</span></p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-medium">
              Active Schedule
            </span>
          </div>

          <!-- Visit Days Badges -->
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">Visit Days:</span>
            <span v-for="day in route.days" :key="day" class="text-xs px-2 py-0.5 rounded bg-white/10 text-indigo-300 font-mono font-semibold">
              {{ day }}
            </span>
          </div>

          <!-- Route Stops List -->
          <div class="space-y-2 border-t border-white/10 pt-4">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Ordered Call Cycle Stops ({{ route.stops.length }})</div>
            <div v-for="(stop, idx) in route.stops" :key="idx" class="p-3 rounded-lg bg-white/5 border border-white/5 flex justify-between items-center text-xs">
              <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-indigo-500/30 text-indigo-300 font-bold flex items-center justify-center text-[10px]">
                  {{ idx + 1 }}
                </span>
                <span class="font-medium text-white">{{ stop.customer }}</span>
              </div>
              <span class="text-gray-400 font-mono">{{ stop.stepsCount }} Call Steps</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const routes = ref([
  {
    id: 1,
    code: 'RTE-NRB-CTL-01',
    name: 'Nairobi Central Mon/Wed/Fri Route',
    rep: 'Central Field Rep (CBD)',
    days: ['Mon', 'Wed', 'Fri'],
    stops: [
      { customer: 'CBD Convenience Store', stepsCount: 7 },
      { customer: 'Naivas Supermarket CBD Branch', stepsCount: 7 },
    ],
  },
  {
    id: 2,
    code: 'RTE-NRB-WST-01',
    name: 'Nairobi West Tue/Thu Route',
    rep: 'Westlands Field Rep',
    days: ['Tue', 'Thu'],
    stops: [
      { customer: 'Sarit Center Mart', stepsCount: 7 },
      { customer: 'Yaya Center MiniMart', stepsCount: 7 },
    ],
  },
]);
</script>
