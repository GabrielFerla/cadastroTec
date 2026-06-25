<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'
import type { DrawerType } from '@/types'
import ProdutoForm from './ProdutoForm.vue'
import LancamentoForm from './LancamentoForm.vue'
import DetalheDrawer from './DetalheDrawer.vue'

const ui = useUiStore()

const open = computed(() => ui.drawer !== null)
const type = computed<DrawerType | null>(() => ui.drawer?.type ?? null)

const isForm = computed(
  () =>
    type.value === 'novo-produto' ||
    type.value === 'nova-compra' ||
    type.value === 'nova-venda',
)

const width = computed(() => {
  switch (type.value) {
    case 'nova-compra':
    case 'nova-venda':
      return '548px'
    case 'novo-produto':
      return '440px'
    default:
      return '480px'
  }
})

const META: Record<DrawerType, { title: string; sub: string }> = {
  'novo-produto': { title: 'Novo produto', sub: 'O estoque inicia em zero.' },
  'nova-compra': { title: 'Nova compra', sub: 'Entrada de estoque — atualiza o custo médio.' },
  'nova-venda': { title: 'Nova venda', sub: 'Saída de estoque — calcula o lucro.' },
  'detalhe-compra': { title: 'Detalhe da compra', sub: 'Itens e valores da entrada.' },
  'detalhe-venda': { title: 'Detalhe da venda', sub: 'Itens, custo e lucro da venda.' },
}
const meta = computed(() => (type.value ? META[type.value] : { title: '', sub: '' }))

const saveLabel = computed(() => {
  switch (type.value) {
    case 'novo-produto':
      return 'Cadastrar produto'
    case 'nova-compra':
      return 'Registrar compra'
    case 'nova-venda':
      return 'Registrar venda'
    default:
      return 'Salvar'
  }
})

// Ref ao form ativo (só um renderiza por vez) para o footer disparar o submit.
const formRef = ref<{ submit: () => void } | null>(null)
function onSubmit() {
  formRef.value?.submit()
}

// Fecha com Esc.
function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') ui.closeDrawer()
}
watch(open, (v) => {
  if (v) window.addEventListener('keydown', onKey)
  else window.removeEventListener('keydown', onKey)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div
        v-if="open"
        class="fixed inset-0 z-[49] bg-[rgba(10,18,12,0.38)]"
        @click="ui.closeDrawer()"
      />
    </Transition>

    <Transition name="drawer">
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 flex h-screen max-w-[94vw] flex-col border-l border-line bg-surface shadow-lg"
        :style="{ width }"
      >
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 border-b border-line px-[22px] pb-4 pt-5">
          <div class="leading-snug">
            <div class="text-[17px] font-semibold tracking-[-0.01em]">{{ meta.title }}</div>
            <div class="mt-0.5 text-[13px] text-muted">{{ meta.sub }}</div>
          </div>
          <button
            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-muted transition-colors hover:bg-surface-2 hover:text-ink"
            aria-label="Fechar"
            @click="ui.closeDrawer()"
          >
            <X class="h-[18px] w-[18px]" :stroke-width="1.9" />
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-auto p-[22px]">
          <ProdutoForm v-if="type === 'novo-produto'" ref="formRef" />
          <LancamentoForm v-else-if="type === 'nova-compra'" ref="formRef" mode="compra" />
          <LancamentoForm v-else-if="type === 'nova-venda'" ref="formRef" mode="venda" />
          <DetalheDrawer v-else-if="type === 'detalhe-compra'" tipo="compra" />
          <DetalheDrawer v-else-if="type === 'detalhe-venda'" tipo="venda" />
        </div>

        <!-- Footer (só nos formulários) -->
        <div
          v-if="isForm"
          class="flex justify-end gap-2.5 border-t border-line bg-surface px-[22px] py-3.5"
        >
          <button
            class="rounded border border-line-strong px-4 py-[9px] text-[14px] font-semibold text-muted transition-colors hover:bg-surface-2 hover:text-ink"
            @click="ui.closeDrawer()"
          >
            Cancelar
          </button>
          <button
            class="rounded bg-accent px-[18px] py-[9px] text-[14px] font-semibold text-accent-ink shadow-sm transition-[filter] hover:brightness-[1.06]"
            @click="onSubmit"
          >
            {{ saveLabel }}
          </button>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
.backdrop-enter-active {
  animation: fadeIn 0.2s ease;
}
.backdrop-leave-active {
  transition: opacity 0.18s ease;
}
.backdrop-leave-to {
  opacity: 0;
}
.drawer-enter-active {
  animation: drawerIn 0.28s cubic-bezier(0.22, 0.8, 0.3, 1);
}
.drawer-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.drawer-leave-to {
  transform: translateX(10px);
  opacity: 0;
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@keyframes drawerIn {
  from {
    transform: translateY(10px);
  }
  to {
    transform: translateY(0);
  }
}
</style>
