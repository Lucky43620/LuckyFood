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
        controls.value = await reader.decodeFromVideoDevice(undefined, videoRef.value, (result, err) => {
            if (result) {
                const code = result.getText()
                if (code !== lastResult.value) {
                    lastResult.value = code
                    stopScanner()
                    emit('scanned', code)
                }
            }
            if (err && !(err instanceof NotFoundException)) {
                error.value = 'Erreur lors de la lecture. Vérifiez que la caméra est accessible.'
                scanning.value = false
            }
        })
        scanning.value = true
    } catch (e) {
        scanning.value = false
        if (e?.name === 'NotAllowedError') {
            error.value = "L'accès à la caméra a été refusé. Autorisez l'accès dans les paramètres du navigateur."
        } else if (e?.name === 'NotFoundError') {
            error.value = 'Aucune caméra détectée sur cet appareil.'
        } else {
            error.value = 'Impossible de démarrer le scanner.'
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
        <div class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50">
                        <ScanLine :size="16" class="text-green-600" />
                    </div>
                    <span class="text-[15px] font-semibold text-neutral-900">Scanner un code-barre</span>
                </div>
                <button
                    @click="close"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700"
                    aria-label="Fermer"
                >
                    <X :size="16" />
                </button>
            </div>

            <!-- Camera view -->
            <div class="relative aspect-[4/3] bg-neutral-950">
                <video ref="videoRef" class="h-full w-full object-cover" autoplay playsinline muted />

                <!-- Scanning overlay -->
                <div
                    v-if="scanning && !error"
                    class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                >
                    <!-- Corner brackets -->
                    <div class="relative h-36 w-48">
                        <span
                            class="absolute left-0 top-0 h-7 w-7 rounded-tl border-l-[3px] border-t-[3px] border-green-400"
                        />
                        <span
                            class="absolute right-0 top-0 h-7 w-7 rounded-tr border-r-[3px] border-t-[3px] border-green-400"
                        />
                        <span
                            class="absolute bottom-0 left-0 h-7 w-7 rounded-bl border-b-[3px] border-l-[3px] border-green-400"
                        />
                        <span
                            class="absolute bottom-0 right-0 h-7 w-7 rounded-br border-b-[3px] border-r-[3px] border-green-400"
                        />
                        <!-- Scan line -->
                        <div class="bg-green-400/70 absolute inset-x-2 top-1/2 h-0.5 -translate-y-1/2 animate-pulse" />
                    </div>
                    <p class="mt-4 text-xs font-medium text-white/70">Pointez la caméra vers un code-barre</p>
                </div>

                <!-- Error state -->
                <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white/10">
                        <AlertCircle :size="22" class="text-amber-400" />
                    </div>
                    <p class="text-sm font-medium text-white">{{ error }}</p>
                </div>

                <!-- Loading state (before camera starts) -->
                <div v-if="!scanning && !error" class="absolute inset-0 flex flex-col items-center justify-center">
                    <div class="mb-3 flex h-10 w-10 animate-pulse items-center justify-center rounded-full bg-white/10">
                        <Camera :size="18" class="text-white/60" />
                    </div>
                    <p class="text-xs text-white/50">Démarrage de la caméra…</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4">
                <p class="text-center text-xs text-neutral-400">Compatible EAN-13, EAN-8, UPC-A, UPC-E, QR Code</p>
            </div>
        </div>
    </div>
</template>
