import axios from 'axios'
import type { AxiosError } from 'axios'
import type {
  ApiError,
  Compra,
  CriarCompraDTO,
  CriarProdutoDTO,
  CriarVendaDTO,
  Dashboard,
  Produto,
  Venda,
} from './types'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

// Sucesso: o interceptor devolve o CORPO JSON (que ainda traz o envelope { data }).
// Erro: rejeita SEMPRE com um ApiError normalizado — todo `catch` recebe o mesmo formato.
client.interceptors.response.use(
  (response) => response.data,
  (error: AxiosError) => Promise.reject(normalizeApiError(error)),
)

interface Envelope<T> {
  data: T
}

// O interceptor já devolveu o corpo; aqui só tipamos e lemos `.data` (desembrulho central).
function getData<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return client.get(url, { params }).then((body) => (body as unknown as Envelope<T>).data)
}
function postData<T>(url: string, payload?: unknown): Promise<T> {
  return client.post(url, payload).then((body) => (body as unknown as Envelope<T>).data)
}

// ---- Serviços por recurso ----

export const listProdutos = () => getData<Produto[]>('/produtos')
export const criarProduto = (dto: CriarProdutoDTO) => postData<Produto>('/produtos', dto)

export const listCompras = () => getData<Compra[]>('/compras')
export const criarCompra = (dto: CriarCompraDTO) => postData<Compra>('/compras', dto)

export const listVendas = () => getData<Venda[]>('/vendas')
export const criarVenda = (dto: CriarVendaDTO) => postData<Venda>('/vendas', dto)
export const cancelarVenda = (id: number) => postData<Venda>(`/vendas/${id}/cancelar`)

// Limiar 30 por padrão: o design usa "estoque <= 30" (o default da API é 10).
export const getDashboard = (estoqueMinimo = 30) =>
  getData<Dashboard>('/dashboard', { estoque_minimo: estoqueMinimo })

// ---- Normalização de erro ----

export function normalizeApiError(error: unknown): ApiError {
  const err = error as AxiosError<{ message?: string; errors?: Record<string, string[]> }>
  const res = err.response

  if (!res) {
    return {
      message: 'Sem conexão com a API. Verifique se o backend está no ar.',
      status: 0,
      fieldErrors: {},
      kind: 'network',
    }
  }

  const body = res.data ?? {}
  const message = body.message ?? 'Ocorreu um erro inesperado.'

  // 422 com `errors` → validação de FormRequest (mostrar por campo).
  if (res.status === 422 && body.errors) {
    const fieldErrors: Record<string, string> = {}
    for (const [field, msgs] of Object.entries(body.errors)) fieldErrors[field] = msgs[0]
    return { message, status: 422, fieldErrors, kind: 'validation' }
  }
  // 422 sem `errors` → estoque insuficiente (só mensagem).
  if (res.status === 422) {
    return { message, status: 422, fieldErrors: {}, kind: 'stock' }
  }
  if (res.status === 404) {
    return { message, status: 404, fieldErrors: {}, kind: 'notfound' }
  }
  if (res.status >= 500) {
    return { message: 'Erro no servidor. Tente novamente.', status: res.status, fieldErrors: {}, kind: 'server' }
  }
  return { message, status: res.status, fieldErrors: {}, kind: 'unknown' }
}

export function isApiError(e: unknown): e is ApiError {
  return typeof e === 'object' && e !== null && 'kind' in e && 'fieldErrors' in e
}
