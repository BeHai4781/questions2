<script setup>
import AppFooter from '@/includes/AppFooter.vue'
import TeacherHeader from '@/includes/TeacherHeader.vue'
import ExamDetail from '@/components/ExamDetail.vue'

const examId = new URLSearchParams(window.location.search).get('id');
</script>

<template>
  <TeacherHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <ExamDetail v-if="!examId" :exam-id="examId" />
    
  </div>
  <AppFooter />
</template>

