import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { DrawerType } from '@/types'

export type ThemeName = 'light' | 'dark'
export type AccentName = 'salvia' | 'petroleo' | 'grafite' | 'terracota'
export type Density = 'confortavel' | 'compacto'

interface DrawerState {
  type: DrawerType
  payload?: number
}

const LS = { theme: 'erp-theme', accent: 'erp-accent', density: 'erp-density' } as const

export const useUiStore = defineStore('ui', () => {
  const theme = ref<ThemeName>('light')
  const accent = ref<AccentName>('salvia')
  const density = ref<Density>('confortavel')
  const query = ref('')
  const toast = ref<string | null>(null)
  const drawer = ref<DrawerState | null>(null)
  // Sidebar off-canvas no mobile (< lg). No desktop a sidebar é sempre visível.
  const sidebarOpen = ref(false)

  let toastTimer: ReturnType<typeof setTimeout> | undefined

  function applyToDom() {
    const el = document.documentElement
    el.dataset.theme = theme.value
    if (accent.value === 'salvia') delete el.dataset.accent
    else el.dataset.accent = accent.value
    if (density.value === 'compacto') el.dataset.density = 'compacto'
    else delete el.dataset.density
  }

  function initTheme() {
    const savedTheme = localStorage.getItem(LS.theme)
    if (savedTheme === 'light' || savedTheme === 'dark') {
      theme.value = savedTheme
    } else {
      theme.value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    const savedAccent = localStorage.getItem(LS.accent) as AccentName | null
    if (savedAccent) accent.value = savedAccent
    const savedDensity = localStorage.getItem(LS.density) as Density | null
    if (savedDensity) density.value = savedDensity
    applyToDom()
  }

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    localStorage.setItem(LS.theme, theme.value)
    applyToDom()
  }

  function setAccent(value: AccentName) {
    accent.value = value
    localStorage.setItem(LS.accent, value)
    applyToDom()
  }

  function toggleDensity() {
    density.value = density.value === 'compacto' ? 'confortavel' : 'compacto'
    localStorage.setItem(LS.density, density.value)
    applyToDom()
  }

  function openDrawer(type: DrawerType, payload?: number) {
    drawer.value = { type, payload }
  }
  function closeDrawer() {
    drawer.value = null
  }

  function openSidebar() {
    sidebarOpen.value = true
  }
  function closeSidebar() {
    sidebarOpen.value = false
  }

  // Trocar de tela limpa a busca, fecha qualquer drawer e a sidebar mobile (regra do design).
  function resetOnNavigate() {
    query.value = ''
    drawer.value = null
    sidebarOpen.value = false
  }

  function showToast(message: string) {
    toast.value = message
    if (toastTimer) clearTimeout(toastTimer)
    toastTimer = setTimeout(() => {
      toast.value = null
    }, 2800)
  }

  return {
    theme,
    accent,
    density,
    query,
    toast,
    drawer,
    sidebarOpen,
    initTheme,
    toggleTheme,
    setAccent,
    toggleDensity,
    openDrawer,
    closeDrawer,
    openSidebar,
    closeSidebar,
    resetOnNavigate,
    showToast,
  }
})
