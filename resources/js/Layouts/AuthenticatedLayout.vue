<template>
  <div class="min-h-screen bg-[#0B0F19] text-gray-100 flex flex-col md:flex-row">
    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-[#0F172A]/80 backdrop-blur-xl border-r border-white/10 flex flex-col justify-between shrink-0">
      <div>
        <!-- Brand Header -->
        <div class="p-6 flex items-center gap-3 border-b border-white/10">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-pink-500 flex items-center justify-center font-heading font-extrabold text-white text-xl shadow-lg shadow-indigo-500/30">
            B
          </div>
          <div>
            <h1 class="font-heading font-bold text-lg text-white tracking-wide">BARA Platform</h1>
            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-medium border border-emerald-500/30">
              PRD v3.0 Live
            </span>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5">
          <Link
            href="/dashboard"
            :class="['flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200', $page.url === '/dashboard' ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5']"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            Dispatch & Map
          </Link>

          <Link
            href="/customers-360"
            :class="['flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200', $page.url.startsWith('/customers-360') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5']"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Customer 360 & SFA
          </Link>

          <Link
            href="/exception-queue"
            :class="['flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200', $page.url === '/exception-queue' ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5']"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Supervisory Queue
          </Link>

          <Link
            href="/route-manager"
            :class="['flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200', $page.url === '/route-manager' ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 shadow-lg shadow-indigo-500/20' : 'text-gray-400 hover:text-gray-200 hover:bg-white/5']"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Routes & Journey Plans
          </Link>
        </nav>
      </div>

      <!-- User Footer Profile -->
      <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white/5 border border-white/10">
          <div class="w-9 h-9 rounded-full bg-indigo-500/30 border border-indigo-400/50 flex items-center justify-center font-bold text-indigo-300 text-sm">
            {{ userInitials }}
          </div>
          <div class="truncate">
            <div class="text-sm font-semibold text-white truncate">{{ userName }}</div>
            <div class="text-xs text-gray-400 capitalize">{{ userRole }}</div>
          </div>
        </div>
        
        <Link href="/logout" method="post" as="button" class="mt-4 w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-sm font-medium border border-rose-500/20 transition">
            Sign Out
        </Link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-y-auto">
      <!-- Top Navbar Header -->
      <header class="h-16 bg-[#0F172A]/60 backdrop-blur-md border-b border-white/10 px-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
          <span class="text-xs px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 font-mono">
            Tenant: Nairobi Central
          </span>
          <span class="text-xs px-3 py-1 rounded-full bg-pink-500/10 text-pink-300 border border-pink-500/30 font-mono">
            Scope: Nairobi Region
          </span>
        </div>

        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs text-emerald-400 font-mono">Reverb WS Connected</span>
          </div>
        </div>
      </header>

      <!-- Slot Content -->
      <div class="p-6 space-y-6 flex-1">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const userName = computed(() => page.props.auth?.user?.name || 'Nairobi Admin');
const userRole = computed(() => page.props.auth?.user?.role || 'Unknown');
const userInitials = computed(() => {
  const name = userName.value;
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});
</script>
