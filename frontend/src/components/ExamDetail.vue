<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTeacherApi } from '@/composables/useTeacherApi.js'
import MathText from '@/components/MathText.vue'

const route  = useRoute()
const router = useRouter()
const api    = useTeacherApi()
const examId = route.params.id

const activeTab = ref('detail')   

const CLASS_OPTIONS = [
  { value: '1', label: 'Lớp 10' },
  { value: '2', label: 'Lớp 11' },
  { value: '3', label: 'Lớp 12' },
]
const TYPE_OPTIONS = [
  { value: '1', label: 'Kiểm tra' },
  { value: '2', label: 'Ôn tập' },
]
const CLASS_MAP = Object.fromEntries(CLASS_OPTIONS.map(c => [c.value, c.label]))
const TYPE_MAP  = Object.fromEntries(TYPE_OPTIONS.map(t => [t.value, t.label]))

// Dữ liệu đề thi 
const exam      = ref(null)
const questions = ref([])
const loading   = ref(true)
const loadErr   = ref('')

// Chỉnh sửa thông tin chung 
const isEditing   = ref(false)
const savingInfo  = ref(false)
const saveInfoErr = ref('')
const saveInfoOk  = ref(false)

const form = ref({
  title: '', description: '', type_id: '1',
  class_id: '', duration: '', shuffle_ques: false, shuffle_ans: false,
})
const isReview = computed(() => form.value.type_id === '2')
watch(isReview, v => { if (v) form.value.duration = '' })

function enterEdit() {
  form.value = {
    title:        exam.value.title       ?? '',
    description:  exam.value.description ?? '',
    type_id:      String(exam.value.type_id  ?? '1'),
    class_id:     String(exam.value.class_id ?? ''),
    duration:     exam.value.duration    ?? '',
    shuffle_ques: !!exam.value.shuffle_ques,
    shuffle_ans:  !!exam.value.shuffle_ans,
  }
  isEditing.value   = true
  saveInfoErr.value = ''
}
function cancelEdit() { isEditing.value = false; saveInfoErr.value = '' }

async function saveExamInfo() {
  saveInfoErr.value = ''; savingInfo.value = true
  try {
    await api.updateExam(examId, {
      title:        form.value.title.trim() || null,
      description:  form.value.description,
      class_id:     Number(form.value.class_id),
      type_id:      Number(form.value.type_id),
      duration:     form.value.duration ? Number(form.value.duration) : null,
      shuffle_ques: form.value.shuffle_ques,
      shuffle_ans:  form.value.shuffle_ans,
    })
    Object.assign(exam.value, {
      title:        form.value.title,
      description:  form.value.description,
      type_id:      form.value.type_id,
      class_id:     form.value.class_id,
      duration:     form.value.duration,
      shuffle_ques: form.value.shuffle_ques,
      shuffle_ans:  form.value.shuffle_ans,
    })
    isEditing.value  = false
    saveInfoOk.value = true
    setTimeout(() => saveInfoOk.value = false, 3000)
  } catch { saveInfoErr.value = 'Lưu thất bại, vui lòng thử lại.' }
  finally  { savingInfo.value = false }
}

// Chỉnh sửa từng câu hỏi 
const qEdits     = ref({})
const deletingId = ref(null)

function startQEdit(q) {
  qEdits.value[String(q.id)] = {
    content:   q.content ?? '',
    image:     q.image   ?? null,
    imageFile: null,
    answers:   (q.answers ?? []).map(a => ({ ...a })),
    saving: false,
    error:  '',
  }
}
function cancelQEdit(id) { delete qEdits.value[String(id)] }

