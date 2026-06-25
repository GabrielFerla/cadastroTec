<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { useFormatters } from '@/composables/useFormatters'
import { isApiError } from '@/api'
import type { LancamentoMode, Produto } from '@/types'

const props = defineProps<{ mode: LancamentoMode }>()

const estoque = useEstoqueStore()
const ui = useUiStore()
const { brl, int } = useFormatters()

interface Line {
  produto_id: string
  quantidade: string
  preco_unitario: string
}
const blank = (): Line => ({ produto_id: '', quantidade: '1', preco_unitario: '' })

const isVenda = computed(() => props.mode === 'venda')
const party = ref('')
const lines = ref<Line[]>([blank()])
const saving = ref(false)
const errors = ref<{ party: string; itens: string; lines: Record<number, string> }>({
  party: '',
  itens: '',
  lines: {},
})

const partyLabel = computed(() => (isVenda.value ? 'Cliente' : 'Fornecedor'))
const partyPlaceholder = computed(() =>
  isVenda.value ? 'Ex.: Maria Fernanda' : 'Ex.: Distribuidora Office SP',
)
const precoHint = computed(() => (isVenda.value ? 'preço de venda' : 'preço de custo'))
const produtoOptions = computed(() => estoque.produtoOptions)

function prodOf(line: Line): Produto | undefined {
  return estoque.produtoById(Number.parseInt(line.produto_id, 10))
}
function subtotal(line: Line): number {
  const q = Number.parseInt(line.quantidade, 10) || 0
  const p = Number.parseFloat(line.preco_unitario) || 0
  return q * p
}
function lineInfo(line: Line): string {
  const prod = prodOf(line)
  if (!prod) return ''
  if (isVenda.value) return `Disponível: ${int(prod.estoque)} un`
  return prod.custo_medio > 0
    ? `Custo médio atual: ${brl(prod.custo_medio)}`
    : 'Primeira entrada deste produto'
}

const total = computed(() => lines.value.reduce((acc, l) => acc + subtotal(l), 0))
const lucroEstimado = computed(() => {
  if (!isVenda.value) return 0
  return lines.value.reduce((acc, l) => {
    const prod = prodOf(l)
    const q = Number.parseInt(l.quantidade, 10) || 0
    const p = Number.parseFloat(l.preco_unitario) || 0
    return acc + (prod ? q * (p - prod.custo_medio) : 0)
  }, 0)
})

function addLine() {
  lines.value.push(blank())
}
function removeLine(i: number) {
  lines.value.splice(i, 1)
  if (lines.value.length === 0) lines.value.push(blank())
}
// Auto-preenche o preço ao escolher o produto (só se ainda estiver vazio).
function onPick(i: number) {
  const line = lines.value[i]
  if (!line.produto_id || line.preco_unitario) return
  const prod = prodOf(line)
  if (!prod) return
  if (isVenda.value) line.preco_unitario = String(prod.preco_venda)
  else if (prod.custo_medio > 0) line.preco_unitario = String(prod.custo_medio)
}

function validate(): boolean {
  const party_ = party.value.trim()
  const lineErr: Record<number, string> = {}
  let partyErr = ''
  let itensErr = ''

  if (!party_) partyErr = isVenda.value ? 'O cliente é obrigatório.' : 'O fornecedor é obrigatório.'

  let any = false
  const seen = new Set<string>()
  lines.value.forEach((l, i) => {
    if (!l.produto_id) return
    any = true
    if (seen.has(l.produto_id)) lineErr[i] = 'Produto repetido neste lançamento.'
    seen.add(l.produto_id)
    const q = Number.parseInt(l.quantidade, 10)
    if (!(q >= 1)) lineErr[i] = 'A quantidade deve ser no mínimo 1.'
    const p = Number.parseFloat(l.preco_unitario)
    if (!(p > 0)) lineErr[i] = 'O preço unitário deve ser positivo.'
    if (isVenda.value) {
      const prod = prodOf(l)
      if (prod && q > prod.estoque) lineErr[i] = `Estoque insuficiente. Disponível: ${prod.estoque}.`
    }
  })
  if (!any) itensErr = 'Informe ao menos um produto.'

  errors.value = { party: partyErr, itens: itensErr, lines: lineErr }
  return !partyErr && !itensErr && Object.keys(lineErr).length === 0
}

function mapServerErrors(fieldErrors: Record<string, string>) {
  for (const [key, msg] of Object.entries(fieldErrors)) {
    if (key === 'cliente' || key === 'fornecedor') errors.value.party = msg
    else {
      const m = key.match(/produtos\.(\d+)\./)
      if (m) errors.value.lines[Number(m[1])] = msg
      else errors.value.itens = msg
    }
  }
}

