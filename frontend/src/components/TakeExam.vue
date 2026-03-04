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
const timeLeft = ref(0) // đếm ngược cho đề có giới hạn thời gian
const startedAt = ref(null)
const elapsed = ref(0) // đếm xuôi cho đề ôn tập (duration = null)
const finished = ref(false)
const loading = ref(true)
const submitting = ref(false)
const error = ref(null)

const isTimed = computed(() => Number(exam.value?.duration ?? 0) > 0)

const timerDisplay = computed(() => {
  const seconds = isTimed.value ? timeLeft.value : elapsed.value
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
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
  const duration = Number(exam.value?.duration ?? 0)
  timeLeft.value = duration > 0 ? duration * 60 : 0
  elapsed.value = 0
  if (!startedAt.value) {
    startedAt.value = new Date().toISOString()
  }
  const interval = setInterval(() => {
    if (finished.value) {
      clearInterval(interval)
      return
    }
    if (isTimed.value) {
      if (timeLeft.value > 0) timeLeft.value--
      else {
        // Đề có giới hạn thời gian: hết giờ thì khóa bài
        finished.value = true
        clearInterval(interval)
      }
    } else {
      // Đề ôn tập: không khóa, chỉ đếm xuôi từ 00:00
      elapsed.value++
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
  questions.value = res.data?.questions ?? normalizeQuestions(examData)
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
    const correctId = q.correctAnswerId ?? q.correct_answer ?? q.correctAnswer
    if (userChoice != null && userChoice !== '' && String(userChoice) === String(correctId)) correct++
  })
  const total = questions.value.length
  return total ? Math.round((correct / total) * 100) : 0
}

async function handleSubmit() {
  if (finished.value || submitting.value) return
  finished.value = true
  submitting.value = true
  const score = calculateScore()
  // Tính thời gian làm bài thực tế
  const totalDuration = Number(exam.value?.duration ?? 0)
  const nowMs = Date.now()
  let usedSeconds = 0
  if (totalDuration > 0) {
    // Đề có giới hạn thời gian: dùng thời gian còn lại
    usedSeconds = totalDuration * 60 - timeLeft.value
  } else if (startedAt.value) {
    // Đề ôn tập: tính từ lúc bắt đầu tới lúc nộp
    const startMs = Date.parse(startedAt.value)
    if (!Number.isNaN(startMs)) {
      usedSeconds = Math.max(0, Math.floor((nowMs - startMs) / 1000))
    }
  } else {
    usedSeconds = elapsed.value
  }
  const usedMinutes = Math.max(0, Math.round(usedSeconds / 60))
  if (!startedAt.value) {
    startedAt.value = new Date(nowMs - usedSeconds * 1000).toISOString()
  }
  const submittedIso = new Date(nowMs).toISOString()
  const answersPayload = answers.value
  try {
    const res = await studentApi.createAttempt({
      examId: props.examId,
      status: 'submitted',
      answers: answersPayload,
      score,
      result: score,
      durationMins: usedMinutes,
      submittedAt: submittedIso,
      startTime: startedAt.value,
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
  // Schema questions3: mảng answers từ API [{ id, content, order_index }, ...]
  const ans = q.answers || q.options
  if (Array.isArray(ans) && ans.length) {
    return ans.map((a) => ({
      key: a.id ?? a.key,
      text: typeof a === 'string' ? a : (a?.text ?? a?.content ?? ''),
    }))
  }
  // Fallback: answer_a, answer_b, answer_c, answer_d
  if (
    q.hasOwnProperty('answer_a') ||
    q.hasOwnProperty('answer_b') ||
    q.hasOwnProperty('answer_c') ||
    q.hasOwnProperty('answer_d')
  ) {
    return [
      { key: 0, text: q.answer_a ?? '' },
      { key: 1, text: q.answer_b ?? '' },
      { key: 2, text: q.answer_c ?? '' },
      { key: 3, text: q.answer_d ?? '' },
    ]
  }
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
          <div v-if="answerOptions(q).length" class="mt-2 grid grid-cols-1 md:grid-cols-1 gap-2">
            <label
              v-for="(ans, aIdx) in answerOptions(q)"
              :key="ans.key + '_' + aIdx"
              class="answer flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-indigo-50 border border-transparent hover:border-indigo-200"
            >
              <input
                type="radio"
                class="accent-indigo-600 bg-white border border-gray-300"
                v-model="answers[q.id]"
                :name="`answer_${q.id}`"
                :value="ans.key"
                :disabled="finished"
              />
              <span class="select-none">{{ ans.text }}</span>
            </label>
          </div>
          <p v-else class="mt-2 text-gray-500 text-sm">Chưa có đáp án.</p>
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