function setCorrect(id, aIdx) {
  qEdits.value[String(id)].answers.forEach((a, i) => { a.is_correct = i === aIdx })
}
function addAnswer(id) {
  qEdits.value[String(id)].answers.push({ id: null, content: '', is_correct: false })
}
function removeAnswer(id, aIdx) {
  if (qEdits.value[String(id)].answers.length <= 2) return
  qEdits.value[String(id)].answers.splice(aIdx, 1)
}
function pickImage(id) {
  const input = document.createElement('input')
  input.type = 'file'; input.accept = 'image/*'
  input.onchange = e => {
    const f = e.target.files[0]; if (!f) return
    qEdits.value[String(id)].image     = URL.createObjectURL(f)
    qEdits.value[String(id)].imageFile = f
  }
  input.click()
}
function removeQImage(id) {
  qEdits.value[String(id)].image     = null
  qEdits.value[String(id)].imageFile = null
}

async function saveQuestion(id) {
  const key  = String(id)
  const edit = qEdits.value[key]
  if (!edit.content.trim())                      { edit.error = 'Chưa nhập nội dung.'; return }
  if (!edit.answers.some(a => a.is_correct))     { edit.error = 'Chưa chọn đáp án đúng.'; return }
  if (edit.answers.some(a => !a.content.trim())) { edit.error = 'Có đáp án trống.'; return }
  edit.saving = true; edit.error = ''
  try {
    if (edit.imageFile) {
      const r = await api.uploadQuestionImage(edit.imageFile)
      const j = (r?.data && typeof r.data === 'object') ? r.data : r
      if (j?.status === 'success') edit.image = j.url
      edit.imageFile = null
    }
    await api.updateQuestion(id, {
      content: edit.content,
      image:   edit.image ?? null,
      answers: edit.answers.map((a, i) => ({
        id: a.id ?? null, content: a.content,
        is_correct: a.is_correct, order_index: i + 1,
      })),
    })
    const q = questions.value.find(q => String(q.id) === key)
    if (q) {
      q.content = edit.content
      q.image   = edit.image
      q.answers = edit.answers.map((a, i) => ({ ...a, order_index: i + 1 }))
    }
    delete qEdits.value[key]
  } catch { edit.error = 'Lưu thất bại.' }
  finally  { edit.saving = false }
}

async function deleteQuestion(id) {
  if (!confirm('Xóa câu hỏi này?')) return
  deletingId.value = id
  try {
    await api.deleteQuestion(id)
    questions.value = questions.value.filter(q => String(q.id) !== String(id))
    delete qEdits.value[String(id)]
  } catch { alert('Xóa thất bại.') }
  finally  { deletingId.value = null }
}

// Upload file thêm câu hỏi 
const fileInputRef    = ref(null)
const uploadLoading   = ref(false)
const uploadError     = ref('')
const showFormatGuide = ref(false)

function triggerFileUpload() { uploadError.value = ''; fileInputRef.value?.click() }

async function onFileChange(e) {
  const file = e.target.files[0]; if (!file) return
  e.target.value = ''
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['docx', 'xlsx'].includes(ext)) { uploadError.value = 'Chỉ hỗ trợ file .docx hoặc .xlsx'; return }
  uploadLoading.value = true; uploadError.value = ''
  try {
    const raw  = await api.uploadExamFile(file)
    const json = (raw?.data && typeof raw.data === 'object') ? raw.data : raw
    if (json?.status !== 'success' || !Array.isArray(json?.data)) {
      uploadError.value = json?.message ?? 'Đọc file thất bại'; return
    }
    const labels = ['A', 'B', 'C', 'D', 'E', 'F']
    for (let i = 0; i < json.data.length; i++) {
      const item = json.data[i]
      await api.createQuestion({
        exam_id: examId, content: item.question, image: null,
        score: 0, order_index: questions.value.length + i + 1,
        answers: (item.answers ?? []).map((text, ai) => ({
          content: text, is_correct: item.correct === labels[ai], order_index: ai + 1,
        })),
      })
    }
    const qRes = await api.getExamById(examId)
    questions.value = qRes?.data?.questions ?? []
  } catch { uploadError.value = 'Lỗi khi xử lý file.' }
  finally  { uploadLoading.value = false }
}

// ─── Lịch sử làm bài ─────────────────────────────────────────────────────
// const attempts      = ref([])
// const attLoading    = ref(false)
// const attErr        = ref('')
// const attPage       = ref(1)
// const attTotalPages = ref(1)
// const attSearch     = ref('')

