<script setup lang="ts">
import type { BadgeTone } from '@/types'

const props = defineProps<{ tone: BadgeTone; label: string }>()

const TONE: Record<BadgeTone, string> = {
  ok: 'bg-accent-soft text-accent-strong',
  warn: 'bg-warn-soft text-warn',
  danger: 'bg-danger-soft text-danger',
}
</script>

<template>
  <span
    class="inline-flex items-center gap-[5px] rounded-pill px-[9px] py-[3px] text-[12px] font-semibold"
    :class="TONE[props.tone]"
  >
    {{ props.label }}
  </span>
</template>
