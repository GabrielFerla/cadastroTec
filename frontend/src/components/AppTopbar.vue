<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Search, Plus, Menu } from 'lucide-vue-next'
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
    class="sticky top-0 z-20 border-b border-line bg-surface px-4 py-4 lg:px-[30px] lg:py-5"
  >
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-5">
      <!-- Título + hamburger (mobile) -->
      <div class="flex items-center gap-3">
        <button
          class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded text-muted transition-colors hover:bg-surface-2 hover:text-ink lg:hidden"
          aria-label="Abrir menu"
          @click="ui.openSidebar()"
        >
          <Menu class="h-[20px] w-[20px]" :stroke-width="1.8" />
        </button>
        <div class="min-w-0 leading-tight">
          <div class="truncate text-[20px] font-semibold tracking-[-0.015em]">{{ title }}</div>
          <div class="truncate text-[13px] text-muted">{{ subtitle }}</div>
        </div>
      </div>

      <!-- Ações: busca + botão primário -->
      <div v-if="showSearch || primaryLabel" class="flex items-center gap-3">
        <div
          v-if="showSearch"
          class="flex flex-1 items-center gap-2 rounded border border-line bg-bg px-3 py-2 lg:w-[240px] lg:flex-none"
        >
          <Search class="h-4 w-4 flex-shrink-0 text-faint" :stroke-width="1.8" />
          <input
            v-model="ui.query"
            :placeholder="searchPlaceholder"
            class="w-full min-w-0 bg-transparent text-[13.5px] text-ink placeholder:text-faint"
          />
        </div>

        <button
          v-if="primaryLabel"
          class="flex flex-shrink-0 items-center gap-[7px] rounded bg-accent px-[15px] py-[9px] text-[14px] font-semibold text-accent-ink shadow-sm transition-[filter] hover:brightness-[1.06]"
          @click="emit('primary')"
        >
          <Plus class="h-[17px] w-[17px]" :stroke-width="2" />
          <span class="whitespace-nowrap">{{ primaryLabel }}</span>
        </button>
      </div>
    </div>
  </header>
</template>
