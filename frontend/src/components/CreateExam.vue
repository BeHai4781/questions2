<script setup>
import { ref, computed, watch } from 'vue'
import MathText from '@/components/MathText.vue'
import { useTeacherApi } from '@/composables/useTeacherApi.js'

const emit = defineEmits(['close', 'created'])
const api  = useTeacherApi()

// Constants 
const CLASS_OPTIONS = [
  { value: '1', label: 'Lớp 10' },
  { value: '2', label: 'Lớp 11' },
  { value: '3', label: 'Lớp 12' },
]
const TYPE_OPTIONS = [
  { value: '1', label: 'Kiểm tra' },
  { value: '2', label: 'Ôn tập' },
]
const LEVEL_OPTIONS = [
  { value: '1', label: 'Nhận biết' },
  { value: '2', label: 'Vận dụng' },
  { value: '3', label: 'Vận dụng cao' },
]

// Form state 
const form = ref({
  title:        '',
  description:  '',
  type_id:      '1',
  class_id:     '',
  duration:     '',
  shuffle_ques: false,
  shuffle_ans:  false,
})

const questions = ref([makeQuestion()])
const saving    = ref(false)
const errorMsg  = ref('')

const isReview = computed(() => form.value.type_id === '2')
watch(isReview, (v) => { if (v) form.value.duration = '' })

// Manual question helpers 
function makeQuestion() {
  return {
    _id:      Date.now() + Math.random(),
    content:  '',
    image:    null,
    imageFile: null,
    answers: [
      { _id: 1, content: '', is_correct: false },
      { _id: 2, content: '', is_correct: false },
      { _id: 3, content: '', is_correct: false },
      { _id: 4, content: '', is_correct: false },
    ],
    score: 10,
  }
}

function addQuestion() {
  questions.value.push(makeQuestion())
}

function removeQuestion(idx) {
  questions.value.splice(idx, 1)
}

function addAnswer(qIdx) {
  const q = questions.value[qIdx]
  q.answers.push({ _id: Date.now(), content: '', is_correct: false })
}

function removeAnswer(qIdx, aIdx) {
  if (questions.value[qIdx].answers.length <= 2) return
  questions.value[qIdx].answers.splice(aIdx, 1)
}

function setCorrect(qIdx, aIdx) {
  questions.value[qIdx].answers.forEach((a, i) => {
    a.is_correct = i === aIdx
  })
}

function pickImage(qIdx) {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    questions.value[qIdx].image = URL.createObjectURL(file)
    questions.value[qIdx].imageFile = file
  }
  input.click()
}

function removeImage(qIdx) {
  questions.value[qIdx].image = null
  questions.value[qIdx].imageFile = null
}

// Nhập từ thư viện (modal) 
const showLibrary    = ref(false)
const libLoading     = ref(false)
const libError       = ref('')
const libConfig = ref({
  count:   10,
  classId: '',
  levels: { '1': 34, '2': 33, '3': 33 },
})
const totalPct = computed(() =>
  Object.values(libConfig.value.levels).reduce((s, v) => s + Number(v || 0), 0)
)

async function fetchFromLibrary() {
  if (totalPct.value !== 100) { libError.value = 'Tổng tỉ lệ phải bằng 100%'; return }
  libError.value = ''
  libLoading.value = true
  try {
    const total = Number(libConfig.value.count) || 10
    const added = []
    for (const [levelId, pct] of Object.entries(libConfig.value.levels)) {
      const n = Math.round(total * Number(pct) / 100)
      if (n <= 0) continue
      const res  = await api.getBankQuestions({ limit: n * 3, levelId, classId: libConfig.value.classId || undefined })
      const pool = (res?.data?.items ?? res?.data ?? []).sort(() => Math.random() - 0.5).slice(0, n)
      pool.forEach(bq => {
        added.push({
          _id:      bq.id,
          content:  bq.content,
          image:    bq.image ?? null,
          imageFile: null,
          answers: (bq.answers ?? []).map((a, i) => ({
            _id: a.id ?? i, content: a.content, is_correct: !!a.is_correct,
          })),
          score: 10,
        })
      })
    }
    if (added.length === 0) { libError.value = 'Không tìm thấy câu hỏi phù hợp.'; return }
    const existIds = new Set(questions.value.map(q => q._id))
    added.forEach(q => { if (!existIds.has(q._id)) questions.value.push(q) })
    // Xóa câu placeholder trống
    questions.value = questions.value.filter(q => q.content.trim() !== '')
    if (questions.value.length === 0) questions.value.push(makeQuestion())
    showLibrary.value = false
  } catch (e) {
    libError.value = 'Lỗi khi lấy câu hỏi từ thư viện.'
  } finally {
    libLoading.value = false
  }
}

