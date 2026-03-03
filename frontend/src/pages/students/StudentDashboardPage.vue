<script setup>
import { ref, onMounted, computed } from 'vue'
import AppFooter from '@/includes/AppFooter.vue'
import StudentHeader from '@/includes/StudentHeader.vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const studentApi = useStudentApi()
const latestExams = ref([])
const recentAttempts = ref([])
const loading = ref(true)
const error = ref(null)

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
      avg_time: '00:00',
      total_time: '00:00',
    }
  }
  const sumScore = list.reduce((s, a) => s + (Number(a.result) ?? Number(a.score) ?? 0), 0)
  const sumTime = list.reduce((s, a) => s + (Number(a.time) ?? 0), 0)
  const avgScore = total ? (sumScore / total).toFixed(1) : 0
  const avgM = Math.floor(sumTime / 60)
  const totalM = Math.floor(sumTime / 60)
  return {
    total_exams: total,
    avg_score: avgScore,
    avg_time: `${avgM}:00`,
    total_time: `${totalM}:00`,
  }
})

onMounted(async () => {
  loading.value = true
  error.value = null
  try {
    const [examsRes, attemptsRes] = await Promise.all([
      studentApi.getExams({ page: 1, limit: 10 }),
      studentApi.getAttempts({ page: 1, limit: 10 }),
    ])
    if (examsRes.ok) latestExams.value = examsRes.data ?? []
    else error.value = examsRes.error?.message
    if (attemptsRes.ok) recentAttempts.value = attemptsRes.data ?? []
  } catch (e) {
    error.value = e?.message || 'Lỗi tải dữ liệu'
  } finally {
    loading.value = false
  }
})

function examTitle(attempt) {
  return attempt?.title ?? attempt?.examTitle ?? `Đề #${attempt?.exam_id ?? attempt?.examId ?? ''}`
}

function examScore(attempt) {
  return attempt?.result ?? attempt?.score ?? '-'
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
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template v-for="exam in latestExams" :key="exam.id">
              <div class="md:col-span-1 flex flex-col gap-2">
                <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50">
                  <span class="font-semibold text-indigo-700">{{ exam.title || exam.name }}</span><br />
                  <small class="text-gray-500">
                    Số câu: {{ examInfo[exam.id]?.total_questions ?? 0 }}
                    Thời gian: {{ examInfo[exam.id]?.duration ?? '' }} phút
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
            <template v-for="a in recentAttempts" :key="a.id">
              <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50 text-indigo-700">
                {{ examTitle(a) }} - Kết quả: {{ examScore(a) }} điểm
              </div>
            </template>
          </div>
          <a href="/student/history" class="text-indigo-600 hover:underline text-sm">Lịch sử bài làm</a>
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
