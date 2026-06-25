<script setup lang="ts">
import { Check } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
</script>

<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="ui.toast"
        class="pointer-events-none fixed inset-x-0 bottom-6 z-[60] flex justify-center"
      >
        <div
          class="flex items-center gap-[9px] rounded-[10px] bg-ink px-[18px] py-[11px] text-[13.5px] font-medium text-bg shadow-lg"
        >
          <Check class="h-4 w-4 flex-shrink-0 text-accent" :stroke-width="2.1" />
          {{ ui.toast }}
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toast-enter-active {
  animation: toastIn 0.3s cubic-bezier(0.22, 0.8, 0.3, 1);
}
.toast-leave-active {
  transition: opacity 0.2s ease;
}
.toast-leave-to {
  opacity: 0;
}
@keyframes toastIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
