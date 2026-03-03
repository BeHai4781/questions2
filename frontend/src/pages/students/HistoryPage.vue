<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import StudentHeader from '@/includes/StudentHeader.vue'
import AppFooter from '@/includes/AppFooter.vue'
import HistoryList from '@/components/HistoryList.vue'
import ReviewExam from '@/components/ReviewExam.vue'

const route = useRoute()
const attemptId = computed(() => route.query.id || null)
</script>

<template>
  <StudentHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <HistoryList v-if="!attemptId" />
    <ReviewExam v-else :attempt-id="attemptId" />
    <AppFooter />
  </div>
</template>

<style scoped></style>
