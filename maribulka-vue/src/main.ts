import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// Стили
import './style.css'
import './assets/buttonGlass.css'
import './assets/padGlass.css'

const app = createApp(App)
app.use(createPinia())
app.mount('#app')
