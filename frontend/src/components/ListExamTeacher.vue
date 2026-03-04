<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTeacherApi } from '@/composables/useTeacherApi.js'
import CreateExam from '@/components/CreateExam.vue'

const api = useTeacherApi()

const allExams     = ref([])
const loading      = ref(false)
const error        = ref('')
const searchText   = ref('')
const selectedType = ref('exam')
const showCreate   = ref(false)
const deletingId   = ref(null)

const CLASS_MAP = { '1': 'Lớp 10', '2': 'Lớp 11', '3': 'Lớp 12' }
const TYPE_MAP  = { '1': 'exam',   '2': 'review' }

// Fetch toàn bộ (loop qua các trang, mỗi trang max 100)
async function fetchExams() {
  loading.value = true
  error.value   = ''
  try {
    let page       = 1
    const limit    = 100
    let totalPages = 1
    const collected = []

    do {
      const res = await api.getExams({ page, limit })
      const items = res?.data?.items ?? res?.data ?? []
      collected.push(...items)
      totalPages = res?.data?.pagination?.totalPages
                ?? res?.pagination?.totalPages
                ?? 1
      page++
    } while (page <= totalPages)

    allExams.value = collected
  } catch (e) {
    error.value = 'Không thể tải danh sách đề thi.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchExams)

const filteredExams = computed(() => {
  const keyword = searchText.value.trim().toLowerCase()
  return allExams.value.filter(exam => {
    const typeKey = TYPE_MAP[String(exam.type_id)] ?? 'exam'
    return (
      typeKey === selectedType.value &&
      (!keyword || (exam.title ?? '').toLowerCase().includes(keyword))
    )
  })
})

const groupedExams = computed(() => {
  const order  = ['Lớp 12', 'Lớp 11', 'Lớp 10']
  const groups = {}
  filteredExams.value.forEach(exam => {
    const cls = CLASS_MAP[String(exam.class_id)] ?? `Lớp ${exam.class_id}`
    if (!groups[cls]) groups[cls] = []
    groups[cls].push(exam)
  })
  const sorted = {}
  order.forEach(cls => { if (groups[cls]) sorted[cls] = groups[cls] })
  Object.keys(groups).forEach(cls => { if (!sorted[cls]) sorted[cls] = groups[cls] })
  return sorted
})

async function handleDelete(exam) {
  if (!confirm(`Bạn có chắc muốn xóa đề thi "${exam.title}"?`)) return
  deletingId.value = exam.id
  try {
    await api.deleteExam(exam.id)
    allExams.value = allExams.value.filter(e => e.id !== exam.id)
  } catch (e) {
    alert('Xóa thất bại, vui lòng thử lại.')
  } finally {
    deletingId.value = null
  }
}

function handleExamCreated(newExam) {
  if (newExam) allExams.value.unshift(newExam)
  showCreate.value = false
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4 md:px-8">
    <div class="max-w-7xl mx-auto">

      <!-- Tabs trên cùng -->
      <div class="flex items-center border-b-2 border-gray-200 mb-8">
        <button @click="selectedType = 'exam'"
          :class="['px-6 py-3 font-medium transition-colors',
            selectedType === 'exam' ? 'text-indigo-700 border-b-2 border-indigo-700' : 'text-gray-600 hover:text-gray-700']">
          Bài kiểm tra
        </button>
        <button @click="selectedType = 'review'"
          :class="['px-6 py-3 font-medium transition-colors',
            selectedType === 'review' ? 'text-indigo-700 border-b-2 border-indigo-700' : 'text-gray-600 hover:text-gray-700']">
          Ôn tập
        </button>
      </div>

      <!-- Toolbar -->
      <div class="flex gap-4 mb-8 items-center flex-wrap">
        <button @click="selectedType = 'exam'"
          :class="['px-4 py-2 rounded-lg font-medium transition',
            selectedType === 'exam' ? 'bg-indigo-700 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50']">
          Bài kiểm tra
        </button>
        <button @click="selectedType = 'review'"
          :class="['px-4 py-2 rounded-lg font-medium transition',
            selectedType === 'review' ? 'bg-indigo-700 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50']">
          Ôn tập
        </button>
        <input v-model="searchText" type="text"
          :placeholder="selectedType === 'exam' ? 'Tìm kiếm bài kiểm tra...' : 'Tìm kiếm đề ôn tập...'"
          class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
        <button @click="showCreate = true"
          class="px-6 py-2 bg-indigo-700 text-white rounded-lg font-medium hover:bg-indigo-800 transition whitespace-nowrap">
          + Tạo đề thi
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-12 text-red-500">{{ error }}</div>

      <!-- Danh sách nhóm theo lớp -->
      <div v-else class="space-y-8">
        <template v-for="(exams, className) in groupedExams" :key="className">
          <div>
            <h3 class="text-xl font-bold text-gray-800 mb-4">{{ className }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="exam in exams" :key="exam.id"
                class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6 border-l-4 border-indigo-600">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 line-clamp-2">{{ exam.title }}</h4>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center gap-2 text-gray-600 text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Thời gian làm bài: {{ exam.duration ? exam.duration + ' phút' : 'Không giới hạn' }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-gray-600 text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Số câu hỏi: {{ exam.total_questions ?? 0 }}</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button @click="$router.push(`/teacher/exams/${exam.id}/edit`)"
                    class="flex-1 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 transition">
                    Chỉnh sửa
                  </button>
                  <button @click="handleDelete(exam)" :disabled="deletingId === exam.id"
                    class="flex-1 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition disabled:opacity-50">
                    {{ deletingId === exam.id ? '...' : 'Xóa' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div v-if="Object.keys(groupedExams).length === 0" class="text-center py-12">
          <p class="text-gray-500 text-lg">Không tìm thấy đề thi nào</p>
        </div>
      </div>
    </div>

    <CreateExam v-if="showCreate" @close="showCreate = false" @created="handleExamCreated" />
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>