<script setup>
import { ref } from 'vue'
const props = defineProps({
  visible: Boolean,
  close: Function,
})

const fullname = ref('')
const username = ref('')
const email = ref('')
const phone = ref('')
const role = ref('student')
const errors = ref([])

function resetForm() {
  fullname.value = ''
  username.value = ''
  email.value = ''
  phone.value = ''
  role.value = 'student'
  errors.value = []
}

function closeModal() {
  resetForm()
  props.close()
}

function submitForm() {
  errors.value = []
  if (!fullname.value) errors.value.push('Vui lòng nhập họ tên.')
  if (!username.value) errors.value.push('Vui lòng nhập tên đăng nhập.')
  if (!email.value) errors.value.push('Vui lòng nhập email.')
  if (!phone.value) errors.value.push('Vui lòng nhập số điện thoại.')
  if (errors.value.length) return

  resetForm()
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
  >
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg relative animate-fade-in">
      <h3 class="text-xl font-bold mb-4 text-indigo-700 text-center mb-6">Thêm tài khoản mới</h3>
      <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitForm">
        <div class="form-group relative col-span-1">
          <input
            v-model="fullname"
            type="text"
            id="fullname"
            name="fullname"
            required
            placeholder=""
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="fullname"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
          >
            Họ tên
          </label>
        </div>

        <div class="form-group relative col-span-1">
          <input
            v-model="username"
            type="text"
            id="username"
            name="username"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="username"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
            >Tên đăng nhập</label
          >
        </div>
        <div class="form-group relative col-span-1">
          <input
            v-model="password"
            type="password"
            id="password"
            name="password"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="password"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
            >Mật khẩu</label
          >
        </div>
        <div class="form-group relative col-span-1">
          <input
            v-model="confirmPassword"
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="confirmPassword"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
            >Xác nhận mật khẩu</label
          >
        </div>
        <div class="form-group relative col-span-1">
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
            >Email</label
          >
        </div>
        <div class="form-group relative col-span-1">
          <input
            v-model="phone"
            type="text"
            id="phone"
            name="phone"
            required
            placeholder=" "
            class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          />
          <label
            for="phone"
            class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700"
            >Số điện thoại</label
          >
        </div>
        <div class="form-group col-span-1 md:col-span-2">
          <select
            v-model="role"
            name="role"
            required
            class="px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full"
          >
            <option value="teacher">Giáo viên</option>
            <option value="student">Học sinh</option>
            <option value="admin">Quản trị viên</option>
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
            Thêm tài khoản
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
