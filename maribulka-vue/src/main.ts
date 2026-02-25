import { createApp } from 'vue'
import { createPinia } from 'pinia' // 1. Импорт
import App from './App.vue'

// Стили
import './assets/theme.css'
import './assets/buttons.css'
import './assets/glass-panel.css'
import './assets/sidebar.css'
import './assets/topbar.css'
import './assets/modal.css'
import './assets/tiptap.css'
import './style.css'

const app = createApp(App)

app.use(createPinia()) // 2. Активация (БЕЗ ЭТОГО БУДЕТ ПУСТОЙ ЭКРАН)
app.mount('#app')