<script setup>
import AppFooter from '@/includes/AppFooter.vue'
import TeacherHeader from '@/includes/TeacherHeader.vue'
import QuestionsList from '@/components/QuestionsList.vue'


const questionId = new URLSearchParams(window.location.search).get('id');
</script>

<template>
  <TeacherHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <QuestionsList v-if="!questionId" :questionId="questionId"/>
    
  </div>
  <AppFooter />
</template>

