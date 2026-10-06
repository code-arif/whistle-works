<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { loadGoogleMaps } from '../../Utils/googleMapsLoader';
import {
  MapPin,
  Crosshair,
  RotateCcw,
  ExternalLink,
  Search,
  Sliders,
  AlertCircle,
  CheckCircle2,
  Building
} from 'lucide-vue-next';

const props = defineProps({
  location: {
    type: String,
    default: '',
  },
  address: {
    type: String,
    default: '',
  },
  latitude: {
    type: [Number, String],
    default: null,
  },
  longitude: {
    type: [Number, String],
    default: null,
  },
  apiKey: {
    type: String,
    default: '',
  },
  errorLocation: {
    type: String,
    default: '',
  },
  errorAddress: {
    type: String,
    default: '',
  },
});

const emit = defineEmits([
  'update:location',
  'update:address',
  'update:latitude',
  'update:longitude',
]);

// DOM Refs
const mapContainerRef = ref(null);
const locationInputRef = ref(null);

// State
const isMapLoaded = ref(false);
const isLoading = ref(true);
const loadError = ref(null);
const isLocating = ref(false);
const showManualCoords = ref(false);

let map = null;
let marker = null;
let autocomplete = null;
let geocoder = null;

// Default Map Center: Centered on USA or general coordinates
const DEFAULT_CENTER = { lat: 39.8283, lng: -98.5795 };
const DEFAULT_ZOOM = 4;
const PIN_ZOOM = 15;

// Executive Dark Mode Google Maps Styling Tokens (Midnight Slate / Obsidian)
const darkMapStyles = [
  { elementType: 'geometry', stylers: [{ color: '#161922' }] },
  { elementType: 'labels.text.stroke', stylers: [{ color: '#0b0f17' }] },
  { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
  {
    featureType: 'administrative.locality',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#cbd5e1' }],
  },
  {
    featureType: 'poi',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#64748b' }],
  },
  {
    featureType: 'poi.park',
    elementType: 'geometry',
    stylers: [{ color: '#1a2233' }],
  },
  {
    featureType: 'poi.park',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#475569' }],
  },
  {
    featureType: 'road',
    elementType: 'geometry',
    stylers: [{ color: '#242e42' }],
  },
  {
    featureType: 'road',
    elementType: 'geometry.stroke',
    stylers: [{ color: '#1e293b' }],
  },
  {
    featureType: 'road',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#94a3b8' }],
  },
  {
    featureType: 'road.highway',
    elementType: 'geometry',
    stylers: [{ color: '#2e3d57' }],
  },
  {
    featureType: 'road.highway',
    elementType: 'geometry.stroke',
    stylers: [{ color: '#1e293b' }],
  },
  {
    featureType: 'road.highway',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#e2e8f0' }],
  },
  {
    featureType: 'transit',
    elementType: 'geometry',
    stylers: [{ color: '#1e293b' }],
  },
  {
    featureType: 'water',
    elementType: 'geometry',
    stylers: [{ color: '#0b0f17' }],
  },
  {
    featureType: 'water',
    elementType: 'labels.text.fill',
    stylers: [{ color: '#475569' }],
  },
];

const isDarkMode = () => {
  if (typeof document === 'undefined') return false;
  return document.documentElement.classList.contains('dark');
};

const initMap = async () => {
  isLoading.value = true;
  loadError.value = null;

  try {
    const googleMaps = await loadGoogleMaps(props.apiKey);

    if (!mapContainerRef.value) {
      isLoading.value = false;
      return;
    }

    const hasCoords = props.latitude && props.longitude;
    const initialCenter = hasCoords
      ? { lat: parseFloat(props.latitude), lng: parseFloat(props.longitude) }
      : DEFAULT_CENTER;
    const initialZoom = hasCoords ? PIN_ZOOM : DEFAULT_ZOOM;

    // Initialize Map
    map = new googleMaps.Map(mapContainerRef.value, {
      center: initialCenter,
      zoom: initialZoom,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
      zoomControl: true,
      styles: isDarkMode() ? darkMapStyles : [],
    });

    geocoder = new googleMaps.Geocoder();

    // Initialize Draggable Marker
    marker = new googleMaps.Marker({
      position: initialCenter,
      map: map,
      draggable: true,
      animation: googleMaps.Animation.DROP,
      visible: !!hasCoords,
      title: 'Camp Location Pin',
    });

    // Initialize Places Autocomplete on the input
    if (locationInputRef.value) {
      autocomplete = new googleMaps.places.Autocomplete(locationInputRef.value, {
        fields: ['formatted_address', 'geometry', 'name', 'address_components'],
        types: ['geocode', 'establishment'],
      });

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
          // Fallback if user pressed Enter without selecting suggestion
          searchPlaceByText(locationInputRef.value.value);
          return;
        }

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        // Update Coordinates
        emit('update:latitude', Number(lat.toFixed(7)));
        emit('update:longitude', Number(lng.toFixed(7)));

        // Update Location text
        const placeLocationName = place.name || place.formatted_address;
        emit('update:location', placeLocationName);

        // Auto-fill venue address if currently empty
        if (!props.address && place.formatted_address) {
          emit('update:address', place.formatted_address);
        }

        // Reposition Map & Marker
        map.setCenter(place.geometry.location);
        map.setZoom(PIN_ZOOM);
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);
      });
    }

    // Map Click Listener -> Places Marker and Reverse Geocodes
    map.addListener('click', (event) => {
      handleLocationSelect(event.latLng);
    });

    // Marker Drag Listener -> Updates Coordinates and Reverse Geocodes on dragend
    marker.addListener('dragend', () => {
      const pos = marker.getPosition();
      handleLocationSelect(pos);
    });

    isMapLoaded.value = true;
  } catch (err) {
    loadError.value = err.message || 'Unable to initialize Google Maps.';
    console.warn('[CampLocationPicker] Map initialization warning:', err);
  } finally {
    isLoading.value = false;
  }
};

