<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-heading font-extrabold text-2xl text-white tracking-tight">Customer 360 & SFA Hub</h2>
          <p class="text-sm text-gray-400">Complete commercial profile, credit limits, 7-tier price waterfall, and collection ledgers.</p>
        </div>

        <div class="flex items-center gap-3">
          <select v-model="selectedCustomer" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-sm font-medium text-white focus:outline-none focus:border-indigo-500">
            <option value="naivas" class="bg-gray-900">Naivas Supermarket CBD Branch</option>
            <option value="sarit" class="bg-gray-900">Sarit Center Mart</option>
            <option value="yaya" class="bg-gray-900">Yaya Center MiniMart</option>
            <option value="cbd_conv" class="bg-gray-900">CBD Convenience Store</option>
          </select>
        </div>
      </div>

      <!-- Customer Overview Card -->
      <div class="glass-panel p-6 border-l-4 border-l-indigo-500">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <h3 class="font-heading font-bold text-xl text-white">{{ customerData.name }}</h3>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-mono border border-indigo-500/30">
                {{ customerData.code }}
              </span>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-medium">
                {{ customerData.channel }}
              </span>
            </div>
            <p class="text-sm text-gray-300 flex items-center gap-2">
              <span>📍 {{ customerData.address }}</span>
              <span>• Tax PIN: {{ customerData.taxPin }}</span>
            </p>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-right">
              <div class="text-xs text-gray-400">Credit Limit</div>
              <div class="text-lg font-heading font-bold text-white">KES {{ customerData.creditLimit.toLocaleString() }}</div>
            </div>
            <div class="text-right pl-4 border-l border-white/10">
              <div class="text-xs text-gray-400">Outstanding Balance</div>
              <div class="text-lg font-heading font-bold text-amber-400">KES {{ customerData.balance.toLocaleString() }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 7-Tier Price Waterfall Precedence Inspector -->
      <div class="glass-panel p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-heading font-bold text-lg text-white flex items-center gap-2">
            <span>🏷️</span>
            7-Tier Price Waterfall Engine Inspector (CP-005 / CP-006)
          </h3>
          <span class="text-xs text-indigo-400 font-mono bg-indigo-500/10 px-2.5 py-1 rounded border border-indigo-500/20">Precedence Engine Active</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
          <div v-for="(tier, idx) in priceWaterfallData" :key="idx" :class="['p-3.5 rounded-xl border transition flex flex-col justify-between', tier.applied ? 'bg-indigo-600/20 border-indigo-500/50 shadow-lg shadow-indigo-500/10' : 'bg-white/5 border-white/10 opacity-60']">
            <div>
              <div class="flex justify-between items-center text-xs mb-1">
                <span class="font-mono text-gray-400 font-bold">Tier {{ tier.level }}</span>
                <span v-if="tier.applied" class="text-emerald-400 font-bold text-[10px] bg-emerald-500/20 px-1.5 py-0.5 rounded">APPLIED</span>
                <span v-else class="text-gray-500 text-[10px]">Skipped</span>
              </div>
              <div class="text-xs font-semibold text-gray-200 line-clamp-1">{{ tier.name }}</div>
            </div>

            <div class="mt-2">
              <div class="text-base font-heading font-bold text-white">{{ tier.price }}</div>
              <div class="text-[10px] font-mono text-gray-400 truncate mt-0.5">{{ tier.ruleRef }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Orders & Merchandising History Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order History -->
        <div class="glass-panel p-5 space-y-4">
          <h3 class="font-heading font-bold text-lg text-white">Order Lifecycle State Machine</h3>

          <div class="space-y-3">
            <div v-for="order in customerOrders" :key="order.id" class="p-4 rounded-xl bg-white/5 border border-white/10 flex justify-between items-center">
              <div>
                <div class="font-mono text-sm text-indigo-300 font-semibold">{{ order.number }}</div>
                <div class="text-xs text-gray-400">{{ order.date }} • {{ order.itemsCount }} Items</div>
              </div>
              <div class="text-right">
                <div class="font-heading font-bold text-white">KES {{ order.amount.toLocaleString() }}</div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-mono capitalize">
                  {{ order.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Merchandising MSL & Share of Shelf Compliance -->
        <div class="glass-panel p-5 space-y-4">
          <h3 class="font-heading font-bold text-lg text-white">Must-Stock-List (MSL) & Shelf Metrics</h3>

          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-300 font-medium">MSL Availability Score</span>
                <span class="text-emerald-400 font-bold font-mono">92%</span>
              </div>
              <div class="w-full h-2.5 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-cyan-400 rounded-full" style="width: 92%"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-300 font-medium">Share of Shelf Percentage</span>
                <span class="text-indigo-400 font-bold font-mono">48%</span>
              </div>
              <div class="w-full h-2.5 rounded-full bg-white/10 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-pink-500 rounded-full" style="width: 48%"></div>
              </div>
            </div>

            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-300">
              ✓ MSL score exceeds 70% threshold. No automated corrective action required.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customer: {
        type: Object,
        default: () => ({
            name: 'No Customer Selected',
            code: 'N/A',
            channel: 'N/A',
            address: 'N/A',
            taxPin: 'N/A',
            creditLimit: 0,
            balance: 0,
        }),
    },
    priceWaterfall: {
        type: Array,
        default: () => [],
    },
    orders: {
        type: Array,
        default: () => [],
    },
});

const selectedCustomer = ref('');

const customerData = computed(() => props.customer);
const priceWaterfallData = computed(() => props.priceWaterfall.length > 0 ? props.priceWaterfall : defaultPriceWaterfall);
const customerOrders = computed(() => props.orders.length > 0 ? props.orders : defaultOrders);

const defaultPriceWaterfall = [
  { level: 1, name: 'Base Price', price: 'KES 150.00', ruleRef: 'PR-BASE-SFJ', applied: false },
  { level: 2, name: 'Country Tier', price: 'KES 148.00', ruleRef: 'PR-CTRY-KE', applied: false },
  { level: 3, name: 'Structure Tier', price: 'KES 145.00', ruleRef: 'PR-STR-NRB', applied: false },
  { level: 4, name: 'Channel Tier', price: 'KES 140.00', ruleRef: 'PR-CHN-KA', applied: true },
  { level: 5, name: 'Volume Tier', price: 'KES 135.00', ruleRef: 'PR-VOL-TIER2', applied: true },
  { level: 6, name: 'Promo Discount', price: 'KES 130.00', ruleRef: 'PR-PROMO-NRB', applied: true },
  { level: 7, name: 'Customer Net', price: 'KES 124.80', ruleRef: 'PR-CUST-NAIVAS', applied: true },
];

const defaultOrders = [
  { id: 1, number: 'SO-NAI-2026-001', date: '2026-07-28', itemsCount: 4, amount: 45000, status: 'delivered' },
  { id: 2, number: 'SO-NAI-2026-002', date: '2026-07-25', itemsCount: 2, amount: 18000, status: 'delivered' },
];
</script>
