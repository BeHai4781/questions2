<script setup>
// Dữ liệu mẫu
const exam = {
  title: 'Đề Toán HK1',
  duration: 45,
  time: 40,
  total_questions: 3,
  result: 2,
  submitted_at: '2025-12-01 09:30',
}
const questions = [
  {
    id: 1,
    question: '2 + 2 = ?',
    answer_a: '3',
    answer_b: '4',
    answer_c: '5',
    answer_d: '6',
    correct_answer: 1, // B
  },
  {
    id: 2,
    question: '5 x 3 = ?',
    answer_a: '15',
    answer_b: '10',
    answer_c: '8',
    answer_d: '20',
    correct_answer: 0, // A
  },
  {
    id: 3,
    question: 'Căn bậc hai của 9?',
    answer_a: '2',
    answer_b: '3',
    answer_c: '4',
    answer_d: '5',
    correct_answer: 1, // B
  },
]
const user_answers = {
  1: 1, // đúng
  2: 2, // sai
  3: null, // chưa trả lời
}
const answer_map = ['A', 'B', 'C', 'D']
</script>

<template>
  <div
    class="w-full max-w-6xl bg-white/90 rounded-2xl shadow-xl p-8 mt-20 animate-fade-in text-black my-10"
  >
    <div class="flex flex-col md:flex-row gap-8">
      <!-- Thông tin đề thi bên trái -->
      <div class="md:w-1/3 w-full flex-shrink-0 mb-8 md:mb-0">
        <h2 class="text-2xl font-bold text-indigo-700 mb-2">{{ exam.title }}</h2>
        <div class="mb-4 text-gray-700">
          <b>Thời gian:</b> {{ exam.duration }} phút<br />
          <b>Thời gian làm bài:</b> {{ exam.time }} phút<br />
          <b>Số câu hỏi:</b> {{ exam.total_questions }}<br />
          <b>Số câu đúng:</b> {{ exam.result }}<br />
          <b>Ngày nộp:</b> {{ exam.submitted_at }}
        </div>
      </div>
      <!-- Các câu hỏi bên phải -->
      <div class="md:w-2/3 w-full">
        <div class="question-list flex flex-col gap-6">
          <div
            v-for="(question, i) in questions"
            :key="question.id"
            class="question-block border-b border-gray-200 pb-4"
          >
            <div class="mb-2">
              <b>Câu {{ i + 1 }}:</b> {{ question.question }}
            </div>
            <ul class="answers mb-2">
              <li
                v-for="(label, idx) in answer_map"
                :key="label"
                class="flex items-center gap-2 mb-1"
              >
                <span>{{ label }}. {{ question['answer_' + label.toLowerCase()] }}</span>
                <span v-if="question.correct_answer === idx" class="text-green-600"
                  ><i class="fa fa-check-circle"></i
                ></span>
                <span
                  v-if="user_answers[question.id] !== null && user_answers[question.id] === idx"
                >
                  <span
                    v-if="user_answers[question.id] === question.correct_answer"
                    class="text-green-700 font-bold"
                    >(Bạn chọn)</span
                  >
                  <span v-else class="text-red-600 font-bold">(Bạn chọn)</span>
                </span>
              </li>
            </ul>
            <div class="mt-1">
              <b>Đáp án đúng:</b> {{ answer_map[question.correct_answer] }}
              <span v-if="user_answers[question.id] !== null">
                | <b>Đáp án của bạn:</b> {{ answer_map[user_answers[question.id]] }}
                <span
                  v-if="user_answers[question.id] === question.correct_answer"
                  class="text-green-600 ml-2"
                  ><i class="fa fa-check-circle"></i> Đúng</span
                >
                <span v-else class="text-red-600 ml-2"><i class="fa fa-times-circle"></i> Sai</span>
              </span>
              <span v-else class="text-red-600 ml-2">Bạn chưa trả lời câu này.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
