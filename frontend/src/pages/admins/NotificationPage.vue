<template>
  <AdminHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <div class="w-full max-w-6xl mx-auto bg-white rounded-xl shadow p-6 animate-fade-in my-8">
      <h2 class="text-2xl font-bold mb-6 text-indigo-700 text-center">Danh sách thông báo</h2>

      <div class="mb-3 flex gap-2">
        <button
          class="px-4 py-2 rounded-lg text-white bg-red-500 hover:bg-red-600 transition font-semibold"
          @click="markAllRead"
        >
          Đánh dấu tất cả đã đọc
        </button>
      </div>

      <!-- Chi tiết thông báo -->
      <div v-if="detailNotification" class="mb-6">
        <div class="bg-gray-50 border rounded-xl shadow p-6 mb-2">
          <h3 class="font-semibold mb-2 text-lg">
            Chi tiết thông báo ({{ detailNotification.type }})
          </h3>
          <p>
            <strong>Người gửi:</strong>
            {{
              detailNotification.fullname ||
              detailNotification.username ||
              detailNotification.email ||
              'Unknown'
            }}
          </p>
          <p v-if="detailNotification.exam_title">
            <strong>Bài/Đề:</strong> {{ detailNotification.exam_title }}
          </p>
          <p v-if="detailNotification.email">
            <strong>Email:</strong> {{ detailNotification.email }}
          </p>
          <p v-if="detailNotification.content">
            <strong>Nội dung:</strong> {{ detailNotification.content }}
          </p>
          <p class="text-gray-500">Gửi lúc: {{ detailNotification.created_at }}</p>
          <div class="flex gap-2 mt-4">
            <button
              class="bg-red-400 text-white text-sm rounded px-4 py-2 hover:bg-red-700 transition"
              @click="deleteNotification(detailNotification.id)"
            >
              Xóa thông báo
            </button>
            <button
              class="bg-gray-300 text-gray-800 text-sm rounded px-4 py-2 hover:bg-gray-400 transition"
              @click="closeDetail"
            >
              Đóng
            </button>
          </div>
        </div>
      </div>

      <!-- 1. Yêu cầu duyệt tài khoản -->
      <h4 id="approvalSection" class="mt-6 mb-2 text-lg font-semibold">
        🟢 Yêu cầu duyệt tài khoản
      </h4>
      <div v-if="pagedApproval.length > 0" class="overflow-x-auto mb-4">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-2 border">Tên</th>
              <th class="px-2 py-2 border">Thời gian</th>
              <th class="px-2 py-2 border">Trạng thái</th>
              <th class="px-2 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="notice in pagedApproval" :key="notice.id" class="hover:bg-indigo-50">
              <td class="px-2 py-2 border font-bold">{{ notice.fullname || 'Chưa có tên' }}</td>
              <td class="px-2 py-2 border">{{ notice.created_at }}</td>
              <td class="px-2 py-2 border text-center">
                <span v-if="!notice.is_read" class="badge bg-red-500 text-white">Mới</span>
                <span v-else class="badge bg-gray-400 text-white">Đã đọc</span>
              </td>
              <td class="px-2 py-2 border text-center">
                <button
                  class="bg-blue-600 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-blue-700 transition"
                  @click="viewDetail(notice, 'new_user')"
                >
                  Xem
                </button>
                <button
                  class="bg-yellow-400 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-yellow-500 transition"
                  @click="markRead(notice, 'new_user')"
                >
                  Đã đọc
                </button>
                <button
                  class="bg-green-500 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-green-600 transition"
                  @click="approveUser(notice.user_id)"
                >
                  Duyệt
                </button>
                <button
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 hover:bg-red-700 transition"
                  @click="deleteNotification(notice.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-4 text-center">Không có yêu cầu duyệt tài khoản.</div>
      <div v-if="totalPagesApproval > 1" class="flex justify-center mb-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in totalPagesApproval" :key="i">
              <button
                @click="pageApproval = i"
                :class="[
                  'px-3 py-1 border rounded-l',
                  i === pageApproval
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

      <!-- 2. Bài viết mới -->
      <h4 id="newPostSection" class="mt-6 mb-2 text-lg font-semibold">📝 Bài viết mới</h4>
      <div v-if="pagedNewPost.length > 0" class="overflow-x-auto mb-4">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-2 border">Người đăng</th>
              <th class="px-2 py-2 border">Tiêu đề</th>
              <th class="px-2 py-2 border">Thời gian</th>
              <th class="px-2 py-2 border">Trạng thái</th>
              <th class="px-2 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="notice in pagedNewPost" :key="notice.id" class="hover:bg-indigo-50">
              <td class="px-2 py-2 border font-bold">{{ notice.username || 'Unknown' }}</td>
              <td class="px-2 py-2 border">{{ notice.exam_title || 'Chưa có tiêu đề' }}</td>
              <td class="px-2 py-2 border">{{ notice.created_at }}</td>
              <td class="px-2 py-2 border text-center">
                <span v-if="!notice.is_read" class="badge bg-red-500 text-white">Mới</span>
                <span v-else class="badge bg-gray-400 text-white">Đã đọc</span>
              </td>
              <td class="px-2 py-2 border text-center">
                <button
                  class="bg-blue-600 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-blue-700 transition"
                  @click="viewDetail(notice, 'new_exam')"
                >
                  Xem
                </button>
                <button
                  class="bg-yellow-400 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-yellow-500 transition"
                  @click="markRead(notice, 'new_exam')"
                >
                  Đã đọc
                </button>
                <button
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 hover:bg-red-700 transition"
                  @click="deleteNotification(notice.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-4 text-center">Không có bài viết mới.</div>
      <div v-if="totalPagesNewPost > 1" class="flex justify-center mb-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in totalPagesNewPost" :key="i">
              <button
                @click="pageNewPost = i"
                :class="[
                  'px-3 py-1 border rounded-l',
                  i === pageNewPost
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

      <!-- 3. Liên hệ -->
      <h4 id="contactSection" class="mt-6 mb-2 text-lg font-semibold">📩 Liên hệ</h4>
      <div v-if="pagedContact.length > 0" class="overflow-x-auto mb-4">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-2 border">Email</th>
              <th class="px-2 py-2 border">Nội dung</th>
              <th class="px-2 py-2 border">Thời gian</th>
              <th class="px-2 py-2 border">Trạng thái</th>
              <th class="px-2 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="notice in pagedContact" :key="notice.id" class="hover:bg-indigo-50">
              <td class="px-2 py-2 border font-bold">{{ notice.email || 'Unknown' }}</td>
              <td class="px-2 py-2 border">
                {{
                  notice.content.length > 100
                    ? notice.content.slice(0, 100) + '...'
                    : notice.content
                }}
              </td>
              <td class="px-2 py-2 border">{{ notice.created_at }}</td>
              <td class="px-2 py-2 border text-center">
                <span v-if="!notice.is_read" class="badge bg-red-500 text-white">Mới</span>
                <span v-else class="badge bg-gray-400 text-white">Đã đọc</span>
              </td>
              <td class="px-2 py-2 border text-center">
                <button
                  class="bg-blue-600 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-blue-700 transition"
                  @click="viewDetail(notice, 'new_contact')"
                >
                  Xem
                </button>
                <button
                  class="bg-yellow-400 text-white text-sm rounded px-2 py-1 mr-1 hover:bg-yellow-500 transition"
                  @click="markRead(notice, 'new_contact')"
                >
                  Đã đọc
                </button>
                <button
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 hover:bg-red-700 transition"
                  @click="deleteNotification(notice.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-4 text-center">Không có liên hệ mới.</div>
      <div v-if="totalPagesContact > 1" class="flex justify-center mb-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in totalPagesContact" :key="i">
              <button
                @click="pageContact = i"
                :class="[
                  'px-3 py-1 border rounded-l',
                  i === pageContact
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
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'

