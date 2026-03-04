<script setup>
import { ref, onMounted, computed } from 'vue'
import AppFooter from '@/includes/AppFooter.vue'
import StudentHeader from '@/includes/StudentHeader.vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const studentApi = useStudentApi()
const latestExams = ref([])
const recentAttempts = ref([])
const examMap = ref({}) // exam_id -> exam (để hiển thị tên đề cho từng attempt)
const loading = ref(true)
const error = ref(null)

/** Lấy thời gian làm bài (giây) từ một attempt: start_time/submit_time hoặc duration_mins */
function getAttemptSeconds(a) {
  const startRaw = a.start_time ?? a.startTime
  const endRaw = a.submit_time ?? a.submittedAt ?? a.submitted_at
  if (startRaw && endRaw) {
    const startMs = Date.parse(startRaw)
    const endMs = Date.parse(endRaw)
    if (!Number.isNaN(startMs) && !Number.isNaN(endMs) && endMs >= startMs) {
      return Math.floor((endMs - startMs) / 1000)
    }
  }
  const mins = a.duration_mins ?? a.durationMins ?? a.time
  if (mins != null && mins !== '') {
    const n = Number(mins)
    if (!Number.isNaN(n)) return Math.round(n * 60)
  }
  return 0
}

/** Format giây thành "mm:ss" */
function formatMmSs(seconds) {
  const s = Math.max(0, Math.floor(Number(seconds) || 0))
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${m}:${sec < 10 ? '0' : ''}${sec}`
}

const examInfo = computed(() => {
  const map = {}
  latestExams.value.forEach((exam) => {
    map[exam.id] = {
      total_questions: exam.total_questions ?? exam.totalQuestions ?? 0,
      duration: exam.duration ?? 0,
    }
  })
  return map
})

const stats = computed(() => {
  const list = recentAttempts.value
  const total = list.length
  if (total === 0) {
    return {
      total_exams: 0,
      avg_score: 0,
      avg_time: '0:00',
      total_time: '0:00',
    }
  }
  const sumScore = list.reduce((s, a) => s + (Number(a.result) ?? Number(a.score) ?? 0), 0)
  let sumSeconds = 0
  list.forEach((a) => {
    sumSeconds += getAttemptSeconds(a)
  })
  const avgScore = total ? (sumScore / total).toFixed(1) : 0
  const avgSeconds = total ? Math.round(sumSeconds / total) : 0
  return {
    total_exams: total,
    avg_score: avgScore,
    avg_time: formatMmSs(avgSeconds),
    total_time: formatMmSs(sumSeconds),
  }
})

/** Bài làm gần đây sắp theo thời gian nộp mới nhất trước */
const sortedRecentAttempts = computed(() => {
  const list = [...recentAttempts.value]
  list.sort((a, b) => {
    const da = a.submit_time ?? a.submittedAt ?? a.submitted_at ?? ''
    const db = b.submit_time ?? b.submittedAt ?? b.submitted_at ?? ''
    const ta = da ? Date.parse(da) || 0 : 0
    const tb = db ? Date.parse(db) || 0 : 0
    return tb - ta
  })
  return list
})

onMounted(async () => {
  loading.value = true
  error.value = null
  try {
    const [examsRes, attemptsRes] = await Promise.all([
      studentApi.getExams({ page: 1, limit: 100 }),
      studentApi.getAttempts({ page: 1, limit: 20 }),
    ])
    if (examsRes.ok) {
      const allExams = examsRes.data ?? []
      latestExams.value = allExams.slice(0, 10)
      const map = {}
      allExams.forEach((e) => {
        map[e.id] = e
      })
      examMap.value = map
    } else {
      error.value = examsRes.error?.message
    }
    if (attemptsRes.ok) recentAttempts.value = attemptsRes.data ?? []
  } catch (e) {
    error.value = e?.message || 'Lỗi tải dữ liệu'
  } finally {
    loading.value = false
  }
})

function examTitle(attempt) {
  const id = attempt?.exam_id ?? attempt?.examId
  const exam = id ? examMap.value[id] : null
  return exam?.title ?? exam?.name ?? attempt?.title ?? attempt?.examTitle ?? `Đề #${id ?? ''}`
}

function examScore(attempt) {
  const v = attempt?.result ?? attempt?.score
  return v != null && v !== '' ? v : '-'
}

function attemptDurationText(attempt) {
  const sec = getAttemptSeconds(attempt)
  if (sec <= 0) return '-'
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m} phút ${s.toString().padStart(2, '0')} giây`
}

function examDurationLabel(exam) {
  const d = exam?.duration ?? examInfo.value[exam?.id]?.duration
  if (d == null || d === '' || Number(d) === 0) return 'Không giới hạn'
  return `${d} phút`
}
</script>

<template>
  <StudentHeader />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center">
    <div class="container_i flex gap-6 w-full max-w-5xl py-8">
      <div class="left flex-[3] flex flex-col gap-6 animate-fade-in">
        <div class="box bg-white rounded-xl shadow p-6">
          <h3 class="text-lg font-bold mb-4 text-indigo-700">Đề thi mới nhất</h3>
          <p v-if="loading" class="text-gray-500">Đang tải...</p>
          <p v-else-if="error" class="text-red-600">{{ error }}</p>
          <p v-else-if="latestExams.length === 0" class="text-gray-500">Không có đề thi nào.</p>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template v-for="exam in latestExams" :key="exam.id">
              <div class="md:col-span-1 flex flex-col gap-2">
                <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50">
                  <span class="font-semibold text-indigo-700">{{ exam.title || exam.name }}</span><br />
                  <small class="text-gray-500">
                    Số câu: {{ examInfo[exam.id]?.total_questions ?? 0 }}
                    Thời gian: {{ examDurationLabel(exam) }}
                  </small>
                </div>
                <div>
                  <a
                    :href="`/student/exam?id=${exam.id}`"
                    class="btn btn-primary bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition inline-block"
                    >Làm bài</a
                  >
                </div>
              </div>
            </template>
          </div>
        </div>
        <div class="box bg-white rounded-xl shadow p-6">
          <h3 class="text-lg font-bold mb-4 text-indigo-700">Bài thi đã làm gần đây</h3>
          <div class="flex flex-col gap-2 mb-2">
            <p v-if="loading" class="text-gray-500">Đang tải...</p>
            <p v-else-if="error" class="text-red-600">{{ error }}</p>
            <p v-else-if="recentAttempts.length === 0" class="text-gray-500">Bạn chưa làm bài thi nào.</p>
            <template v-for="a in sortedRecentAttempts" :key="a.id">
              <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50 text-indigo-700">
                <span class="font-medium">{{ examTitle(a) }}</span>
                — Kết quả: {{ examScore(a) }} điểm
                <span v-if="attemptDurationText(a) !== '-'" class="text-gray-600 text-sm"> · {{ attemptDurationText(a) }}</span>
              </div>
            </template>
          </div>
          <a v-if="recentAttempts.length > 0" href="/student/history" class="text-indigo-600 hover:underline text-sm">Lịch sử bài làm</a>
        </div>
      </div>
      <div class="right flex-1 border border-gray-200 rounded-xl bg-white shadow p-6 flex flex-col gap-4 h-fit text-indigo-700 animate-fade-in">
        <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Số lượng bài thi đã làm: {{ stats.total_exams }}</div>
        <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Điểm trung bình: {{ stats.avg_score }}</div>
        <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Thời gian trung bình: {{ stats.avg_time }}</div>
        <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Tổng thời gian: {{ stats.total_time }}</div>
      </div>
    </div>
    <AppFooter />
  </div>
</template>