// Tải file 
const fileInputRef   = ref(null)
const uploadLoading  = ref(false)
const uploadError    = ref('')
const showFormatGuide = ref(false)

function triggerFileUpload() {
  uploadError.value = ''
  fileInputRef.value?.click()
}

async function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  e.target.value = ''
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['docx', 'xlsx'].includes(ext)) {
    uploadError.value = 'Chỉ hỗ trợ file .docx hoặc .xlsx'
    return
  }
  uploadLoading.value = true
  uploadError.value   = ''
  try {
    const raw = await api.uploadExamFile(file)

    // Response::success() wraps thành { success, data: { status, data:[...] } }
    // Nên unwrap: lấy raw.data nếu có, fallback về raw
    const json = (raw?.data && typeof raw.data === 'object') ? raw.data : raw

    if (json?.status !== 'success' || !Array.isArray(json?.data)) {
      uploadError.value = json?.message ?? raw?.message ?? 'Đọc file thất bại'
      console.error('[upload] unexpected response:', raw)
      return
    }
    const labels = ['A','B','C','D','E','F']
    const newQs  = json.data.map((item, i) => ({
      _id:      'upload_' + Date.now() + i,
      content:  item.question,
      image:    null,
      imageFile: null,
      answers: (item.answers ?? []).map((text, ai) => ({
        _id: ai, content: text, is_correct: item.correct === labels[ai],
      })),
      score: 10,
    }))
    const existIds = new Set(questions.value.map(q => q._id))
    newQs.forEach(q => { if (!existIds.has(q._id)) questions.value.push(q) })
    // Xóa các câu placeholder trống (câu mặc định khi mở form)
    questions.value = questions.value.filter(q => q.content.trim() !== '')
    // Đảm bảo luôn có ít nhất 1 câu
    if (questions.value.length === 0) questions.value.push(makeQuestion())
  } catch (e) {
    uploadError.value = 'Lỗi khi xử lý file.'
  } finally {
    uploadLoading.value = false
  }
}

