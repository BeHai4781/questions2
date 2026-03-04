<script setup>
import { ref, watch } from 'vue'
import MathText from '@/components/MathText.vue'
import { useTeacherApi } from '@/composables/useTeacherApi.js'

const props = defineProps({
  question: { type: Object, required: true },
})
const emit = defineEmits(['close', 'updated'])

const api = useTeacherApi()

//  Constants 
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

//  Form state 
const saving    = ref(false)
const errorMsg  = ref('')
const saveOk    = ref(false)

// Load dữ liệu từ prop vào form
const form = ref(buildForm(props.question))

function buildForm(q) {
  return {
    class_id:  String(q.class_id  ?? ''),
    level_id:  String(q.level_id  ?? ''),
    content:   q.content  ?? '',
    image:     q.image    ?? null,
    imageFile: null,
    answers:   (q.answers ?? []).map(a => ({
      id:         a.id         ?? null,
      _id:        a.id         ?? (Date.now() + Math.random()),
      content:    a.content    ?? '',
      is_correct: a.is_correct ?? false,
    })),
  }
}

// Nếu prop thay đổi (parent truyền câu hỏi mới) → reset form
watch(() => props.question, (q) => {
  form.value = buildForm(q)
  errorMsg.value = ''
  saveOk.value   = false
}, { immediate: false })

// Answer helpers 
function setCorrect(aIdx) {
  form.value.answers.forEach((a, i) => { a.is_correct = i === aIdx })
}
function addAnswer() {
  form.value.answers.push({ id: null, _id: Date.now(), content: '', is_correct: false })
}
function removeAnswer(aIdx) {
  if (form.value.answers.length <= 2) return
  form.value.answers.splice(aIdx, 1)
}

// Image 
function pickImage() {
  const input = document.createElement('input')
  input.type = 'file'; input.accept = 'image/*'
  input.onchange = e => {
    const f = e.target.files[0]; if (!f) return
    form.value.image     = URL.createObjectURL(f)
    form.value.imageFile = f
  }
  input.click()
}
function removeImage() {
  form.value.image     = null
  form.value.imageFile = null
}

//Validate 
function validate() {
  if (!form.value.class_id)                        return 'Vui lòng chọn lớp.'
  if (!form.value.level_id)                        return 'Vui lòng chọn mức độ.'
  if (!form.value.content.trim())                  return 'Chưa nhập nội dung câu hỏi.'
  if (!form.value.answers.some(a => a.is_correct)) return 'Chưa chọn đáp án đúng.'
  if (form.value.answers.some(a => !a.content.trim())) return 'Còn đáp án bị trống.'
  return null
}

// Save
async function save() {
  errorMsg.value = ''
  const err = validate()
  if (err) { errorMsg.value = err; return }

  saving.value = true
  try {
    // Upload ảnh mới nếu có
    let imageUrl = form.value.image
    if (form.value.imageFile) {
      const r = await api.uploadQuestionImage(form.value.imageFile)
      const j = (r?.data && typeof r.data === 'object') ? r.data : r
      if (j?.url) imageUrl = j.url
      form.value.imageFile = null
    }

    const payload = {
      class_id: Number(form.value.class_id),
      level_id: Number(form.value.level_id),
      content:  form.value.content.trim(),
      image:    imageUrl ?? null,
      answers:  form.value.answers.map((a, i) => ({
        id:          a.id ?? null,
        content:     a.content,
        is_correct:  a.is_correct,
        order_index: i + 1,
      })),
    }

    const res    = await api.updateBankQuestion(props.question.id, payload)
    const updated = res?.data?.question ?? res?.data ?? payload

    saveOk.value = true
    setTimeout(() => saveOk.value = false, 2500)

    emit('updated', updated)
  } catch { errorMsg.value = 'Lưu thất bại, vui lòng thử lại.' }
  finally  { saving.value = false }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

      <!-- Header -->
      <div class="sticky top-0 bg-white border-b-2 border-indigo-600 px-8 py-6 flex justify-between items-center z-10">
        <div></div>
        <h2 class="text-2xl font-bold text-indigo-600">Chỉnh sửa câu hỏi</h2>
        <button @click="$emit('close')" class="text-2xl text-gray-500 hover:text-gray-800 leading-none">×</button>
      </div>

      <div class="p-8 space-y-8">

        <!-- Form câu hỏi -->
        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">

          <!-- Lớp + Mức độ -->
          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-gray-700 font-medium mb-2">Lớp</label>
              <select v-model="form.class_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn lớp --</option>
                <option v-for="c in CLASS_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-medium mb-2">Mức độ</label>
              <select v-model="form.level_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 bg-white">
                <option value="">-- Chọn mức độ --</option>
                <option v-for="lv in LEVEL_OPTIONS" :key="lv.value" :value="lv.value">{{ lv.label }}</option>
              </select>
            </div>
          </div>

          <!-- Nội dung câu hỏi -->
          <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Nhập nội dung câu hỏi:</label>
            <textarea v-model="form.content" rows="4" placeholder="Nhập câu hỏi..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 resize-none" />
            <!-- Preview LaTeX -->
            <div v-if="form.content"
              class="mt-2 px-3 py-2 bg-white border border-indigo-100 rounded-lg text-gray-800 text-sm leading-relaxed">
              <span class="text-xs text-indigo-400 font-medium mr-1">Preview:</span>
              <MathText :text="form.content" />
            </div>
          </div>

          <!-- Upload ảnh -->
          <div class="mb-6">
            <div v-if="form.image" class="flex items-center gap-3 mb-2">
              <img :src="form.image" class="h-20 w-20 object-cover rounded border border-gray-300" />
              <button @click="removeImage" class="text-sm text-red-500 hover:text-red-700">Xóa ảnh</button>
            </div>
            <button @click="pickImage"
              class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              {{ form.image ? 'Đổi ảnh' : 'Tải ảnh' }}
            </button>
          </div>

          <!-- Câu trả lời -->
          <div class="border border-gray-200 rounded-lg p-5 bg-white">
            <label class="block text-gray-700 font-medium mb-4">Câu trả lời:</label>
            <div class="space-y-3">
              <div v-for="(ans, aIdx) in form.answers" :key="ans._id" class="flex items-center gap-3">
                <input type="radio" name="question_answers"
                  :checked="ans.is_correct"
                  @change="setCorrect(aIdx)"
                  class="w-5 h-5 cursor-pointer flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <input v-model="ans.content" type="text" placeholder="Câu trả lời..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
                  <div v-if="ans.content && ans.content.includes('\\(')"
                    class="mt-1 px-2 py-1 bg-gray-50 border border-indigo-100 rounded text-sm text-gray-700">
                    <MathText :text="ans.content" />
                  </div>
                </div>
                <button @click="removeAnswer(aIdx)"
                  :disabled="form.answers.length <= 2"
                  class="text-red-500 hover:text-red-700 p-1 flex-shrink-0 disabled:opacity-30">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                  </svg>
                </button>
              </div>
            </div>
            <button @click="addAnswer"
              class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium text-sm">
              + Thêm câu trả lời
            </button>
          </div>
        </div>

        <!-- Feedback -->
        <p v-if="errorMsg" class="text-red-500 text-sm text-center font-medium">{{ errorMsg }}</p>
        <p v-if="saveOk"   class="text-green-600 text-sm text-center font-medium">Đã lưu thành công!</p>

        <!-- Footer buttons -->
        <div class="flex justify-end gap-4">
          <button @click="$emit('close')"
            class="px-6 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition font-medium">
            Hủy
          </button>
          <button @click="save" :disabled="saving"
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