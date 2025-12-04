<script setup>
import { ref } from 'vue';
import { toast } from 'vue3-toastify';

const fullname = ref('');
const username = ref('');
const password = ref('');
const confirmPassword = ref('');
const email = ref('');
const phone = ref('');
const role = ref('teacher');

const handleRegister = () => {
    if (!fullname.value || !username.value || !password.value || !confirmPassword.value || !email.value || !phone.value || !role.value) {
        toast.error('Vui lòng nhập đầy đủ thông tin!');
        return;
    }
    if (password.value.length < 6) {
        toast.error('Mật khẩu phải tối thiểu 6 ký tự!');
        return;
    }
    if (password.value !== confirmPassword.value) {
        toast.error('Mật khẩu xác nhận không khớp!');
        return;
    }
    // Giả lập đăng ký thành công
    toast.success('Đăng ký thành công!');
    setTimeout(() => {
        window.location.replace('/login');
    }, 1200);
};
</script>
<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center justify-center">
        <div class="bg-white/90 rounded-2xl shadow-xl p-8 w-full max-w-xl flex flex-col gap-6 animate-fade-in">
            <div class="flex flex-col items-center mb-2">
                <img src="/logo.svg" alt="Logo" class="h-12 w-12 mb-2" />
                <h2 class="text-2xl font-bold text-indigo-700">Đăng ký</h2>
                <p class="text-gray-500 text-sm">Tạo tài khoản mới để bắt đầu!</p>
            </div>
            <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleRegister">
                <div class="form-group relative col-span-1">
                    <input v-model="fullname" type="text" id="fullname" name="fullname" required placeholder="" class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="fullname" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">
                        Họ tên
                    </label>
                </div>
                
                <div class="form-group relative col-span-1">
                    <input v-model="username" type="text" id="username" name="username" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="username" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Tên đăng nhập</label>
                </div>
                <div class="form-group relative col-span-1">
                    <input v-model="password" type="password" id="password" name="password" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="password" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Mật khẩu</label>
                </div>
                <div class="form-group relative col-span-1">
                    <input v-model="confirmPassword" type="password" id="confirmPassword" name="confirmPassword" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="confirmPassword" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Xác nhận mật khẩu</label>
                </div>
                <div class="form-group relative col-span-1">
                    <input v-model="email" type="email" id="email" name="email" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="email" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Email</label>
                </div>
                <div class="form-group relative col-span-1">
                    <input v-model="phone" type="text" id="phone" name="phone" required placeholder=" " class="peer px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full" />
                    <label for="phone" class="absolute left-5 -top-3 px-1 bg-slate-50 rounded-lg text-gray-500 text-sm transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-focus:-top-3 peer-focus:text-sm peer-focus:bg-white peer-focus:rounded-lg peer-focus:px-1 peer-focus:text-indigo-700">Số điện thoại</label>
                </div>
                <div class="form-group col-span-1 md:col-span-2">
                    <select v-model="role" name="role" required class="px-5 py-3 rounded-lg border border-gray-300 focus:border-indigo-500 outline-none bg-slate-50 text-black w-full">
                        <option value="teacher" >Giáo viên</option>
                        <option value="student">Học sinh</option>
                    </select>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <button type="submit" class="w-full px-5 py-3 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition">Đăng ký</button>
                </div>
            </form>
            <div class="flex justify-center items-center text-sm text-gray-500 mt-2">
                <span></span>
                <a href="/login" class="hover:underline text-indigo-600">Đã có tài khoản? Đăng nhập</a>
            </div>
        </div>
    </div>
</template>
