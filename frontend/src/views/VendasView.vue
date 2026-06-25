<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Trash2 } from 'lucide-vue-next'
import AppTopbar from '@/components/AppTopbar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { useFormatters } from '@/composables/useFormatters'
import { isApiError } from '@/api'
import type { BadgeTone } from '@/types'

const estoque = useEstoqueStore()
const ui = useUiStore()
const { brl, dateBR } = useFormatters()

onMounted(() => {
  if (!estoque.vendas.length) estoque.fetchVendas()
})

const rows = computed(() => {
  const q = ui.query.trim().toLowerCase()
  return estoque.vendas
    .filter((v) => !q || v.cliente.toLowerCase().includes(q))
    .map((v) => {
      const conc = v.status === 'concluida'
      return {
        id: v.id,
        cliente: v.cliente,
        itensLabel: `${v.itens.length} ${v.itens.length === 1 ? 'item' : 'itens'}`,
        dataFmt: dateBR(v.created_at),
        totalFmt: brl(v.total),
        lucroFmt: conc ? brl(v.lucro) : '—',
        concluida: conc,
        statusLabel: conc ? 'Concluída' : 'Cancelada',
        tone: (conc ? 'ok' : 'danger') as BadgeTone,
      }
    })
})

async function cancelar(id: number) {
  try {
    await estoque.cancelarVenda(id)
    ui.showToast('Venda cancelada — estoque revertido.')
  } catch (err) {
    if (isApiError(err)) ui.showToast(err.message)
  }
}
</script>

<template>
  <AppTopbar
    show-search
    search-placeholder="Buscar cliente…"
    primary-label="Nova venda"
    @primary="ui.openDrawer('nova-venda')"
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
              <th class="th text-left">Cliente</th>
              <th class="th text-left">Itens</th>
              <th class="th text-right">Data</th>
              <th class="th text-right">Total</th>
              <th class="th text-right">Lucro</th>
              <th class="th text-center">Status</th>
              <th class="w-10 border-b border-line"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="cursor-pointer border-b border-line transition-colors last:border-b-0 hover:bg-surface-2"
              :class="{ 'opacity-[0.62]': !row.concluida }"
              @click="ui.openDrawer('detalhe-venda', row.id)"
            >
              <td class="td font-medium">{{ row.cliente }}</td>
              <td class="td text-muted">{{ row.itensLabel }}</td>
              <td class="td text-right tabular-nums text-muted">{{ row.dataFmt }}</td>
              <td class="td text-right font-semibold tabular-nums">{{ row.totalFmt }}</td>
              <td class="td text-right font-medium tabular-nums text-accent-strong">
                {{ row.lucroFmt }}
              </td>
              <td class="td text-center">
                <StatusBadge :tone="row.tone" :label="row.statusLabel" />
              </td>
              <td class="td pl-0 text-right">
                <button
                  v-if="row.concluida"
                  class="ml-auto flex h-[30px] w-[30px] items-center justify-center rounded-lg text-faint transition-colors hover:bg-danger-soft hover:text-danger"
                  title="Cancelar venda"
                  @click.stop="cancelar(row.id)"
                >
                  <Trash2 class="h-4 w-4" :stroke-width="1.8" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-else
        class="rounded-card border border-line bg-surface p-[54px] text-center text-[14px] text-faint"
      >
        Nenhuma venda encontrada.
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
