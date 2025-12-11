<script setup>
import { ref, computed } from 'vue'
import CreateQuestions from './CreateQuestions.vue'

// Mock data - danh sách câu hỏi
const questionsData = [
  {
    id: 1,
    content: 'Miền nghiệm của bất phương trình \\(3x +2y > -6\\) là',
    imageUrl: '/question-1.jpg',
    subject: 'Toán',
    type: 'Trắc nghiệm'
  },
  {
    id: 2,
    content: 'Giả sử CD = h là chiều cao của thấp trong độ C là chân thập. Chọn hai điểm A, B trên mặt đất sao cho ba điểm A, B, C thẳng hàng. Ta do được AB = 24m, CAD = $63°\\circ$; CBD = $48°\\circ$. Chiều cao h của khối thấp gần với giá trị nào sau đây?',
    imageUrl: '/question-2.jpg',
    subject: 'Toán',
    type: 'Trắc nghiệm'
  },
  {
    id: 3,
    content: 'Phần không tô đậm (không kể biên) trong hình về sau biều diễn miền nghiệm của hệ bất phương trình nào trong các hệ bất phương trình cho dưới đây?',
    imageUrl: '/question-3.jpg',
    subject: 'Toán',
    type: 'Trắc nghiệm'
  },
  {
    id: 4,
    content: 'Tập xác định của hàm số \\(y = \\sqrt{4 - x^2}\\) là:',
    imageUrl: null,
    subject: 'Toán',
    type: 'Trắc nghiệm'
  },
  {
    id: 5,
    content: 'Phương trình \\(x^2 - 4 = 0\\) có nghiệm là:',
    imageUrl: null,
    subject: 'Toán',
    type: 'Trắc nghiệm'
  },
]

const searchText = ref('')
const showCreateQuestion = ref(false)
const filterSubject = ref('Tất cả')
const subjects = ['Tất cả', 'Toán', 'Vật lý', 'Hóa học', 'Sinh học']

// Lọc câu hỏi
const filteredQuestions = computed(() => {
  return questionsData.filter(question => {
    const matchSearch = question.content.toLowerCase().includes(searchText.value.toLowerCase())
    const matchSubject = filterSubject.value === 'Tất cả' || question.subject === filterSubject.value
    return matchSearch && matchSubject
  })
})

const handleCreateQuestion = () => {
  showCreateQuestion.value = true
}

const handleCloseQuestion = () => {
  showCreateQuestion.value = false
}

const handleEditQuestion = (questionId) => {
  console.log('Chỉnh sửa câu hỏi', questionId)
}

const handleDeleteQuestion = (questionId) => {
  console.log('Xóa câu hỏi', questionId)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4 md:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Header tabs -->
      <div class="flex justify-between items-center mb-8 border-b-2 border-gray-200">
        <div class="flex gap-12">
          <button
            class="px-2 py-3 font-semibold text-indigo-600 border-b-2 border-indigo-600 text-lg"
          >
            Câu hỏi
          </button>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-col md:flex-row gap-4 mb-8 items-center justify-between">
        <div class="flex gap-4 flex-1">
          <input
            v-model="searchText"
            type="text"
            placeholder="Nhập từ khóa"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
          />
          <select
            v-model="filterSubject"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white"
          >
            <option v-for="subject in subjects" :key="subject" :value="subject">
              {{ subject }}
            </option>
          </select>
        </div>
        <button
          @click="handleCreateQuestion"
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition whitespace-nowrap"
        >
          + Tạo câu hỏi
        </button>
        <CreateQuestions v-if="showCreateQuestion" @close="handleCloseQuestion" />
      </div>

      <!-- Danh sách câu hỏi -->
      <div class="space-y-4">
        <div
          v-for="question in filteredQuestions"
          :key="question.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6 flex items-center gap-6"
        >
          <!-- Icon -->
          <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"></path>
            </svg>
          </div>

          <!-- Nội dung câu hỏi -->
          <div class="flex-1 min-w-0">
            <p class="text-gray-800 text-base leading-relaxed break-words">
              {{ question.content }}
            </p>
          </div>

          <!-- Hình ảnh -->
          <div v-if="question.imageUrl" class="flex-shrink-0">
            <img
              :src="question.imageUrl"
              alt="Question image"
              class="h-16 w-16 object-cover rounded border border-gray-200"
            />
          </div>

          <!-- Actions -->
          <div class="flex gap-3 flex-shrink-0">
            <button
              @click="handleEditQuestion(question.id)"
              class="text-indigo-600 hover:text-indigo-700 p-2 transition"
              title="Chỉnh sửa"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button
              @click="handleDeleteQuestion(question.id)"
              class="text-red-600 hover:text-red-700 p-2 transition"
              title="Xóa"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Thông báo khi không có kết quả -->
        <div v-if="filteredQuestions.length === 0" class="text-center py-12">
          <p class="text-gray-500 text-lg">Không tìm thấy câu hỏi nào</p>
        </div>
      </div>

      <!-- Pagination (optional) -->
      <div v-if="filteredQuestions.length > 0" class="mt-8 flex justify-center gap-2">
        <button class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">«</button>
        <button class="px-3 py-2 border border-indigo-600 bg-indigo-600 text-white rounded">1</button>
        <button class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">2</button>
        <button class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">3</button>
        <button class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">»</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