// Submit 
async function saveExam() {
  errorMsg.value = ''
  if (!form.value.class_id)                   { errorMsg.value = 'Vui lòng chọn lớp.'; return }
  if (!isReview.value && !form.value.duration) { errorMsg.value = 'Vui lòng nhập thời gian làm bài.'; return }
  if (questions.value.length === 0)            { errorMsg.value = 'Vui lòng thêm ít nhất 1 câu hỏi.'; return }

  for (let i = 0; i < questions.value.length; i++) {
    const q = questions.value[i]
    if (!q.content.trim())              { errorMsg.value = `Câu ${i+1}: chưa nhập nội dung.`; return }
    if (!q.answers.some(a => a.is_correct)) { errorMsg.value = `Câu ${i+1}: chưa chọn đáp án đúng.`; return }
    if (q.answers.some(a => !a.content.trim())) { errorMsg.value = `Câu ${i+1}: có đáp án trống.`; return }
  }

  saving.value = true
  try {
    // Upload ảnh
    for (const q of questions.value) {
      if (q.imageFile) {
        const imgRaw = await api.uploadQuestionImage(q.imageFile)
        const imgJson = (imgRaw?.data && typeof imgRaw.data === 'object') ? imgRaw.data : imgRaw
        if (imgJson?.status === 'success') q.image = imgJson.url
        q.imageFile = null
      }
    }

    const n = questions.value.length
    const scorePerQ = Math.round((10 / n) * 100) / 100

    const examRes = await api.createExam({
      title:       form.value.title.trim() || null,
      description: form.value.description,
      class_id:    Number(form.value.class_id),
      type_id:     Number(form.value.type_id),
      duration:    form.value.duration ? Number(form.value.duration) : null,
      shuffle_ques: form.value.shuffle_ques,
      shuffle_ans:  form.value.shuffle_ans,
    })

    const newExam = examRes?.data?.exam ?? examRes?.data
    if (!newExam?.id) throw new Error('Tạo đề thi thất bại')

    for (let i = 0; i < questions.value.length; i++) {
      const q = questions.value[i]
      await api.createQuestion({
        exam_id: newExam.id, content: q.content, image: q.image ?? null,
        score: scorePerQ, order_index: i + 1,
        answers: q.answers.map((a, ai) => ({
          content: a.content, is_correct: a.is_correct, order_index: ai + 1,
        })),
      })
    }

    emit('created', newExam)
  } catch (e) {
    errorMsg.value = e.message || 'Lưu đề thi thất bại.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

      <!-- Header -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center z-10">
        <div></div>
        <h2 class="text-2xl font-bold text-indigo-600">Bài kiểm tra/Ôn tập</h2>
        <button @click="$emit('close')" class="text-2xl text-gray-500 hover:text-gray-800 leading-none">×</button>
      </div>

      <div class="p-8">

        <!-- ── THÔNG TIN CHUNG ── -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Thông tin chung</h3>

          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Tiêu đề bài kiểm tra</label>
            <input v-model="form.title" type="text"
              placeholder="Nhập tiêu đề bài kiểm tra"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
            <p class="text-xs text-gray-400 mt-1">* Để trống → tự động đặt tên "Bài kiểm tra số [STT]"</p>
          </div>

          <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Mô tả ngắn gọn</label>
            <textarea v-model="form.description" rows="4" placeholder="Nhập mô tả..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
          </div>

          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Hình thức</label>
              <select v-model="form.type_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn hình thức --</option>
                <option v-for="t in TYPE_OPTIONS" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Lớp</label>
              <select v-model="form.class_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn lớp --</option>
                <option v-for="c in CLASS_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Thời gian làm bài (phút)</label>
            <input v-model.number="form.duration" type="number" min="1"
              :placeholder="isReview ? 'Để trống = không giới hạn' : 'Nhập số phút...'"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
            <p class="text-xs text-gray-500 mt-2">* Nếu là ôn tập, phần thời gian có thể để trống</p>
          </div>
        </div>

        <hr class="my-8" />

        <!-- ── TRỘN ── -->
        <div class="mb-8 space-y-4">
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.shuffle_ques" type="checkbox" class="w-5 h-5 rounded border-gray-300" />
            <span class="text-gray-800 font-medium">Trộn thứ tự câu hỏi</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.shuffle_ans" type="checkbox" class="w-5 h-5 rounded border-gray-300" />
            <span class="text-gray-800 font-medium">Trộn thứ tự đáp án</span>
          </label>
        </div>

        <hr class="my-8" />

        <!-- ── CÂU HỎI ── -->
        <div class="mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-6">Câu hỏi</h3>

          <div v-for="(question, qIdx) in questions" :key="question._id"
            class="mb-8 border border-gray-300 rounded-lg p-6 bg-gray-50">

            <!-- Tiêu đề câu + nút xóa -->
            <div class="flex justify-between items-start mb-4">
              <h4 class="text-lg font-bold text-gray-800">Câu {{ qIdx + 1 }}</h4>
              <button @click="removeQuestion(qIdx)" class="text-red-500 hover:text-red-700">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                </svg>
              </button>
            </div>

            <!-- Nội dung câu hỏi -->
            <div class="mb-4">
              <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
              <textarea v-model="question.content" rows="3" placeholder="Nhập câu hỏi..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
              <div v-if="question.content" class="mt-2 px-3 py-2 bg-white border border-indigo-100 rounded-lg text-gray-800 text-sm leading-relaxed">
                <span class="text-xs text-indigo-400 font-medium mr-1">Preview:</span>
                <MathText :text="question.content" />
              </div>
            </div>

            <!-- Upload ảnh -->
            <div class="mb-6">
              <div v-if="question.image" class="flex items-center gap-3 mb-2">
                <img :src="question.image" class="h-20 w-20 object-cover rounded border border-gray-300" />
                <button @click="removeImage(qIdx)"
                  class="text-sm text-red-500 hover:text-red-700">Xóa ảnh</button>
              </div>
              <button @click="pickImage(qIdx)"
                class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ question.image ? 'Đổi ảnh' : 'Tải ảnh' }}
              </button>
            </div>

            <!-- Đáp án -->
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-3">Câu trả lời:</label>
              <div class="space-y-3">
                <div v-for="(answer, aIdx) in question.answers" :key="answer._id"
                  class="flex items-center gap-3">
                  <input type="radio" :name="`q_${question._id}`" :checked="answer.is_correct"
                    @change="setCorrect(qIdx, aIdx)" class="w-5 h-5 cursor-pointer flex-shrink-0" />
                  <div class="flex-1 min-w-0">
                    <input v-model="answer.content" type="text" placeholder="Câu trả lời..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
                    <div v-if="answer.content && answer.content.includes('\\(')" class="mt-1 px-2 py-1 bg-white border border-indigo-100 rounded text-sm text-gray-700">
                      <MathText :text="answer.content" />
                    </div>
                  </div>
                  <button @click="removeAnswer(qIdx, aIdx)" class="text-red-500 hover:text-red-700 p-2 flex-shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                    </svg>
                  </button>
                </div>
              </div>
              <button @click="addAnswer(qIdx)"
                class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm flex items-center gap-2">
                + Thêm câu trả lời
              </button>
            </div>

            <!-- Điểm -->
            <div>
              <label class="block text-gray-700 font-medium mb-2">Điểm:</label>
              <input v-model.number="question.score" type="number"
                class="w-24 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
            </div>
          </div>

          <!-- + Thêm câu hỏi -->
          <button @click="addQuestion"
            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 text-gray-600 rounded-lg hover:border-indigo-500 hover:text-indigo-600 transition font-medium">
            + Thêm câu hỏi
          </button>

          <!-- Tải file -->
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
            <!-- Hướng dẫn định dạng -->
            <div v-if="showFormatGuide"
              class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 space-y-1">
              <p><strong>.docx:</strong> Câu 1: [nội dung] → A. [đáp án] B. [đáp án] C. ... D. ... → Đáp án: A</p>
              <p><strong>.xlsx:</strong> Cột A=Câu hỏi, B=Đáp án đúng (A/B/C/D), C→F=Nội dung đáp án. Hàng 1 là tiêu đề.</p>
            </div>
          </div>
        </div>

        <!-- Error -->
        <p v-if="errorMsg" class="text-red-500 text-sm text-center font-medium mb-4">⚠️ {{ errorMsg }}</p>

        <hr class="my-8" />

        <!-- Footer buttons -->
        <div class="flex justify-end gap-4">
          <button @click="addQuestion"
            class="px-6 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition font-medium">
            Thêm câu hỏi
          </button>
          <button @click="showLibrary = true"
            class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition font-medium">
            Nhập từ thư viện
          </button>
          <button @click="saveExam" :disabled="saving"
            class="px-6 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition font-bold disabled:opacity-50">
            {{ saving ? 'Đang lưu...' : 'Đăng bài' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── MODAL: NHẬP TỪ THƯ VIỆN ── -->
    <div v-if="showLibrary" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-5">
          <h3 class="text-lg font-bold text-gray-800">🎲 Lấy câu hỏi từ thư viện</h3>
          <button @click="showLibrary = false" class="text-gray-400 hover:text-gray-700 text-xl">×</button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Số câu muốn lấy</label>
              <input v-model.number="libConfig.count" type="number" min="1" max="100"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Lọc theo lớp</label>
              <select v-model="libConfig.classId"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 bg-white">
                <option value="">Tất cả lớp</option>
                <option v-for="c in CLASS_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Tỉ lệ theo mức độ (%)
              <span :class="totalPct === 100 ? 'text-green-600' : 'text-red-500'" class="ml-2 font-bold text-xs">
                Tổng: {{ totalPct }}%
              </span>
            </label>
            <div class="grid grid-cols-3 gap-3">
              <div v-for="lv in LEVEL_OPTIONS" :key="lv.value">
                <label class="block text-xs text-gray-500 mb-1">{{ lv.label }}</label>
                <div class="flex items-center gap-1">
                  <input v-model.number="libConfig.levels[lv.value]" type="number" min="0" max="100"
                    class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                  <span class="text-gray-400 text-xs">%</span>
                </div>
              </div>
            </div>
          </div>

          <p v-if="libError" class="text-red-500 text-sm">{{ libError }}</p>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showLibrary = false"
            class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition text-sm">
            Hủy
          </button>
          <button @click="fetchFromLibrary" :disabled="libLoading"
            class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium text-sm disabled:opacity-50">
            {{ libLoading ? 'Đang lấy...' : '✅ Thêm câu hỏi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
input[type="radio"]    { accent-color: #4f46e5; }
input[type="checkbox"] { accent-color: #4f46e5; }
</style>