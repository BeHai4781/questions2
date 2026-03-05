<template>
  <AdminHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <div class="w-full max-w-6xl mx-auto bg-white rounded-xl shadow p-6 animate-fade-in my-8">
      <h2 class="text-2xl font-bold mb-6 text-indigo-700 text-center">Quản lý đề thi</h2>

      <!-- Search form -->
      <form
        @submit.prevent="searchExams"
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4"
      >
        <div class="flex gap-2 w-full md:w-auto">
          <input
            v-model="searchTitle"
            type="text"
            placeholder="Tìm theo tiêu đề"
            class="form-input border rounded px-3 py-2 w-full md:w-64"
          />
          <input
            v-model="searchCreator"
            type="text"
            placeholder="Tìm theo người tạo"
            class="form-input border rounded px-3 py-2 w-full md:w-64 hidden"
          />
          <button
            type="submit"
            class="btn btn-outline-secondary rounded-3xl px-4 hover:bg-blue-200"
          >
            🔍
          </button>
        </div>
      </form>

      <div class="mb-3 text-center">
        <strong>Tổng số đề thi:</strong> {{ total }}
      </div>

      <div v-if="loading" class="text-center py-4 text-blue-500">Đang tải...</div>
      <div v-else-if="exams.length > 0" class="overflow-x-auto">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-1 py-2 border">ID</th>
              <th class="px-3 py-2 border">Tiêu đề</th>
              <th class="px-3 py-2 border">Người tạo</th>
              <th class="px-3 py-2 border">Ngày tạo</th>
              <th class="px-3 py-2 border">Thời gian</th>
              <th class="px-3 py-2 border">Số câu</th>
              <th class="px-3 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exam in exams" :key="exam.id" class="hover:bg-indigo-50">
              <td class="px-3 py-2 border font-bold text-center">{{ exam.id }}</td>
              <td class="px-3 py-2 border">{{ exam.title }}</td>
              <td class="px-3 py-2 border">{{ exam.created_by_name }}</td>
              <td class="px-3 py-2 border">{{ exam.created_at }}</td>
              <td class="px-3 py-2 border">{{ exam.duration }} phút</td>
              <td class="px-3 py-2 border">{{ exam.total_questions }}</td>
              <td class="px-3 py-2 border text-center">
                <button
                  @click="openDeleteModal(exam)"
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-red-700 transition"
                  :disabled="deletingId === exam.id"
                >
                  🗑️ Xoá
                </button>
              </td>
                  <!-- Delete Confirm Modal -->
                  <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm animate-fade-in">
                      <div class="text-lg font-semibold mb-4 text-red-600">Xác nhận xoá đề thi</div>
                      <div class="mb-4">Bạn có chắc chắn muốn xoá đề thi <b>{{ examToDelete?.title }}</b> không?</div>
                      <div class="flex justify-end gap-2">
                        <button @click="showDeleteModal = false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Huỷ</button>
                        <button @click="confirmDeleteExam" :disabled="deletingId === examToDelete?.id" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-700">
                          Xoá
                        </button>
                      </div>
                    </div>
                  </div>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-8 text-center">Không có đề thi nào.</div>

      <!-- Pagination -->
      <div v-if="total > pageSize" class="flex justify-center mt-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in Math.ceil(total / pageSize)" :key="i">
              <button
                @click="gotoPage(i)"
                :class="[
                  'px-3 py-1 border rounded-l',
                  i === currentPage
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
import { ref, onMounted } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import { useAdminApi } from '@/composables/useAdminApi.js'
import { toast } from 'vue3-toastify'
// Dữ liệu mẫu
const exams = ref([])
const total = ref(0)
const loading = ref(false)
const searchTitle = ref('')
const searchCreator = ref('')
const deletingId = ref(null)
const showDeleteModal = ref(false)
const examToDelete = ref(null)
const currentPage = ref(1)
const pageSize = ref(5)
const { getExams, deleteExam } = useAdminApi()

async function fetchExams() {
  loading.value = true
  const params = {
    page: currentPage.value,
    limit: pageSize.value,
    search: searchTitle.value.trim(),
    created_by: searchCreator.value.trim(),
  }
  const res = await getExams(params)
  if (res.ok) {
    console.log('Exams fetched:', res)
    exams.value = res.data ?? []
    total.value = res.pagination?.total ?? 0
  } else {
    exams.value = []
    total.value = 0
  }
  loading.value = false
}

import { watch } from 'vue'
onMounted(fetchExams)
watch([currentPage, pageSize], fetchExams)

function searchExams() {
  currentPage.value = 1
  fetchExams()
}
function gotoPage(p) {
  currentPage.value = p
  fetchExams()
}


function openDeleteModal(exam) {
  examToDelete.value = exam
  showDeleteModal.value = true
}

async function confirmDeleteExam() {
  if (!examToDelete.value) return
  deletingId.value = examToDelete.value.id
  try {
    const res = await deleteExam(examToDelete.value.id)
    if (res.ok) {
      exams.value = exams.value.filter((e) => e.id !== examToDelete.value.id)
      showDeleteModal.value = false
      examToDelete.value = null
      toast.success('Xoá đề thi thành công')
    } else {
      toast.error(res.error?.message || 'Lỗi xoá đề thi')
    }
  } catch (e) {
    toast.error(e?.message || 'Lỗi xoá đề thi')
  } finally {
    deletingId.value = null
  }
}

// ...existing code...
</script>




