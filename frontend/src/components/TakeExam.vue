<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
// Dữ liệu mẫu
const exam = {
  id: 1,
  title: 'Đề Toán HK1',
  duration: 45, // phút
  total_questions: 3,
}
const questions = [
  {
    id: 1,
    question: '2 + 2 = ?',
    answers: [
      { key: 0, text: '3' },
      { key: 1, text: '4' },
      { key: 2, text: '5' },
      { key: 3, text: '6' },
    ],
  },
  {
    id: 2,
    question: '5 x 3 = ?',
    answers: [
      { key: 0, text: '15' },
      { key: 1, text: '10' },
      { key: 2, text: '8' },
      { key: 3, text: '20' },
    ],
  },
  {
    id: 3,
    question: 'Căn bậc hai của 9?',
    answers: [
      { key: 0, text: '2' },
      { key: 1, text: '3' },
      { key: 2, text: '4' },
      { key: 3, text: '5' },
    ],
  },
]

const answers = ref({})
questions.forEach((q) => {
  q.choice = null
})
const timeLeft = ref(exam.duration * 60) // giây
const timerDisplay = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
  const s = timeLeft.value % 60
  return `${m}:${s < 10 ? '0' : ''}${s}`
})
const finished = ref(false)

onMounted(() => {
  // Khởi tạo tất cả câu hỏi với giá trị null
  questions.forEach((q) => {
    answers.value[q.id] = null
  })
  const interval = setInterval(() => {
    if (timeLeft.value > 0 && !finished.value) {
      timeLeft.value--
    } else if (timeLeft.value === 0 && !finished.value) {
      finished.value = true
      clearInterval(interval)
    }
  }, 1000)
})

function handleSubmit() {
  finished.value = true
  toast.success('Bạn đã hoàn thành bài thi!')
  setTimeout(() => {
    window.location.href = '/student/history?id=' + exam.id
  }, 2000)
}
function handleCancel() {
  if (confirm('Bạn có chắc chắn không làm tiếp bài thi này?')) {
    // Chuyển về trang danh sách đề thi
    window.location.href = '/student/exam'
  }
}
const answeredCount = computed(() => Object.keys(answers.value).length)
</script>

<template>
  <div
    class="w-full max-w-3xl bg-white/90 rounded-2xl shadow-xl p-8 animate-fade-in mt-20 text-black my-10"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-2">{{ exam.title }}</h2>
    <div class="mb-4 text-gray-700">
      <b>Thời gian còn lại:</b> <span>{{ timerDisplay }}</span
      ><br />
      <b>Số câu:</b> {{ answeredCount }}/{{ exam.total_questions }}
    </div>
    <form @submit.prevent="handleSubmit">
      <div
        v-for="(q, idx) in questions"
        :key="q.id"
        class="question-box mb-6 p-4 border border-gray-200 rounded-lg"
      >
        <b>Câu {{ idx + 1 }}:</b> {{ q.question }}
        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
          <label
            v-for="ans in q.answers"
            :key="ans.key"
            class="answer flex items-center gap-2 cursor-pointer"
          >
            <input
              type="radio"
              class="accent-indigo-600 bg-white border border-gray-300"
              v-model="answers[q.id]"
              :name="`answer_${q.id}`"
              :value="ans.key"
              :disabled="finished"
            />
            <span>{{ ans.text }}</span>
          </label>
        </div>
      </div>
      <div class="flex gap-4 mt-8">
        <button
          type="submit"
          class="px-6 py-2 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition"
          :disabled="finished"
        >
          Hoàn thành
        </button>
        <button
          type="button"
          class="px-6 py-2 rounded-xl bg-gray-300 text-gray-700 font-bold shadow hover:bg-gray-400 transition"
          @click="handleCancel"
          :disabled="finished"
        >
          Hủy
        </button>
      </div>
    </form>
  </div>
</template>