// const filteredAttempts = computed(() => {
//   const q = attSearch.value.trim().toLowerCase()
//   if (!q) return attempts.value
//   return attempts.value.filter(a => {
//     const name  = (a.student?.fullname ?? a.studentName ?? a.student_name ?? '').toLowerCase()
//     const email = (a.student?.email    ?? a.studentEmail ?? '').toLowerCase()
//     return name.includes(q) || email.includes(q)
//   })
// })

// async function fetchAttempts(page = 1) {
//   attLoading.value = true; attErr.value = ''
//   try {
//     const res  = await api.getExamAttempts({ examId, page, limit: 50 })
//     const data = res?.data ?? {}
//     attempts.value      = data.items ?? data.data ?? []
//     attTotalPages.value = data.pagination?.totalPages ?? 1
//     attPage.value       = page
//   } catch { attErr.value = 'Không thể tải lịch sử làm bài.' }
//   finally  { attLoading.value = false }
// }

// function switchTab(tab) {
//   activeTab.value = tab
//   if (tab === 'history' && attempts.value.length === 0) fetchAttempts(1)
// }

function exportCSV() {
  const rows = [
    ['Họ tên', 'Email', 'Thời gian nộp', 'Câu đúng', 'Tổng câu', 'Điểm', 'Thời gian làm (phút)', 'Kết quả'],
    ...filteredAttempts.value.map(a => [
      a.student?.fullname ?? a.studentName ?? a.student_name ?? 'Học sinh',
      a.student?.email    ?? a.studentEmail ?? '',
      fmtDate(a.submit_time ?? a.submitTime),
      a.correct_count  ?? a.correctCount  ?? '',
      a.total_questions ?? a.totalQuestions ?? questions.value.length,
      a.total_score != null ? Number(a.total_score).toFixed(1) : '',
      a.duration_mins  ?? a.durationMins  ?? '',
      a.total_score != null ? (Number(a.total_score) >= 5 ? 'Đạt' : 'Chưa đạt') : '',
    ]),
  ]
  const csv  = rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href = url; a.download = `lich-su-lam-bai-${examId}.csv`; a.click()
  URL.revokeObjectURL(url)
}

// Fetch ban đầu 
async function fetchExam() {
  loading.value = true; loadErr.value = ''
  try {
    const res = await api.getExamById(examId)
    const d   = res?.data ?? {}
    exam.value      = d.exam      ?? d
    questions.value = d.questions ?? []
  } catch { loadErr.value = 'Không thể tải đề thi.' }
  finally  { loading.value = false }
}

