<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { useStudentApi } from '@/composables/useStudentApi.js'

const props = defineProps({
  examId: { type: String, default: null },
})

const router = useRouter()
const studentApi = useStudentApi()

const exam = ref(null)
const questions = ref([])
const answers = ref({})
const timeLeft = ref(0)
const finished = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref(null)

const timerDisplay = computed(() => {
  const m = Math.floor(timeLeft.value / 60)
  const s = timeLeft.value % 60
  return `${m}:${s < 10 ? '0' : ''}${s}`
})

const answeredCount = computed(() => {
  return Object.values(answers.value).filter((v) => v !== null && v !== undefined).length
})

function normalizeQuestions(examData) {
  const qs = examData?.questions
  if (Array.isArray(qs) && qs.length) return qs
  return []
}

function startTimer() {
  const duration = exam.value?.duration ?? 0
  timeLeft.value = duration * 60
  const interval = setInterval(() => {
    if (timeLeft.value > 0 && !finished.value) timeLeft.value--
    else if (timeLeft.value <= 0 && !finished.value) {
      finished.value = true
      clearInterval(interval)
    }
  }, 1000)
}

async function loadExam() {
  if (!props.examId) return
  loading.value = true
  error.value = null
  const res = await studentApi.getExamById(props.examId)
  if (!res.ok) {
    error.value = res.error?.message || 'Không tải được đề thi'
    loading.value = false
    return
  }
  const examData = res.data?.exam ?? res.data
  if (!examData) {
    error.value = 'Đề thi không tồn tại'
    loading.value = false
    return
  }
  exam.value = examData
  questions.value = normalizeQuestions(examData)
  const init = {}
  questions.value.forEach((q) => {
    init[q.id] = null
  })
  answers.value = { ...init }
  startTimer()
  loading.value = false
}

onMounted(() => {
  loadExam()
})

watch(
  () => props.examId,
  (id) => {
    if (id) loadExam()
  }
)

function calculateScore() {
  let correct = 0
  questions.value.forEach((q) => {
    const userChoice = answers.value[q.id]
    const correctIdx = q.correctAnswer ?? q.correct_answer
    if (userChoice !== null && userChoice !== undefined && Number(userChoice) === Number(correctIdx)) correct++
  })
  const total = questions.value.length
  return total ? Math.round((correct / total) * 100) : 0
}

async function handleSubmit() {
  if (finished.value || submitting.value) return
  finished.value = true
  submitting.value = true
  const score = calculateScore()
  const answersPayload = answers.value
  try {
    const res = await studentApi.createAttempt({
      examId: props.examId,
      status: 'submitted',
      answers: answersPayload,
      score,
      result: score,
    })
    if (res.ok && res.data?.attempt?.id) {
      toast.success('Bạn đã hoàn thành bài thi!')
      setTimeout(() => {
        router.push(`/student/history?id=${res.data.attempt.id}`)
      }, 1500)
    } else {
      toast.error(res.error?.message || 'Nộp bài thất bại')
      finished.value = false
    }
  } catch (e) {
    toast.error('Lỗi kết nối')
    finished.value = false
  } finally {
    submitting.value = false
  }
}

function handleCancel() {
  if (confirm('Bạn có chắc chắn không làm tiếp bài thi này?')) {
    router.push('/student/exam')
  }
}

function answerOptions(q) {
  const ans = q.answers || q.options
  if (Array.isArray(ans)) return ans.map((a, i) => ({ key: i, text: typeof a === 'string' ? a : a?.text ?? a?.content ?? '' }))
  return []
}
</script>

<template>
  <div
    class="w-full max-w-3xl bg-white/90 rounded-2xl shadow-xl p-8 animate-fade-in mt-20 text-black my-10"
  >
    <p v-if="loading" class="text-gray-500">Đang tải đề thi...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <template v-else-if="exam">
      <h2 class="text-2xl font-bold text-indigo-700 mb-2">{{ exam.title || exam.name }}</h2>
      <div class="mb-4 text-gray-700">
        <b>Thời gian còn lại:</b> <span>{{ timerDisplay }}</span><br />
        <b>Số câu:</b> {{ answeredCount }}/{{ questions.length || exam.total_questions || exam.totalQuestions || 0 }}
      </div>
      <form v-if="questions.length" @submit.prevent="handleSubmit">
        <div
          v-for="(q, idx) in questions"
          :key="q.id"
          class="question-box mb-6 p-4 border border-gray-200 rounded-lg"
        >
          <b>Câu {{ idx + 1 }}:</b> {{ q.question || q.content }}
          <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
            <label
              v-for="ans in answerOptions(q)"
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
            :disabled="finished || submitting"
          >
            {{ submitting ? 'Đang nộp...' : 'Hoàn thành' }}
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
      <p v-else class="text-gray-500">Đề thi chưa có câu hỏi.</p>
    </template>
  </div>
</template>