// Handle marker positioning & reverse geocoding
const handleLocationSelect = (latLng) => {
  if (!latLng) return;

  const lat = typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat;
  const lng = typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng;

  const formattedLat = Number(lat.toFixed(7));
  const formattedLng = Number(lng.toFixed(7));

  emit('update:latitude', formattedLat);
  emit('update:longitude', formattedLng);

  if (marker) {
    marker.setPosition(latLng);
    marker.setVisible(true);
  }

  // Reverse Geocoding to fetch address/city
  if (geocoder) {
    geocoder.geocode({ location: latLng }, (results, status) => {
      if (status === 'OK' && results && results[0]) {
        const result = results[0];
        // If address is empty, set formatted address
        if (!props.address) {
          emit('update:address', result.formatted_address);
        }

        // If location is empty, extract city, state
        if (!props.location) {
          let city = '';
          let state = '';
          for (const comp of result.address_components) {
            if (comp.types.includes('locality')) city = comp.long_name;
            if (comp.types.includes('administrative_area_level_1')) state = comp.short_name;
          }
          const cityState = city && state ? `${city}, ${state}` : (city || result.formatted_address);
          emit('update:location', cityState);
        }
      }
    });
  }
};

// Search by text if user types and clicks search
const searchPlaceByText = (text) => {
  if (!text || !geocoder) return;

  geocoder.geocode({ address: text }, (results, status) => {
    if (status === 'OK' && results && results[0]) {
      const loc = results[0].geometry.location;
      map.setCenter(loc);
      map.setZoom(PIN_ZOOM);
      handleLocationSelect(loc);
    }
  });
};

