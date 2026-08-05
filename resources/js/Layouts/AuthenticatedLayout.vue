<template>
  <div class="min-h-screen bg-[#0B0F19] text-gray-100 flex flex-col md:flex-row font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-72 bg-[#0F172A]/85 backdrop-blur-2xl border-r border-white/10 flex flex-col justify-between shrink-0 shadow-2xl z-20">
      <div>
        <!-- Brand Header -->
        <div class="p-5 flex items-center justify-between border-b border-white/10 bg-white/[0.02]">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center font-heading font-extrabold text-white text-xl shadow-lg shadow-indigo-500/30">
              B
            </div>
            <div>
              <h1 class="font-heading font-extrabold text-lg text-white tracking-wide leading-none">BARA Platform</h1>
              <div class="flex items-center gap-1.5 mt-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] font-mono text-emerald-400 font-semibold tracking-wider uppercase">Enterprise v1.4</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Categorized Navigation Hierarchy -->
        <div class="p-4 space-y-6">
          <!-- Section 1: Field Operations -->
          <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold tracking-widest text-indigo-400 uppercase">
              Field Operations
            </div>
            <nav class="space-y-1">
              <Link
                href="/dashboard"
                :class="[
                  'flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group',
                  $page.url === '/dashboard' || $page.url === '/'
                    ? 'bg-gradient-to-r from-indigo-600/30 to-purple-600/20 text-indigo-300 border border-indigo-500/40 shadow-lg shadow-indigo-500/10 font-bold'
                    : 'text-gray-400 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
              >
                <div class="flex items-center gap-3">
                  <svg class="w-4 h-4 text-indigo-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                  <span>Dispatch & GPS Map</span>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">Live</span>
              </Link>
            </nav>
          </div>

          <!-- Section 2: Customer 360 & Commercial SFA -->
          <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold tracking-widest text-pink-400 uppercase">
              Customer 360 & SFA
            </div>
            <nav class="space-y-1">
              <Link
                href="/customers-360"
                :class="[
                  'flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group',
                  $page.url.startsWith('/customers-360')
                    ? 'bg-gradient-to-r from-pink-600/30 to-purple-600/20 text-pink-300 border border-pink-500/40 shadow-lg shadow-pink-500/10 font-bold'
                    : 'text-gray-400 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
              >
                <div class="flex items-center gap-3">
                  <svg class="w-4 h-4 text-pink-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                  <span>Customer 360 & Waterfall</span>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-pink-500/10 text-pink-300 border border-pink-500/20">7-Tier</span>
              </Link>
            </nav>
          </div>

          <!-- Section 3: Supervisory & Approvals -->
          <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold tracking-widest text-amber-400 uppercase">
              Supervisory Governance
            </div>
            <nav class="space-y-1">
              <Link
                href="/supervisory-queue"
                :class="[
                  'flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group',
                  $page.url.startsWith('/supervisory-queue') || $page.url.startsWith('/exception-queue')
                    ? 'bg-gradient-to-r from-amber-600/30 to-orange-600/20 text-amber-300 border border-amber-500/40 shadow-lg shadow-amber-500/10 font-bold'
                    : 'text-gray-400 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
              >
                <div class="flex items-center gap-3">
                  <svg class="w-4 h-4 text-amber-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  <span>Supervisory Queue</span>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">Overrides</span>
              </Link>
            </nav>
          </div>

          <!-- Section 4: Territory & Journey Plans -->
          <div>
            <div class="px-3 mb-2 text-[10px] font-mono font-bold tracking-widest text-emerald-400 uppercase">
              Routes & Operations
            </div>
            <nav class="space-y-1">
              <Link
                href="/route-manager"
                :class="[
                  'flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group',
                  $page.url.startsWith('/route-manager')
                    ? 'bg-gradient-to-r from-emerald-600/30 to-teal-600/20 text-emerald-300 border border-emerald-500/40 shadow-lg shadow-emerald-500/10 font-bold'
                    : 'text-gray-400 hover:text-white hover:bg-white/5 border border-transparent'
                ]"
              >
                <div class="flex items-center gap-3">
                  <svg class="w-4 h-4 text-emerald-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  <span>Routes & Journey Plans</span>
                </div>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Cycles</span>
              </Link>
            </nav>
          </div>
        </div>
      </div>

      <!-- User Footer Profile Card -->
      <div class="p-4 border-t border-white/10 bg-white/[0.01]">
        <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/10">
          <div class="flex items-center gap-3 truncate">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 border border-indigo-400/40 flex items-center justify-center font-extrabold text-white text-xs shadow">
              {{ userInitials }}
            </div>
            <div class="truncate">
              <div class="text-xs font-bold text-white truncate">{{ userName }}</div>
              <div class="text-[11px] text-indigo-300 font-mono capitalize">{{ userRole }}</div>
            </div>
          </div>
        </div>

        <Link
          href="/logout"
          method="post"
          as="button"
          class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 text-xs font-bold border border-rose-500/30 transition shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span>Sign Out Session</span>
        </Link>
      </div>
    </aside>

    <!-- Main Content Workspace -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      <!-- Top Navbar Header with Global Scope Selectors -->
      <header class="h-16 bg-[#0F172A]/75 backdrop-blur-xl border-b border-white/10 px-6 flex items-center justify-between shrink-0 sticky top-0 z-10 shadow-lg">
        <!-- Global Tenant & Commercial Scope Selectors -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Tenant Dropdown Selector -->
          <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
            <span class="text-[11px] text-gray-400 font-mono font-semibold uppercase">Tenant:</span>
            <select
              v-model="selectedTenant"
              class="bg-transparent text-xs font-bold text-indigo-300 focus:outline-none cursor-pointer"
            >
              <option value="nairobi_central" class="bg-gray-900 text-white">Nairobi Central (Main)</option>
              <option value="mombasa_coastal" class="bg-gray-900 text-white">Mombasa Coastal Hub</option>
              <option value="kisumu_western" class="bg-gray-900 text-white">Kisumu Western Branch</option>
            </select>
          </div>

          <!-- Commercial Scope Selector -->
          <div class="flex items-center gap-2 bg-white/5 px-3 py-1.5 rounded-xl border border-white/10">
            <span class="text-[11px] text-gray-400 font-mono font-semibold uppercase">Territory Scope:</span>
            <select
              v-model="selectedScope"
              class="bg-transparent text-xs font-bold text-pink-300 focus:outline-none cursor-pointer"
            >
              <option value="nairobi_region" class="bg-gray-900 text-white">Nairobi Region (All Nodes)</option>
              <option value="cbd_zone" class="bg-gray-900 text-white">CBD Zone (Node #101)</option>
              <option value="westlands_zone" class="bg-gray-900 text-white">Westlands Zone (Node #102)</option>
              <option value="kasarani_zone" class="bg-gray-900 text-white">Kasarani Zone (Node #103)</option>
            </select>
          </div>
        </div>

        <!-- System & WebSocket Real-time Status -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-xs text-emerald-300 font-mono font-semibold">Reverb WS (Port 8080)</span>
          </div>
        </div>
      </header>

      <!-- Page Content Slot -->
      <div class="p-6 space-y-6 flex-1">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const selectedTenant = ref('nairobi_central');
const selectedScope = ref('nairobi_region');

const userName = computed(() => page.props.auth?.user?.name || 'Nairobi System Supervisor');
const userRole = computed(() => page.props.auth?.user?.role || 'Supervisor');
const userInitials = computed(() => {
  const name = userName.value;
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});
</script>
