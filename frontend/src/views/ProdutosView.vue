<script setup lang="ts">
import { computed, onMounted } from 'vue'
import AppTopbar from '@/components/AppTopbar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { useFormatters } from '@/composables/useFormatters'
import type { BadgeTone } from '@/types'

const estoque = useEstoqueStore()
const ui = useUiStore()
const { brl, int, pct } = useFormatters()

const showMargem = true

onMounted(() => {
  if (!estoque.produtos.length) estoque.fetchProdutos()
})

const rows = computed(() => {
  const q = ui.query.trim().toLowerCase()
  return estoque.produtos
    .filter((p) => !q || p.nome.toLowerCase().includes(q))
    .map((p) => {
      const isOut = p.estoque <= 0
      const isLow = p.estoque > 0 && p.estoque <= 30
      const margem = p.preco_venda > 0 ? ((p.preco_venda - p.custo_medio) / p.preco_venda) * 100 : 0
      return {
        id: p.id,
        nome: p.nome,
        custoFmt: p.custo_medio > 0 ? brl(p.custo_medio) : '—',
        precoFmt: brl(p.preco_venda),
        margemFmt: p.custo_medio > 0 ? pct(margem) : '—',
        estoqueFmt: int(p.estoque),
        tone: (isOut ? 'danger' : isLow ? 'warn' : 'ok') as BadgeTone,
        statusLabel: isOut ? 'Sem estoque' : isLow ? 'Estoque baixo' : 'Em estoque',
      }
    })
})
</script>

<template>
  <AppTopbar
    show-search
    search-placeholder="Buscar produto…"
    primary-label="Novo produto"
    @primary="ui.openDrawer('novo-produto')"
  />

  <div class="px-[30px] pb-10 pt-[26px]">
    <div class="max-w-[1180px]">
      <div
        v-if="rows.length"
        class="overflow-hidden rounded-card border border-line bg-surface shadow-sm"
      >
        <table class="w-full border-collapse text-[14px]">
          <thead>
            <tr class="bg-surface-2">
              <th class="th text-left">Produto</th>
              <th class="th text-right">Custo médio</th>
              <th class="th text-right">Preço de venda</th>
              <th v-if="showMargem" class="th text-right">Margem</th>
              <th class="th text-right">Estoque</th>
              <th class="th text-right">Situação</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="border-b border-line transition-colors last:border-b-0 hover:bg-surface-2"
            >
              <td class="td font-medium">{{ row.nome }}</td>
              <td class="td text-right tabular-nums text-muted">{{ row.custoFmt }}</td>
              <td class="td text-right font-medium tabular-nums">{{ row.precoFmt }}</td>
              <td v-if="showMargem" class="td text-right tabular-nums text-muted">
                {{ row.margemFmt }}
              </td>
              <td class="td text-right font-semibold tabular-nums">{{ row.estoqueFmt }}</td>
              <td class="td text-right">
                <StatusBadge :tone="row.tone" :label="row.statusLabel" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-else
        class="rounded-card border border-line bg-surface p-[54px] text-center text-[14px] text-faint"
      >
        Nenhum produto encontrado.
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
