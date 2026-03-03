<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import AppFooter from '@/includes/AppFooter.vue'
import StudentHeader from '@/includes/StudentHeader.vue'
import TeacherHeader from '@/includes/TeacherHeader.vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const fullName = ref('')
const phone = ref('')
const password = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const saving = ref(false)

const user = computed(() => authStore.user)
const role = computed(() => user.value?.role || 'student')
const username = computed(() => user.value?.username ?? '')
const email = computed(() => user.value?.email ?? '')

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.replace('/login')
    return
  }
  loading.value = true
  await authStore.fetchMe()
  fullName.value = user.value?.fullname ?? ''
  phone.value = user.value?.phone ?? ''
  loading.value = false
})

async function handleEdit() {
  if (password.value !== confirmPassword.value) {
    toast.error('Mật khẩu xác nhận không khớp')
    return
  }
  if (password.value && password.value.length < 6) {
    toast.error('Mật khẩu mới phải tối thiểu 6 ký tự')
    return
  }
  saving.value = true
  const res = await authStore.updateProfile({
    fullname: fullName.value,
    phone: phone.value,
    newPassword: password.value || undefined,
  })
  saving.value = false
  if (res.ok) {
    toast.success('Cập nhật thông tin thành công')
    password.value = ''
    confirmPassword.value = ''
  } else {
    toast.error(res.error?.message || 'Cập nhật thất bại')
  }
}

function handleLogout() {
  authStore.logout()
  toast.success('Đã đăng xuất')
  router.replace('/login')
}

const roleLabel = computed(() => {
  const r = role.value
  if (r === 'admin') return 'Quản trị viên'
  if (r === 'teacher') return 'Giáo viên'
  return 'Học sinh'
})
</script>

<template>
  <StudentHeader v-if="role === 'student'" />
  <AdminHeader v-else-if="role === 'admin'" />
  <TeacherHeader v-else-if="role === 'teacher'" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center py-10">
    <div class="w-full max-w-4xl px-4">
      <div class="box bg-white rounded-xl shadow p-6 mb-8 align-center justify-center animate-fade-in">
        <h2 class="text-2xl font-bold text-indigo-700 mb-4 text-center">Thông tin cá nhân</h2>
        <p v-if="loading" class="text-center text-gray-500">Đang tải...</p>
        <form v-else class="flex flex-col gap-4 max-w-md mx-auto" @submit.prevent="handleEdit">
          <div>
            <label class="block text-gray-700 mb-1">Tên đăng nhập</label>
            <input type="text" :value="username" disabled
              class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
          </div>
          <div>
            <label class="block text-gray-700 mb-1">Họ và tên</label>
            <input type="text" v-model="fullName"
              class="w-full border border-gray-300 rounded px-3 py-2 bg-white" />
          </div>
          <div>
            <label class="block text-gray-700 mb-1">Email</label>
            <input type="email" :value="email" disabled
              class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
          </div>
          <div>
            <label class="block text-gray-700 mb-1">Số điện thoại</label>
            <input type="text" v-model="phone" placeholder="Không bắt buộc"
              class="w-full border border-gray-300 rounded px-3 py-2 bg-white" />
          </div>
          <div>
            <label class="block text-gray-700 mb-1">Vai trò</label>
            <input type="text" :value="roleLabel" disabled
              class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
          </div>
          <div class="text-center text-lg pt-2 text-indigo-600">
            Đổi mật khẩu (để trống nếu không muốn đổi)
          </div>
          <div class="flex gap-4">
            <div class="flex-1">
              <label class="block text-gray-700 mb-1">Mật khẩu mới</label>
              <input type="password" v-model="password" placeholder="Tối thiểu 6 ký tự"
                class="w-full border border-gray-300 rounded px-3 py-2 bg-white" />
            </div>
            <div class="flex-1">
              <label class="block text-gray-700 mb-1">Xác nhận mật khẩu</label>
              <input type="password" v-model="confirmPassword" placeholder="Nhập lại mật khẩu mới"
                class="w-full border border-gray-300 rounded px-3 py-2 bg-white" />
            </div>
          </div>
          <div class="flex gap-4 justify-center">
            <button type="submit"
              class="mt-4 px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition disabled:opacity-60"
              :disabled="saving">
              {{ saving ? 'Đang lưu...' : 'Chỉnh sửa thông tin' }}
            </button>
            <button type="button"
              class="mt-4 px-4 py-2 rounded-xl bg-red-600 text-white font-bold shadow hover:bg-red-700 transition"
              @click="handleLogout">
              Đăng xuất
            </button>
          </div>
        </form>
      </div>
    </div>
    <AppFooter />
  </div>
</template>
