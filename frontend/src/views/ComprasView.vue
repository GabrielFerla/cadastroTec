<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import AppTopbar from '@/components/AppTopbar.vue'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { useFormatters } from '@/composables/useFormatters'

const estoque = useEstoqueStore()
const ui = useUiStore()
const { brl, dateBR } = useFormatters()

onMounted(() => {
  if (!estoque.compras.length) estoque.fetchCompras()
})

const rows = computed(() => {
  const q = ui.query.trim().toLowerCase()
  return estoque.compras
    .filter((c) => !q || c.fornecedor.toLowerCase().includes(q))
    .map((c) => ({
      id: c.id,
      fornecedor: c.fornecedor,
      itensLabel: `${c.itens.length} ${c.itens.length === 1 ? 'item' : 'itens'}`,
      dataFmt: dateBR(c.created_at),
      totalFmt: brl(c.total),
    }))
})
</script>

<template>
  <AppTopbar
    show-search
    search-placeholder="Buscar fornecedor…"
    primary-label="Nova compra"
    @primary="ui.openDrawer('nova-compra')"
  />

  <div class="px-4 pb-10 pt-5 lg:px-[30px] lg:pt-[26px]">
    <div>
      <div
        v-if="rows.length"
        class="overflow-hidden rounded-card border border-line bg-surface shadow-sm"
      >
        <div class="overflow-x-auto">
          <table class="w-full min-w-[560px] border-collapse text-[14px]">
          <thead>
            <tr class="bg-surface-2">
              <th class="th text-left">Fornecedor</th>
              <th class="th text-left">Itens</th>
              <th class="th text-right">Data</th>
              <th class="th text-right">Total</th>
              <th class="w-10 border-b border-line"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="cursor-pointer border-b border-line transition-colors last:border-b-0 hover:bg-surface-2"
              @click="ui.openDrawer('detalhe-compra', row.id)"
            >
              <td class="td font-medium">{{ row.fornecedor }}</td>
              <td class="td text-muted">{{ row.itensLabel }}</td>
              <td class="td text-right tabular-nums text-muted">{{ row.dataFmt }}</td>
              <td class="td text-right font-semibold tabular-nums">{{ row.totalFmt }}</td>
              <td class="td pl-0 text-right text-faint">
                <ChevronRight class="ml-auto h-4 w-4" :stroke-width="1.8" />
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <div
        v-else
        class="rounded-card border border-line bg-surface p-[54px] text-center text-[14px] text-faint"
      >
        Nenhuma compra encontrada.
      </div>
    </div>
  </div>
</template>

<style scoped>
.th {
  @apply border-b border-line px-[18px] py-[11px] text-[11.5px] font-semibold uppercase tracking-[0.05em] text-muted;
}
.td {
  @apply px-[18px] py-[var(--row-py)];
}
</style>
