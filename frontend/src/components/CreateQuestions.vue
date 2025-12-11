<script setup>
import { ref } from 'vue'

const emit = defineEmits(['close'])

const form = ref({
  class: '',
  difficulty: '',
  content: '',
  imageUrl: null,
  answers: [
    { id: 1, text: '', isCorrect: false },
    { id: 2, text: '', isCorrect: false },
    { id: 3, text: '', isCorrect: false },
    { id: 4, text: '', isCorrect: false }
  ]
})

const classes = ['-- Chọn lớp --', '10A1', '10A2', '11A1', '11A2', '12A1', '12A2']
const difficulties = ['-- Chọn mức độ --', 'Dễ', 'Trung bình', 'Khó', 'Rất khó']

const addAnswer = () => {
  const newAnswerId = Math.max(...form.value.answers.map(a => a.id), 0) + 1
  form.value.answers.push({
    id: newAnswerId,
    text: '',
    isCorrect: false
  })
}

const removeAnswer = (answerIndex) => {
  if (form.value.answers.length > 1) {
    form.value.answers.splice(answerIndex, 1)
  }
}

const uploadImage = () => {
  console.log('Upload image')
  // Implement file upload logic
}

const uploadFile = () => {
  console.log('Upload file')
  // Implement file upload logic
}

const submitForm = () => {
  console.log('Form submitted:', form.value)
  emit('close')
}

const addMoreQuestions = () => {
  console.log('Add more questions')
}

const importFromLibrary = () => {
  console.log('Import from library')
}

const closeForm = () => {
  emit('close')
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center">
        <div></div>
        <h2 class="text-2xl font-bold text-indigo-600">Tạo câu hỏi</h2>
        <button @click="closeForm" class="text-2xl text-gray-600 hover:text-gray-800">×</button>
      </div>

      <!-- Content -->
      <div class="p-8">
        <!-- Lớp và Mức độ -->
        <div class="grid grid-cols-2 gap-6 mb-8">
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
          <div>
            <label class="block text-gray-700 font-medium mb-2">Mức độ</label>
            <select
              v-model="form.difficulty"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
            >
              <option v-for="difficulty in difficulties" :key="difficulty" :value="difficulty">
                {{ difficulty }}
              </option>
            </select>
          </div>
        </div>

        <!-- Nội dung câu hỏi -->
        <div class="mb-6">
          <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
          <textarea
            v-model="form.content"
            placeholder="Nhập câu hỏi..."
            rows="4"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none"
          ></textarea>
        </div>

        <!-- Upload ảnh -->
        <div class="mb-8">
          <button
            @click="uploadImage"
            class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tải ảnh
          </button>
        </div>

        <!-- Câu trả lời -->
        <div class="mb-6 border border-gray-300 rounded-lg p-6 bg-gray-50">
          <label class="block text-gray-700 font-medium mb-4">Câu trả lời:</label>
          <div class="space-y-3">
            <div
              v-for="(answer, aIndex) in form.answers"
              :key="answer.id"
              class="flex items-center gap-3"
            >
              <input
                v-model="answer.isCorrect"
                type="radio"
                :name="'answer-option'"
                class="w-5 h-5 cursor-pointer"
              />
              <input
                v-model="answer.text"
                type="text"
                placeholder="Câu trả lời..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <button
                @click="removeAnswer(aIndex)"
                class="text-red-500 hover:text-red-700 p-2 transition"
                :disabled="form.answers.length === 1"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Thêm câu trả lời -->
          <button
            @click="addAnswer"
            class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm flex items-center gap-2"
          >
            <span>+ Thêm câu trả lời</span>
          </button>
        </div>

        <!-- Divider -->
        <hr class="my-8" />

        <!-- Tải file button -->
        <div class="mb-8">
          <button
            @click="uploadFile"
            class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tải file
          </button>
        </div>

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
            class="px-6 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition font-medium"
          >
            Lưu câu hỏi
          </button>
          <button
            @click="submitForm"
            class="px-6 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition font-bold"
          >
            Lưu câu hỏi
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

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
