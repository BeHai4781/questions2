<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const studentApi = useStudentApi()
const attempts = ref([])
const examMap = ref({})
const loading = ref(true)
const error = ref(null)

const historyList = computed(() => {
  const items = attempts.value.map((a) => {
    const exam = examMap.value[a.exam_id ?? a.examId] || {}
    const startRaw = a.start_time ?? a.startTime
    const endRaw = a.submit_time ?? a.submittedAt ?? a.submitted_at
    let durationText = '-'
    if (startRaw && endRaw) {
      const startMs = Date.parse(startRaw)
      const endMs = Date.parse(endRaw)
      if (!Number.isNaN(startMs) && !Number.isNaN(endMs) && endMs >= startMs) {
        const diffSec = Math.floor((endMs - startMs) / 1000)
        const m = Math.floor(diffSec / 60)
        const s = diffSec % 60
        durationText = `${m} phút ${s.toString().padStart(2, '0')} giây`
      }
    }
    if (durationText === '-') {
      const rawDuration =
        a.durationMins ??
        a.duration_mins ??
        a.time
      if (rawDuration != null && rawDuration !== '') {
        durationText = `${rawDuration} phút`
      }
    }
    const dateRaw = a.submitted_at ?? a.submittedAt ?? a.created_at ?? a.createdAt ?? null
    const ts = dateRaw ? Date.parse(dateRaw) || 0 : 0
    return {
      id: a.id,
      title: exam.title || exam.name || `Đề #${a.exam_id ?? a.examId}`,
      date: dateRaw ?? '-',
      score: a.result ?? a.score ?? '-',
      duration: durationText,
      _ts: ts,
    }
  })
  // Sắp xếp theo thời gian nộp, bản ghi không có ngày sẽ xuống cuối
  return items
    .sort((a, b) => (b._ts ?? 0) - (a._ts ?? 0))
    .map(({ _ts, ...rest }) => rest)
})

onMounted(async () => {
  loading.value = true
  error.value = null
  const [attemptsRes, examsRes] = await Promise.all([
    studentApi.getAttempts({ page: 1, limit: 50 }),
    studentApi.getExams({ page: 1, limit: 100 }),
  ])
  if (attemptsRes.ok) attempts.value = attemptsRes.data ?? []
  else error.value = attemptsRes.error?.message
  if (examsRes.ok) {
    const map = {}
    ;(examsRes.data ?? []).forEach((e) => {
      map[e.id] = e
    })
    examMap.value = map
  }
  loading.value = false
})
</script>

<template>
  <div
    class="w-full max-w-6xl bg-white/90 rounded-2xl shadow-xl p-8 mt-10 flex flex-col gap-6 animate-fade-in"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Lịch sử làm bài</h2>
    <p v-if="loading" class="text-gray-500">Đang tải...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <table v-else class="w-full border-separate border-spacing-y-2">
      <thead>
        <tr class="text-indigo-700 text-base text-center">
          <th class="py-2">Tên đề thi</th>
          <th class="py-2">Ngày làm</th>
          <th class="py-2">Điểm</th>
          <th class="py-2">Thời gian</th>
          <th class="py-2">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!historyList.length">
          <td colspan="5" class="text-center text-gray-500 py-4">Chưa có lịch sử làm bài.</td>
        </tr>
        <tr
          v-for="item in historyList"
          :key="item.id"
          class="bg-indigo-50 hover:bg-indigo-100 transition rounded-lg text-black"
        >
          <td class="py-3 px-4 font-semibold">{{ item.title }}</td>
          <td class="py-3 px-4 text-center">{{ item.date }}</td>
          <td class="py-3 px-4 text-indigo-700 font-bold text-center">{{ item.score }}</td>
          <td class="py-3 px-4 text-center">{{ item.duration }}</td>
          <td class="py-3 px-4 text-center">
            <a
              :href="`/student/history?id=${item.id}`"
              class="text-indigo-600 hover:underline font-medium"
              >Chi tiết</a
            >
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
