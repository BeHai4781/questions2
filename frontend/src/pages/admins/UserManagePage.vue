<script setup>
import { ref, computed } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import CreateUserModal from '@/components/CreateUserModal.vue'
import EditUserModal from '@/components/EditUserModal.vue'

// Dữ liệu mẫu
const allUsers = [
  {
    id: 1,
    fullname: 'Nguyễn Văn A',
    email: 'a@email.com',
    phone: '0123456789',
    username: 'nguyenvana',
    role: 'student',
    status: 'active',
  },
  {
    id: 2,
    fullname: 'Trần Thị B',
    email: 'b@email.com',
    phone: '0987654321',
    username: 'tranthib',
    role: 'teacher',
    status: 'banned',
  },
  {
    id: 3,
    fullname: 'Lê Văn C',
    email: 'c@email.com',
    phone: '0111222333',
    username: 'levanc',
    role: 'admin',
    status: 'active',
  },
  {
    id: 4,
    fullname: 'Phạm Thị D',
    email: 'd@email.com',
    phone: '0222333444',
    username: 'phamthid',
    role: 'student',
    status: 'banned',
  },
  {
    id: 5,
    fullname: 'Hoàng Văn E',
    email: 'e@email.com',
    phone: '0333444555',
    username: 'hoangvane',
    role: 'teacher',
    status: 'active',
  },
]

const duyetMode = ref(true) // true: đang hoạt động, false: bị cấm
const search = ref('')
const page = ref(1)
const limit = 5

const filteredUsers = computed(() => {
  let users = allUsers.filter((u) =>
    duyetMode.value ? u.status === 'actived' : u.status === 'banned',
  )
  if (search.value.trim()) {
    const s = search.value.trim().toLowerCase()
    users = users.filter(
      (u) =>
        u.fullname.toLowerCase().includes(s) ||
        u.email.toLowerCase().includes(s) ||
        u.phone.includes(s) ||
        u.username.toLowerCase().includes(s),
    )
  }
  return users
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / limit))
const pagedUsers = computed(() =>
  filteredUsers.value.slice((page.value - 1) * limit, page.value * limit),
)

function switchTab(mode) {
  duyetMode.value = mode
  page.value = 1
}
function onSearch(e) {
  e.preventDefault()
  page.value = 1
}
function gotoPage(p) {
  page.value = p
}

const createUserModalVisible = ref(false)
const closeCreateUserModal = () => {
  createUserModalVisible.value = false
}

const editUserModalVisible = ref(false)
const selectedUser = ref(null)
const editUser = (user) => {
    selectedUser.value = user   
    editUserModalVisible.value = true

}
const closeEditUserModal = () => {
  editUserModalVisible.value = false
}
</script>

<template>
  <AdminHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <div class="w-full max-w-6xl mx-auto bg-white rounded-xl shadow p-6 animate-fade-in my-8">
      <h2 class="text-2xl font-bold mb-6">
        {{ duyetMode ? 'Danh sách tài khoản đang hoạt động' : 'Danh sách tài khoản bị cấm' }}
      </h2>

      <!-- Form tìm kiếm và thêm -->
      <form
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4"
        @submit="onSearch"
      >
        <div class="flex gap-2 w-full md:w-auto">
          <input
            v-model="search"
            type="text"
            class="form-input border rounded px-3 py-2 w-full md:w-72"
            placeholder="Tìm theo tên, email, SĐT..."
          />
          <button
            type="submit"
            class="btn btn-outline-secondary rounded-3xl px-4 hover:bg-blue-200"
          >
            🔍
          </button>
        </div>
        <button
          @click="createUserModalVisible = true"
          class="px-4 py-2 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition font-semibold"
        >
          ➕ Thêm tài khoản
        </button>
      </form>

      <!-- Tabs -->
      <div class="flex gap-2 mb-4">
        <button
          @click="switchTab(true)"
          :class="[
            'btn',
            'px-4',
            'py-2',
            'rounded',
            duyetMode ? 'btn-primary' : 'btn-outline-primary',
          ]"
        >
          👥 Danh sách người dùng
        </button>
        <button
          @click="switchTab(false)"
          :class="[
            'btn',
            'px-4',
            'py-2',
            'rounded',
            !duyetMode ? 'btn-primary' : 'btn-outline-primary',
          ]"
        >
          📋 Danh sách người dùng bị cấm
        </button>
      </div>

      <!-- Bảng danh sách -->
      <div v-if="pagedUsers.length > 0" class="overflow-x-auto">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-1 py-2 border">STT</th>
              <th class="px-3 py-2 border">Họ tên</th>
              <th class="px-3 py-2 border">Email</th>
              <th class="px-3 py-2 border">SĐT</th>
              <th class="px-3 py-2 border">Tên đăng nhập</th>
              <th class="px-3 py-2 border">Vai trò</th>
              <th class="px-3 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(user, idx) in pagedUsers" :key="user.id" class="hover:bg-indigo-50">
              <td class="px-3 py-2 border font-bold text-center">
                {{ (page - 1) * limit + idx + 1 }}
              </td>
              <td class="px-3 py-2 border">{{ user.fullname }}</td>
              <td class="px-3 py-2 border">{{ user.email }}</td>
              <td class="px-3 py-2 border">{{ user.phone }}</td>
              <td class="px-3 py-2 border">{{ user.username }}</td>
              <td class="px-3 py-2 border">{{ user.role }}</td>
              <td class="px-3 py-2 border text-center">
                <template v-if="!duyetMode">
                  <button
                    class="bg-green-400 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-green-500 transition"
                  >
                    🔓 Mở khóa
                  </button>
                  <button
                    class="bg-red-400 text-white text-sm rounded px-2 py-1 hover:bg-red-700 transition"
                  >
                    🗑️ Xóa
                  </button>
                </template>
                <template v-else>
                  <button
                    class="bg-yellow-600 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-yellow-700 transition"
                  >
                    🔒 Vô hiệu hoá
                  </button>
                  <button
                    @click="editUser(user)"
                    class="bg-blue-600 text-white text-sm rounded px-2 py-1 hover:bg-blue-700 transition"
                  >
                    ✏️ Cập nhật
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-8 text-center">Không có tài khoản nào.</div>

      <!-- Phân trang -->
      <div v-if="totalPages > 1" class="flex justify-center mt-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in totalPages" :key="i">
              <button
                @click="gotoPage(i)"
                :class="[
                  'px-3 py-1 border rounded-l',
                  i === page
                    ? 'bg-indigo-500 text-white'
                    : 'bg-white text-indigo-700 hover:bg-indigo-100',
                ]"
              >
                {{ i }}
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
    <AppFooter />
  </div>
  <CreateUserModal :visible="createUserModalVisible" :close="closeCreateUserModal" />
  <EditUserModal :visible="editUserModalVisible" @close="closeEditUserModal" :user="selectedUser" />
</template>
