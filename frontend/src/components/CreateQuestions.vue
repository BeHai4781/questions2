<script setup>
import { ref } from 'vue'
import MathText from '@/components/MathText.vue'
import { useTeacherApi } from '@/composables/useTeacherApi.js'

const emit = defineEmits(['close', 'created'])
const api  = useTeacherApi()

// ─── Constants ───────────────────────────────
const CLASS_OPTIONS = [
  { value: '1', label: 'Lớp 10' },
  { value: '2', label: 'Lớp 11' },
  { value: '3', label: 'Lớp 12' },
]
const LEVEL_OPTIONS = [
  { value: '1', label: 'Nhận biết' },
  { value: '2', label: 'Vận dụng' },
  { value: '3', label: 'Vận dụng cao' },
]

// ─── State: danh sách câu hỏi đang soạn ──────
// Mỗi câu là 1 object độc lập để hỗ trợ "Thêm câu hỏi"
function makeQuestion() {
  return {
    _id:       Date.now() + Math.random(),
    class_id:  '',
    level_id:  '',
    content:   '',
    image:     null,
    imageFile: null,
    answers: [
      { _id: 1, content: '', is_correct: false },
      { _id: 2, content: '', is_correct: false },
      { _id: 3, content: '', is_correct: false },
      { _id: 4, content: '', is_correct: false },
    ],
  }
}

const questions   = ref([makeQuestion()])
const saving      = ref(false)
const errorMsg    = ref('')
const uploadError = ref('')
const uploadLoading = ref(false)
const showFormatGuide = ref(false)
const fileInputRef  = ref(null)

// ─── Answer helpers ───────────────────────────
function addAnswer(qIdx) {
  questions.value[qIdx].answers.push({ _id: Date.now(), content: '', is_correct: false })
}

function removeAnswer(qIdx, aIdx) {
  if (questions.value[qIdx].answers.length <= 2) return
  questions.value[qIdx].answers.splice(aIdx, 1)
}

function setCorrect(qIdx, aIdx) {
  questions.value[qIdx].answers.forEach((a, i) => { a.is_correct = i === aIdx })
}

// ─── Image upload ─────────────────────────────
function pickImage(qIdx) {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    questions.value[qIdx].image     = URL.createObjectURL(file)
    questions.value[qIdx].imageFile = file
  }
  input.click()
}

function removeImage(qIdx) {
  questions.value[qIdx].image     = null
  questions.value[qIdx].imageFile = null
}

// ─── Thêm câu hỏi mới (chưa lưu) ─────────────
function addQuestion() {
  questions.value.push(makeQuestion())
}

function removeQuestion(idx) {
  if (questions.value.length === 1) return
  questions.value.splice(idx, 1)
}

// ─── Upload file .docx/.xlsx ──────────────────
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
    uploadError.value = 'Chỉ hỗ trợ .docx hoặc .xlsx'; return
  }
  uploadLoading.value = true
  uploadError.value   = ''
  try {
    const raw     = await api.uploadExamFile(file)
    const payload = (raw?.data && typeof raw.data === 'object') ? raw.data : raw

    if (payload?.status !== 'success' || !Array.isArray(payload?.data)) {
      uploadError.value = payload?.message ?? 'Đọc file thất bại'; return
    }

    const labels = ['A','B','C','D','E','F']
    const newQs  = payload.data.map((item) => ({
      _id:       Date.now() + Math.random(),
      class_id:  '',
      level_id:  '',
      content:   item.question,
      image:     null,
      imageFile: null,
      answers: (item.answers ?? []).map((text, ai) => ({
        _id: ai, content: text, is_correct: item.correct === labels[ai],
      })),
    }))

    // Xóa câu placeholder trống, thêm câu từ file
    questions.value = questions.value.filter(q => q.content.trim() !== '')
    questions.value.push(...newQs)
    if (questions.value.length === 0) questions.value.push(makeQuestion())
  } catch {
    uploadError.value = 'Lỗi khi xử lý file.'
  } finally {
    uploadLoading.value = false
  }
}

