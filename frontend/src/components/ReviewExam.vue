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

const answerLabels = ['A', 'B', 'C', 'D', 'E', 'F']
function getAnswerLabelByIndex(q, idx) {
  return answerLabels[idx] ?? String(idx + 1)
}

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

/** Chỉ số đáp án đúng (0-based). Schema questions3: dùng answers[].is_correct hoặc correctAnswer/correct_answer */
function correctIndex(q) {
  const ans = q.answers || q.options
  if (Array.isArray(ans)) {
    const idx = ans.findIndex((a) => a?.is_correct === true)
    if (idx >= 0) return idx
  }
  return Number(q.correctAnswer ?? q.correct_answer ?? 0)
}

/** Chỉ số đáp án học sinh chọn (0-based). attempt.answers lưu theo questionId -> answerId */
function userChoice(q) {
  const selectedId = userAnswers.value[q.id]
  if (selectedId === null || selectedId === undefined || selectedId === '') return null
  const ans = q.answers || q.options
  if (Array.isArray(ans)) {
    const idx = ans.findIndex((a) => String(a?.id) === String(selectedId))
    if (idx >= 0) return idx
  }
  return Number(selectedId)
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
    if (examRes.ok) {
      const examData = examRes.data?.exam ?? examRes.data
      const qs = examRes.data?.questions ?? []
      exam.value = { ...examData, questions: qs }
    }
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
const examDuration = computed(() => {
  const d = exam.value?.duration ?? attempt.value?.duration
  if (d == null || d === '' || Number(d) === 0) return 'Không giới hạn'
  return d
})
const attemptDurationText = computed(() => {
  const a = attempt.value
  if (!a) return '-'
  const startRaw = a.start_time ?? a.startTime
  const endRaw = a.submit_time ?? a.submittedAt ?? a.submitted_at
  if (startRaw && endRaw) {
    const startMs = Date.parse(startRaw)
    const endMs = Date.parse(endRaw)
    if (!Number.isNaN(startMs) && !Number.isNaN(endMs) && endMs >= startMs) {
      const diffSec = Math.floor((endMs - startMs) / 1000)
      const m = Math.floor(diffSec / 60)
      const s = diffSec % 60
      return `${m} phút ${s.toString().padStart(2, '0')} giây`
    }
  }
  const rawDuration =
    a.durationMins ??
    a.duration_mins ??
    a.time
  if (rawDuration != null && rawDuration !== '') {
    return `${rawDuration} phút`
  }
  return '-'
})
const totalQuestions = computed(() => (questions.value.length || attempt.value?.total_questions) ?? exam.value?.total_questions ?? '-')
const resultScore = computed(() => attempt.value?.result ?? attempt.value?.score ?? '-')
const submittedAt = computed(() => attempt.value?.submitted_at ?? attempt.value?.submittedAt ?? '-')
</script>

<template>
  <div
    class="w-full max-w-6xl bg-white/90 rounded-2xl shadow-xl p-8 mt-20 animate-fade-in text-black my-10"
  >
    <!-- Box tóm tắt đề thi (phía trên) -->
    <div class="border border-indigo-200 rounded-xl bg-indigo-50/50 p-6 mb-8">
      <h2 class="text-2xl font-bold text-indigo-700 mb-2">{{ examTitle }}</h2>
      <div class="text-gray-700">
        <b>Thời gian đề:</b> {{ examDuration === 'Không giới hạn' ? examDuration : examDuration + ' phút' }}<br />
        <b>Thời gian làm bài:</b> {{ attemptDurationText }}<br />
        <b>Số câu hỏi:</b> {{ totalQuestions }}<br />
        <b>Điểm:</b> {{ resultScore }}<br />
        <b>Ngày nộp:</b> {{ submittedAt }}
      </div>
    </div>

    <!-- Bài làm của học sinh: từng câu hỏi + đáp án đúng / đáp án bạn chọn -->
    <h3 class="text-xl font-bold text-indigo-700 mb-4">Bài làm của học sinh</h3>
    <p v-if="questions.length === 0" class="text-gray-500">Không có câu hỏi trong bài làm này.</p>
    <div v-else class="question-list flex flex-col gap-6">
      <div
        v-for="(question, i) in questions"
        :key="question.id"
        class="question-block border border-gray-200 rounded-lg p-4 bg-slate-50/80"
      >
        <div class="mb-2 font-medium">
          <b>Câu {{ i + 1 }}:</b> {{ question.question || question.content }}
        </div>
        <ul class="answers mb-2 space-y-1">
          <li
            v-for="(_, idx) in (question.answers || question.options || [])"
            :key="idx"
            class="flex items-center gap-2 flex-wrap"
          >
            <span>{{ getAnswerLabelByIndex(question, idx) }}. {{ getAnswerLabel(question, idx) }}</span>
            <span v-if="correctIndex(question) === idx" class="text-green-600 font-medium">✓ Đáp án đúng</span>
            <span v-if="userChoice(question) !== null && userChoice(question) === idx">
              <span
                v-if="userChoice(question) === correctIndex(question)"
                class="text-green-700 font-bold"
              >
                (Bạn chọn · Đúng)
              </span>
              <span v-else class="text-red-600 font-bold">(Bạn chọn · Sai)</span>
            </span>
          </li>
        </ul>
        <div class="mt-2 pt-2 border-t border-gray-200 text-sm">
          <b>Đáp án đúng:</b> {{ getAnswerLabelByIndex(question, correctIndex(question)) }}. {{ getAnswerLabel(question, correctIndex(question)) }}
          <template v-if="userChoice(question) !== null">
            | <b>Đáp án của bạn:</b> {{ getAnswerLabelByIndex(question, userChoice(question)) }}. {{ getAnswerLabel(question, userChoice(question)) }}
            <span
              v-if="userChoice(question) === correctIndex(question)"
              class="text-green-600 ml-1"
            >
              ✓ Đúng
            </span>
            <span v-else class="text-red-600 ml-1">✗ Sai</span>
          </template>
          <span v-else class="text-amber-600 ml-1">— Bạn chưa trả lời câu này.</span>
        </div>
      </div>
    </div>
  </div>
</template>
