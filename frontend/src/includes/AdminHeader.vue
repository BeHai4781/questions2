<script setup>
import { ref } from 'vue'
const unread = ref(3) // Số thông báo chưa đọc (demo)
const notifications = ref([
  { type: 'new_user', fullname: 'Nguyễn Văn A', created_at: '2025-12-18 10:00' },
  {
    type: 'new_exam',
    username: 'teacher01',
    exam_title: 'Toán 12 HK1',
    created_at: '2025-12-17 15:30',
  },
  { type: 'new_contact', email_client: 'user@email.com', created_at: '2025-12-16 09:20' },
])
const showDropdown = ref(false)
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}
const closeDropdown = (e) => {
  if (!e.target.closest('.notification-bell') && !e.target.closest('.notification-dropdown')) {
    showDropdown.value = false
  }
}
if (typeof window !== 'undefined') {
  window.addEventListener('click', closeDropdown)
}
</script>

<template>
  <header
    class="navbar sticky top-0 z-50 w-full flex items-center justify-between px-8 py-6 bg-white/80 shadow-md backdrop-blur-md"
  >
    <div class="flex items-center gap-2">
      <a href="/admin/dashboard" class="flex items-center gap-2">
        <img src="/logo.svg" alt="Logo" class="h-10 w-10" />
        <span class="text-2xl font-bold text-indigo-700 pr-20">EMS</span>
      </a>
      <nav class="hidden md:flex gap-14 text-gray-700 font-medium text-lg">
        <a href="/admin/users" class="hover:text-indigo-600 transition">Người dùng</a>
        <a href="/admin/exams" class="hover:text-indigo-600 transition">Đề thi</a>
        <a href="/admin/questions" class="hover:text-indigo-600 transition">Câu hỏi</a>
        <a href="/admin/reports" class="hover:text-indigo-600 transition">Báo cáo</a>
      </nav>
    </div>
    <div class="flex items-center gap-4">
      <!-- Thông báo -->
      <div class="relative notification-bell">
        <button
          @click.stop="toggleDropdown"
          class="relative p-2 rounded-full hover:bg-indigo-100 transition focus:outline-none"
        >
          <span class="text-2xl">🔔</span>
          <span
            v-if="unread > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5 border border-white"
            >{{ unread > 99 ? '99+' : unread }}</span
          >
        </button>
        <div
          v-if="showDropdown"
          class="notification-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-30"
        >
          <div v-if="notifications.length === 0" class="p-4 text-center text-gray-400">
            Không có thông báo mới.
          </div>
          <ul v-else class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
            <li v-for="(n, idx) in notifications" :key="idx" class="p-3 hover:bg-indigo-50">
              <template v-if="n.type === 'new_user'">
                🟢 <span class="font-semibold">{{ n.fullname }}</span> đã tham gia.
                <a href="/admin/users" class="text-indigo-600 ml-2">Xem</a>
              </template>
              <template v-else-if="n.type === 'new_exam'">
                📝 <span class="font-semibold">{{ n.username }}</span> đã tạo đề thi mới:
                <span class="font-semibold">{{ n.exam_title }}</span
                >.
                <a href="/admin/exams" class="text-indigo-600 ml-2">Xem</a>
              </template>
              <template v-else-if="n.type === 'new_contact'">
                📩 <span class="font-semibold">{{ n.email_client }}</span> vừa gửi liên hệ.
                <a href="/admin/reports" class="text-indigo-600 ml-2">Xem</a>
              </template>
              <template v-else> Thông báo: {{ n.content }} </template>
              <div class="text-xs text-gray-400 mt-1">{{ n.created_at }}</div>
            </li>
          </ul>
          <div class="p-2 text-center border-t border-gray-100">
            <a href="/admin/reports" class="text-indigo-600 hover:underline"
              >Xem tất cả thông báo</a
            >
          </div>
        </div>
      </div>
      <!-- Avatar + menu -->
      <div class="dropdown relative">
        <button
          tabindex="0"
          role="button"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-100 text-gray-700 rounded-xl shadow hover:bg-indigo-200 transition font-semibold"
        >
          <div class="w-8 h-8 rounded-full overflow-hidden border border-indigo-200">
            <img alt="Avatar" src="/avatar.png" class="w-full h-full object-cover" />
          </div>
          <span class="hidden md:inline">Admin</span>
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
          class="menu dropdown-content bg-white text-black rounded-box z-10 mt-3 w-40 p-2 shadow text-sm border border-gray-200 absolute right-0"
        >
          <li class="hover:bg-indigo-100 hover:text-indigo-700 hover:font-bold rounded">
            <a href="/admin/profile">Trang cá nhân</a>
          </li>
          <li class="hover:bg-indigo-100 hover:text-indigo-700 hover:font-bold rounded">
            <a href="/admin/settings">Cài đặt</a>
          </li>
          <li class="hover:bg-indigo-100 hover:text-indigo-700 hover:font-bold rounded">
            <a href="/logout">Đăng xuất</a>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>