// ─── Validate một câu hỏi ─────────────────────
function validateQuestion(q, idx) {
  if (!q.content.trim())                   return `Câu ${idx+1}: chưa nhập nội dung.`
  if (!q.answers.some(a => a.is_correct))  return `Câu ${idx+1}: chưa chọn đáp án đúng.`
  if (q.answers.some(a => !a.content.trim())) return `Câu ${idx+1}: có đáp án trống.`
  return null
}

// ─── Lưu tất cả câu hỏi ──────────────────────
async function saveAll() {
  errorMsg.value = ''
  for (let i = 0; i < questions.value.length; i++) {
    const err = validateQuestion(questions.value[i], i)
    if (err) { errorMsg.value = err; return }
  }
  saving.value = true
  try {
    const saved = []
    for (const q of questions.value) {
      // Upload ảnh nếu có
      if (q.imageFile) {
        const imgRaw  = await api.uploadQuestionImage(q.imageFile)
        const imgJson = (imgRaw?.data && typeof imgRaw.data === 'object') ? imgRaw.data : imgRaw
        if (imgJson?.status === 'success') q.image = imgJson.url
        q.imageFile = null
      }
      const res = await api.createBankQuestion({
        content:  q.content,
        class_id: q.class_id ? Number(q.class_id) : undefined,
        level_id: q.level_id ? Number(q.level_id) : undefined,
        image:    q.image ?? null,
        answers:  q.answers.map((a, ai) => ({
          content:    a.content,
          is_correct: a.is_correct,
          order_index: ai + 1,
        })),
      })
      saved.push(res?.data?.question ?? res?.data)
    }
    emit('created', saved)
  } catch (e) {
    errorMsg.value = e.message || 'Lưu thất bại.'
  } finally {
    saving.value = false
  }
}

