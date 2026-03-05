<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useStudentApi } from '@/composables/useStudentApi.js'

const studentApi = useStudentApi()
const exams = ref([])
const total = ref(0)
const loading = ref(true)
const error = ref(null)
const page = ref(1)
const limit = ref(10)
const selectedType = ref('')
const selectedClass = ref('')
const searchText = ref('')
const types = ref([])
const classes = ref([])

async function fetchExams() {
  loading.value = true
  error.value = null
  const params = {
    page: page.value,
    limit: limit.value,
    search: searchText.value.trim() || undefined,
    type: selectedType.value || undefined,
    class: selectedClass.value || undefined,
  }
  const res = await studentApi.getExams(params)
  if (res.ok) {
    exams.value = res.data ?? []
    total.value = res.pagination?.total ?? 0
  } else {
    exams.value = []
    total.value = 0
    error.value = res.error?.message || 'Không tải được danh sách đề thi'
  }
  loading.value = false
}

async function loadFilterOptions() {
  const res = await studentApi.getExams({ page: 1, limit: 100 })
  if (res.ok && Array.isArray(res.data)) {
    const typeSet = new Set()
    const classSet = new Set()
    res.data.forEach((e) => {
      const t = e.type || e.subject
      const c = e.class || e.className
      if (t) typeSet.add(t)
      if (c) classSet.add(c)
    })
    types.value = Array.from(typeSet)
    classes.value = Array.from(classSet)
  }
}

function gotoPage(p) {
  page.value = p
}

function onSearch(e) {
  e?.preventDefault()
  page.value = 1
}

function onFilterChange() {
  page.value = 1
}

onMounted(async () => {
  await loadFilterOptions()
  fetchExams()
})
watch([page, limit, selectedType, selectedClass], fetchExams)
</script>

<template>
  <div
    class="w-full max-w-6xl text-black bg-white/90 rounded-2xl shadow-xl p-8 flex flex-col gap-6 animate-fade-in mt-10"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Danh sách đề thi</h2>
    <form class="flex flex-col md:flex-row gap-4 mb-6" @submit="onSearch">
      <select
        v-model="selectedType"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
        @change="onFilterChange"
      >
        <option value="">-- Môn/Loại --</option>
        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
      </select>
      <select
        v-model="selectedClass"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
        @change="onFilterChange"
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
      <button
        type="submit"
        class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition font-medium"
      >
        🔍 Tìm kiếm
      </button>
    </form>
    <p v-if="loading" class="text-gray-500">Đang tải...</p>
    <p v-else-if="error" class="text-red-600">{{ error }}</p>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-separate border-spacing-y-2">
        <thead>
          <tr class="text-indigo-700 text-base text-center">
            <th class="py-2">STT</th>
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
            v-for="(exam, idx) in exams"
            :key="exam.id"
            class="bg-indigo-50 hover:bg-indigo-100 transition rounded-lg"
          >
            <td class="py-3 px-4 text-center font-bold">{{ (page - 1) * limit + idx + 1 }}</td>
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
          <tr v-if="exams.length === 0">
            <td colspan="7" class="text-center py-6 text-gray-500">Không tìm thấy đề thi phù hợp.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Phân trang -->
    <div v-if="total > limit" class="flex justify-center mt-4">
      <nav>
        <ul class="inline-flex -space-x-px">
          <li v-for="i in Math.ceil(total / limit)" :key="i">
            <button
              type="button"
              @click="gotoPage(i)"
              :class="[
                'px-3 py-1 border rounded-l',
                i === page
                  ? 'bg-indigo-500 text-white'
                  : 'bg-white text-indigo-700 hover:bg-indigo-100',
              ]"
            >
              {{ i }}
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>
