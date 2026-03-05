<script setup>
import { ref, onMounted, watch } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import CreateUserModal from '@/components/CreateUserModal.vue'
import EditUserModal from '@/components/EditUserModal.vue'
import { useAdminApi } from '@/composables/useAdminApi.js'
import { toast } from 'vue3-toastify'
import { useAuthStore } from '@/stores/auth'
const { getUsers, updateUser, deleteUser } = useAdminApi()
const userId = useAuthStore().user?.id
// Dữ liệu mẫu
const users = ref([])
const total = ref(0)
const loading = ref(false)
const page = ref(1)
const limit = ref(5)
const search = ref('')
const duyetMode = ref(true) // true: actived, false: banned

async function fetchUsers() {
  loading.value = true
  const params = {
    page: page.value,
    limit: limit.value,
    search: search.value.trim(),
    status: duyetMode.value ? 'actived' : 'banned',
  }
  const res = await getUsers(params)
  if (res.ok) {
    console.log('Users fetched:', res)
    users.value = res.data ?? []
    total.value = res.pagination?.total ?? 0
  } else {
    users.value = []
    total.value = 0
  }
  loading.value = false
}


// Modal xác nhận thao tác
const confirmModalVisible = ref(false)
const confirmAction = ref(null)
const confirmUser = ref(null)
const confirmMessage = ref('')

function showConfirmModal(action, user, message) {
  confirmAction.value = action
  confirmUser.value = user
  confirmMessage.value = message
  confirmModalVisible.value = true
}

async function handleConfirm() {
  if (!confirmAction.value || !confirmUser.value) return
  if (confirmAction.value === 'lock') {
    await updateUser(confirmUser.value.id, { ...confirmUser.value, status: 'banned' })
    fetchUsers()
    toast.success(`Đã khóa tài khoản ${confirmUser.value.fullname}`)
    console.log('Khóa tài khoản', confirmUser.value)
  } else if (confirmAction.value === 'unlock') {
    await updateUser(confirmUser.value.id, { ...confirmUser.value, status: 'actived' })
    fetchUsers()
    toast.success(`Đã mở khóa tài khoản ${confirmUser.value.fullname}`)
    console.log('Mở khóa tài khoản', confirmUser.value)
  } else if (confirmAction.value === 'delete') {
    // Gọi API xóa tài khoản
    await deleteUser(confirmUser.value.id)
    fetchUsers()
    toast.success(`Đã xóa tài khoản ${confirmUser.value.fullname}`)
    console.log('Xóa tài khoản', confirmUser.value)
  }
  confirmModalVisible.value = false
}

function handleCancel() {
  confirmModalVisible.value = false
}

const lockUser = (user) => {
  //Không thể tự khoá bản thân
  if (user.id === userId) {
    toast.error('Bạn không thể khóa tài khoản của chính mình!')
    return
  }
  showConfirmModal('lock', user, `Bạn có chắc chắn muốn khóa tài khoản ${user.fullname}?`)
}

const unlockUser = (user) => {
  //Không thể tự mở khóa bản thân
  if (user.id === userId) {
    toast.error('Bạn không thể mở khóa tài khoản của chính mình!')
    return
  }
  showConfirmModal('unlock', user, `Bạn có chắc chắn muốn mở khóa tài khoản ${user.fullname}?`)
}

const deleteUserById = (user) => {
  //Không thể tự xóa bản thân
  if (user.id === userId) {
    toast.error('Bạn không thể xóa tài khoản của chính mình!')
    return
  }
  showConfirmModal('delete', user, `Bạn có chắc chắn muốn xóa tài khoản ${user.fullname}?`)
}


onMounted(fetchUsers)
watch([page, limit, duyetMode], fetchUsers)

function switchTab(mode) {
  users.value = []
  duyetMode.value = mode
  page.value = 1
  fetchUsers()
}
function onSearch(e) {
  e.preventDefault()
  page.value = 1
  fetchUsers()
}
function gotoPage(p) {
  page.value = p
  fetchUsers()
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
            placeholder="Tìm theo tên, email..."
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
      
      <div v-if="users.length > 0" class="overflow-x-auto">
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
            <tr v-for="(user, idx) in users" :key="user.id" class="hover:bg-indigo-50">
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
                    @click="unlockUser(user)"
                  >
                    🔓 Mở khóa
                  </button>
                  <button
                    class="bg-red-400 text-white text-sm rounded px-2 py-1 hover:bg-red-700 transition"
                    @click="deleteUserById(user)"
                  >
                    🗑️ Xóa
                  </button>
                </template>
                <template v-else>
                  <button
                    class="bg-yellow-600 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-yellow-700 transition"
                    @click="lockUser(user)"
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
      <div v-if="loading" class="text-center py-4 text-blue-500">Đang tải...</div>

      <!-- Phân trang -->
      <div v-if="total > limit" class="flex justify-center mt-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in Math.ceil(total / limit)" :key="i">
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
  <CreateUserModal :visible="createUserModalVisible" :close="closeCreateUserModal" @created="fetchUsers" />
  <EditUserModal :visible="editUserModalVisible" @close="closeEditUserModal" :user="selectedUser" @update="fetchUsers"/>

  <!-- Modal xác nhận thao tác -->
  <div v-if="confirmModalVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm animate-fade-in">
      <div class="mb-4 text-lg font-semibold text-gray-800">Xác nhận thao tác</div>
      <div class="mb-6 text-gray-700">{{ confirmMessage }}</div>
      <div class="flex justify-end gap-2">
        <button @click="handleCancel" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Huỷ</button>
        <button @click="handleConfirm" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Xác nhận</button>
      </div>
    </div>
  </div>
</template>
