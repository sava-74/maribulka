import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// Стили
import './style.css'
import './assets/buttonGlass.css'
import './assets/padGlass.css'
import './assets/modal.css'
import './assets/selectBox.css'
import './assets/flatpickr.css'
import './assets/animations.css'
import './assets/home.css'
import './assets/calendar.css'
import './assets/calendar-table.css'
import 'ckeditor5/ckeditor5.css'
import './assets/editor.css'

const app = createApp(App)
app.use(createPinia())
app.mount('#app')

import { useAuthStore } from './stores/auth'
window.addEventListener('beforeunload', () => {
  const auth = useAuthStore()
  if (auth.isAdmin && !localStorage.getItem('isAdmin')) {
    navigator.sendBeacon('/api/auth.php?action=logout')
  }
})
