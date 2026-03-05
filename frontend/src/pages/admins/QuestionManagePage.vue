<template>
  <AdminHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <div class="w-full max-w-6xl mx-auto bg-white rounded-xl shadow p-6 animate-fade-in my-8">
      <h2 class="text-2xl font-bold mb-6 text-indigo-700 text-center">Quản lý ngân hàng câu hỏi</h2>

      <!-- Search form -->
      <form
        @submit.prevent="searchQuestions"
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4"
      >
        <div class="flex gap-2 w-full md:w-auto">
          <input
            v-model="searchClass"
            type="text"
            placeholder="Tìm theo lớp"
            class="form-input border rounded px-3 py-2 w-full md:w-48"
          />
          <input
            v-model="searchLevel"
            type="text"
            placeholder="Tìm theo mức độ"
            class="form-input border rounded px-3 py-2 w-full md:w-48"
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
        <strong>Tổng số câu hỏi:</strong> {{ total }}
      </div>

      <div v-if="loading" class="text-center py-4 text-blue-500">Đang tải...</div>
      <div v-else-if="questions.length > 0" class="overflow-x-auto">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-1 py-2 border">ID</th>
              <th class="px-3 py-2 border">Lớp</th>
              <th class="px-3 py-2 border">Mức độ</th>
              <th class="px-3 py-2 border">Nội dung</th>
              <th class="px-3 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="q in questions" :key="q.id" class="hover:bg-indigo-50">
              <td class="px-3 py-2 border font-bold text-center">{{ q.id }}</td>
              <td class="px-3 py-2 border">{{ q.class_name }}</td>
              <td class="px-3 py-2 border">{{ q.level_name }}</td>
              <td class="px-3 py-2 border">
                {{ q.question.length > 100 ? q.question.slice(0, 100) + '...' : q.question }}
              </td>
              <td class="px-3 py-2 border text-center">
                <button
                  @click="openDeleteModal(q)"
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-red-700 transition"
                  :disabled="deletingId === q.id"
                >
                  🗑️ Xoá
                </button>
              </td>
                  <!-- Delete Confirm Modal -->
                  <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm animate-fade-in">
                      <div class="text-lg font-semibold mb-4 text-red-600">Xác nhận xoá câu hỏi</div>
                      <div class="mb-4">Bạn có chắc chắn muốn xoá câu hỏi <b>{{ questionToDelete?.question?.slice(0, 50) }}...</b> không?</div>
                      <div class="flex justify-end gap-2">
                        <button @click="showDeleteModal = false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Huỷ</button>
                        <button @click="confirmDeleteQuestion" :disabled="deletingId === questionToDelete?.id" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-700">
                          Xoá
                        </button>
                      </div>
                    </div>
                  </div>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-8 text-center">Không có câu hỏi nào.</div>

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
import { ref, watch, onMounted } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import { useAdminApi } from '@/composables/useAdminApi.js'
import { toast } from 'vue3-toastify'

// Dữ liệu mẫu

const questions = ref([])
const total = ref(0)
const loading = ref(false)
const { getQuestions, deleteQuestion } = useAdminApi()
const searchLevel = ref('')
const searchClass = ref('')
const deletingId = ref(null)
const showDeleteModal = ref(false)
const questionToDelete = ref(null)
const currentPage = ref(1)
const pageSize = ref(5)

async function fetchQuestions() {
  loading.value = true
  const params = {
    page: currentPage.value,
    limit: pageSize.value,
    class: searchClass.value.trim(),
    difficulty: searchLevel.value.trim(),
  }
  const res = await getQuestions(params)
  if (res.ok) {
    console.log('Fetched questions:', res)
    questions.value = res.data ?? []
    total.value = res.pagination?.total ?? 0
  } else {
    questions.value = []
    total.value = 0
  }
  loading.value = false
}

onMounted(fetchQuestions)
watch([currentPage, pageSize], fetchQuestions)

function searchQuestions() {
  currentPage.value = 1
  fetchQuestions()
}
function gotoPage(p) {
  currentPage.value = p
  fetchQuestions()
}


function openDeleteModal(q) {
  questionToDelete.value = q
  showDeleteModal.value = true
}

async function confirmDeleteQuestion() {
  if (!questionToDelete.value) return
  deletingId.value = questionToDelete.value.id
  try {
    const res = await deleteQuestion(questionToDelete.value.id)
    if (res.ok) {
      questions.value = questions.value.filter((q) => q.id !== questionToDelete.value.id)
      showDeleteModal.value = false
      questionToDelete.value = null
      toast.success('Đã xoá câu hỏi!')
    } else {
      toast.error(res.error?.message || 'Lỗi xoá câu hỏi')
    }
  } catch (e) {
    toast.error(e?.message || 'Lỗi xoá câu hỏi')
  } finally {
    deletingId.value = null
  }
}
</script>

<style scoped>
.btn {
  transition: background 0.2s;
}
.btn-danger:hover {
  background: #dc2626;
}
</style>
