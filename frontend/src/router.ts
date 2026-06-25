import { createRouter, createWebHistory } from 'vue-router'
import { useUiStore } from './stores/ui'

declare module 'vue-router' {
  interface RouteMeta {
    title: string
    sub: string
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { title: 'Visão geral', sub: 'Resumo do estoque e das movimentações' },
    },
    {
      path: '/produtos',
      name: 'produtos',
      component: () => import('@/views/ProdutosView.vue'),
      meta: { title: 'Produtos', sub: 'Catálogo, custo médio e estoque atual' },
    },
    {
      path: '/compras',
      name: 'compras',
      component: () => import('@/views/ComprasView.vue'),
      meta: { title: 'Compras', sub: 'Entradas de estoque por fornecedor' },
    },
    {
      path: '/vendas',
      name: 'vendas',
      component: () => import('@/views/VendasView.vue'),
      meta: { title: 'Vendas', sub: 'Saídas, lucro e cancelamentos' },
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

// Trocar de tela limpa a busca e fecha qualquer drawer.
router.afterEach(() => {
  useUiStore().resetOnNavigate()
})

export default router
