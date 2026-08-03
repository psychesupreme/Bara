<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-heading font-extrabold text-2xl text-white tracking-tight">Supervisory Exception Review Queue</h2>
          <p class="text-sm text-gray-400">Review and authorize off-geofence completions, credit limit overrides, and MSL compliance exceptions.</p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 font-mono border border-amber-500/30">
            {{ exceptions.length }} Pending Approvals
          </span>
        </div>
      </div>

      <!-- Exception Queue Table Panel -->
      <div class="glass-panel overflow-hidden border border-white/10">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-white/5 border-b border-white/10 text-xs text-gray-400 font-heading uppercase tracking-wider">
              <tr>
                <th class="p-4">Exception Code / Type</th>
                <th class="p-4">Sales Rep / User</th>
                <th class="p-4">Customer Outlet</th>
                <th class="p-4">Reason / Severity</th>
                <th class="p-4">Timestamp</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="item in exceptions" :key="item.id" class="hover:bg-white/5 transition">
                <td class="p-4">
                  <div class="font-mono font-semibold text-indigo-300">{{ item.code }}</div>
                  <div class="text-xs text-gray-400 capitalize">{{ item.type.replace('_', ' ') }}</div>
                </td>
                <td class="p-4 font-medium text-white">{{ item.rep }}</td>
                <td class="p-4 text-gray-300">{{ item.customer }}</td>
                <td class="p-4">
                  <span :class="['text-xs px-2.5 py-1 rounded-full border font-medium', item.severity === 'high' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30']">
                    {{ item.reason }}
                  </span>
                </td>
                <td class="p-4 font-mono text-xs text-gray-400">{{ item.timestamp }}</td>
                <td class="p-4 text-right space-x-2">
                  <button @click="approveException(item)" class="px-3 py-1.5 rounded-lg bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 border border-emerald-500/40 text-xs font-semibold transition">
                    Approve
                  </button>
                  <button @click="rejectException(item)" class="px-3 py-1.5 rounded-lg bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 border border-rose-500/40 text-xs font-semibold transition">
                    Reject
                  </button>
                </td>
              </tr>

              <tr v-if="exceptions.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-400 font-medium">
                  🎉 All supervisory exceptions have been reviewed and resolved!
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const exceptions = ref([
  {
    id: 1,
    code: 'EXP-GEOFENCE-001',
    type: 'off_geofence',
    rep: 'Central Field Rep (CBD)',
    customer: 'CBD Convenience Store',
    reason: 'Off-Geofence (180m distance)',
    severity: 'medium',
    timestamp: '2026-07-30 10:15 AM',
  },
  {
    id: 2,
    code: 'EXP-CREDIT-004',
    type: 'credit_override',
    rep: 'Westlands Field Rep',
    customer: 'Sarit Center Mart',
    reason: 'Credit Limit Exceeded (+ KES 15,000)',
    severity: 'high',
    timestamp: '2026-07-30 09:45 AM',
  },
]);

const approveException = (item) => {
  exceptions.value = exceptions.value.filter(e => e.id !== item.id);
};

const rejectException = (item) => {
  exceptions.value = exceptions.value.filter(e => e.id !== item.id);
};
</script>
