<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { AlertTriangle, ShoppingCart, Download } from 'lucide-vue-next'
import AppTopbar from '@/components/AppTopbar.vue'
import KpiCard from '@/components/KpiCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useEstoqueStore } from '@/stores/estoque'
import { useFormatters } from '@/composables/useFormatters'
import type { BadgeTone } from '@/types'

const estoque = useEstoqueStore()
const { brl, int, pct, dateShort } = useFormatters()

onMounted(() => {
  estoque.fetchDashboard(10)
})

const d = computed(() => estoque.dashboard)

const atividade = computed(() => {
  if (!d.value) return []
  return d.value.atividade_recente.slice(0, 6).map((a) => {
    const isVenda = a.tipo === 'venda'
    const cancelada = a.status === 'cancelada'
    return {
      isVenda,
      titulo: a.descricao,
      sub: `${isVenda ? 'Venda' : 'Compra'} · ${dateShort(a.data)}`,
      // valor já vem assinado do backend; exibimos a magnitude com o sinal pelo tipo.
      valorFmt: `${isVenda ? '+ ' : '− '}${brl(Math.abs(a.valor))}`,
      valorClass: isVenda && !cancelada ? 'text-accent-strong' : 'text-muted',
      iconWrap: isVenda ? 'bg-accent-soft text-accent-strong' : 'bg-surface-3 text-muted',
      cancelada,
    }
  })
})

const lowStock = computed(() => {
  if (!d.value) return []
  return d.value.estoque_baixo.map((p) => ({
    nome: p.nome,
    estoqueFmt: int(p.estoque),
    tone: (p.status === 'sem_estoque' ? 'danger' : 'warn') as BadgeTone,
    label: p.status === 'sem_estoque' ? 'Sem estoque' : 'Baixo',
  }))
})
</script>

<template>
  <AppTopbar />

  <div class="px-[30px] pb-10 pt-[26px]">
    <div v-if="d">
      <!-- KPIs -->
      <div class="grid grid-cols-[repeat(auto-fit,minmax(190px,1fr))] gap-4">
        <KpiCard
          label="Valor em estoque"
          :value="brl(d.valor_em_estoque)"
          :sub="`${int(d.unidades_em_estoque)} unidades · a custo médio`"
        />
        <KpiCard label="Faturamento" :value="brl(d.faturamento)" sub="vendas concluídas" />
        <KpiCard
          label="Lucro"
          :value="brl(d.lucro)"
          value-class="text-accent-strong"
          :sub="`margem média ${pct(d.margem_media)}`"
        />
        <KpiCard
          label="Alertas de estoque"
          :value="int(d.alertas_estoque)"
          sub="produtos para repor"
        >
          <template #badge>
            <AlertTriangle
              v-if="d.alertas_estoque > 0"
              class="h-[18px] w-[18px] text-warn"
              :stroke-width="1.8"
            />
          </template>
        </KpiCard>
      </div>

      <!-- Atividade recente + Estoque baixo -->
      <div class="mt-4 grid grid-cols-[repeat(auto-fit,minmax(290px,1fr))] items-start gap-4">
        <!-- Atividade recente -->
        <div class="overflow-hidden rounded-card border border-line bg-surface shadow-sm">
          <div class="border-b border-line px-5 py-4 text-[14.5px] font-semibold">
            Atividade recente
          </div>
          <div
            v-for="(a, i) in atividade"
            :key="i"
            class="flex items-center gap-[13px] border-b border-line px-5 py-[13px] last:border-b-0"
          >
            <div
              class="flex h-[34px] w-[34px] flex-shrink-0 items-center justify-center rounded"
              :class="a.iconWrap"
            >
              <ShoppingCart v-if="a.isVenda" class="h-[17px] w-[17px]" :stroke-width="1.7" />
              <Download v-else class="h-[17px] w-[17px]" :stroke-width="1.7" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="truncate text-[14px] font-medium">{{ a.titulo }}</div>
              <div class="mt-px text-[12.5px] text-faint">{{ a.sub }}</div>
            </div>
            <div class="text-right">
              <div class="text-[14px] font-semibold tabular-nums" :class="a.valorClass">
                {{ a.valorFmt }}
              </div>
              <div v-if="a.cancelada" class="mt-px text-[11.5px] font-medium text-danger">
                cancelada
              </div>
            </div>
          </div>
        </div>

        <!-- Estoque baixo -->
        <div class="overflow-hidden rounded-card border border-line bg-surface shadow-sm">
          <div class="border-b border-line px-5 py-4 text-[14.5px] font-semibold">Estoque baixo</div>
          <template v-if="lowStock.length">
            <div
              v-for="(p, i) in lowStock"
              :key="i"
              class="flex items-center justify-between gap-2.5 border-b border-line px-5 py-[13px] last:border-b-0"
            >
              <div class="min-w-0 truncate text-[13.5px] font-medium">{{ p.nome }}</div>
              <div class="flex flex-shrink-0 items-center gap-2">
                <span class="text-[13px] font-semibold tabular-nums text-muted">
                  {{ p.estoqueFmt }} un
                </span>
                <StatusBadge :tone="p.tone" :label="p.label" />
              </div>
            </div>
          </template>
          <div v-else class="px-5 py-[34px] text-center text-[13px] text-faint">
            Tudo certo — nenhum produto abaixo do mínimo.
          </div>
        </div>
      </div>
    </div>

    <div v-else class="py-20 text-center text-[14px] text-faint">
      Carregando…
    </div>
  </div>
</template>