// Use HTML5 Browser Geolocation
const locateMe = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }

  isLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const userLoc = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
      };

      if (map) {
        map.setCenter(userLoc);
        map.setZoom(PIN_ZOOM);
      }
      handleLocationSelect(userLoc);
      isLocating.value = false;
    },
    (err) => {
      console.warn('Geolocation failed:', err);
      isLocating.value = false;
      alert('Unable to retrieve your current location. Please allow location permissions.');
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
};

// Reset pin location
const resetLocation = () => {
  emit('update:latitude', null);
  emit('update:longitude', null);
  if (marker) {
    marker.setVisible(false);
  }
  if (map) {
    map.setCenter(DEFAULT_CENTER);
    map.setZoom(DEFAULT_ZOOM);
  }
};

// Update coordinates manually
const handleManualLatChange = (e) => {
  const val = parseFloat(e.target.value);
  if (!isNaN(val)) {
    emit('update:latitude', val);
    updateMapFromCoords(val, props.longitude);
  }
};

const handleManualLngChange = (e) => {
  const val = parseFloat(e.target.value);
  if (!isNaN(val)) {
    emit('update:longitude', val);
    updateMapFromCoords(props.latitude, val);
  }
};

const updateMapFromCoords = (lat, lng) => {
  if (!map || !marker || !lat || !lng) return;
  const pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
  map.setCenter(pos);
  map.setZoom(PIN_ZOOM);
  marker.setPosition(pos);
  marker.setVisible(true);
};

// Watch for external coordinate changes (e.g. when switching between camps in edit modal)
watch(
  () => [props.latitude, props.longitude],
  ([newLat, newLng]) => {
    if (newLat && newLng && map && marker) {
      const pos = { lat: parseFloat(newLat), lng: parseFloat(newLng) };
      map.setCenter(pos);
      map.setZoom(PIN_ZOOM);
      marker.setPosition(pos);
      marker.setVisible(true);
    } else if (!newLat && !newLng && marker) {
      marker.setVisible(false);
    }
  }
);

// Method to trigger map resize when modal opens
const refreshMap = () => {
  nextTick(() => {
    setTimeout(() => {
      if (map && window.google && window.google.maps) {
        window.google.maps.event.trigger(map, 'resize');
        if (props.latitude && props.longitude) {
          map.setCenter({ lat: parseFloat(props.latitude), lng: parseFloat(props.longitude) });
        }
      }
    }, 250);
  });
};

defineExpose({
  refreshMap,
});

onMounted(() => {
  initMap();
  // Ensure map is resized if inside modal
  setTimeout(refreshMap, 400);
});
</script>

