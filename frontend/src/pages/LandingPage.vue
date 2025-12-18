<script setup>
import AppFooter from '@/includes/AppFooter.vue';


</script>

<template>
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col items-center">
	<!-- Header -->
	<header class="sticky top-0 z-50 w-full flex items-center justify-between px-8 py-6 bg-white/80 shadow-md backdrop-blur-md">
		<div class="flex items-center gap-2">
			<img src="/logo.svg" alt="Logo" class="h-10 w-10" />
			<span class="text-2xl font-bold text-indigo-700 mr-20">EMS</span>
		</div>
		<nav class="hidden md:flex gap-14 text-gray-700 font-medium text-lg">
			<a href="#features" class="hover:text-indigo-600 transition">Tính năng</a>
			<a href="#about" class="hover:text-indigo-600 transition">Giới thiệu</a>
			<a href="#contact" class="hover:text-indigo-600 transition">Liên hệ</a>
		</nav>
        <div class="flex gap-4 text-white">
            <a href="/login" class="btn btn-accent rounded-lg">Đăng nhập</a>
            <a href="/register" class="btn btn-primary rounded-lg">Đăng ký</a>
        </div>
    </header>

	<!-- Hero Section -->
	<section class="flex flex-col md:flex-row items-center justify-between px-8 py-20 md:py-32 gap-12">
		<div class="flex-1">
			<h1 class="text-5xl md:text-6xl font-extrabold text-indigo-800 mb-6 leading-tight">
				Nền tảng quản lý đề thi và thi cử <span class="text-blue-500">hiện đại</span>
			</h1>
			<p class="text-lg md:text-xl text-gray-700 mb-8">
				Tối ưu hóa quy trình, tăng hiệu suất, kết nối mọi người dễ dàng hơn với EMS.
			</p>
			<a href="/register" class="inline-block px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500 text-white font-bold text-lg shadow-lg hover:scale-105 transition">Khám phá ngay</a>
		</div>
	</section>

	<!-- Features Section -->
	<section id="features" class="px-8 py-16 bg-white/70 rounded-3xl mx-4 md:mx-24 shadow-lg">
		<h2 class="text-3xl font-bold text-indigo-700 mb-10 text-center">Tính năng nổi bật</h2>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-10">
			<div class="bg-gradient-to-br from-indigo-100 to-blue-100 p-8 rounded-xl shadow hover:scale-105 transition">
				<!-- <img src="/public/feature1.svg" alt="Feature 1" class="h-16 mb-4 mx-auto" /> -->
				<h3 class="text-xl font-semibold text-indigo-700 mb-2">Quản lý thông minh</h3>
				<p class="text-gray-600">Tự động hóa các tác vụ, giảm thiểu sai sót, tiết kiệm thời gian.</p>
			</div>
			<div class="bg-gradient-to-br from-indigo-100 to-blue-100 p-8 rounded-xl shadow hover:scale-105 transition">
				<!-- <img src="/public/feature2.svg" alt="Feature 2" class="h-16 mb-4 mx-auto" /> -->
				<h3 class="text-xl font-semibold text-indigo-700 mb-2">Kết nối dễ dàng</h3>
				<p class="text-gray-600">Giao diện thân thiện, hỗ trợ đa nền tảng, kết nối mọi người nhanh chóng.</p>
			</div>
			<div class="bg-gradient-to-br from-indigo-100 to-blue-100 p-8 rounded-xl shadow hover:scale-105 transition">
				<!-- <img src="/public/feature3.svg" alt="Feature 3" class="h-16 mb-4 mx-auto" /> -->
				<h3 class="text-xl font-semibold text-indigo-700 mb-2">Bảo mật tối ưu</h3>
				<p class="text-gray-600">Dữ liệu được bảo vệ an toàn với công nghệ mã hóa hiện đại.</p>
			</div>
		</div>
	</section>

	<!-- About Section -->
	<section id="about" class="px-8 py-16 flex flex-col md:flex-row items-center gap-12">
		<div class="flex-1">
			<h2 class="text-3xl font-bold text-indigo-700 mb-6">Về EMS</h2>
			<p class="text-gray-700 text-lg mb-4">EMS là giải pháp quản lý toàn diện, giúp phát triển bền vững trong thời đại số.</p>
			<ul class="list-disc pl-6 text-gray-600">
				<li>Đội ngũ chuyên gia giàu kinh nghiệm</li>
				<li>Hỗ trợ 24/7</li>
				<li>Được tin dùng bởi hàng trăm doanh nghiệp</li>
			</ul>
		</div>
	</section>

	<!-- Contact Section -->
	<section id="contact" class="px-8 py-16 w-4/12 bg-indigo-50 rounded-3xl mx-4 md:mx-24 mb-8 shadow-lg">
		<h2 class="text-3xl font-bold text-indigo-700 mb-8 text-center">Liên hệ với chúng tôi</h2>
		<form class="max-w-xl mx-auto flex flex-col gap-6">
			<input type="text" placeholder="Họ và tên" class="px-5 py-3 bg-white rounded-lg border border-gray-300 focus:border-indigo-500 outline-none text-black" />
			<input type="email" placeholder="Email" class="px-5 py-3 bg-white rounded-lg border border-gray-300 focus:border-indigo-500 outline-none text-black" />
			<textarea placeholder="Nội dung" rows="4" class="px-5 py-3 bg-white rounded-lg border border-gray-300 focus:border-indigo-500 outline-none text-black"></textarea>
			<button type="submit" class="px-8 py-3 rounded-xl bg-indigo-600 text-white font-bold shadow hover:bg-indigo-700 transition">Gửi liên hệ</button>
		</form>
	</section>
    <AppFooter />
</div>
</template>

<style scoped>
.animate-fade-in {
	animation: fadeIn 1.2s ease;
}
@keyframes fadeIn {
	from { opacity: 0; transform: translateY(40px); }
	to { opacity: 1; transform: translateY(0); }
}
</style>
