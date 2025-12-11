<script setup>
import { ref, computed } from 'vue'
import CreateExam from '@/components/CreateExam.vue';
// Mock data - tương tự như cấu trúc trong ảnh
const examsData = [
  // Lớp 12
  {
    id: 1,
    title: 'Bài kt',
    class: 'Lớp 12',
    duration: 15,
    questions: 15,
    type: 'exam'
  },
  {
    id: 2,
    title: 'Kiểm tra kì thị tổng kết năm học Toán 12',
    class: 'Lớp 12',
    duration: 30,
    questions: 15,
    type: 'exam'
  },
  {
    id: 3,
    title: 'Kiểm tra toán 12 lần 1',
    class: 'Lớp 12',
    duration: 30,
    questions: 20,
    type: 'exam'
  },
  // Lớp 11
  {
    id: 4,
    title: 'Đề thi Toán 11 năm học 2025-2026',
    class: 'Lớp 11',
    duration: 30,
    questions: 15,
    type: 'exam'
  },
  // Lớp 10
  {
    id: 5,
    title: 'Đề kiểm tra nâng lực Toán 10 lần 2',
    class: 'Lớp 10',
    duration: 20,
    questions: 0,
    type: 'exam'
  },
  {
    id: 6,
    title: 'Bài kiểm tra giữa kì Toán 10',
    class: 'Lớp 10',
    duration: 15,
    questions: 0,
    type: 'exam'
  },
]

const searchText = ref('')
const selectedTab = ref('Bài kiểm tra')
const showCreateExam = ref(false)

const tabs = ['Bài kiểm tra', 'Ôn tập']

// Nhóm đề thi theo lớp
const groupedExams = computed(() => {
  const filtered = examsData.filter(exam =>
    exam.title.toLowerCase().includes(searchText.value.toLowerCase())
  )

  const groups = {}
  filtered.forEach(exam => {
    if (!groups[exam.class]) {
      groups[exam.class] = []
    }
    groups[exam.class].push(exam)
  })

  // Sắp xếp theo thứ tự: Lớp 12, 11, 10
  const sorted = {}
  const order = ['Lớp 12', 'Lớp 11', 'Lớp 10', 'Lớp 9', 'Lớp 8', 'Lớp 7']
  order.forEach(className => {
    if (groups[className]) {
      sorted[className] = groups[className]
    }
  })

  return sorted
})

const handleCreateExam = () => {
  showCreateExam.value = true
}

const handleCloseCreateExam = () => {
  showCreateExam.value = false
}

const handleExamAction = (examId, action) => {
  console.log(`${action} exam ${examId}`)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4 md:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header với tabs -->
      <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div class="flex items-center gap-0 border-b-2">
          <button
            v-for="tab in tabs"
            :key="tab"
            @click="selectedTab = tab"
            :class="[
              'px-6 py-3 font-medium transition-colors',
              selectedTab === tab
                ? 'text-indigo-700 border-b-2 border-indigo-700'
                : 'text-gray-600 hover:text-gray-700'
            ]"
          >
            {{ tab }}
          </button>
        </div>
      </div>

      <!-- Tabs nội dung -->
      <div class="flex gap-4 mb-8 items-center">
        <button
          @click="selectedTab = 'Bài kiểm tra'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition',
            selectedTab === 'Bài kiểm tra'
              ? 'bg-indigo-700 text-white'
              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
          ]"
        >
          Bài kiểm tra
        </button>
        <button
          @click="selectedTab = 'Ôn tập'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition',
            selectedTab === 'Ôn tập'
              ? 'bg-indigo-700 text-white'
              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
          ]"
        >
          Ôn tập
        </button>

        <!-- Search và Create button -->
        <div class="flex-1 flex gap-4">
          <input
            v-model="searchText"
            type="text"
            placeholder="Tìm kiếm bài kiểm tra..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
          />
          <button
            @click="handleCreateExam"
            class="px-6 py-2 bg-indigo-700 text-white rounded-lg font-medium hover:bg-indigo-800 transition whitespace-nowrap"
          >
            + Tạo đề thi
          </button>
          <CreateExam v-if="showCreateExam" @close="handleCloseCreateExam" />
        </div>
      </div>

      <!-- Danh sách đề thi theo lớp -->
      <div class="space-y-8">
        <template v-for="(exams, className) in groupedExams" :key="className">
          <div>
            <h3 class="text-xl font-bold text-gray-800 mb-4">{{ className }}</h3>

            <!-- Grid của cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div
                v-for="exam in exams"
                :key="exam.id"
                class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6 border-l-4 border-indigo-600"
              >
                <!-- Tiêu đề -->
                <h4 class="text-lg font-semibold text-gray-800 mb-3 line-clamp-2">
                  {{ exam.title }}
                </h4>

                <!-- Thông tin chi tiết -->
                <div class="space-y-2 mb-4">
                  <div class="flex items-center gap-2 text-gray-600 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Thời gian làm bài: {{ exam.duration }} phút</span>
                  </div>
                  <div class="flex items-center gap-2 text-gray-600 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Số câu hỏi: {{ exam.questions }}</span>
                  </div>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-2">
                  <button
                    @click="handleExamAction(exam.id, 'edit')"
                    class="flex-1 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 transition"
                  >
                    Chỉnh sửa
                  </button>
                  <button
                    @click="handleExamAction(exam.id, 'delete')"
                    class="flex-1 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition"
                  >
                    Xóa
                  </button> 
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Thông báo khi không có kết quả -->
        <div v-if="Object.keys(groupedExams).length === 0" class="text-center py-12">
          <p class="text-gray-500 text-lg">Không tìm thấy bài kiểm tra nào</p>
        </div>
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
