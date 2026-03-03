<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const username = ref('');
const password = ref('');
const loading = ref(false);

const handleLogin = async () => {
    if (loading.value) return;

    loading.value = true;
    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username.value,
                password: password.value,
            }),
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.success !== true) {
            const message =
                data?.error?.message ||
                data?.message ||
                'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin!';
            toast.error(message);
            return;
        }

        const { token, refreshToken, user } = data.data || {};

        if (!token || !user) {
            toast.error('Phản hồi từ máy chủ không hợp lệ.');
            return;
        }

        authStore.setAuth({ token, refreshToken, user });
        toast.success('Đăng nhập thành công!');

        setTimeout(() => {
            router.replace(authStore.getDashboardRoute());
        }, 800);
    } catch (err) {
        console.error(err);
        toast.error('Có lỗi xảy ra khi kết nối tới máy chủ.');
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center">
        <div class="bg-white/90 rounded-2xl shadow-xl p-8 w-full max-w-md flex flex-col gap-6 animate-fade-in">
            <div class="flex flex-col items-center mb-2">
                <img src="/logo.svg" alt="Logo" class="h-12 w-12 mb-2" />
                <h2 class="text-2xl font-bold text-indigo-700">Đăng nhập</h2>
                <p class="text-gray-500 text-sm">Chào mừng bạn quay lại!</p>
            </div>
            <form class="flex flex-col gap-4" @submit.prevent="handleLogin">
                <div class="form-group relative col-span-1">
                    <input v-model="username" type="text" id="username" name="username" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" autocomplete="username" />
                    <label for="username" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Tên đăng nhập</label>
                </div>
                <div class="form-group relative col-span-1">
                    <input v-model="password" type="password" id="password" name="password" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" autocomplete="current-password" />
                    <label for="password" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Mật khẩu</label>
                </div>
                <button
                    type="submit"
                    class="px-5 py-3 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="loading"
                >
                    <span v-if="!loading">Đăng nhập</span>
                    <span v-else>Đang xử lý...</span>
                </button>
            </form>
            <div class="flex justify-between items-center text-sm text-gray-500 mt-2">
                <a href="#" class="hover:underline">Quên mật khẩu?</a>
                <a href="/register" class="hover:underline text-indigo-600">Đăng ký tài khoản</a>
            </div>
        </div>
    </div>
</template>
