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
            class="form-input border rounded px-3 py-2 w-full md:w-64"
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
        >
          ➕ Thêm đề thi
        </button>
      </form>

      <div class="mb-3 text-left">
        <strong>Tổng số đề thi:</strong> {{ filteredExams.length }}
      </div>

      <div v-if="paginatedExams.length > 0" class="overflow-x-auto">
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
            <tr v-for="exam in paginatedExams" :key="exam.id" class="hover:bg-indigo-50">
              <td class="px-3 py-2 border font-bold text-center">{{ exam.id }}</td>
              <td class="px-3 py-2 border">{{ exam.title }}</td>
              <td class="px-3 py-2 border">{{ exam.creator }}</td>
              <td class="px-3 py-2 border">{{ exam.created_at }}</td>
              <td class="px-3 py-2 border">{{ exam.duration }} phút</td>
              <td class="px-3 py-2 border">{{ exam.total_questions }}</td>
              <td class="px-3 py-2 border text-center">
                <button
                  @click="deleteExam(exam.id)"
                  class="bg-red-400 text-white text-sm rounded px-2 py-1 mr-2 hover:bg-red-700 transition"
                  :disabled="deletingId === exam.id"
                >
                  🗑️ Xoá
                </button>
                <button
                  class="bg-blue-600 text-white text-sm rounded px-2 py-1 hover:bg-blue-700 transition"
                >
                  ✏️ Sửa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-gray-400 py-8 text-center">Không có đề thi nào.</div>

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
const exams = ref([
  {
    id: 1,
    title: 'Đề thi Toán',
    creator: 'teacher1',
    created_at: '2023-12-01',
    duration: 60,
    total_questions: 40,
  },
  {
    id: 2,
    title: 'Đề thi Văn',
    creator: 'teacher2',
    created_at: '2023-12-10',
    duration: 45,
    total_questions: 30,
  },
  {
    id: 3,
    title: 'Đề thi Anh',
    creator: 'teacher3',
    created_at: '2023-12-15',
    duration: 50,
    total_questions: 35,
  },
  // ...more data
])

const searchTitle = ref('')
const searchCreator = ref('')
const deletingId = ref(null)
const currentPage = ref(1)
const pageSize = 5

const filteredExams = computed(() => {
  return exams.value.filter(
    (e) =>
      (!searchTitle.value || e.title.toLowerCase().includes(searchTitle.value.toLowerCase())) &&
      (!searchCreator.value || e.creator.toLowerCase().includes(searchCreator.value.toLowerCase())),
  )
})

const totalPages = computed(() => Math.ceil(filteredExams.value.length / pageSize))
const paginatedExams = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredExams.value.slice(start, start + pageSize)
})

function searchExams() {
  currentPage.value = 1
}

function deleteExam(id) {
  if (confirm('Xoá đề thi này?')) {
    deletingId.value = id
    setTimeout(() => {
      exams.value = exams.value.filter((e) => e.id !== id)
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