<template>
  <div class="space-y-3">
    <!-- Row 1: Location Search & Venue Address -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
      <!-- Location City/State Input with Google Autocomplete -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Location City / State <span class="text-rose-500">*</span>
          </label>
          <span class="text-[10px] text-slate-400 dark:text-slate-500 flex items-center gap-1 font-mono">
            <Search class="w-2.5 h-2.5" />
            Places Autocomplete
          </span>
        </div>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
            <MapPin class="w-3.5 h-3.5 text-[#F29F67]" />
          </div>
          <input
            ref="locationInputRef"
            :value="location"
            @input="$emit('update:location', $event.target.value)"
            type="text"
            placeholder="Search venue or city (e.g. Austin, TX)..."
            autocomplete="off"
            class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
            required
          />
        </div>
        <p v-if="errorLocation" class="mt-1 text-[11px] text-rose-500 font-medium">
          {{ errorLocation }}
        </p>
      </div>

      <!-- Venue Full Address -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Venue Full Address
          </label>
          <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
            Street / Building
          </span>
        </div>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
            <Building class="w-3.5 h-3.5" />
          </div>
          <input
            :value="address"
            @input="$emit('update:address', $event.target.value)"
            type="text"
            placeholder="e.g. 100 Main St, Sports Complex Court #2"
            class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
          />
        </div>
        <p v-if="errorAddress" class="mt-1 text-[11px] text-rose-500 font-medium">
          {{ errorAddress }}
        </p>
      </div>
    </div>

    <!-- Map Canvas & Control Bar -->
    <div class="rounded-lg border border-slate-200 dark:border-white/[0.08] overflow-hidden bg-slate-100 dark:bg-[#151722] transition-all">
      <!-- Toolbar Header -->
      <div class="px-3 py-2 bg-slate-50 dark:bg-[#1C1F2D] border-b border-slate-200 dark:border-white/[0.08] flex flex-wrap items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200 text-[11px]">
            <MapPin class="w-3.5 h-3.5 text-[#F29F67]" />
            Interactive Pin Location
          </span>

          <!-- Coordinates Status Pill -->
          <div
            v-if="latitude && longitude"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
          >
            <CheckCircle2 class="w-2.5 h-2.5" />
            <span>{{ Number(latitude).toFixed(4) }}, {{ Number(longitude).toFixed(4) }}</span>
          </div>
          <span v-else class="text-[10px] text-slate-400 dark:text-slate-500">
            (Click map or search to drop pin)
          </span>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-1.5">
          <!-- Locate Me Button -->
          <button
            type="button"
            @click="locateMe"
            :disabled="isLocating"
            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-white dark:bg-[#262A3B] hover:bg-slate-100 dark:hover:bg-[#32374E] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] text-[11px] font-medium transition-colors cursor-pointer shadow-2xs disabled:opacity-50"
            title="Use Current Device Location"
          >
            <Crosshair :class="['w-3 h-3 text-[#3B8FF3]', isLocating ? 'animate-spin' : '']" />
            <span>{{ isLocating ? 'Locating...' : 'Locate Me' }}</span>
          </button>

          <!-- Toggle Manual Coordinates -->
          <button
            type="button"
            @click="showManualCoords = !showManualCoords"
            :class="[
              showManualCoords ? 'bg-[#F29F67]/15 text-[#F29F67] border-[#F29F67]/40' : 'bg-white dark:bg-[#262A3B] text-slate-600 dark:text-slate-300 border-slate-200 dark:border-white/[0.08]',
              'inline-flex items-center gap-1 px-2 py-1 rounded border text-[11px] font-medium transition-colors cursor-pointer shadow-2xs'
            ]"
            title="Fine-tune latitude and longitude coordinates"
          >
            <Sliders class="w-3 h-3" />
            <span>Fine-Tune</span>
          </button>

          <!-- Reset Button -->
          <button
            v-if="latitude && longitude"
            type="button"
            @click="resetLocation"
            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-white dark:bg-[#262A3B] hover:bg-rose-500/10 hover:text-rose-500 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/[0.08] text-[11px] font-medium transition-colors cursor-pointer shadow-2xs"
            title="Clear Coordinates"
          >
            <RotateCcw class="w-2.5 h-2.5" />
            <span>Reset</span>
          </button>

          <!-- Open in Google Maps Link -->
          <a
            v-if="latitude && longitude"
            :href="`https://www.google.com/maps?q=${latitude},${longitude}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-white dark:bg-[#262A3B] hover:bg-slate-100 dark:hover:bg-[#32374E] text-[#3B8FF3] border border-slate-200 dark:border-white/[0.08] text-[11px] font-medium transition-colors shadow-2xs"
            title="Preview in Google Maps"
          >
            <ExternalLink class="w-3 h-3" />
          </a>
        </div>
      </div>

      <!-- Collapsible Manual Coordinate Inputs -->
      <div
        v-show="showManualCoords"
        class="px-3 py-2 bg-slate-100/70 dark:bg-[#181B26] border-b border-slate-200 dark:border-white/[0.08] grid grid-cols-2 gap-2 text-xs transition-all"
      >
        <div>
          <label class="block text-[10px] uppercase font-mono text-slate-500 dark:text-slate-400 mb-0.5">
            Latitude (-90 to 90)
          </label>
          <input
            :value="latitude"
            @input="handleManualLatChange"
            type="number"
            step="0.0000001"
            min="-90"
            max="90"
            placeholder="e.g. 30.2671530"
            class="w-full px-2.5 py-1 text-xs rounded bg-white dark:bg-[#212433] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-1 focus:ring-[#F29F67]"
          />
        </div>
        <div>
          <label class="block text-[10px] uppercase font-mono text-slate-500 dark:text-slate-400 mb-0.5">
            Longitude (-180 to 180)
          </label>
          <input
            :value="longitude"
            @input="handleManualLngChange"
            type="number"
            step="0.0000001"
            min="-180"
            max="180"
            placeholder="e.g. -97.7430608"
            class="w-full px-2.5 py-1 text-xs rounded bg-white dark:bg-[#212433] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-1 focus:ring-[#F29F67]"
          />
        </div>
      </div>

      <!-- Google Map Container -->
      <div class="relative w-full h-52 sm:h-64 bg-slate-200 dark:bg-[#11131C] overflow-hidden">
        <div ref="mapContainerRef" class="w-full h-full"></div>

        <!-- Loading State Overlay -->
        <div
          v-if="isLoading"
          class="absolute inset-0 bg-white/70 dark:bg-[#11131C]/80 backdrop-blur-xs flex flex-col items-center justify-center gap-2 text-xs text-slate-600 dark:text-slate-300"
        >
          <div class="w-6 h-6 border-2 border-[#F29F67] border-t-transparent rounded-full animate-spin"></div>
          <span class="font-mono text-[11px]">Loading Google Maps...</span>
        </div>

        <!-- Error Fallback Banner -->
        <div
          v-if="loadError"
          class="absolute inset-0 p-4 bg-slate-100 dark:bg-[#161824] flex flex-col items-center justify-center text-center gap-2 text-xs text-slate-600 dark:text-slate-300"
        >
          <AlertCircle class="w-6 h-6 text-amber-500" />
          <p class="font-semibold text-slate-800 dark:text-slate-200">Google Maps Preview Unavailable</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-sm">
            {{ loadError }}
          </p>
          <p class="text-[10px] text-slate-400">
            You can still enter the location and venue address manually above.
          </p>
        </div>

        <!-- Map Interaction Guide Overlay -->
        <div
          v-if="isMapLoaded && !loadError"
          class="absolute bottom-2 left-2 pointer-events-none bg-slate-900/80 dark:bg-black/80 backdrop-blur-xs px-2.5 py-1 rounded text-[10px] text-white/90 font-mono flex items-center gap-1.5 shadow-sm"
        >
          <MapPin class="w-3 h-3 text-[#F29F67]" />
          <span>Click map or drag pin to position</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Executive Google Places Autocomplete dropdown overrides */
.pac-container {
  z-index: 99999 !important;
  border-radius: 0.5rem !important;
  margin-top: 4px !important;
  font-family: inherit !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3) !important;
  border: 1px solid rgba(203, 213, 225, 0.8) !important;
  background-color: #ffffff !important;
}

.dark .pac-container {
  background-color: #1e1e2c !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}

.pac-item {
  padding: 6px 10px !important;
  font-size: 12px !important;
  cursor: pointer !important;
  border-top: 1px solid #f1f5f9 !important;
  color: #334155 !important;
}

.dark .pac-item {
  border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
  color: #cbd5e1 !important;
}

.pac-item:hover,
.pac-item-selected {
  background-color: #f8fafc !important;
}

.dark .pac-item:hover,
.dark .pac-item-selected {
  background-color: #2a2a3e !important;
}

.pac-item-query {
  font-size: 12px !important;
  color: #0f172a !important;
}

.dark .pac-item-query {
  color: #ffffff !important;
}

.pac-matched {
  font-weight: 700 !important;
  color: #f29f67 !important;
}

.pac-icon {
  margin-top: 3px !important;
}
</style>
