import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },

  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'pinia'],
          'fullcalendar': ['@fullcalendar/core', '@fullcalendar/vue3', '@fullcalendar/daygrid', '@fullcalendar/timegrid', '@fullcalendar/interaction'],
          'charts': ['chart.js', '@tanstack/vue-table']
        }
      }
    },
    chunkSizeWarningLimit: 600
  },

  // Прокси для API запросов и медиа-файлов в development режиме
  server: {
    proxy: {
      '/api': {
        target: 'https://xn--80aac1alfd7a3a5g.xn--p1ai',
        changeOrigin: true,
        secure: false,
        cookieDomainRewrite: 'localhost',
        configure: (proxy, _options) => {
          proxy.on('proxyReq', (_proxyReq, req, _res) => {
            // Логируем метод запроса для отладки
            console.log('Proxy API request:', req.method, req.url)
          })
        }
      },
      '/media': {
        target: 'https://xn--80aac1alfd7a3a5g.xn--p1ai',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
