// Tipos do contrato da API (verificados no backend Laravel).
// Todas as respostas vêm embrulhadas em { data: ... } — desembrulhado em api.ts.

export interface Produto {
  id: number
  nome: string
  custo_medio: number
  preco_venda: number
  estoque: number
}

export interface CompraItem {
  produto_id: number
  nome: string | null
  quantidade: number
  preco_unitario: number
}

export interface Compra {
  id: number
  fornecedor: string
  total: number
  created_at: string
  itens: CompraItem[]
}

export type VendaStatus = 'concluida' | 'cancelada'

export interface VendaItem {
  produto_id: number
  nome: string | null
  quantidade: number
  preco_unitario: number
  custo_unitario: number
}

export interface Venda {
  id: number
  cliente: string
  status: VendaStatus
  total: number
  lucro: number
  created_at: string
  itens: VendaItem[]
}

export interface EstoqueBaixo {
  id: number
  nome: string
  estoque: number
  status: 'sem_estoque' | 'baixo'
}

export type AtividadeTipo = 'compra' | 'venda'

export interface AtividadeRecente {
  tipo: AtividadeTipo
  descricao: string
  /** Assinado pelo backend: venda positivo, compra negativo. */
  valor: number
  status: VendaStatus | null
  /** ISO created_at. */
  data: string
}

export interface Dashboard {
  valor_em_estoque: number
  unidades_em_estoque: number
  faturamento: number
  lucro: number
  margem_media: number
  vendas_concluidas: number
  alertas_estoque: number
  estoque_baixo: EstoqueBaixo[]
  atividade_recente: AtividadeRecente[]
}

// ---- Payloads de criação ----

export interface CriarProdutoDTO {
  nome: string
  preco_venda: number
}

export interface ItemLancamentoDTO {
  id: number
  quantidade: number
  preco_unitario: number
}

export interface CriarCompraDTO {
  fornecedor: string
  produtos: ItemLancamentoDTO[]
}

export interface CriarVendaDTO {
  cliente: string
  produtos: ItemLancamentoDTO[]
}

// ---- Erro normalizado (consumido pela UI) ----

export type ApiErrorKind =
  | 'validation'
  | 'stock'
  | 'notfound'
  | 'server'
  | 'network'
  | 'unknown'

export interface ApiError {
  message: string
  status: number
  fieldErrors: Record<string, string>
  kind: ApiErrorKind
}

// ---- Tipos de UI ----

export type DrawerType =
  | 'novo-produto'
  | 'nova-compra'
  | 'nova-venda'
  | 'detalhe-compra'
  | 'detalhe-venda'

export type LancamentoMode = 'compra' | 'venda'
export type BadgeTone = 'ok' | 'warn' | 'danger'
