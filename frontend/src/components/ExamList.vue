<script setup>
import { ref, computed } from 'vue'

const subjects = [
  { id: 1, name: 'Toán học' },
  { id: 2, name: 'Vật lý' },
  { id: 3, name: 'Hóa học' },
]
const classes = [
  { id: 1, name: '12A1' },
  { id: 2, name: '12A2' },
  { id: 3, name: '11B1' },
]
const exams = [
  { id: 1, title: 'Đề Toán HK1', subject: 1, class: 1, total_questions: 20, duration: 45 },
  { id: 2, title: 'Đề Lý HK1', subject: 2, class: 2, total_questions: 15, duration: 30 },
  { id: 3, title: 'Đề Hóa HK2', subject: 3, class: 3, total_questions: 25, duration: 50 },
  { id: 4, title: 'Đề Toán nâng cao', subject: 1, class: 2, total_questions: 30, duration: 60 },
]

const selectedSubject = ref('')
const selectedClass = ref('')
const searchText = ref('')

const filteredExams = computed(() => {
  return exams.filter((exam) => {
    const matchSubject = selectedSubject.value ? exam.subject == selectedSubject.value : true
    const matchClass = selectedClass.value ? exam.class == selectedClass.value : true
    const matchText = searchText.value
      ? exam.title.toLowerCase().includes(searchText.value.toLowerCase())
      : true
    return matchSubject && matchClass && matchText
  })
})
</script>

<template>
  <div
    class="w-full max-w-6xl text-black bg-white/90 rounded-2xl shadow-xl p-8 flex flex-col gap-6 animate-fade-in mt-10 p-12"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Danh sách đề thi</h2>
    <div class="flex flex-col md:flex-row gap-4 mb-6">
      <select
        v-model="selectedSubject"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
      >
        <option value="">-- Môn học --</option>
        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
          {{ subject.name }}
        </option>
      </select>
      <select
        v-model="selectedClass"
        class="select select-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg"
      >
        <option value="">-- Lớp học --</option>
        <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">
          {{ classItem.name }}
        </option>
      </select>
      <input
        v-model="searchText"
        type="text"
        placeholder="Tìm theo tên đề thi..."
        class="input input-info px-4 py-2 bg-slate-50 text-black border-gray-300 border rounded-lg flex-1"
      />
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-separate border-spacing-y-2">
        <thead>
          <tr class="text-indigo-700 text-base text-center">
            <th class="py-2">Tên đề thi</th>
            <th class="py-2">Môn học</th>
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
            <td class="py-3 px-4 font-semibold">{{ exam.title }}</td>
            <td class="py-3 px-4 text-center">
              {{ subjects.find((s) => s.id === exam.subject)?.name }}
            </td>
            <td class="py-3 px-4 text-center">
              {{ classes.find((c) => c.id === exam.class)?.name }}
            </td>
            <td class="py-3 px-4 text-center">{{ exam.total_questions }}</td>
            <td class="py-3 px-4 text-center">{{ exam.duration }} phút</td>
            <td class="py-3 px-4 text-center">
              <a
                :href="`/student/exam?id=${exam.id}`"
                class="text-white bg-indigo-600 px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition font-medium"
                >Làm bài</a
              >
            </td>
          </tr>
          <tr v-if="filteredExams.length === 0">
            <td colspan="6" class="text-center py-6 text-gray-500">
              Không tìm thấy đề thi phù hợp.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
