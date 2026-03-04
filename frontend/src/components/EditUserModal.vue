<script setup>
import { useAdminApi } from '@/composables/useAdminApi.js'
const { updateUser } = useAdminApi()
import { ref, watch} from 'vue'
import { toast } from 'vue3-toastify'
const props = defineProps({
  visible: Boolean,
  user: Object,
})
const emit = defineEmits(['close', 'update'])
const fullname = ref('')
const email = ref('')
const phone = ref('')
const status = ref('actived')
const role = ref('student')
const newPassword = ref('')
const confirmPassword = ref('')
const errors = ref([])

watch(
  () => props.user,
  (val) => {
    if (val) {
      fullname.value = val.fullname || ''
      email.value = val.email || ''
      phone.value = val.phone || ''
      status.value = val.status || 'actived'
      role.value = val.role || 'student'
      newPassword.value = ''
      confirmPassword.value = ''
      errors.value = []
    }
  },
  { immediate: true },
)

function closeModal() {
  emit('close')
}

function submitForm() {
  errors.value = []
  if (!fullname.value) errors.value.push('Vui lòng nhập họ tên.')
  if (!email.value) errors.value.push('Vui lòng nhập email.')
  if (newPassword.value && newPassword.value.length < 6) errors.value.push('Mật khẩu mới phải có ít nhất 6 ký tự.')
  if (confirmPassword.value && newPassword.value !== confirmPassword.value) errors.value.push('Mật khẩu xác nhận không khớp.')
  if (errors.value.length) return
  const updatedData = {
    fullname: fullname.value,
    email: email.value,
    phone: phone.value,
    status: status.value,
    role: role.value,
  }
  if (newPassword.value) updatedData.password = newPassword.value
  updateUser(props.user.id, updatedData)
    .then((res) => {
      if (res.ok) {
        emit('update')
        closeModal()
        toast.success('Cập nhật thông tin người dùng thành công!')
      } else {
        errors.value.push(res.error?.message || 'Lỗi cập nhật tài khoản')
      }
    })
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
  >
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg relative animate-fade-in">
      <h3 class="text-xl font-bold mb-4 text-indigo-700 text-center mb-6">
        Cập nhật thông tin người dùng
      </h3>
      <form class="grid grid-cols-1 gap-4" @submit.prevent="submitForm">
        <div v-if="errors.length" class="mb-3">
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
            <ul class="mb-0 pl-4 list-disc">
              <li v-for="e in errors" :key="e">{{ e }}</li>
            </ul>
          </div>
        </div>
        <div class="form-group relative">
          <input
            v-model="fullname"
            type="text"
            id="fullname"
            name="fullname"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="fullname"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Họ tên
          </label>
        </div>
        <div class="form-group relative">
          <input
            :value="props.user?.username"
            type="text"
            id="username"
            name="username"
            disabled
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 bg-gray-100 text-black w-full cursor-not-allowed"
          />
          <label
            for="username"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-400 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Tên đăng nhập
          </label>
        </div>
        <div class="form-group relative">
          <input
            v-model="email"
            type="email"
            id="email"
            name="email"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="email"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Email
          </label>
        </div>
        <div class="form-group relative">
          <input
            v-model="phone"
            type="text"
            id="phone"
            name="phone"
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="phone"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Số điện thoại
          </label>
        </div>
        <div class="form-group relative">
          <input
            v-model="newPassword"
            type="password"
            id="newPassword"
            name="newPassword"
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="newPassword"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Mật khẩu mới (để trống nếu không đổi)
          </label>
        </div>
        <div class="form-group relative">
          <input
            v-model="confirmPassword"
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="confirmPassword"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Xác nhận mật khẩu
          </label>
        </div>
        <div class="form-group">
          <select
            v-model="status"
            name="status"
            required
            class="px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          >
            <option value="actived">Đã duyệt</option>
            <option value="banned">Bị khóa</option>
          </select>
        </div>
        <div class="flex justify-center gap-2 mt-4">
          <button
            type="button"
            @click="closeModal"
            class="bg-red-300 rounded-lg hover:bg-red-400 px-4 py-2 transition"
          >
            Đóng
          </button>
          <button
            type="submit"
            class="bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 px-4 py-2 transition"
          >
            Cập nhật
          </button>
        </div>
      </form>
      <button
        @click="closeModal"
        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-2xl"
      >
        &times;
      </button>
    </div>
  </div>
</template>
