<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTeacherApi } from '@/composables/useTeacherApi.js'
import CreateQuestions from './CreateQuestions.vue'
import QuestionDetail  from './QuestionDetail.vue'

const api = useTeacherApi()

const questions   = ref([])
const loading     = ref(false)
const error       = ref('')
const searchText  = ref('')
const filterClass = ref('')
const deletingId  = ref(null)
const showCreate  = ref(false)

const editingQuestion = ref(null)  

function openEdit(question) {
  editingQuestion.value = question
}
function closeEdit() {
  editingQuestion.value = null
}
function handleUpdated(updatedQuestion) {
  // Cập nhật lại câu hỏi trong danh sách mà không cần reload toàn bộ
  const idx = questions.value.findIndex(q => String(q.id) === String(updatedQuestion.id))
  if (idx !== -1) questions.value[idx] = updatedQuestion
  closeEdit()
}

//Pagination
const currentPage  = ref(1)
const totalPages   = ref(1)
const totalItems   = ref(0)
const LIMIT        = 15

const CLASS_OPTIONS = [
  { value: '', label: 'Tất cả' },
  { value: '1', label: 'Lớp 10' },
  { value: '2', label: 'Lớp 11' },
  { value: '3', label: 'Lớp 12' },
]
const LEVEL_MAP = { '1': 'Nhận biết', '2': 'Vận dụng', '3': 'Vận dụng cao' }
const CLASS_MAP = { '1': 'Lớp 10',   '2': 'Lớp 11',  '3': 'Lớp 12' }

async function fetchQuestions(page = 1) {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.getBankQuestions({
      page,
      limit: LIMIT,
      search:  searchText.value.trim() || undefined,
      classId: filterClass.value || undefined,
    })

    const items = res?.data?.items ?? res?.data ?? []
    questions.value   = items
    totalPages.value  = res?.data?.pagination?.totalPages ?? res?.pagination?.totalPages ?? 1
    totalItems.value  = res?.data?.pagination?.total      ?? res?.pagination?.total      ?? items.length
    currentPage.value = page
  } catch (e) {
    error.value = 'Không thể tải danh sách câu hỏi.'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchQuestions(1))

let debounceTimer = null
watch(filterClass, () => fetchQuestions(1))
watch(searchText, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchQuestions(1), 400)
})

const pageNumbers = computed(() => {
  const pages = []
  const total = totalPages.value
  const cur   = currentPage.value
  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (cur > 3) pages.push('...')
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
    if (cur < total - 2) pages.push('...')
    pages.push(total)
  }
  return pages
})

async function handleDelete(question) {
  if (!confirm('Bạn có chắc muốn xóa câu hỏi này?')) return
  deletingId.value = question.id
  try {
    await api.deleteBankQuestion(question.id)
    questions.value  = questions.value.filter(q => q.id !== question.id)
    totalItems.value = Math.max(0, totalItems.value - 1)
  } catch {
    alert('Xóa thất bại, vui lòng thử lại.')
  } finally {
    deletingId.value = null
  }
}

function handleQuestionCreated() {
  showCreate.value = false
  fetchQuestions(currentPage.value)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4 md:px-8">
    <div class="max-w-6xl mx-auto">

      <!-- Header tab -->
      <div class="flex justify-between items-center mb-8 border-b-2 border-gray-200">
        <div class="flex gap-12">
          <button class="px-2 py-3 font-semibold text-indigo-600 border-b-2 border-indigo-600 text-lg">
            Câu hỏi
          </button>
        </div>
        <span class="text-sm text-gray-400 mb-1">{{ totalItems }} câu hỏi</span>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-col md:flex-row gap-4 mb-8 items-center justify-between">
        <div class="flex gap-4 flex-1 w-full">
          <input v-model="searchText" type="text" placeholder="Nhập từ khóa"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
          <select v-model="filterClass"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white">
            <option v-for="opt in CLASS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <button @click="showCreate = true"
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition whitespace-nowrap">
          + Tạo câu hỏi
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
      </div>

      <div v-else-if="error" class="text-center py-12 text-red-500">{{ error }}</div>

      <!-- Danh sách câu hỏi -->
      <div v-else class="space-y-4">
        <div v-for="question in questions" :key="question.id"
          class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-5 flex items-start gap-4">

          <div class="flex-shrink-0 pt-1">
            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/>
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-gray-800 text-base leading-relaxed break-words">{{ question.content }}</p>
            <div class="flex gap-2 mt-2 flex-wrap">
              <span v-if="question.class_id"
                class="text-xs px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full border border-blue-200">
                {{ CLASS_MAP[String(question.class_id)] ?? `Lớp ${question.class_id}` }}
              </span>
              <span v-if="question.level_id"
                class="text-xs px-2 py-0.5 bg-green-50 text-green-600 rounded-full border border-green-200">
                {{ LEVEL_MAP[String(question.level_id)] ?? `Level ${question.level_id}` }}
              </span>
            </div>
          </div>

          <!-- Ảnh -->
          <div v-if="question.image" class="flex-shrink-0">
            <img :src="question.image" alt="Question image"
              class="h-16 w-16 object-cover rounded border border-gray-200"
              @error="(e) => e.target.style.display='none'" />
          </div>
          <div v-else class="flex-shrink-0 w-16 h-16 rounded border border-dashed border-gray-200 bg-gray-50 flex items-center justify-center">
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>

          <!-- Actions -->
          <div class="flex gap-1 flex-shrink-0 pt-1">
            <!-- Nút Chỉnh sửa  -->
            <button @click="openEdit(question)"
              class="text-indigo-600 hover:text-indigo-700 p-2 rounded hover:bg-indigo-50 transition" title="Chỉnh sửa">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button @click="handleDelete(question)" :disabled="deletingId === question.id"
              class="text-red-500 hover:text-red-600 p-2 rounded hover:bg-red-50 transition disabled:opacity-50" title="Xóa">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="questions.length === 0" class="text-center py-12">
          <p class="text-gray-500 text-lg">Không tìm thấy câu hỏi nào</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex justify-center gap-2 flex-wrap">
        <button @click="fetchQuestions(currentPage - 1)" :disabled="currentPage === 1"
          class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition disabled:opacity-40">«</button>
        <template v-for="p in pageNumbers" :key="p">
          <span v-if="p === '...'" class="px-3 py-2 text-gray-400">…</span>
          <button v-else @click="fetchQuestions(p)"
            :class="['px-3 py-2 border rounded transition',
              p === currentPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 hover:bg-gray-100']">
            {{ p }}
          </button>
        </template>
        <button @click="fetchQuestions(currentPage + 1)" :disabled="currentPage === totalPages"
          class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-100 transition disabled:opacity-40">»</button>
      </div>
    </div>

    <!-- Modal tạo câu hỏi mới -->
    <CreateQuestions v-if="showCreate"
      @close="showCreate = false"
      @created="handleQuestionCreated" />

    <!-- Modal chỉnh sửa câu hỏi -->
    <QuestionDetail v-if="editingQuestion"
      :question="editingQuestion"
      @close="closeEdit"
      @updated="handleUpdated" />

  </div>
</template>