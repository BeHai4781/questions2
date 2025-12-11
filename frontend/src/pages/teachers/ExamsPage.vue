<script setup>
import AppFooter from '@/includes/AppFooter.vue'
import TeacherHeader from '@/includes/TeacherHeader.vue'
import ListExamTeacher from '@/components/ListExamTeacher.vue'


const examId = new URLSearchParams(window.location.search).get('id');
</script>

<template>
  <TeacherHeader />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center"
  >
    <ListExamTeacher v-if="!examId"/>
    
  </div>
  <AppFooter />
</template>

