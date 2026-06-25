import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '@/api'
import type {
  Compra,
  CriarCompraDTO,
  CriarProdutoDTO,
  CriarVendaDTO,
  Dashboard,
  Produto,
  Venda,
} from '@/types'

export const useEstoqueStore = defineStore('estoque', () => {
  const produtos = ref<Produto[]>([])
  const compras = ref<Compra[]>([])
  const vendas = ref<Venda[]>([])
  const dashboard = ref<Dashboard | null>(null)

  const loading = ref({
    produtos: false,
    compras: false,
    vendas: false,
    dashboard: false,
  })

  const produtoById = computed(() => {
    const map = new Map<number, Produto>()
    for (const p of produtos.value) map.set(p.id, p)
    return (id: number | null | undefined) => (id == null ? undefined : map.get(id))
  })

  const produtoOptions = computed(() =>
    produtos.value.map((p) => ({ id: p.id, nome: p.nome })),
  )

  // ---- Fetch ----
  async function fetchProdutos() {
    loading.value.produtos = true
    try {
      produtos.value = await api.listProdutos()
    } finally {
      loading.value.produtos = false
    }
  }
  async function fetchCompras() {
    loading.value.compras = true
    try {
      compras.value = await api.listCompras()
    } finally {
      loading.value.compras = false
    }
  }
  async function fetchVendas() {
    loading.value.vendas = true
    try {
      vendas.value = await api.listVendas()
    } finally {
      loading.value.vendas = false
    }
  }
  async function fetchDashboard(estoqueMinimo = 30) {
    loading.value.dashboard = true
    try {
      dashboard.value = await api.getDashboard(estoqueMinimo)
    } finally {
      loading.value.dashboard = false
    }
  }

  async function refreshDashboardIfLoaded() {
    if (dashboard.value) await fetchDashboard()
  }

  // ---- Mutações (refetch dos recursos afetados; derivados são do servidor) ----
  async function criarProduto(dto: CriarProdutoDTO): Promise<Produto> {
    const novo = await api.criarProduto(dto)
    await fetchProdutos()
    await refreshDashboardIfLoaded()
    return novo
  }

  async function criarCompra(dto: CriarCompraDTO): Promise<Compra> {
    const compra = await api.criarCompra(dto)
    await Promise.all([fetchCompras(), fetchProdutos()])
    await refreshDashboardIfLoaded()
    return compra
  }

  async function criarVenda(dto: CriarVendaDTO): Promise<Venda> {
    const venda = await api.criarVenda(dto)
    await Promise.all([fetchVendas(), fetchProdutos()])
    await refreshDashboardIfLoaded()
    return venda
  }

  async function cancelarVenda(id: number): Promise<Venda> {
    const venda = await api.cancelarVenda(id)
    await Promise.all([fetchVendas(), fetchProdutos()])
    await refreshDashboardIfLoaded()
    return venda
  }

  return {
    produtos,
    compras,
    vendas,
    dashboard,
    loading,
    produtoById,
    produtoOptions,
    fetchProdutos,
    fetchCompras,
    fetchVendas,
    fetchDashboard,
    criarProduto,
    criarCompra,
    criarVenda,
    cancelarVenda,
  }
})
