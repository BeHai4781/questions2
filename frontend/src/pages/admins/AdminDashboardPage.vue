<script setup>
import { ref, onMounted } from 'vue'
import AdminHeader from '@/includes/AdminHeader.vue'
import { Chart, registerables } from 'chart.js'
import AppFooter from '@/includes/AppFooter.vue'

Chart.register(...registerables)

// Dữ liệu mẫu
const totalUsers = ref(1234)
const totalExams = ref(567)
const newUsersWeek = ref(12)
const newPostsToday = ref(3)

const hotTopic = ref({ name: 'Toán 12', total_views: 234 })

const recentPosts = ref([
  {
    slug: 'bai-viet-1',
    title: 'Bài viết 1',
    author: 'Nguyễn Văn A',
    created_at: '2025-12-18T08:30:00',
    views: 10,
  },
  {
    slug: 'bai-viet-2',
    title: 'Bài viết 2',
    author: 'Trần Thị B',
    created_at: '2025-12-18T09:00:00',
    views: 7,
  },
])
const popularPosts = ref([
  {
    slug: 'bai-viet-3',
    title: 'Bài viết 3',
    author: 'Lê Văn C',
    created_at: '2025-12-18T07:45:00',
    views: 25,
  },
  {
    slug: 'bai-viet-1',
    title: 'Bài viết 1',
    author: 'Nguyễn Văn A',
    created_at: '2025-12-18T08:30:00',
    views: 10,
  },
])

// Dữ liệu biểu đồ mẫu
const chartLabels = ref([
  '19/11',
  '20/11',
  '21/11',
  '22/11',
  '23/11',
  '24/11',
  '25/11',
  '26/11',
  '27/11',
  '28/11',
  '29/11',
  '30/11',
  '01/12',
  '02/12',
  '03/12',
  '04/12',
  '05/12',
  '06/12',
  '07/12',
  '08/12',
  '09/12',
  '10/12',
  '11/12',
  '12/12',
  '13/12',
  '14/12',
  '15/12',
  '16/12',
  '17/12',
  '18/12',
])
const chartData = ref([
  10, 12, 8, 15, 20, 18, 22, 25, 30, 28, 35, 40, 38, 36, 32, 30, 28, 27, 25, 24, 22, 20, 18, 16, 15,
  14, 13, 12, 11, 10,
])

onMounted(() => {
  const ctx = document.getElementById('lineChart')
  if (!(ctx instanceof HTMLCanvasElement)) return
  const context = ctx.getContext('2d')
  if (!context) return
  const gradient = context.createLinearGradient(0, 0, 0, 400)
  gradient.addColorStop(0, 'rgba(0,145,174,0.8)')
  gradient.addColorStop(1, 'rgba(0,145,174,0.08)')
  new Chart(context, {
    type: 'line',
    data: {
      labels: chartLabels.value,
      datasets: [
        {
          label: 'Lượt xem',
          data: chartData.value,
          backgroundColor: gradient,
          borderColor: 'rgba(0,145,174,1)',
          pointRadius: 3,
          tension: 0.25,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false },
      },
      scales: {
        x: { display: true, title: { display: false } },
        y: { display: true, beginAtZero: true },
      },
    },
  })
})
</script>

<template>
  <AdminHeader />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center">
    <div class="max-w-7xl mx-auto px-6 py-8 w-full">
      <!-- Thẻ thống kê -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-5 flex flex-col items-center">
          <h6 class="text-gray-500 text-sm">Tổng người dùng</h6>
          <h3 class="text-2xl font-bold text-indigo-700 mt-1">
            <a href="/admin/users">{{ totalUsers.toLocaleString() }}</a>
          </h3>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex flex-col items-center">
          <h6 class="text-gray-500 text-sm">Tổng đề thi</h6>
          <h3 class="text-2xl font-bold text-indigo-700 mt-1">
            <a href="/admin/exams">{{ totalExams.toLocaleString() }}</a>
          </h3>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex flex-col items-center">
          <h6 class="text-gray-500 text-sm">Người dùng mới (7 ngày)</h6>
          <h3 class="text-2xl font-bold text-indigo-700 mt-1">{{ newUsersWeek }}</h3>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex flex-col items-center">
          <h6 class="text-gray-500 text-sm">Bài viết mới hôm nay</h6>
          <h3 class="text-2xl font-bold text-indigo-700 mt-1">{{ newPostsToday }}</h3>
        </div>
      </div>
      <!-- Biểu đồ -->
      <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h5 class="font-semibold mb-4">📈 Thống kê lượt xem bài viết 30 ngày gần nhất</h5>
        <div style="position: relative; height: 400px">
          <canvas id="lineChart"></canvas>
        </div>
      </div>
      <!-- Chủ đề hot -->
      <div class="mb-6">
        <div class="bg-yellow-100 border-l-4 border-yellow-400 text-yellow-800 p-4 rounded">
          <strong>Chủ đề hot trong tuần:</strong>
          {{ hotTopic.name }} ({{ hotTopic.total_views }} lượt xem)
        </div>
      </div>
      <!-- Danh sách bài viết -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h5 class="font-semibold mb-2">Bài viết mới nhất hôm nay</h5>
          <div v-if="recentPosts.length === 0" class="text-gray-400">
            Không có bài viết hôm nay.
          </div>
          <ul v-else class="divide-y divide-gray-100 bg-white rounded-xl shadow">
            <li
              v-for="p in recentPosts"
              :key="p.slug"
              class="flex justify-between items-start px-4 py-3"
            >
              <div>
                <a :href="`/post/${p.slug}`" class="font-bold text-indigo-700 hover:underline">{{
                  p.title
                }}</a>
                <div class="text-xs text-gray-500">
                  {{ p.author }} •
                  {{
                    new Date(p.created_at).toLocaleString('vi-VN', {
                      hour: '2-digit',
                      minute: '2-digit',
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                    })
                  }}
                </div>
              </div>
              <span
                class="bg-indigo-500 text-white rounded-full px-3 py-1 text-xs font-bold self-center"
                >{{ p.views }}</span
              >
            </li>
          </ul>
        </div>
        <div>
          <h5 class="font-semibold mb-2">Bài viết được quan tâm nhiều nhất hôm nay</h5>
          <div v-if="popularPosts.length === 0" class="text-gray-400">
            Không có bài viết hôm nay.
          </div>
          <ul v-else class="divide-y divide-gray-100 bg-white rounded-xl shadow">
            <li
              v-for="p in popularPosts"
              :key="p.slug"
              class="flex justify-between items-start px-4 py-3"
            >
              <div>
                <a :href="`/post/${p.slug}`" class="font-bold text-indigo-700 hover:underline">{{
                  p.title
                }}</a>
                <div class="text-xs text-gray-500">
                  {{ p.author }} •
                  {{
                    new Date(p.created_at).toLocaleString('vi-VN', {
                      hour: '2-digit',
                      minute: '2-digit',
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                    })
                  }}
                </div>
              </div>
              <span
                class="bg-green-500 text-white rounded-full px-3 py-1 text-xs font-bold self-center"
                >{{ p.views }}</span
              >
            </li>
          </ul>
        </div>
      </div>
    </div>
    <AppFooter />
  </div>
</template>
