<script setup>
import { onUnmounted, ref } from 'vue'
import { BrowserMultiFormatReader } from '@zxing/browser'
import { NotFoundException } from '@zxing/library'
import { X, ScanLine, Camera, AlertCircle } from 'lucide-vue-next'

const emit = defineEmits(['scanned', 'close'])

const videoRef = ref(null)
const controls = ref(null)
const error = ref(null)
const scanning = ref(false)
const lastResult = ref(null)

async function startScanner() {
  error.value = null
  scanning.value = true
  lastResult.value = null

  try {
    const reader = new BrowserMultiFormatReader()
    controls.value = await reader.decodeFromVideoDevice(
      undefined,
      videoRef.value,
      (result, err) => {
        if (result) {
          const code = result.getText()
          if (code !== lastResult.value) {
            lastResult.value = code
            stopScanner()
            emit('scanned', code)
          }
        }
        if (err && !(err instanceof NotFoundException)) {
          error.value = "Erreur lors de la lecture. Vérifiez que la caméra est accessible."
          scanning.value = false
        }
      },
    )
    scanning.value = true
  } catch (e) {
    scanning.value = false
    if (e?.name === 'NotAllowedError') {
      error.value = "L'accès à la caméra a été refusé. Autorisez l'accès dans les paramètres du navigateur."
    } else if (e?.name === 'NotFoundError') {
      error.value = "Aucune caméra détectée sur cet appareil."
    } else {
      error.value = "Impossible de démarrer le scanner."
    }
  }
}

function stopScanner() {
  controls.value?.stop()
  controls.value = null
  scanning.value = false
}

function close() {
  stopScanner()
  emit('close')
}

startScanner()
onUnmounted(stopScanner)
</script>

<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="close">
    <div class="absolute inset-0 bg-neutral-950/70 backdrop-blur-sm" @click="close" />

    <!-- Modal -->
    <div class="relative z-10 w-full max-w-sm rounded-2xl bg-white overflow-hidden shadow-2xl">

      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-lg bg-green-50 flex items-center justify-center">
            <ScanLine :size="16" class="text-green-600" />
          </div>
          <span class="font-semibold text-[15px] text-neutral-900">Scanner un code-barre</span>
        </div>
        <button
          @click="close"
          class="h-8 w-8 rounded-lg flex items-center justify-center text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 transition-colors"
          aria-label="Fermer"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Camera view -->
      <div class="relative bg-neutral-950 aspect-[4/3]">
        <video
          ref="videoRef"
          class="w-full h-full object-cover"
          autoplay
          playsinline
          muted
        />

        <!-- Scanning overlay -->
        <div v-if="scanning && !error" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <!-- Corner brackets -->
          <div class="relative w-48 h-36">
            <span class="absolute top-0 left-0 w-7 h-7 border-t-[3px] border-l-[3px] border-green-400 rounded-tl" />
            <span class="absolute top-0 right-0 w-7 h-7 border-t-[3px] border-r-[3px] border-green-400 rounded-tr" />
            <span class="absolute bottom-0 left-0 w-7 h-7 border-b-[3px] border-l-[3px] border-green-400 rounded-bl" />
            <span class="absolute bottom-0 right-0 w-7 h-7 border-b-[3px] border-r-[3px] border-green-400 rounded-br" />
            <!-- Scan line -->
            <div class="absolute inset-x-2 top-1/2 -translate-y-1/2 h-0.5 bg-green-400/70 animate-pulse" />
          </div>
          <p class="mt-4 text-xs font-medium text-white/70">Pointez la caméra vers un code-barre</p>
        </div>

        <!-- Error state -->
        <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
          <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center mb-3">
            <AlertCircle :size="22" class="text-amber-400" />
          </div>
          <p class="text-sm font-medium text-white">{{ error }}</p>
        </div>

        <!-- Loading state (before camera starts) -->
        <div v-if="!scanning && !error" class="absolute inset-0 flex flex-col items-center justify-center">
          <div class="h-10 w-10 rounded-full bg-white/10 flex items-center justify-center mb-3 animate-pulse">
            <Camera :size="18" class="text-white/60" />
          </div>
          <p class="text-xs text-white/50">Démarrage de la caméra…</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-5 py-4">
        <p class="text-xs text-center text-neutral-400">
          Compatible EAN-13, EAN-8, UPC-A, UPC-E, QR Code
        </p>
      </div>
    </div>
  </div>
</template>
