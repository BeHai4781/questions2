<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router   = useRouter()
const authStore = useAuthStore()

const displayName = () => authStore.user?.fullname || authStore.user?.username || 'Tài khoản'

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header
    class="navbar sticky top-0 z-50 w-full flex items-center justify-between px-8 py-6 bg-white/80 shadow-md backdrop-blur-md"
  >
    <!-- Logo + Nav -->
    <div class="flex items-center gap-2">
      <a href="/teacher/dashboard" class="flex items-center gap-2">
        <img src="/logo.svg" alt="Logo" class="h-10 w-10" />
        <span class="text-2xl font-bold text-indigo-700 pr-20">EMS</span>
      </a>
      <nav class="hidden md:flex gap-14 text-gray-700 font-medium text-lg">
        <a href="/teacher/exams"     class="hover:text-indigo-600 transition">Đề thi</a>
        <a href="/teacher/questions" class="hover:text-indigo-600 transition">Thư viện câu hỏi</a>
      </nav>
    </div>

    <!-- Avatar dropdown -->
    <div class="flex gap-4">
      <div class="dropdown">
        <button
          tabindex="0"
          role="button"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-100 text-gray-700 rounded-xl shadow hover:bg-indigo-200 transition font-semibold"
        >
          <div class="w-8 h-8 rounded-full overflow-hidden border border-indigo-200">
            <img alt="Avatar" src="/avatar.png" class="w-full h-full object-cover" />
          </div>
          <span class="hidden md:inline">{{ displayName() }}</span>
          <svg
            class="w-4 h-4 ml-1 text-indigo-500"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <ul
          tabindex="-1"
          class="menu dropdown-content bg-white text-black rounded-box z-10 mt-3 w-52 p-2 shadow text-sm border border-gray-200"
        >
          <li class="hover:bg-indigo-100 hover:text-indigo-700 hover:font-bold rounded">
            <router-link to="/teacher/profile">Trang cá nhân</router-link>
          </li>
          <li class="hover:bg-indigo-100 hover:text-indigo-700 hover:font-bold rounded">
            <a href="#" @click.prevent="handleLogout">Đăng xuất</a>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>