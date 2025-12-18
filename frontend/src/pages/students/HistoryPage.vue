<script setup>
import StudentHeader from '@/includes/StudentHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import HistoryList from '@/components/HistoryList.vue';
import ReviewExam from '@/components/ReviewExam.vue';

const examId = new URLSearchParams(window.location.search).get('id');
// Dữ liệu mẫu

</script>

<template>
    <StudentHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <HistoryList v-if="!examId" />
    <ReviewExam v-else />
    <AppFooter />
  </div>
</template>

<style scoped></style>
