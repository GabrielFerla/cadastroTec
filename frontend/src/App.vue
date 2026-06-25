<script setup lang="ts">
import { onMounted } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppDrawer from '@/components/AppDrawer.vue'
import AppToast from '@/components/AppToast.vue'
import { useEstoqueStore } from '@/stores/estoque'

const estoque = useEstoqueStore()

// Produtos são necessários nos formulários (selects, pré-check de estoque, lucro estimado).
onMounted(() => {
  estoque.fetchProdutos()
})
</script>

<template>
  <div class="flex min-h-screen bg-bg font-sans text-ink">
    <AppSidebar />
    <main class="flex min-w-0 flex-1 flex-col">
      <RouterView />
    </main>
    <AppDrawer />
    <AppToast />
  </div>
</template>
