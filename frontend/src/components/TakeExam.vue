<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import { useStudentApi } from '@/composables/useStudentApi.js'

const STORAGE_KEY_PREFIX = 'ems_exam_'

function getStorageKey(examId) {
  return examId ? `${STORAGE_KEY_PREFIX}${examId}` : null
}

function loadExamState(examId) {
  const key = getStorageKey(examId)
  if (!key) return null
  try {
    const raw = sessionStorage.getItem(key)
    if (!raw) return null
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function saveExamState(examId, data) {
  const key = getStorageKey(examId)
  if (!key) return
  try {
    sessionStorage.setItem(key, JSON.stringify(data))
  } catch (_) {}
}

function clearExamState(examId) {
  const key = getStorageKey(examId)
  if (key) sessionStorage.removeItem(key)
}

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
const showCancelConfirm = ref(false)

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
  const nowSec = Math.floor(Date.now() / 1000)
  const startSec = startedAt.value ? Math.floor(new Date(startedAt.value).getTime() / 1000) : null

  if (startSec != null) {
    const elapsedSec = nowSec - startSec
    if (isTimed.value && duration > 0) {
      const totalSec = duration * 60
      timeLeft.value = Math.max(0, totalSec - elapsedSec)
      if (timeLeft.value <= 0) finished.value = true
    } else {
      elapsed.value = Math.max(0, elapsedSec)
    }
  } else {
    startedAt.value = new Date().toISOString()
    timeLeft.value = duration > 0 ? duration * 60 : 0
    elapsed.value = 0
  }

  const interval = setInterval(() => {
    if (finished.value) {
      clearInterval(interval)
      return
    }
    if (isTimed.value) {
      if (timeLeft.value > 0) timeLeft.value--
      else {
        finished.value = true
        clearInterval(interval)
      }
    } else {
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

  const saved = loadExamState(props.examId)
  if (saved?.startedAt) {
    startedAt.value = saved.startedAt
  }
  if (saved?.answers && typeof saved.answers === 'object') {
    const merged = { ...init }
    questions.value.forEach((q) => {
      const id = String(q.id)
      if (saved.answers[id] !== undefined && saved.answers[id] !== null && saved.answers[id] !== '') {
        merged[q.id] = saved.answers[id]
      }
    })
    answers.value = merged
  } else {
    answers.value = { ...init }
  }

  startTimer()
  saveExamState(props.examId, { startedAt: startedAt.value, answers: answers.value })
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

let saveTimeout = null
watch(
  () => answers.value,
  (val) => {
    if (!props.examId || !val || typeof val !== 'object') return
    if (saveTimeout) clearTimeout(saveTimeout)
    saveTimeout = setTimeout(() => {
      saveExamState(props.examId, {
        startedAt: startedAt.value,
        answers: { ...val },
      })
      saveTimeout = null
    }, 300)
  },
  { deep: true }
)

/** Điểm theo thang 10: mỗi câu chia đều, tổng = tổng điểm các câu đúng (backend cũng tính lại khi nộp). */
function calculateScore() {
  let correct = 0
  questions.value.forEach((q) => {
    const userChoice = answers.value[q.id]
    const correctId = q.correctAnswerId ?? q.correct_answer ?? q.correctAnswer
    if (userChoice != null && userChoice !== '' && String(userChoice) === String(correctId)) correct++
  })
  const total = questions.value.length
  if (!total) return 0
  const pointsPerQuestion = 10 / total
  return Math.round(correct * pointsPerQuestion * 100) / 100
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
      clearExamState(props.examId)
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
  if (finished.value || submitting.value) return
  showCancelConfirm.value = true
}

function closeCancelConfirm() {
  showCancelConfirm.value = false
}

function confirmCancel() {
  showCancelConfirm.value = false
  router.push('/student/exam')
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
  <div
    v-if="showCancelConfirm"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
      <h3 class="text-lg font-bold text-gray-900">Xác nhận hủy bài thi</h3>
      <p class="mt-2 text-sm text-gray-600">Bạn có chắc chắn không làm tiếp bài thi này?</p>
      <div class="mt-6 flex items-center justify-end gap-3">
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
          @click="closeCancelConfirm"
        >
          Tiếp tục làm
        </button>
        <button
          type="button"
          class="rounded-lg px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition"
          @click="confirmCancel"
        >
          Xác nhận hủy
        </button>
      </div>
    </div>
  </div>
</template>
