import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

// Khôi phục đăng nhập từ localStorage khi load trang
useAuthStore().initFromStorage()

app.use(router)

app.use(Vue3Toastify, {
    autoClose: 3000,
    pauseOnHover: true,
    pauseOnFocusLoss: true,
    position: "top-center",
});

app.mount('#app')
