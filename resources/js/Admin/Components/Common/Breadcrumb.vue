<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

const props = defineProps({
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  autoDashboard: {
    type: Boolean,
    default: true
  }
});

const resolvedItems = computed(() => {
  if (!props.items || props.items.length === 0) return [];
  
  const firstItem = props.items[0];
  if (props.autoDashboard && firstItem?.label?.toLowerCase() !== 'dashboard') {
    return [
      { label: 'Dashboard', href: '/admin/v2/dashboard' },
      ...props.items
    ];
  }
  return props.items;
});
</script>

<template>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-mono flex-wrap">
    <template v-for="(item, index) in resolvedItems" :key="index">
      <!-- Clickable link for non-leaf items with href -->
      <Link 
        v-if="item.href && index < resolvedItems.length - 1" 
        :href="item.href" 
        class="hover:text-[#F29F67] transition-colors"
      >
        {{ item.label }}
      </Link>

      <!-- Plain label for non-leaf items without href -->
      <span 
        v-else-if="index < resolvedItems.length - 1" 
        class="text-slate-400 dark:text-slate-500"
      >
        {{ item.label }}
      </span>

      <!-- Leaf/Current active page -->
      <span 
        v-else 
        class="text-[#F29F67] font-semibold"
      >
        {{ item.label }}
      </span>

      <!-- Chevron divider -->
      <ChevronRight 
        v-if="index < resolvedItems.length - 1" 
        class="w-3 h-3 text-slate-400 dark:text-slate-500 shrink-0" 
      />
    </template>
  </nav>
</template>
