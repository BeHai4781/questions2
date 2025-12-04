<script setup>
import AppFooter from '@/includes/AppFooter.vue'
import StudentHeader from '@/includes/StudentHeader.vue'
import ExamList from '@/components/ExamList.vue'
import TakeExam from '@/components/TakeExam.vue'

const examId = new URLSearchParams(window.location.search).get('id');
</script>

<template>
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <StudentHeader />
    <ExamList v-if="!examId"/>
    <TakeExam v-else />
    <AppFooter />
  </div>
</template>

