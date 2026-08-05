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
          <span v-if="isPolling" class="text-xs px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20 font-mono animate-pulse">
            🔄 Auto-Polling (5s)
          </span>
        </div>
      </div>

      <!-- Exception Notification Toast -->
      <div v-if="toastMessage" class="p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between">
        <span>{{ toastMessage }}</span>
        <button @click="toastMessage = ''" class="text-emerald-400 font-bold text-xs uppercase">Dismiss</button>
      </div>

      <!-- Explicit Red Error Alert Modal -->
      <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-sm flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-2">
          <span class="text-lg">⚠️</span>
          <span><strong>API Approval Error:</strong> {{ errorMessage }}</span>
        </div>
        <button @click="errorMessage = ''" class="text-rose-400 font-bold text-xs uppercase px-2 py-1 bg-rose-500/20 rounded hover:bg-rose-500/40">Dismiss</button>
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

          <!-- Approve / Reject Action Bar with Loading Spinners -->
          <div class="flex items-center gap-3 pt-1">
            <button @click="approveException(item)" :disabled="item.processing" class="flex-1 py-2.5 rounded-xl bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 border border-emerald-500/40 text-xs font-bold transition flex items-center justify-center gap-1 disabled:opacity-50">
              <span v-if="item.processing" class="w-3.5 h-3.5 border-2 border-emerald-300 border-t-transparent rounded-full animate-spin"></span>
              <span v-else>✓ Approve Request</span>
            </button>
            <button @click="rejectException(item)" :disabled="item.processing" class="flex-1 py-2.5 rounded-xl bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 border border-rose-500/40 text-xs font-bold transition flex items-center justify-center gap-1 disabled:opacity-50">
              <span v-if="item.processing" class="w-3.5 h-3.5 border-2 border-rose-300 border-t-transparent rounded-full animate-spin"></span>
              <span v-else>✕ Reject Request</span>
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    exceptions: {
        type: Array,
        default: () => [],
    },
});

const filter = ref('all');
const toastMessage = ref('');
const errorMessage = ref('');
const isPolling = ref(false);
let pollingTimer = null;

const localExceptions = ref([...props.exceptions]);

watch(() => props.exceptions, (newVal) => {
    localExceptions.value = [...newVal];
}, { deep: true });

const filteredExceptions = computed(() => {
  if (filter.value === 'all') return localExceptions.value;
  return localExceptions.value.filter(e => e.type === filter.value);
});

// Refactored to native Inertia router.post for automatic CSRF token injection
const approveException = (item) => {
  item.processing = true;
  errorMessage.value = '';

  router.post(`/exceptions/${item.id}/approve`, {
    notes: 'Approved via Web Admin Supervisory Queue',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toastMessage.value = `Approved exception ${item.code} for ${item.customer}.`;
    },
    onError: (errors) => {
      errorMessage.value = Object.values(errors).join('; ') || 'Approval failed';
    },
    onFinish: () => {
      item.processing = false;
    },
  });
};

const rejectException = (item) => {
  item.processing = true;
  errorMessage.value = '';

  router.post(`/exceptions/${item.id}/reject`, {
    notes: 'Rejected via Web Admin Supervisory Queue',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toastMessage.value = `Rejected exception ${item.code} for ${item.customer}.`;
    },
    onError: (errors) => {
      errorMessage.value = Object.values(errors).join('; ') || 'Rejection failed';
    },
    onFinish: () => {
      item.processing = false;
    },
  });
};

const fetchExceptions = () => {
  router.reload({ only: ['exceptions'], preserveScroll: true, preserveState: true });
};

onMounted(() => {
  // Listen to Reverb WebSockets on port 8080 (exception-stream channel)
  if (typeof window !== 'undefined' && window.Echo) {
    try {
      window.Echo.channel('exception-stream')
        .listen('ExceptionRaisedEvent', (e) => {
          const newException = {
            id: e.id || Date.now(),
            code: e.code || 'EXP-AUTO-001',
            type: e.exception_type || 'geofence',
            rep: e.rep_name || 'Central Field Rep',
            customer: e.customer_name || 'Nairobi Outlet',
            reason: e.reason || 'Supervisory Override Request',
            description: `Live mobile override request received. Timestamp: ${e.timestamp}`,
            severity: e.severity || 'high',
            timestamp: e.timestamp || new Date().toLocaleTimeString(),
            processing: false,
          };

          if (!localExceptions.value.some(item => item.code === newException.code)) {
            localExceptions.value.unshift(newException);
            toastMessage.value = `⚠️ New Supervisory Exception Raised: ${newException.code} (${newException.customer})`;
          }
        })
        .listen('ExceptionResolvedEvent', (e) => {
          localExceptions.value = localExceptions.value.filter(item => String(item.id) !== String(e.id) && item.code !== e.code);
        });
    } catch (_) {}
  }

  // Fallback Polling Guard
  pollingTimer = setInterval(() => {
    fetchExceptions();
    isPolling.value = true;
  }, 5000);
});

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer);
  if (typeof window !== 'undefined' && window.Echo) {
      window.Echo.leave('exception-stream');
  }
});
</script>
