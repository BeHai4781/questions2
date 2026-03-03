<script setup lang="ts">
import AppFooter from '@/includes/AppFooter.vue';
import StudentHeader from '@/includes/StudentHeader.vue';
import TeacherHeader from '@/includes/TeacherHeader.vue';
import AdminHeader from '@/includes/AdminHeader.vue';
const role = localStorage.getItem('role') || 'student';
</script>

<template>
    <StudentHeader v-if="role === 'student'" />
    <AdminHeader v-else-if="role === 'admin'" />
    <TeacherHeader v-else-if="role === 'teacher'" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center py-10">
        <div class="w-full max-w-4xl px-4">
            <div class="box bg-white rounded-xl shadow p-6 mb-8 align-center justify-center animate-fade-in">
                <h2 class="text-2xl font-bold text-indigo-700 mb-4 text-center">Thông tin cá nhân</h2>
                <form class="flex flex-col gap-4 max-w-md mx-auto" @submit.prevent="handleEdit">
                    <div>
                        <label class="block text-gray-700 mb-1">Tên đăng nhập</label>
                        <input type="text" v-model="username" disabled
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Họ và tên</label>
                        <input type="text" v-model="fullName"
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-white-100" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Email</label>
                        <input type="email" v-model="email" disabled
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Vai trò</label>
                        <input type="text" v-model="role" disabled
                            class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100" />
                    </div>
                    <div class="text-center text-xl pt-2 text-indigo-600">Đổi mật khẩu (Để trống nếu không muốn đổi)
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-gray-700 mb-1">Nhập mật khẩu</label>
                            <input type="password" v-model="password"
                                class="w-full border border-gray-300 rounded px-3 py-2 bg-white-100" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-gray-700 mb-1">Xác nhận mật khẩu</label>
                            <input type="password" v-model="confirmPassword"
                                class="w-full border border-gray-300 rounded px-3 py-2 bg-white-100" />
                        </div>
                    </div>
                    <div class="flex gap-4 justify-center">
                        <button type="submit"
                            class="mt-4 px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition">
                            Chỉnh sửa thông tin
                        </button>
                        <button type="button"
                            class="mt-4 px-4 py-2 rounded-xl bg-red-600 text-white font-bold shadow hover:bg-red-700 transition"
                            @click="handleLogout">Đăng xuất
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <AppFooter />
    </div>
</template>