async function submit() {
  if (saving.value || !validate()) return
  const produtos = lines.value
    .filter((l) => l.produto_id)
    .map((l) => ({
      id: Number.parseInt(l.produto_id, 10),
      quantidade: Number.parseInt(l.quantidade, 10),
      preco_unitario: Number.parseFloat(l.preco_unitario),
    }))

  saving.value = true
  try {
    if (isVenda.value) {
      await estoque.criarVenda({ cliente: party.value.trim(), produtos })
      ui.showToast('Venda registrada.')
    } else {
      await estoque.criarCompra({ fornecedor: party.value.trim(), produtos })
      ui.showToast('Compra registrada — estoque e custo médio atualizados.')
    }
    ui.closeDrawer()
  } catch (err) {
    if (!isApiError(err)) return
    if (err.kind === 'validation') {
      mapServerErrors(err.fieldErrors)
    } else {
      if (err.kind === 'stock') errors.value.itens = err.message
      ui.showToast(err.message)
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (!estoque.produtos.length) estoque.fetchProdutos()
})

defineExpose({ submit })
</script>

<template>
  <div class="flex flex-col gap-5">
    <!-- Fornecedor / Cliente -->
    <div>
      <label class="mb-[7px] block text-[13px] font-semibold text-muted">{{ partyLabel }}</label>
      <input v-model="party" class="input" :placeholder="partyPlaceholder" />
      <p v-if="errors.party" class="mt-1.5 text-[12.5px] text-danger">{{ errors.party }}</p>
    </div>

    <!-- Itens -->
    <div>
      <div class="mb-[9px] flex items-center justify-between">
        <label class="text-[13px] font-semibold text-muted">Itens</label>
        <span class="text-[12px] text-faint">{{ precoHint }}</span>
      </div>

      <div class="flex flex-col gap-2.5">
        <div
          v-for="(line, i) in lines"
          :key="i"
          class="rounded-item border border-line bg-bg p-3"
        >
          <div class="flex items-start gap-2">
            <select v-model="line.produto_id" class="input-sm flex-1" @change="onPick(i)">
              <option value="">Selecione um produto…</option>
              <option v-for="opt in produtoOptions" :key="opt.id" :value="String(opt.id)">
                {{ opt.nome }}
              </option>
            </select>
            <button
              v-if="lines.length > 1"
              class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-faint transition-colors hover:bg-danger-soft hover:text-danger"
              aria-label="Remover item"
              @click="removeLine(i)"
            >
              <Trash2 class="h-4 w-4" :stroke-width="1.8" />
            </button>
          </div>

          <div class="mt-[9px] flex gap-2">
            <div class="flex-1">
              <div class="mb-1 text-[11px] font-semibold uppercase tracking-[0.04em] text-faint">
                Quantidade
              </div>
              <input
                v-model="line.quantidade"
                type="number"
                min="1"
                step="1"
                class="input-sm tabular-nums"
              />
            </div>
            <div class="flex-[1.3]">
              <div class="mb-1 text-[11px] font-semibold uppercase tracking-[0.04em] text-faint">
                Preço unit. (R$)
              </div>
              <input
                v-model="line.preco_unitario"
                type="number"
                min="0"
                step="0.01"
                class="input-sm tabular-nums"
                placeholder="0,00"
              />
            </div>
            <div class="flex-1">
              <div class="mb-1 text-[11px] font-semibold uppercase tracking-[0.04em] text-faint">
                Subtotal
              </div>
              <div class="py-2 text-[13.5px] font-semibold tabular-nums">
                {{ brl(subtotal(line)) }}
              </div>
            </div>
          </div>

          <p v-if="lineInfo(line)" class="mt-2 text-[12px] text-muted">{{ lineInfo(line) }}</p>
          <p v-if="errors.lines[i]" class="mt-[7px] text-[12px] font-medium text-danger">
            {{ errors.lines[i] }}
          </p>
        </div>
      </div>

      <button
        class="mt-[11px] flex w-full items-center justify-center gap-[7px] rounded-lg border border-dashed border-line-strong px-[11px] py-2 text-[13.5px] font-semibold text-accent-strong transition-colors hover:bg-accent-soft"
        @click="addLine"
      >
        <Plus class="h-4 w-4" :stroke-width="2" />
        Adicionar item
      </button>
      <p v-if="errors.itens" class="mt-2 text-[12.5px] text-danger">{{ errors.itens }}</p>
    </div>

    <!-- Totais -->
    <div class="flex flex-col gap-[9px] border-t border-line pt-4">
      <div class="flex items-center justify-between">
        <span class="text-[14px] text-muted">Total</span>
        <span class="text-[19px] font-semibold tabular-nums">{{ brl(total) }}</span>
      </div>
      <div v-if="isVenda" class="flex items-center justify-between">
        <span class="text-[13.5px] text-muted">Lucro estimado</span>
        <span
          class="text-[15px] font-semibold tabular-nums"
          :class="lucroEstimado >= 0 ? 'text-accent-strong' : 'text-danger'"
        >
          {{ brl(lucroEstimado) }}
        </span>
      </div>
    </div>
  </div>
</template>
