<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const studentApi = useStudentApi()
const exams = ref([])
const loading = ref(true)
const error = ref(null)
const selectedType = ref('')
const selectedClass = ref('')
const searchText = ref('')

const filteredExams = computed(() => {
  let list = exams.value
  if (selectedType.value) list = list.filter((e) => (e.type || e.subject) == selectedType.value)
  if (selectedClass.value) list = list.filter((e) => (e.class || e.className) == selectedClass.value)
  if (searchText.value) {
    const q = searchText.value.toLowerCase()
    list = list.filter(
      (e) =>
        (e.title || e.name || '').toLowerCase().includes(q)
    )
  }
  return list
})

const types = computed(() => {
  const set = new Set()
  exams.value.forEach((e) => {
    const v = e.type || e.subject
    if (v) set.add(v)
  })
  return Array.from(set)
})

const classes = computed(() => {
  const set = new Set()
  exams.value.forEach((e) => {
    const v = e.class || e.className
    if (v) set.add(v)
  })
  return Array.from(set)
})

onMounted(async () => {
  loading.value = true
  error.value = null
  const res = await studentApi.getExams({ page: 1, limit: 100 })
  if (res.ok) exams.value = res.data ?? []
  else error.value = res.error?.message || 'Không tải được danh sách đề thi'
  loading.value = false
})
</script>

<template>
  <div
    class="w-full max-w-6xl text-black bg-white/90 rounded-2xl shadow-xl p-8 flex flex-col gap-6 animate-fade-in mt-10"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Danh sách đề thi</h2>
    <div class="flex flex-col md:flex-row gap-4 mb-6">
      <select
        v-model="selectedType"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
      >
        <option value="">-- Môn/Loại --</option>
        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
      </select>
      <select
        v-model="selectedClass"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
      >
        <option value="">-- Lớp --</option>
        <option v-for="c in classes" :key="c" :value="c">{{ c }}</option>
      </select>
      <input
        v-model="searchText"
        type="text"
        placeholder="Tìm theo tên đề thi..."
        class="input input-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg flex-1"
      />
    </div>
    <p v-if="loading" class="text-gray-500">Đang tải...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-separate border-spacing-y-2">
        <thead>
          <tr class="text-indigo-700 text-base text-center">
            <th class="py-2">Tên đề thi</th>
            <th class="py-2">Môn/Loại</th>
            <th class="py-2">Lớp</th>
            <th class="py-2">Số câu</th>
            <th class="py-2">Thời gian</th>
            <th class="py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="exam in filteredExams"
            :key="exam.id"
            class="bg-indigo-50 hover:bg-indigo-100 transition rounded-lg"
          >
            <td class="py-3 px-4 font-semibold">{{ exam.title || exam.name }}</td>
            <td class="py-3 px-4 text-center">{{ exam.type || exam.subject || '-' }}</td>
            <td class="py-3 px-4 text-center">{{ exam.class || exam.className || '-' }}</td>
            <td class="py-3 px-4 text-center">{{ exam.total_questions ?? exam.totalQuestions ?? '-' }}</td>
            <td class="py-3 px-4 text-center">{{ exam.duration != null && exam.duration !== '' ? exam.duration + ' phút' : '-' }}</td>
            <td class="py-3 px-4 text-center">
              <a
                :href="`/student/exam?id=${exam.id}`"
                class="text-white bg-indigo-600 px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition font-medium"
                >Làm bài</a
              >
            </td>
          </tr>
          <tr v-if="filteredExams.length === 0">
            <td colspan="6" class="text-center py-6 text-gray-500">Không tìm thấy đề thi phù hợp.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