// ─── Lưu từng câu (nút "Lưu câu hỏi" giữa) ───
async function saveCurrent() {
  errorMsg.value = ''
  // Lưu tất cả câu đã điền đủ
  const filled = questions.value.filter(q => q.content.trim() && q.answers.some(a => a.is_correct))
  if (filled.length === 0) { errorMsg.value = 'Chưa có câu hỏi hợp lệ để lưu.'; return }
  saving.value = true
  try {
    for (const q of filled) {
      if (q.imageFile) {
        const imgRaw  = await api.uploadQuestionImage(q.imageFile)
        const imgJson = (imgRaw?.data && typeof imgRaw.data === 'object') ? imgRaw.data : imgRaw
        if (imgJson?.status === 'success') q.image = imgJson.url
        q.imageFile = null
      }
      await api.createBankQuestion({
        content:  q.content,
        class_id: q.class_id ? Number(q.class_id) : undefined,
        level_id: q.level_id ? Number(q.level_id) : undefined,
        image:    q.image ?? null,
        answers:  q.answers.map((a, ai) => ({
          content: a.content, is_correct: a.is_correct, order_index: ai + 1,
        })),
      })
    }
    // Giữ lại các câu chưa điền
    questions.value = questions.value.filter(q => !filled.includes(q))
    if (questions.value.length === 0) questions.value.push(makeQuestion())
    errorMsg.value = ''
  } catch (e) {
    errorMsg.value = e.message || 'Lưu thất bại.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

      <!-- Header -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center z-10">
        <div></div>
        <h2 class="text-2xl font-bold text-indigo-600">Tạo câu hỏi</h2>
        <button @click="$emit('close')" class="text-2xl text-gray-500 hover:text-gray-800 leading-none">×</button>
      </div>

      <div class="p-8 space-y-8">

        <!-- ── TỪNG CÂU HỎI ── -->
        <div v-for="(q, qIdx) in questions" :key="q._id"
          class="border border-gray-200 rounded-lg p-6 bg-gray-50 relative">

          <!-- Nút xóa câu (chỉ hiện khi có > 1 câu) -->
          <button v-if="questions.length > 1" @click="removeQuestion(qIdx)"
            class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition text-lg leading-none">
            ×
          </button>

          <!-- Tiêu đề câu -->
          <p v-if="questions.length > 1" class="text-sm font-semibold text-indigo-600 mb-4">
            Câu {{ qIdx + 1 }}
          </p>

          <!-- Lớp + Mức độ -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Lớp</label>
              <select v-model="q.class_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn lớp --</option>
                <option v-for="c in CLASS_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Mức độ</label>
              <select v-model="q.level_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn mức độ --</option>
                <option v-for="lv in LEVEL_OPTIONS" :key="lv.value" :value="lv.value">{{ lv.label }}</option>
              </select>
            </div>
          </div>

          <!-- Nội dung câu hỏi -->
          <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
            <textarea v-model="q.content" rows="4" placeholder="Nhập câu hỏi..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
            <!-- Preview LaTeX -->
            <div v-if="q.content" class="mt-2 px-3 py-2 bg-white border border-indigo-100 rounded-lg text-gray-800 text-sm leading-relaxed">
              <span class="text-xs text-indigo-400 font-medium mr-1">Preview:</span>
              <MathText :text="q.content" />
            </div>
          </div>

          <!-- Upload ảnh -->
          <div class="mb-6">
            <div v-if="q.image" class="flex items-center gap-3 mb-2">
              <img :src="q.image" class="h-20 w-20 object-cover rounded border border-gray-300" />
              <button @click="removeImage(qIdx)" class="text-sm text-red-500 hover:text-red-700">Xóa ảnh</button>
            </div>
            <button @click="pickImage(qIdx)"
              class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              {{ q.image ? 'Đổi ảnh' : 'Tải ảnh' }}
            </button>
          </div>

          <!-- Câu trả lời -->
          <div class="border border-gray-200 rounded-lg p-5 bg-white">
            <label class="block text-gray-700 font-medium mb-4">Câu trả lời:</label>
            <div class="space-y-3">
              <div v-for="(ans, aIdx) in q.answers" :key="ans._id" class="flex items-center gap-3">
                <input type="radio" :name="`q_${q._id}`" :checked="ans.is_correct"
                  @change="setCorrect(qIdx, aIdx)"
                  class="w-5 h-5 cursor-pointer flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <input v-model="ans.content" type="text" placeholder="Câu trả lời..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
                  <div v-if="ans.content && ans.content.includes('\\(')"
                    class="mt-1 px-2 py-1 bg-gray-50 border border-indigo-100 rounded text-sm text-gray-700">
                    <MathText :text="ans.content" />
                  </div>
                </div>
                <button @click="removeAnswer(qIdx, aIdx)"
                  :disabled="q.answers.length <= 2"
                  class="text-red-500 hover:text-red-700 p-1 flex-shrink-0 disabled:opacity-30">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                  </svg>
                </button>
              </div>
            </div>
            <button @click="addAnswer(qIdx)"
              class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm">
              + Thêm câu trả lời
            </button>
          </div>
        </div>

        <!-- Error -->
        <p v-if="errorMsg" class="text-red-500 text-sm text-center font-medium">⚠️ {{ errorMsg }}</p>

        <hr />

        <!-- Tải file -->
        <div>
          <input ref="fileInputRef" type="file" accept=".docx,.xlsx" class="hidden" @change="onFileChange" />
          <button @click="triggerFileUpload" :disabled="uploadLoading"
            class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium disabled:opacity-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ uploadLoading ? 'Đang xử lý...' : 'Tải file' }}
          </button>
          <p v-if="uploadError" class="text-red-500 text-xs mt-1">{{ uploadError }}</p>
          <p class="text-xs text-gray-400 mt-1">
            Hỗ trợ .docx, .xlsx —
            <span class="text-indigo-500 cursor-pointer hover:underline"
              @click="showFormatGuide = !showFormatGuide">Xem định dạng mẫu</span>
          </p>
          <div v-if="showFormatGuide"
            class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 space-y-1">
            <p><strong>.docx:</strong> Câu 1: [nội dung] → A. ... B. ... C. ... D. ... → Đáp án: A</p>
            <p><strong>.xlsx:</strong> Cột A=Câu hỏi, B=Đáp án đúng (A/B/C/D), C→F=Nội dung đáp án. Hàng 1 là tiêu đề.</p>
          </div>
        </div>

        <!-- Footer buttons -->
        <div class="flex justify-end gap-4">
          <button @click="addQuestion"
            class="px-6 py-2 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition font-medium">
            Thêm câu hỏi
          </button>
          <button @click="saveAll" :disabled="saving"
            class="px-6 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition font-bold disabled:opacity-50">
            {{ saving ? 'Đang lưu...' : 'Lưu câu hỏi' }}
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