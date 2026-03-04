<script setup>
/**
 * MathText.vue — render text có chứa LaTeX inline \(...\) và block \[...\]
 *
 * Dùng KaTeX (load 1 lần qua CDN, cache lại).
 *
 * Ví dụ:
 *   <MathText :text="'Tính \\(a^2 + b^2\\) với \\(a=2, b=3\\)'" />
 */
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  text: { type: String, default: '' },
  // tag bao ngoài, mặc định span để dùng inline
  tag: { type: String, default: 'span' },
})

const rendered = ref('')

// ── Load KaTeX một lần duy nhất ──────────────────────────────────────────
let katexReady = false
let katexPromise = null

function loadKatex() {
  if (katexReady) return Promise.resolve()
  if (katexPromise) return katexPromise

  katexPromise = new Promise((resolve) => {
    // CSS
    if (!document.getElementById('katex-css')) {
      const link = document.createElement('link')
      link.id   = 'katex-css'
      link.rel  = 'stylesheet'
      link.href = 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css'
      document.head.appendChild(link)
    }
    // JS
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js'
    script.onload = () => { katexReady = true; resolve() }
    script.onerror = () => resolve()   // fallback: render as plain text
    document.head.appendChild(script)
  })

  return katexPromise
}

// ── Render text: thay \(...\) và \[...\] bằng KaTeX HTML ─────────────────
function renderMath(text) {
  if (!text) return ''
  if (!katexReady || !window.katex) return escapeHtml(text)

  // Tách chuỗi theo các đoạn math: \(...\) inline và \[...\] block
  // Pattern: \\( ... \\) hoặc \( ... \) — cả 2 dạng
  const parts = []
  // Regex: bắt \(...\) inline và \[...\] display
  const regex = /\\\((.+?)\\\)|\\\[(.+?)\\\]/gs

  let lastIndex = 0
  let match

  while ((match = regex.exec(text)) !== null) {
    // Phần text thường trước đoạn math
    if (match.index > lastIndex) {
      parts.push({ type: 'text', value: text.slice(lastIndex, match.index) })
    }

    const isInline  = match[1] !== undefined
    const mathStr   = isInline ? match[1] : match[2]

    try {
      const html = window.katex.renderToString(mathStr, {
        displayMode: !isInline,
        throwOnError: false,
        output: 'html',
      })
      parts.push({ type: 'html', value: html })
    } catch {
      // Nếu KaTeX lỗi thì giữ nguyên
      parts.push({ type: 'text', value: match[0] })
    }

    lastIndex = match.index + match[0].length
  }

  // Phần text còn lại sau đoạn cuối
  if (lastIndex < text.length) {
    parts.push({ type: 'text', value: text.slice(lastIndex) })
  }

  return parts
    .map(p => p.type === 'html' ? p.value : escapeHtml(p.value))
    .join('')
}

function escapeHtml(str) {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

async function update() {
  await loadKatex()
  rendered.value = renderMath(props.text)
}

onMounted(update)
watch(() => props.text, update)
</script>

<template>
  <component :is="tag" v-html="rendered" class="math-text" />
</template>

<style>
/* Đảm bảo KaTeX inline không bị vỡ layout */
.math-text .katex { font-size: 1em; }
.math-text .katex-display { margin: 0.3em 0; }
</style>