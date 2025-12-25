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
            v-model="searchSubject"
            type="text"
            placeholder="Tìm theo môn học"
            class="form-input border rounded px-3 py-2 w-full md:w-48"
          />
          <input
            v-model="searchClass"
            type="text"
            placeholder="Tìm theo lớp"
            class="form-input border rounded px-3 py-2 w-full md:w-48"
          />
          <button
            type="submit"
            class="btn btn-outline-secondary rounded-3xl px-4 hover:bg-blue-200"
          >
            🔍
          </button>
        </div>
        <button
          class="px-4 py-2 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition font-semibold"
          disabled
        >
          ➕ Thêm câu hỏi
        </button>
      </form>

      <div class="mb-3 text-center">
        <strong>Tổng số câu hỏi:</strong> {{ filteredQuestions.length }}
      </div>

      <div v-if="paginatedQuestions.length > 0" class="overflow-x-auto">
        <table class="min-w-full table-auto border rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-1 py-2 border">ID</th>
              <th class="px-3 py-2 border">Môn học</th>
              <th class="px-3 py-2 border">Lớp</th>
              <th class="px-3 py-2 border">Nội dung</th>
              <th class="px-3 py-2 border">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="q in paginatedQuestions" :key="q.id" class="hover:bg-indigo-50">
              <td class="px-3 py-2 border font-bold text-center">{{ q.id }}</td>
              <td class="px-3 py-2 border">{{ q.subject_name }}</td>
              <td class="px-3 py-2 border">{{ q.class_name }}</td>
              <td class="px-3 py-2 border">
                {{ q.question.length > 100 ? q.question.slice(0, 100) + '...' : q.question }}
              </td>
              <td class="px-3 py-2 border text-center">
                <button
                  @click="deleteQuestion(q.id)"
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-red-700 transition"
                  :disabled="deletingId === q.id"
                >
                  🗑️ Xoá
                </button>
                <button
                  class="bg-blue-600 text-white text-sm rounded px-2 py-1 hover:bg-blue-700 transition"
                  disabled
                >
                  ✏️ Sửa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-8 text-center">Không có câu hỏi nào.</div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center mt-4">
        <nav>
          <ul class="inline-flex -space-x-px">
            <li v-for="i in totalPages" :key="i">
              <button
                @click="currentPage = i"
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
import { ref, computed } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'

// Dữ liệu mẫu
const questions = ref([
  {
    id: 1,
    subject_name: 'Toán',
    class_name: '12',
    question: 'Giải phương trình bậc hai: x^2 - 5x + 6 = 0.',
  },
  {
    id: 2,
    subject_name: 'Văn',
    class_name: '11',
    question: 'Phân tích nhân vật Tràng trong Vợ nhặt.',
  },
  {
    id: 3,
    subject_name: 'Anh',
    class_name: '10',
    question: 'Write an essay about your favorite hobby.',
  },
  {
    id: 4,
    subject_name: 'Lý',
    class_name: '12',
    question: 'Trình bày định luật II Newton và lấy ví dụ minh hoạ.',
  },
  {
    id: 5,
    subject_name: 'Hoá',
    class_name: '11',
    question: 'Tính số mol của 22,4 lít khí oxi ở đktc.',
  },
  // ...more data
])

const searchSubject = ref('')
const searchClass = ref('')
const deletingId = ref(null)
const currentPage = ref(1)
const pageSize = 5

const filteredQuestions = computed(() => {
  return questions.value.filter(
    (q) =>
      (!searchSubject.value ||
        q.subject_name.toLowerCase().includes(searchSubject.value.toLowerCase())) &&
      (!searchClass.value || q.class_name.toLowerCase().includes(searchClass.value.toLowerCase())),
  )
})

const totalPages = computed(() => Math.ceil(filteredQuestions.value.length / pageSize))
const paginatedQuestions = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredQuestions.value.slice(start, start + pageSize)
})

function searchQuestions() {
  currentPage.value = 1
}

function deleteQuestion(id) {
  if (confirm('Xoá câu hỏi này?')) {
    deletingId.value = id
    setTimeout(() => {
      questions.value = questions.value.filter((q) => q.id !== id)
      deletingId.value = null
    }, 500) // Simulate API delay
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
