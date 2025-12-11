<script setup>
import { ref } from 'vue'

const emit = defineEmits(['close'])

const form = ref({
  title: '',
  description: '',
  type: 'multiple', // multiple choice or practice
  class: '',
  duration: '',
  questionType: 'random', // random or specific
  questions: [
    {
      id: 1,
      content: '',
      imageUrl: null,
      answers: [
        { id: 1, text: '', isCorrect: false },
        { id: 2, text: '', isCorrect: false },
        { id: 3, text: '', isCorrect: false },
        { id: 4, text: '', isCorrect: false }
      ],
      score: 10
    }
  ]
})

const showQuestionForm = ref(false)
const examTypes = ['-- Chọn hình thức --', 'Trắc nghiệm', 'Tự luận', 'Kết hợp']
const classes = ['-- Chọn lớp --', '10A1', '10A2', '11A1', '11A2', '12A1', '12A2']

const addQuestion = () => {
  const newId = Math.max(...form.value.questions.map(q => q.id)) + 1
  form.value.questions.push({
    id: newId,
    content: '',
    imageUrl: null,
    answers: [
      { id: 1, text: '', isCorrect: false },
      { id: 2, text: '', isCorrect: false },
      { id: 3, text: '', isCorrect: false },
      { id: 4, text: '', isCorrect: false }
    ],
    score: 10
  })
}

const addAnswer = (questionIndex) => {
  const question = form.value.questions[questionIndex]
  const newAnswerId = Math.max(...question.answers.map(a => a.id)) + 1
  question.answers.push({
    id: newAnswerId,
    text: '',
    isCorrect: false
  })
}

const removeAnswer = (questionIndex, answerIndex) => {
  form.value.questions[questionIndex].answers.splice(answerIndex, 1)
}

const removeQuestion = (questionIndex) => {
  form.value.questions.splice(questionIndex, 1)
}

const uploadImage = (questionIndex) => {
  console.log('Upload image for question', questionIndex)
  // Implement file upload logic
}

const submitForm = () => {
  console.log('Form submitted:', form.value)
}

const addMoreQuestions = () => {
  console.log('Add more questions from library')
}

const importFromLibrary = () => {
  console.log('Import from question library')
}

const saveExam = () => {
  submitForm()
}

const closeForm = () => {
  emit('close')
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center">
        <div></div>
        <h2 class="text-2xl font-bold text-indigo-600">Bài kiểm tra/Ôn tập</h2>
        <button @click="closeForm" class="text-2xl text-gray-600 hover:text-gray-800">×</button>
      </div>

      <!-- Content -->
      <div class="p-8">
        <!-- Thông tin chung -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Thông tin chung</h3>

          <!-- Tiêu đề bài kiểm tra -->
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Tiêu đề bài kiểm tra</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Nhập tiêu đề bài kiểm tra"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            />
          </div>

          <!-- Mô tả ngắn gọn -->
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Mô tả ngắn gọn</label>
            <textarea
              v-model="form.description"
              placeholder="Nhập mô tả..."
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none"
            ></textarea>
          </div>

          <!-- Hình thức và Lớp -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Hình thức</label>
              <select
                v-model="form.type"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              >
                <option v-for="type in examTypes" :key="type" :value="type">
                  {{ type }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Lớp</label>
              <select
                v-model="form.class"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              >
                <option v-for="cls in classes" :key="cls" :value="cls">
                  {{ cls }}
                </option>
              </select>
            </div>
          </div>

          <!-- Thời gian làm bài -->
          <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Thời gian làm bài (phút)</label>
            <input
              v-model.number="form.duration"
              type="number"
              placeholder="Nhập số phút..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            />
            <p class="text-xs text-gray-500 mt-2">* Nếu là ôn tập, phần thời gian có thể để trống</p>
          </div>
        </div>

        <!-- Divider -->
        <hr class="my-8" />

        <!-- Trộn thứ tự câu hỏi -->
        <div class="mb-8">
          <label class="flex items-center gap-3 cursor-pointer mb-6">
            <input
              v-model="form.questionType"
              type="checkbox"
              value="shuffle"
              class="w-5 h-5 rounded border-gray-300"
            />
            <span class="text-gray-800 font-medium">Trộn thứ tự câu hỏi</span>
          </label>

          <!-- Trộn thứ tự đáp án -->
          <label class="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              class="w-5 h-5 rounded border-gray-300"
            />
            <span class="text-gray-800 font-medium">Trộn thứ tự đáp án</span>
          </label>
        </div>

        <!-- Divider -->
        <hr class="my-8" />

        <!-- Danh sách câu hỏi -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Câu hỏi</h3>

          <!-- Câu hỏi -->
          <div v-for="(question, qIndex) in form.questions" :key="question.id" class="mb-8 border border-gray-300 rounded-lg p-6 bg-gray-50">
            <div class="flex justify-between items-start mb-4">
              <h4 class="text-lg font-bold text-gray-800">Câu {{ qIndex + 1 }}</h4>
              <button
                @click="removeQuestion(qIndex)"
                class="text-red-500 hover:text-red-700"
              >
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
                </svg>
              </button>
            </div>

            <!-- Nội dung câu hỏi -->
            <div class="mb-4">
              <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
              <textarea
                v-model="question.content"
                placeholder="Nhập câu hỏi..."
                rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none"
              ></textarea>
            </div>

            <!-- Upload ảnh -->
            <div class="mb-6">
              <button
                @click="uploadImage(qIndex)"
                class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tải ảnh
              </button>
            </div>

            <!-- Câu trả lời -->
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-3">Câu trả lời:</label>
              <div class="space-y-3">
                <div v-for="(answer, aIndex) in question.answers" :key="answer.id" class="flex items-center gap-3">
                  <input
                    v-model="answer.isCorrect"
                    type="radio"
                    :name="`question-${question.id}`"
                    class="w-5 h-5 cursor-pointer"
                  />
                  <input
                    v-model="answer.text"
                    type="text"
                    placeholder="Câu trả lời..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                  />
                  <button
                    @click="removeAnswer(qIndex, aIndex)"
                    class="text-red-500 hover:text-red-700 p-2"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Thêm câu trả lời -->
              <button
                @click="addAnswer(qIndex)"
                class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm flex items-center gap-2"
              >
                <span>+ Thêm câu trả lời</span>
              </button>
            </div>

            <!-- Điểm -->
            <div>
              <label class="block text-gray-700 font-medium mb-2">Điểm:</label>
              <input
                v-model.number="question.score"
                type="number"
                class="w-24 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
            </div>
          </div>

          <!-- Thêm câu hỏi button -->
          <button
            @click="addQuestion"
            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 text-gray-600 rounded-lg hover:border-indigo-500 hover:text-indigo-600 transition font-medium"
          >
            + Thêm câu hỏi
          </button>

          <!-- Tải file button -->
          <button
            @click="uploadImage(-1)"
            class="mt-4 flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tải file
          </button>
        </div>

        <!-- Divider -->
        <hr class="my-8" />

        <!-- Footer buttons -->
        <div class="flex justify-end gap-4">
          <button
            @click="addMoreQuestions"
            class="px-6 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition font-medium"
          >
            Thêm câu hỏi
          </button>
          <button
            @click="importFromLibrary"
            class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition font-medium"
          >
            Nhập từ thư viện
          </button>
          <button
            @click="saveExam"
            class="px-6 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition font-bold"
          >
            Đăng bài
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
input[type="radio"] {
  accent-color: #4f46e5;
}

input[type="checkbox"] {
  accent-color: #4f46e5;
}
</style>
