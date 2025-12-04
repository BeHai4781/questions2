<script setup>
const historyList = [
  {
    id: 1,
    title: 'Toán học cơ bản',
    date: '2025-12-01',
    score: 8.5,
    duration: '40 phút',
  },
  {
    id: 2,
    title: 'Vật lý đại cương',
    date: '2025-11-28',
    score: 7.0,
    duration: '35 phút',
  },
  {
    id: 3,
    title: 'Hóa học nâng cao',
    date: '2025-11-20',
    score: 9.0,
    duration: '50 phút',
  },
]
</script>
<template>
  <div
    class="w-full max-w-6xl bg-white/90 rounded-2xl shadow-xl p-8 mt-10 flex flex-col gap-6 animate-fade-in"
  >
    <h2 class="text-2xl font-bold text-indigo-700 mb-4">Lịch sử làm bài</h2>
    <table class="w-full border-separate border-spacing-y-2">
      <thead>
        <tr class="text-indigo-700 text-base text-center">
          <th class="py-2">Tên đề thi</th>
          <th class="py-2">Ngày làm</th>
          <th class="py-2">Điểm</th>
          <th class="py-2">Thời gian</th>
          <th class="py-2">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!historyList.length">
          <td colspan="5" class="text-center text-gray-500 py-4">Chưa có lịch sử làm bài.</td>
        </tr>
        <tr
          v-for="item in historyList"
          :key="item.id"
          class="bg-indigo-50 hover:bg-indigo-100 transition rounded-lg text-black"
        >
          <td class="py-3 px-4 font-semibold">{{ item.title }}</td>
          <td class="py-3 px-4 text-center">{{ item.date }}</td>
          <td class="py-3 px-4 text-indigo-700 font-bold text-center">{{ item.score }}</td>
          <td class="py-3 px-4 text-center">{{ item.duration }}</td>
          <td class="py-3 px-4 text-center">
            <a
              :href="`/student/history?id=${item.id}`"
              class="text-indigo-600 hover:underline font-medium"
              >Chi tiết</a
            >
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
