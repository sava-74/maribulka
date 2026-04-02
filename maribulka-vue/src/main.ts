import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// Стили
import './style.css'
import './assets/buttonGlass.css'
import './assets/padGlass.css'
import './assets/modal.css'
import './components/ui/selectBox/selectBox.css'
import './components/ui/datePicker/datePicker.css'
import './components/ui/searchTable/searchTable.css'
import './components/ui/SwitchToggle/switchToggle.css'
import './components/ui/ball/ball.css'
import './components/salaryTypes/salaryTypes.css'
import './assets/animations.css'
import './assets/home.css'
import './assets/calendar.css'
import './assets/table.css'
import './assets/calendar-table.css'
import './assets/income-table.css'
import 'ckeditor5/ckeditor5.css'
import './assets/editor.css'
import './sandbox/sandbox.css'

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
