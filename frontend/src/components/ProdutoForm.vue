<script setup lang="ts">
import { ref } from 'vue'
import { Info } from 'lucide-vue-next'
import { useEstoqueStore } from '@/stores/estoque'
import { useUiStore } from '@/stores/ui'
import { isApiError } from '@/api'

const estoque = useEstoqueStore()
const ui = useUiStore()

const nome = ref('')
const preco = ref('')
const errors = ref<Record<string, string>>({})
const saving = ref(false)

function validate(): boolean {
  const e: Record<string, string> = {}
  const n = nome.value.trim()
  if (!n) e.nome = 'O nome é obrigatório.'
  else if (n.length < 3) e.nome = 'O nome deve ter no mínimo 3 caracteres.'
  const p = Number.parseFloat(preco.value)
  if (!(p > 0)) e.preco_venda = 'Informe um preço de venda válido.'
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  if (saving.value || !validate()) return
  saving.value = true
  try {
    await estoque.criarProduto({
      nome: nome.value.trim(),
      preco_venda: Number.parseFloat(preco.value),
    })
    ui.closeDrawer()
    ui.showToast('Produto cadastrado.')
  } catch (err) {
    if (isApiError(err)) {
      errors.value = { ...errors.value, ...err.fieldErrors }
      if (err.kind !== 'validation') ui.showToast(err.message)
    }
  } finally {
    saving.value = false
  }
}

defineExpose({ submit })
</script>

<template>
  <div class="flex flex-col gap-[18px]">
    <div>
      <label class="mb-[7px] block text-[13px] font-semibold text-muted">Nome do produto</label>
      <input v-model="nome" class="input" placeholder="Ex.: Caneta Esferográfica Azul" />
      <p v-if="errors.nome" class="mt-1.5 text-[12.5px] text-danger">{{ errors.nome }}</p>
    </div>

    <div>
      <label class="mb-[7px] block text-[13px] font-semibold text-muted">
        Preço de venda (R$)
      </label>
      <input
        v-model="preco"
        type="number"
        step="0.01"
        min="0"
        class="input tabular-nums"
        placeholder="0,00"
      />
      <p v-if="errors.preco_venda" class="mt-1.5 text-[12.5px] text-danger">
        {{ errors.preco_venda }}
      </p>
    </div>

    <div class="flex items-start gap-2.5 rounded-[10px] bg-accent-soft px-3.5 py-3">
      <Info class="mt-px h-[17px] w-[17px] flex-shrink-0 text-accent-strong" :stroke-width="1.8" />
      <p class="text-[12.5px] leading-relaxed text-accent-strong">
        O estoque começa em <strong>zero</strong>. O custo médio é calculado automaticamente ao
        registrar a primeira compra.
      </p>
    </div>
  </div>
</template>
