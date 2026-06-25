<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Search, Plus } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'

defineProps<{
  showSearch?: boolean
  searchPlaceholder?: string
  primaryLabel?: string
}>()
const emit = defineEmits<{ primary: [] }>()

const route = useRoute()
const ui = useUiStore()
const title = computed(() => route.meta.title ?? '')
const subtitle = computed(() => route.meta.sub ?? '')
</script>

<template>
  <header
    class="sticky top-0 z-20 flex items-center justify-between gap-5 border-b border-line bg-surface px-[30px] py-5"
  >
    <div class="leading-tight">
      <div class="text-[20px] font-semibold tracking-[-0.015em]">{{ title }}</div>
      <div class="mt-0.5 text-[13px] text-muted">{{ subtitle }}</div>
    </div>

    <div class="flex items-center gap-3">
      <div
        v-if="showSearch"
        class="flex w-[240px] items-center gap-2 rounded border border-line bg-bg px-3 py-2"
      >
        <Search class="h-4 w-4 flex-shrink-0 text-faint" :stroke-width="1.8" />
        <input
          v-model="ui.query"
          :placeholder="searchPlaceholder"
          class="w-full bg-transparent text-[13.5px] text-ink placeholder:text-faint"
        />
      </div>

      <button
        v-if="primaryLabel"
        class="flex items-center gap-[7px] rounded bg-accent px-[15px] py-[9px] text-[14px] font-semibold text-accent-ink shadow-sm transition-[filter] hover:brightness-[1.06]"
        @click="emit('primary')"
      >
        <Plus class="h-[17px] w-[17px]" :stroke-width="2" />
        {{ primaryLabel }}
      </button>
    </div>
  </header>
</template>
