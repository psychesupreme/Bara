<template>
  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Executive KPI Overview Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h2 class="font-heading font-extrabold text-2xl text-white tracking-tight">Nairobi Dispatch & Telemetry Command Center</h2>
          <p class="text-sm text-gray-400">Real-time GPS tracking, active route operations, and field activity stream.</p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="clearTelemetryPins" class="px-3.5 py-2 rounded-xl bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20 text-sm font-medium transition text-rose-300 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Clear Pins
          </button>
          <button @click="refreshTelemetry" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-sm font-medium transition text-gray-300 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Refresh Telemetry
          </button>
        </div>
      </div>

      <!-- KPI Stat Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="glass-card p-5 border-l-4 border-l-indigo-500">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Today's Collections</div>
          <div class="text-2xl font-heading font-bold text-white mt-2">KES {{ todayCollections.toLocaleString() }}</div>
          <div class="text-xs text-emerald-400 mt-1 flex items-center gap-1">
            <span>↑ 14.2%</span> vs yesterday
          </div>
        </div>

        <div class="glass-card p-5 border-l-4 border-l-cyan-500">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Field Reps</div>
          <div class="text-2xl font-heading font-bold text-white mt-2">4 On Shift</div>
          <div class="text-xs text-cyan-400 mt-1 flex items-center gap-1">
            <span>CBD, Westlands & Kasarani Zones</span>
          </div>
        </div>

        <div class="glass-card p-5 border-l-4 border-l-emerald-500">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Outlets Visited</div>
          <div class="text-2xl font-heading font-bold text-white mt-2">{{ visitedOutletsCount }} / 24 Outlets</div>
          <div class="text-xs text-emerald-400 mt-1 flex items-center gap-1">
            <span>{{ Math.round((visitedOutletsCount/24)*100) }}% Journey Completion</span>
          </div>
        </div>

        <div class="glass-card p-5 border-l-4 border-l-rose-500">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Supervisory Exceptions</div>
          <div class="text-2xl font-heading font-bold text-white mt-2">{{ pendingExceptionsCount }} Active</div>
          <div class="text-xs text-rose-400 mt-1 flex items-center gap-1">
            <span>Pending Review on Queue</span>
          </div>
        </div>
      </div>

      <!-- Live Dispatch Map & Activity Console Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Leaflet Map Container -->
        <div class="lg:col-span-2 glass-panel p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-heading font-bold text-lg text-white flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-cyan-400 animate-ping shrink-0"></span>
              Live Geofence & Location Telemetry (Reverb Port 8080)
            </h3>
            <span class="text-xs text-gray-400 font-mono">Nairobi County Auto-Fit Viewport</span>
          </div>

          <!-- Leaflet Map Mount Point -->
          <div id="dispatch-map" class="h-[450px] w-full rounded-xl overflow-hidden border border-white/10 relative z-0"></div>
        </div>

        <!-- Real-Time Activity Log Stream -->
        <div class="glass-panel p-5 flex flex-col justify-between space-y-4">
          <div>
            <h3 class="font-heading font-bold text-lg text-white mb-3 flex items-center justify-between">
              Live Activity Stream
              <span class="text-xs font-mono text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">Reverb WebSockets Active</span>
            </h3>

            <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
              <div v-for="(log, idx) in activityLogs" :key="idx" class="p-3 rounded-xl bg-white/5 border border-white/10 space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-semibold text-indigo-300">{{ log.user }}</span>
                  <span class="font-mono text-gray-400">{{ log.time }}</span>
                </div>
                <div class="text-sm font-medium text-gray-200">{{ log.action }}</div>
                <div class="text-xs text-emerald-400 flex items-center gap-1 font-mono">
                  <span>📍 {{ log.location }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Action -->
          <button class="w-full py-2.5 rounded-xl btn-gradient font-medium text-sm text-white shadow-lg">
            Broadcast Targeted Notice
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import L from 'leaflet';

const props = defineProps({
  outlets: {
    type: Array,
    default: () => [],
  },
});

let mapInstance = null;
let featureGroup = null;
const repMarkersMap = {};

const todayCollections = ref(2450000);
const visitedOutletsCount = ref(18);
const pendingExceptionsCount = ref(2);

const activityLogs = ref([
  { user: 'Central Field Rep (CBD)', action: 'Check-in: Kasarani Live Test Store', location: 'Kasarani Zone (-1.2002, 36.8344)', time: '11:45:10 AM', lat: -1.2002000, lng: 36.8344000 },
  { user: 'Central Field Rep (CBD)', action: 'Check-in: Naivas Supermarket CBD Branch', location: 'CBD Zone (-1.2833, 36.8166)', time: '10:40:15 AM', lat: -1.2833300, lng: 36.8166700 },
  { user: 'Westlands Field Rep', action: 'Order Entry: KES 1,500 - Sarit Center Mart', location: 'Westlands Zone (-1.2612, 36.8041)', time: '10:35:40 AM', lat: -1.2612000, lng: 36.8041000 },
  { user: 'Nairobi Collection Officer', action: 'Payment Captured: KES 25,000 Cash', location: 'CBD Zone (-1.2845, 36.8210)', time: '10:28:10 AM', lat: -1.2845000, lng: 36.8210000 },
]);

const addTelemetryMarker = (repId, lat, lng, repName, outletName, timestamp) => {
  if (!mapInstance) return;

  const popupContent = `
    <div style="font-family: Inter, sans-serif; padding: 4px;">
      <b style="color: #818CF8; font-size: 14px;">${repName}</b><br/>
      <span style="color: #F8FAFC; font-weight: 600;">${outletName}</span><br/>
      <span style="color: #10B981; font-size: 11px;">📍 Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}</span><br/>
      <span style="color: #94A3B8; font-size: 10px;">Time: ${timestamp}</span>
    </div>
  `;

  if (repMarkersMap[repId]) {
    repMarkersMap[repId].setLatLng([lat, lng]);
    repMarkersMap[repId].setPopupContent(popupContent);
    repMarkersMap[repId].openPopup();
  } else {
    const marker = L.circleMarker([lat, lng], {
      color: '#EC4899',
      fillColor: '#F43F5E',
      fillOpacity: 0.9,
      radius: 11,
    });

    marker.bindPopup(popupContent);
    featureGroup.addLayer(marker);
    repMarkersMap[repId] = marker;
    marker.openPopup();
  }

  // Smooth flyTo animation to active rep position marker
  mapInstance.flyTo([lat, lng], 14, { animate: true, duration: 1.2 });
};

const clearTelemetryPins = () => {
  if (!mapInstance) return;
  Object.keys(repMarkersMap).forEach((id) => {
    featureGroup.removeLayer(repMarkersMap[id]);
    delete repMarkersMap[id];
  });
  if (featureGroup.getLayers().length > 0) {
    mapInstance.fitBounds(featureGroup.getBounds().pad(0.15));
  }
};

const refreshTelemetry = () => {
  const timeStr = new Date().toLocaleTimeString();
  const sampleLat = -1.2002 + (Math.random() - 0.5) * 0.01;
  const sampleLng = 36.8344 + (Math.random() - 0.5) * 0.01;
  const repId = 'REP-CBD-001';
  const repName = 'Central Field Rep (CBD)';
  const outletName = 'Kasarani Live Test Store';

  activityLogs.value.unshift({
    user: repName,
    action: `GPS Telemetry Check-in: ${outletName}`,
    location: `Lat ${sampleLat.toFixed(4)}, Lng ${sampleLng.toFixed(4)}`,
    time: timeStr,
    lat: sampleLat,
    lng: sampleLng,
  });

  addTelemetryMarker(repId, sampleLat, sampleLng, repName, outletName, timeStr);
};

onMounted(() => {
  // Initialize Leaflet Map centered on Nairobi
  mapInstance = L.map('dispatch-map').setView([-1.2500000, 36.8100000], 12);

  // Dark Tile Layer
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    maxZoom: 19,
  }).addTo(mapInstance);

  featureGroup = L.featureGroup().addTo(mapInstance);

  // Combine Inertia props outlets with default fallback list (including Kasarani Live Test Store)
  const defaultOutlets = [
    { name: 'Kasarani Live Test Store', lat: -1.2002000, lng: 36.8344000, status: 'Active Visit (Live UAT)' },
    { name: 'Naivas Supermarket CBD Branch', lat: -1.2833300, lng: 36.8166700, status: 'Active Visit' },
    { name: 'Sarit Center Mart', lat: -1.2612000, lng: 36.8041000, status: 'Order Submitted' },
    { name: 'Yaya Center MiniMart', lat: -1.2917000, lng: 36.7865000, status: 'Scheduled' },
    { name: 'CBD Convenience Store', lat: -1.2845000, lng: 36.8210000, status: 'Visit Completed' },
  ];

  const mapOutlets = (props.outlets && props.outlets.length > 0)
    ? props.outlets.map((o) => ({ name: o.name, lat: parseFloat(o.latitude), lng: parseFloat(o.longitude), status: 'Active Outlet' }))
    : defaultOutlets;

  mapOutlets.forEach((outlet) => {
    if (!isNaN(outlet.lat) && !isNaN(outlet.lng)) {
      const marker = L.circleMarker([outlet.lat, outlet.lng], {
        color: outlet.lat === -1.2002 ? '#10B981' : '#6366F1',
        fillColor: outlet.lat === -1.2002 ? '#34D399' : '#818CF8',
        fillOpacity: 0.85,
        radius: outlet.lat === -1.2002 ? 10 : 8,
      }).bindPopup(`<b>${outlet.name}</b><br><span style="color:#10B981;">Status: ${outlet.status}</span>`);

      featureGroup.addLayer(marker);
    }
  });

  // Rep Initial Live GPS Pin (Kasarani Live Test Store Position)
  addTelemetryMarker('REP-CBD-001', -1.2002000, 36.8344000, 'Central Field Rep (CBD)', 'Kasarani Live Test Store | On Shift', new Date().toLocaleTimeString());

  // Auto-fit map viewport bounds to frame Kasarani, CBD, Westlands & Kilimani cleanly
  if (featureGroup.getLayers().length > 0) {
    mapInstance.fitBounds(featureGroup.getBounds().pad(0.15));
  }

  // Real-time Reverb WebSocket Listeners (Port 8080)
  if (typeof window !== 'undefined' && window.Echo) {
    // 1. Telemetry Stream Channel
    window.Echo.channel('telemetry-stream')
      .listen('TelemetryPingEvent', (e) => {
        const timeStr = new Date().toLocaleTimeString();
        const lat = e.latitude || -1.2002;
        const lng = e.longitude || 36.8344;
        const repId = e.rep_id || 'REP-CBD-001';
        const repName = e.rep_name || 'Central Field Rep (CBD)';
        const outletName = e.outlet_name || 'Kasarani Live Test Store';

        visitedOutletsCount.value += 1;

        activityLogs.value.unshift({
          user: repName,
          action: `Live GPS Check-in: ${outletName}`,
          location: `Lat ${lat.toFixed(4)}, Lng ${lng.toFixed(4)}`,
          time: timeStr,
          lat,
          lng,
        });

        addTelemetryMarker(repId, lat, lng, repName, outletName, timeStr);
      });

    // 2. Dispatch Operations Channel
    window.Echo.channel('dispatch-channel')
      .listen('OrderCreatedEvent', (e) => {
        const timeStr = e.timestamp || new Date().toLocaleTimeString();
        activityLogs.value.unshift({
          user: 'Sales Rep',
          action: `New Order Placed: ${e.order_number} (KES ${parseFloat(e.total_amount).toLocaleString()}) - ${e.customer_name}`,
          location: `Status: ${e.status}`,
          time: timeStr,
        });
      })
      .listen('CollectionCapturedEvent', (e) => {
        const timeStr = e.timestamp || new Date().toLocaleTimeString();
        todayCollections.value += parseFloat(e.amount || 0);
        activityLogs.value.unshift({
          user: 'Collector',
          action: `Payment Captured: KES ${parseFloat(e.amount).toLocaleString()} (${e.method}) - ${e.customer_name}`,
          location: `Receipt: ${e.receipt_number}`,
          time: timeStr,
        });
      });

    // 3. Supervisory Exception Stream Channel
    window.Echo.channel('exception-stream')
      .listen('ExceptionRaisedEvent', (e) => {
        pendingExceptionsCount.value += 1;
        activityLogs.value.unshift({
          user: e.rep_name || 'Field Rep',
          action: `⚠️ Exception Raised: ${e.code} (${e.reason})`,
          location: e.customer_name,
          time: e.timestamp || new Date().toLocaleTimeString(),
        });
      })
      .listen('ExceptionResolvedEvent', (e) => {
        if (pendingExceptionsCount.value > 0) {
          pendingExceptionsCount.value -= 1;
        }
        activityLogs.value.unshift({
          user: e.reviewer_name || 'Supervisor',
          action: `✓ Exception ${e.code} ${e.status.toUpperCase()}: ${e.notes}`,
          location: 'Supervisory Queue',
          time: e.timestamp || new Date().toLocaleTimeString(),
        });
      });
  }
});
</script>
