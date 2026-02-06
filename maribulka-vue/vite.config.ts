import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],

  // Прокси для API запросов в development режиме
  server: {
    proxy: {
      '/api': {
        target: 'http://xn--80aac1alfd7a3a5g.xn--p1ai',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
