<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-heading font-extrabold text-2xl text-white tracking-tight">Supervisory Exception Review Queue</h2>
          <p class="text-sm text-gray-400">Review and authorize off-geofence completions, credit limit overrides, and MSL compliance exceptions.</p>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex bg-white/5 p-1 rounded-xl border border-white/10 text-xs">
            <button @click="filter = 'all'" :class="['px-3 py-1.5 rounded-lg transition font-medium', filter === 'all' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white']">All</button>
            <button @click="filter = 'credit'" :class="['px-3 py-1.5 rounded-lg transition font-medium', filter === 'credit' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white']">Credit Overrides</button>
            <button @click="filter = 'geofence'" :class="['px-3 py-1.5 rounded-lg transition font-medium', filter === 'geofence' ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white']">Geofence Alerts</button>
          </div>
          <span class="text-xs px-3 py-1.5 rounded-full bg-amber-500/20 text-amber-300 font-mono border border-amber-500/30">
            {{ filteredExceptions.length }} Pending
          </span>
        </div>
      </div>

      <!-- Exception Notification Toast -->
      <div v-if="toastMessage" class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between">
        <span>{{ toastMessage }}</span>
        <button @click="toastMessage = ''" class="text-emerald-400 font-bold text-xs uppercase">Dismiss</button>
      </div>

      <!-- Actionable Exception Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div v-for="item in filteredExceptions" :key="item.id" class="glass-panel p-5 space-y-4 border-l-4 border-l-amber-500">
          <div class="flex justify-between items-start">
            <div>
              <span class="font-mono text-xs text-indigo-300 font-bold px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20">{{ item.code }}</span>
              <h3 class="font-heading font-bold text-lg text-white mt-1">{{ item.customer }}</h3>
              <p class="text-xs text-gray-400">Submitted by: <span class="text-gray-200 font-medium">{{ item.rep }}</span></p>
            </div>
            <span :class="['text-xs px-2.5 py-1 rounded-full border font-bold uppercase tracking-wider', item.severity === 'high' ? 'bg-rose-500/20 text-rose-400 border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30']">
              {{ item.severity }} priority
            </span>
          </div>

          <div class="p-3 rounded-lg bg-white/5 border border-white/10 space-y-1 text-xs">
            <div class="text-gray-300 font-medium flex items-center justify-between">
              <span>Exception Details:</span>
              <span class="font-mono text-gray-400">{{ item.timestamp }}</span>
            </div>
            <div class="text-amber-300 font-semibold text-sm">{{ item.reason }}</div>
            <div class="text-gray-400 text-xs mt-1">{{ item.description }}</div>
          </div>

          <!-- Approve / Reject Action Bar -->
          <div class="flex items-center gap-3 pt-1">
            <button @click="approveException(item)" class="flex-1 py-2.5 rounded-xl bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 border border-emerald-500/40 text-xs font-bold transition flex items-center justify-center gap-1">
              ✓ Approve Request
            </button>
            <button @click="rejectException(item)" class="flex-1 py-2.5 rounded-xl bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 border border-rose-500/40 text-xs font-bold transition flex items-center justify-center gap-1">
              ✕ Reject Request
            </button>
          </div>
        </div>
      </div>

      <div v-if="filteredExceptions.length === 0" class="glass-panel p-12 text-center text-gray-400 font-medium">
        🎉 All supervisory exceptions have been reviewed and resolved!
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const filter = ref('all');
const toastMessage = ref('');

const exceptions = ref([
  {
    id: 1,
    code: 'EXP-CREDIT-004',
    type: 'credit',
    rep: 'Central Field Rep (CBD)',
    customer: 'Sarit Center Mart',
    reason: 'Credit Limit Exceeded (+ KES 15,000)',
    description: 'Draft Order SO-SARIT-2026-003 exceeds approved KES 150,000 credit limit threshold. Supervisor override requested.',
    severity: 'high',
    timestamp: '2026-08-03 10:45 AM',
  },
  {
    id: 2,
    code: 'EXP-GEOFENCE-001',
    type: 'geofence',
    rep: 'Central Field Rep (CBD)',
    customer: 'Kasarani Live Test Store',
    reason: 'Off-Geofence Remote Check-In (1.8 km distance)',
    description: 'Rep checked in outside standard 1500m geofence radius due to road detour. GPS: Lat -1.2002, Lng 36.8344.',
    severity: 'medium',
    timestamp: '2026-08-03 11:15 AM',
  },
]);

const filteredExceptions = computed(() => {
  if (filter.value === 'all') return exceptions.value;
  return exceptions.value.filter(e => e.type === filter.value);
});

const approveException = (item) => {
  exceptions.value = exceptions.value.filter(e => e.id !== item.id);
  toastMessage.value = `Approved exception ${item.code} for ${item.customer}. Order state machine updated.`;
};

const rejectException = (item) => {
  exceptions.value = exceptions.value.filter(e => e.id !== item.id);
  toastMessage.value = `Rejected exception ${item.code} for ${item.customer}.`;
};
</script>
