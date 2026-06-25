<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { LayoutGrid, Package, Download, ShoppingCart, Sun, Moon } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'

const route = useRoute()
const ui = useUiStore()
const isDark = computed(() => ui.theme === 'dark')

const navItems = [
  { to: '/', label: 'Visão geral', icon: LayoutGrid },
  { to: '/produtos', label: 'Produtos', icon: Package },
  { to: '/compras', label: 'Compras', icon: Download },
  { to: '/vendas', label: 'Vendas', icon: ShoppingCart },
] as const

function isActive(to: string) {
  return to === '/' ? route.path === '/' : route.path.startsWith(to)
}
</script>

<template>
  <!-- Backdrop (apenas mobile, quando aberta) -->
  <div
    v-if="ui.sidebarOpen"
    class="fixed inset-0 z-[54] bg-[rgba(10,18,12,0.38)] lg:hidden"
    @click="ui.closeSidebar()"
  />

  <aside
    class="fixed left-0 top-0 z-[55] flex h-screen w-[248px] flex-shrink-0 flex-col border-r border-line bg-surface transition-transform duration-200 lg:sticky lg:z-auto lg:translate-x-0"
    :class="ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
  >
    <!-- Marca -->
    <div class="flex items-center gap-[11px] px-5 pb-[18px] pt-[22px]">
      <div
        class="flex h-[34px] w-[34px] flex-shrink-0 items-center justify-center rounded bg-accent text-accent-ink"
      >
        <Package class="h-[18px] w-[18px]" :stroke-width="1.7" />
      </div>
      <div class="leading-tight">
        <div class="text-[16px] font-semibold tracking-[-0.01em]">Estoque</div>
        <div class="text-[11px] font-medium uppercase tracking-[0.04em] text-faint">
          ERP · Controle
        </div>
      </div>
    </div>

    <!-- Navegação -->
    <nav class="flex flex-col gap-0.5 px-3 py-1.5">
      <RouterLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-[11px] rounded px-[11px] py-[9px] text-[14px] font-medium transition-colors"
        :class="
          isActive(item.to)
            ? 'bg-accent-soft font-semibold text-accent-strong'
            : 'text-muted hover:bg-surface-2 hover:text-ink'
        "
        @click="ui.closeSidebar()"
      >
        <component :is="item.icon" class="h-[18px] w-[18px]" :stroke-width="1.7" />
        {{ item.label }}
      </RouterLink>
    </nav>

    <!-- Rodapé: tema -->
    <div class="mt-auto border-t border-line px-4 py-3.5">
      <button
        class="flex w-full items-center gap-2.5 rounded px-[11px] py-[9px] text-left text-[13.5px] font-medium text-muted transition-colors hover:bg-surface-2 hover:text-ink"
        @click="ui.toggleTheme()"
      >
        <Sun v-if="isDark" class="h-[17px] w-[17px]" :stroke-width="1.7" />
        <Moon v-else class="h-[17px] w-[17px]" :stroke-width="1.7" />
        {{ isDark ? 'Tema claro' : 'Tema escuro' }}
      </button>
    </div>
  </aside>
</template>