// Helpers
function fmtDate(s) {
  if (!s) return '—'
  return new Date(s).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
function scoreColor(s) {
  return +s >= 8 ? 'text-green-600' : +s >= 5 ? 'text-yellow-600' : 'text-red-500'
}

const avgScore = computed(() => {
  const v = filteredAttempts.value.filter(a => a.total_score != null)
  if (!v.length) return null
  return (v.reduce((s, a) => s + Number(a.total_score), 0) / v.length).toFixed(1)
})
const passCount = computed(() =>
  filteredAttempts.value.filter(a => Number(a.total_score) >= 5).length
)
const highestScore = computed(() => {
  const v = filteredAttempts.value.filter(a => a.total_score != null)
  if (!v.length) return null
  return Math.max(...v.map(a => Number(a.total_score))).toFixed(1)
})

onMounted(fetchExam)
</script>

<template>
  <div class="min-h-screen bg-gray-100 py-8 px-4">

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-24">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>
    <div v-else-if="loadErr" class="text-center py-24 text-red-500 text-lg">{{ loadErr }}</div>

    <!-- Card chính -->
    <div v-else-if="exam" class="bg-white rounded-lg shadow-2xl w-full max-w-4xl mx-auto">

      <!-- ── HEADER ── -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center z-10 rounded-t-lg">
        <button @click="router.push('/teacher/exams')"
          class="text-gray-400 hover:text-gray-700 text-sm flex items-center gap-1 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Quay lại
        </button>

        <h2 class="text-2xl font-bold text-indigo-600">Bài kiểm tra/Ôn tập</h2>

        <div class="flex gap-2">
          <template v-if="activeTab === 'detail' && !isEditing">
            <button @click="enterEdit"
              class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
              Chỉnh sửa
            </button>
          </template>
          <template v-else-if="activeTab === 'detail' && isEditing">
            <button @click="saveExamInfo" :disabled="savingInfo"
              class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition">
              {{ savingInfo ? 'Đang lưu...' : 'Lưu' }}
            </button>
            <button @click="cancelEdit"
              class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
              Hủy
            </button>
          </template>
          <div v-else class="w-20"></div>
        </div>
      </div>

      <!-- ── TABS ── -->
      <div class="flex border-b border-gray-200 px-8">
        <button @click="switchTab('detail')"
          :class="['px-5 py-3 text-sm font-medium border-b-2 -mb-px transition',
            activeTab === 'detail'
              ? 'border-indigo-600 text-indigo-700'
              : 'border-transparent text-gray-500 hover:text-gray-700']">
          Chi tiết đề thi
        </button>
        <!-- <button @click="switchTab('history')"
          :class="['px-5 py-3 text-sm font-medium border-b-2 -mb-px transition',
            activeTab === 'history'
              ? 'border-indigo-600 text-indigo-700'
              : 'border-transparent text-gray-500 hover:text-gray-700']">
          Lịch sử làm bài
          <span v-if="attempts.length"
            class="ml-1.5 text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full font-semibold">
            {{ attempts.length }}
          </span>
        </button> -->
      </div>

      <!-- ══════════════════════════════════════════
           TAB CHI TIẾT
           ══════════════════════════════════════════ -->
      <div v-if="activeTab === 'detail'" class="p-8">

        <p v-if="saveInfoOk"  class="mb-4 text-center text-green-600 font-medium text-sm">Đã lưu thành công!</p>
        <p v-if="saveInfoErr" class="mb-4 text-center text-red-500 text-sm">⚠️ {{ saveInfoErr }}</p>

        <!-- ── THÔNG TIN CHUNG ── -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Thông tin chung</h3>

          <!-- Tiêu đề -->
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Tiêu đề bài kiểm tra</label>
            <div v-if="!isEditing"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-800 min-h-[48px]">
              {{ exam.title || '—' }}
            </div>
            <input v-else v-model="form.title" type="text"
              placeholder="Nhập tiêu đề bài kiểm tra"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
            <p v-if="isEditing" class="text-xs text-gray-400 mt-1">* Để trống → tự động đặt tên "Bài kiểm tra số [STT]"</p>
          </div>

          <!-- Mô tả -->
          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Mô tả ngắn gọn</label>
            <div v-if="!isEditing"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-800 min-h-[96px] whitespace-pre-wrap">
              {{ exam.description || '—' }}
            </div>
            <textarea v-else v-model="form.description" rows="4" placeholder="Nhập mô tả..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
          </div>

          <!-- Hình thức + Lớp -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Hình thức</label>
              <div v-if="!isEditing"
                class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-800">
                {{ TYPE_MAP[String(exam.type_id)] ?? '—' }}
              </div>
              <select v-else v-model="form.type_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn hình thức --</option>
                <option v-for="t in TYPE_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Lớp</label>
              <div v-if="!isEditing"
                class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-800">
                {{ CLASS_MAP[String(exam.class_id)] ?? '—' }}
              </div>
              <select v-else v-model="form.class_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn lớp --</option>
                <option v-for="c in CLASS_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <!-- Thời gian -->
          <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Thời gian làm bài (phút)</label>
            <div v-if="!isEditing"
              class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-800">
              {{ exam.duration ? exam.duration + ' phút' : 'Không giới hạn' }}
            </div>
            <input v-else v-model.number="form.duration" type="number" min="1"
              :placeholder="isReview ? 'Để trống = không giới hạn' : 'Nhập số phút...'"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
            <p v-if="isEditing" class="text-xs text-gray-500 mt-2">* Nếu là ôn tập, phần thời gian có thể để trống</p>
          </div>
        </div>

        <hr class="my-8" />

        <!-- ── TRỘN THỨ TỰ ── -->
        <div class="mb-8 space-y-4">
          <template v-if="!isEditing">
            <label class="flex items-center gap-3">
              <input type="checkbox" :checked="exam.shuffle_ques" disabled class="w-5 h-5 rounded border-gray-300" />
              <span class="text-gray-800 font-medium">Trộn thứ tự câu hỏi</span>
            </label>
            <label class="flex items-center gap-3">
              <input type="checkbox" :checked="exam.shuffle_ans" disabled class="w-5 h-5 rounded border-gray-300" />
              <span class="text-gray-800 font-medium">Trộn thứ tự đáp án</span>
            </label>
          </template>
          <template v-else>
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.shuffle_ques" type="checkbox" class="w-5 h-5 rounded border-gray-300" />
              <span class="text-gray-800 font-medium">Trộn thứ tự câu hỏi</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.shuffle_ans" type="checkbox" class="w-5 h-5 rounded border-gray-300" />
              <span class="text-gray-800 font-medium">Trộn thứ tự đáp án</span>
            </label>
          </template>
        </div>

        <hr class="my-8" />

        <!-- ── DANH SÁCH CÂU HỎI ── -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Câu hỏi</h3>

          <div v-for="(q, qIdx) in questions" :key="q.id"
            class="mb-8 border border-gray-300 rounded-lg p-6 bg-gray-50">

            <!-- Header câu -->
            <div class="flex justify-between items-start mb-4">
              <h4 class="text-lg font-bold text-gray-800">Câu {{ qIdx + 1 }}</h4>
              <div v-if="!qEdits[q.id]" class="flex gap-2">
                <button @click="startQEdit(q)"
                  class="text-sm px-3 py-1.5 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 font-medium transition">
                  Sửa
                </button>
                <button @click="deleteQuestion(q.id)" :disabled="deletingId === q.id"
                  class="text-sm px-3 py-1.5 text-red-500 bg-red-50 rounded-lg hover:bg-red-100 font-medium disabled:opacity-50 transition">
                  {{ deletingId === q.id ? '...' : 'Xóa' }}
                </button>
              </div>
              <button v-else @click="cancelQEdit(q.id)" class="text-red-500 hover:text-red-700">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                </svg>
              </button>
            </div>

            <!-- VIEW MODE -->
            <template v-if="!qEdits[q.id]">
              <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
                <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-white text-gray-800 leading-relaxed min-h-[72px]">
                  <MathText :text="q.content" />
                </div>
              </div>
              <div v-if="q.image" class="mb-6">
                <img :src="q.image" class="max-h-48 rounded-lg border border-gray-200 object-contain" />
              </div>
              <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-3">Câu trả lời:</label>
                <div class="space-y-3">
                  <div v-for="(ans, aIdx) in q.answers" :key="ans.id ?? aIdx" class="flex items-center gap-3">
                    <input type="radio" :name="`view_q_${q.id}`" :checked="ans.is_correct" disabled
                      class="w-5 h-5 flex-shrink-0" />
                    <div :class="['flex-1 min-w-0 px-4 py-2 border rounded-lg',
                      ans.is_correct ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-white']">
                      <MathText :text="ans.content"
                        :class="ans.is_correct ? 'font-medium text-indigo-800' : 'text-gray-700'" />
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- EDIT MODE -->
            <template v-else>
              <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
                <textarea v-model="qEdits[q.id].content" rows="3" placeholder="Nhập câu hỏi..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
                <div v-if="qEdits[q.id].content"
                  class="mt-2 px-3 py-2 bg-white border border-indigo-100 rounded-lg text-gray-800 text-sm leading-relaxed">
                  <span class="text-xs text-indigo-400 font-medium mr-1">Preview:</span>
                  <MathText :text="qEdits[q.id].content" />
                </div>
              </div>

              <div class="mb-6">
                <div v-if="qEdits[q.id].image" class="flex items-center gap-3 mb-2">
                  <img :src="qEdits[q.id].image" class="h-20 w-20 object-cover rounded border border-gray-300" />
                  <button @click="removeQImage(q.id)" class="text-sm text-red-500 hover:text-red-700">Xóa ảnh</button>
                </div>
                <button @click="pickImage(q.id)"
                  class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  {{ qEdits[q.id].image ? 'Đổi ảnh' : 'Tải ảnh' }}
                </button>
              </div>

              <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-3">Câu trả lời:</label>
                <div class="space-y-3">
                  <div v-for="(ans, aIdx) in qEdits[q.id].answers" :key="aIdx" class="flex items-center gap-3">
                    <input type="radio" :name="`q_${q.id}`" :checked="ans.is_correct"
                      @change="setCorrect(q.id, aIdx)" class="w-5 h-5 cursor-pointer flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                      <input v-model="ans.content" type="text" placeholder="Câu trả lời..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
                      <div v-if="ans.content && ans.content.includes('\\(')"
                        class="mt-1 px-2 py-1 bg-white border border-indigo-100 rounded text-sm text-gray-700">
                        <MathText :text="ans.content" />
                      </div>
                    </div>
                    <button @click="removeAnswer(q.id, aIdx)" class="text-red-500 hover:text-red-700 p-2 flex-shrink-0">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <button @click="addAnswer(q.id)"
                  class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm flex items-center gap-2">
                  + Thêm câu trả lời
                </button>
              </div>

              <p v-if="qEdits[q.id].error" class="text-red-500 text-sm font-medium mb-3">⚠️ {{ qEdits[q.id].error }}</p>

              <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button @click="saveQuestion(q.id)" :disabled="qEdits[q.id].saving"
                  class="px-6 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition font-bold disabled:opacity-50">
                  {{ qEdits[q.id].saving ? 'Đang lưu...' : 'Lưu câu hỏi' }}
                </button>
                <button @click="cancelQEdit(q.id)"
                  class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition font-medium">
                  Hủy
                </button>
              </div>
            </template>

          </div><!-- end v-for questions -->

          <!-- Upload file -->
          <div class="mt-4">
            <input ref="fileInputRef" type="file" accept=".docx,.xlsx" class="hidden" @change="onFileChange" />
            <button @click="triggerFileUpload" :disabled="uploadLoading"
              class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium disabled:opacity-50">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              {{ uploadLoading ? 'Đang xử lý...' : 'Tải file' }}
            </button>
            <p v-if="uploadError" class="text-red-500 text-xs mt-1">{{ uploadError }}</p>
            <p class="text-xs text-gray-400 mt-1">Hỗ trợ .docx, .xlsx —
              <span class="text-indigo-500 cursor-pointer hover:underline" @click="showFormatGuide = !showFormatGuide">
                Xem định dạng mẫu
              </span>
            </p>
            <div v-if="showFormatGuide"
              class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 space-y-1">
              <p><strong>.docx:</strong> Câu 1: [nội dung] → A. [đáp án] B. [đáp án] C. ... D. ... → Đáp án: A</p>
              <p><strong>.xlsx:</strong> Cột A=Câu hỏi, B=Đáp án đúng (A/B/C/D), C→F=Nội dung đáp án. Hàng 1 là tiêu đề.</p>
            </div>
          </div>

        </div><!-- end câu hỏi section -->

      </div><!-- end tab detail -->

    </div><!-- end card -->
  </div>
</template>

<style scoped>
input[type="radio"]             { accent-color: #4f46e5; }
input[type="radio"]:disabled    { opacity: 0.8; cursor: default; }
input[type="checkbox"]          { accent-color: #4f46e5; }
input[type="checkbox"]:disabled { opacity: 0.7; cursor: default; }
</style>