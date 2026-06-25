import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// CORS já está liberado no backend para `api/*`, então não usamos proxy:
// um único caminho de código via VITE_API_URL mantém dev e Docker idênticos.
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true, // 0.0.0.0 — necessário dentro do container
    port: 5173,
  },
})
