<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppFooter from '@/includes/AppFooter.vue'
import StudentHeader from '@/includes/StudentHeader.vue'
import ExamList from '@/components/ExamList.vue'
import TakeExam from '@/components/TakeExam.vue'

const route = useRoute()
const examId = computed(() => route.query.id || null)
</script>

<template>
  <StudentHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <ExamList v-if="!examId" />
    <TakeExam v-else :exam-id="examId" />
    <AppFooter />
  </div>
</template>