// Dữ liệu mẫu
const approvalNotifications = ref([
  { id: 1, fullname: 'Nguyễn Văn A', user_id: 101, created_at: '2023-12-01', is_read: false },
  { id: 2, fullname: 'Trần Thị B', user_id: 102, created_at: '2023-12-02', is_read: true },
])
const newPostNotifications = ref([
  {
    id: 3,
    username: 'teacher1',
    exam_title: 'Đề thi Toán',
    created_at: '2023-12-03',
    is_read: false,
  },
  {
    id: 4,
    username: 'teacher2',
    exam_title: 'Đề thi Văn',
    created_at: '2023-12-04',
    is_read: true,
  },
])
const contactNotifications = ref([
  {
    id: 5,
    email: 'user@email.com',
    content: 'Tôi cần hỗ trợ về tài khoản.',
    created_at: '2023-12-05',
    is_read: false,
  },
  {
    id: 6,
    email: 'guest@email.com',
    content: 'Làm sao để đăng ký?',
    created_at: '2023-12-06',
    is_read: true,
  },
])

const detailNotification = ref(null)

// Pagination
const pageApproval = ref(1)
const pageNewPost = ref(1)
const pageContact = ref(1)
const limit = 5

const totalPagesApproval = computed(() => Math.ceil(approvalNotifications.value.length / limit))
const totalPagesNewPost = computed(() => Math.ceil(newPostNotifications.value.length / limit))
const totalPagesContact = computed(() => Math.ceil(contactNotifications.value.length / limit))

const pagedApproval = computed(() =>
  approvalNotifications.value.slice((pageApproval.value - 1) * limit, pageApproval.value * limit),
)
const pagedNewPost = computed(() =>
  newPostNotifications.value.slice((pageNewPost.value - 1) * limit, pageNewPost.value * limit),
)
const pagedContact = computed(() =>
  contactNotifications.value.slice((pageContact.value - 1) * limit, pageContact.value * limit),
)

function viewDetail(notice, type) {
  detailNotification.value = { ...notice, type }
}
function closeDetail() {
  detailNotification.value = null
}
function markRead(notice, type) {
  notice.is_read = true
}
function markAllRead() {
  approvalNotifications.value.forEach((n) => (n.is_read = true))
  newPostNotifications.value.forEach((n) => (n.is_read = true))
  contactNotifications.value.forEach((n) => (n.is_read = true))
}
function deleteNotification(id) {
  approvalNotifications.value = approvalNotifications.value.filter((n) => n.id !== id)
  newPostNotifications.value = newPostNotifications.value.filter((n) => n.id !== id)
  contactNotifications.value = contactNotifications.value.filter((n) => n.id !== id)
  if (detailNotification.value && detailNotification.value.id === id)
    detailNotification.value = null
}
function approveUser(user_id) {
  alert('Chức năng duyệt tài khoản: ' + user_id)
}
</script>

<style scoped>
.btn {
  transition: background 0.2s;
}
.btn-danger:hover {
  background: #dc2626;
}
.badge {
  font-size: 0.85em;
  padding: 0.2em 0.6em;
  border-radius: 0.5em;
}
.card {
  background: #f8fafc;
}
</style>
