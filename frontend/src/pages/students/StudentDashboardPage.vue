
<script setup>
import AppFooter from '@/includes/AppFooter.vue';
import StudentHeader from '@/includes/StudentHeader.vue';
//import { ref } from 'vue';

// Dữ liệu mẫu
const latestExams = [
    { id: 1, title: 'Toán học cơ bản' },
    { id: 2, title: 'Vật lý đại cương' },
];
const examInfo = {
    1: { total_questions: 20, duration: 45 },
    2: { total_questions: 15, duration: 30 },
};
const recentExams = [
    { id: 1, title: 'Toán học cơ bản', result: 8 },
    { id: 2, title: 'Vật lý đại cương', result: 7 },
];
const stats = {
    total_exams: 12,
    avg_score: 7.5,
    avg_time: '00:35:00',
    total_time: '07:00:00',
};
</script>

<template>
        <StudentHeader />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center">
        <div class="container_i flex gap-6 w-full max-w-5xl py-8">
            <!-- Left -->
            <div class="left flex-[3] flex flex-col gap-6 animate-fade-in">
                <div class="box bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold mb-4 text-indigo-700">Đề thi mới nhất</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template v-for="exam in latestExams" :key="exam.id">
                            <div class="md:col-span-1 flex flex-col gap-2">
                                <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50">
                                    <span class="font-semibold text-indigo-700">{{ exam.title }}</span><br>
                                    <small class="text-gray-500">
                                        Số câu: {{ examInfo[exam.id]?.total_questions ?? 0 }} |
                                        Thời gian: {{ examInfo[exam.id]?.duration ?? '' }} phút
                                    </small>
                                </div>
                                <div>
                                    <button class="btn btn-primary bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition">Làm bài</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="box bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold mb-4 text-indigo-700">Bài thi đã làm gần đây</h3>
                    <div class="flex flex-col gap-2 mb-2">
                        <template v-for="exam in recentExams" :key="exam.id">
                            <div class="item border border-gray-200 rounded-lg p-3 bg-slate-50 text-indigo-700">
                                {{ exam.title }} - Kết quả: {{ exam.result }} điểm
                            </div>
                        </template>
                    </div>
                    <a href="/student/history" class="text-indigo-600 hover:underline text-sm">Lịch sử bài làm</a>
                </div>
            </div>
            <!-- Right -->
            <div class="right flex-1 border border-gray-200 rounded-xl bg-white shadow p-6 flex flex-col gap-4 h-fit text-indigo-700 animate-fade-in">
                <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Số lượng bài thi đã làm: {{ stats.total_exams }}</div>
                <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Điểm trung bình: {{ stats.avg_score }}</div>
                <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Thời gian trung bình: {{ stats.avg_time }}</div>
                <div class="item border border-gray-100 rounded-lg p-3 bg-slate-50">Tổng thời gian: {{ stats.total_time }}</div>
            </div>
        </div>
        <AppFooter />
    </div>
</template>