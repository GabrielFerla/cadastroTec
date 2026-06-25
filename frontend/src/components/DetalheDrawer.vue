<script setup lang="ts">
import { computed } from 'vue'
import { Trash2, XCircle } from 'lucide-vue-next'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { useFormatters } from '@/composables/useFormatters'
import { isApiError } from '@/api'
import type { CompraItem, VendaItem } from '@/types'

const props = defineProps<{ tipo: 'compra' | 'venda' }>()

const estoque = useEstoqueStore()
const ui = useUiStore()
const { brl, int } = useFormatters()

const id = computed(() => ui.drawer?.payload ?? null)
const isVenda = computed(() => props.tipo === 'venda')

const compra = computed(() =>
  props.tipo === 'compra' ? estoque.compras.find((c) => c.id === id.value) ?? null : null,
)
const venda = computed(() =>
  props.tipo === 'venda' ? estoque.vendas.find((v) => v.id === id.value) ?? null : null,
)

const total = computed(() => compra.value?.total ?? venda.value?.total ?? 0)
const itens = computed<(CompraItem | VendaItem)[]>(
  () => compra.value?.itens ?? venda.value?.itens ?? [],
)
const isCancelada = computed(() => venda.value?.status === 'cancelada')
const canCancel = computed(() => venda.value?.status === 'concluida')
const lucroFmt = computed(() =>
  venda.value && venda.value.status === 'concluida' ? brl(venda.value.lucro) : '—',
)

function itemDetail(it: CompraItem | VendaItem): string {
  const base = `${int(it.quantidade)} × ${brl(it.preco_unitario)}`
  if (isVenda.value && 'custo_unitario' in it) return `${base} · custo ${brl(it.custo_unitario)}`
  return base
}
function itemSubtotal(it: CompraItem | VendaItem): number {
  return it.quantidade * it.preco_unitario
}

async function cancelar() {
  if (!venda.value) return
  try {
    await estoque.cancelarVenda(venda.value.id)
    ui.closeDrawer()
    ui.showToast('Venda cancelada — estoque revertido.')
  } catch (err) {
    if (isApiError(err)) ui.showToast(err.message)
  }
}
</script>

<template>
  <div class="flex flex-col gap-[18px]">
    <!-- Cartões de resumo -->
    <div class="flex flex-wrap gap-2.5">
      <div class="min-w-[120px] flex-1 rounded-item border border-line bg-bg px-[15px] py-[13px]">
        <div class="text-[11.5px] font-semibold uppercase tracking-[0.04em] text-muted">Total</div>
        <div class="mt-[5px] text-[20px] font-semibold tabular-nums">{{ brl(total) }}</div>
      </div>
      <div
        v-if="isVenda"
        class="min-w-[120px] flex-1 rounded-item border border-line bg-bg px-[15px] py-[13px]"
      >
        <div class="text-[11.5px] font-semibold uppercase tracking-[0.04em] text-muted">Lucro</div>
        <div class="mt-[5px] text-[20px] font-semibold tabular-nums text-accent-strong">
          {{ lucroFmt }}
        </div>
      </div>
    </div>

    <!-- Itens -->
    <div>
      <div class="mb-[9px] text-[13px] font-semibold text-muted">Itens</div>
      <div class="overflow-hidden rounded-item border border-line">
        <div
          v-for="(it, idx) in itens"
          :key="idx"
          class="flex items-center justify-between gap-3 border-b border-line px-3.5 py-3 last:border-b-0"
        >
          <div class="min-w-0">
            <div class="truncate text-[13.5px] font-medium">{{ it.nome }}</div>
            <div class="mt-0.5 text-[12px] text-faint">{{ itemDetail(it) }}</div>
          </div>
          <div class="flex-shrink-0 text-[13.5px] font-semibold tabular-nums">
            {{ brl(itemSubtotal(it)) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Cancelar / aviso -->
    <button
      v-if="canCancel"
      class="flex items-center justify-center gap-2 rounded border border-danger-soft bg-danger-soft px-3 py-[11px] text-[13.5px] font-semibold text-danger transition-[filter] hover:brightness-[0.97]"
      @click="cancelar"
    >
      <Trash2 class="h-4 w-4" :stroke-width="1.8" />
      Cancelar venda e reverter estoque
    </button>
    <div
      v-else-if="isCancelada"
      class="flex items-center gap-2.5 rounded-[10px] bg-danger-soft px-3.5 py-3 text-[13px] font-medium text-danger"
    >
      <XCircle class="h-4 w-4 flex-shrink-0" :stroke-width="1.8" />
      Venda cancelada — estoque já revertido.
    </div>
  </div>
</template>
