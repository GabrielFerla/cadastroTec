<script setup lang="ts">
defineProps<{
  label: string
  value: string
  sub?: string
  valueClass?: string
}>()
</script>

<template>
  <div class="rounded-card border border-line bg-surface px-5 py-[18px] shadow-sm">
    <div class="text-[11.5px] font-semibold uppercase tracking-[0.05em] text-muted">
      {{ label }}
    </div>
    <div class="mt-[9px] flex items-center gap-2">
      <div class="text-[27px] font-semibold tabular-nums tracking-[-0.02em]" :class="valueClass">
        {{ value }}
      </div>
      <slot name="badge" />
    </div>
    <div v-if="sub" class="mt-[5px] text-[12.5px] text-faint">{{ sub }}</div>
  </div>
</template>
