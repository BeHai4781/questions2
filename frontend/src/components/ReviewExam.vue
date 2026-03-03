<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const props = defineProps({
  attemptId: { type: String, default: null },
})

const studentApi = useStudentApi()
const attempt = ref(null)
const exam = ref(null)
const loading = ref(true)
const error = ref(null)

const answerMap = ['A', 'B', 'C', 'D']

const questions = computed(() => {
  const qs = attempt.value?.questions ?? exam.value?.questions
  if (Array.isArray(qs)) return qs
  return []
})

const userAnswers = computed(() => {
  const a = attempt.value?.answers
  if (a && typeof a === 'object') return a
  return {}
})

function getAnswerLabel(q, idx) {
  const ans = q.answers || q.options
  if (Array.isArray(ans) && ans[idx] != null) {
    const v = ans[idx]
    return typeof v === 'string' ? v : v?.text ?? v?.content ?? ''
  }
  return ''
}

function correctIndex(q) {
  return Number(q.correctAnswer ?? q.correct_answer ?? 0)
}

function userChoice(q) {
  const id = q.id
  const v = userAnswers.value[id]
  return v === null || v === undefined ? null : Number(v)
}

async function loadAttempt() {
  if (!props.attemptId) return
  loading.value = true
  error.value = null
  const res = await studentApi.getAttemptById(props.attemptId)
  if (!res.ok) {
    error.value = res.error?.message || 'Không tải được bài làm'
    loading.value = false
    return
  }
  const att = res.data?.attempt ?? res.data
  if (!att) {
    error.value = 'Bài làm không tồn tại'
    loading.value = false
    return
  }
  attempt.value = att
  const examId = att.exam_id ?? att.examId
  if (examId) {
    const examRes = await studentApi.getExamById(examId)
    if (examRes.ok) exam.value = examRes.data?.exam ?? examRes.data
  }
  loading.value = false
}

onMounted(() => {
  loadAttempt()
})

watch(
  () => props.attemptId,
  (id) => {
    if (id) loadAttempt()
  }
)

const examTitle = computed(() => exam.value?.title ?? exam.value?.name ?? attempt.value?.title ?? 'Đề thi')
const examDuration = computed(() => exam.value?.duration ?? attempt.value?.duration ?? '-')
const attemptTime = computed(() => attempt.value?.time ?? '-')
const totalQuestions = computed(() => questions.value.length || attempt.value?.total_questions ?? exam.value?.total_questions ?? '-')
const resultScore = computed(() => attempt.value?.result ?? attempt.value?.score ?? '-')
const submittedAt = computed(() => attempt.value?.submitted_at ?? attempt.value?.submittedAt ?? '-')
</script>

<template>
  <div
    class="w-full max-w-6xl bg-white/90 rounded-2xl shadow-xl p-8 mt-20 animate-fade-in text-black my-10"
  >
    <div class="flex flex-col md:flex-row gap-8">
      <!-- Thông tin đề thi bên trái -->
      <div class="md:w-1/3 w-full flex-shrink-0 mb-8 md:mb-0">
        <h2 class="text-2xl font-bold text-indigo-700 mb-2">{{ exam.title }}</h2>
        <div class="mb-4 text-gray-700">
          <b>Thời gian đề:</b> {{ examDuration }} phút<br />
          <b>Thời gian làm bài:</b> {{ attemptTime }} phút<br />
          <b>Số câu hỏi:</b> {{ totalQuestions }}<br />
          <b>Điểm:</b> {{ resultScore }}<br />
          <b>Ngày nộp:</b> {{ submittedAt }}
        </div>
      </div>
      <!-- Các câu hỏi bên phải -->
      <div class="md:w-2/3 w-full">
        <div class="question-list flex flex-col gap-6">
          <div
            v-for="(question, i) in questions"
            :key="question.id"
            class="question-block border-b border-gray-200 pb-4"
          >
            <div class="mb-2">
              <b>Câu {{ i + 1 }}:</b> {{ question.question || question.content }}
            </div>
            <ul class="answers mb-2">
              <li
                v-for="(label, idx) in answerMap"
                :key="label"
                class="flex items-center gap-2 mb-1"
              >
                <span>{{ label }}. {{ getAnswerLabel(question, idx) }}</span>
                <span v-if="correctIndex(question) === idx" class="text-green-600">✓</span>
                <span v-if="userChoice(question) !== null && userChoice(question) === idx">
                  <span
                    v-if="userChoice(question) === correctIndex(question)"
                    class="text-green-700 font-bold"
                  >
                    (Bạn chọn)
                  </span>
                  <span v-else class="text-red-600 font-bold">(Bạn chọn)</span>
                </span>
              </li>
            </ul>
            <div class="mt-1">
              <b>Đáp án đúng:</b> {{ answerMap[correctIndex(question)] }}
              <span v-if="userChoice(question) !== null">
                | <b>Đáp án của bạn:</b> {{ answerMap[userChoice(question)] }}
                <span
                  v-if="userChoice(question) === correctIndex(question)"
                  class="text-green-600 ml-2"
                >
                  ✓ Đúng
                </span>
                <span v-else class="text-red-600 ml-2">✗ Sai</span>
              </span>
              <span v-else class="text-red-600 ml-2">Bạn chưa trả lời câu này.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
