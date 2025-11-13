<template>
  <Transition name="fade">
    <div
      v-if="showRefresh"
      class="fixed bottom-4 left-1/2 -translate-x-1/2 w-[90%] max-w-md
             bg-white shadow-xl rounded-xl border border-gray-200 p-4
             flex items-center justify-between gap-4 z-50"
    >
      <div class="flex items-center gap-3">
        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
               viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-width="2"
                  d="M12 4v16m8-8H4" />
          </svg>
        </div>

        <div class="text-sm">
          <p class="font-semibold text-gray-800">Nouvelle version disponible</p>
          <p class="text-gray-600">Cliquez pour mettre à jour l’application</p>
        </div>
      </div>

      <button
        @click="update"
        class="flex-shrink-0 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium
               hover:bg-indigo-500 active:scale-95 transition"
      >
        Mettre à jour
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { usePwa } from '@/composables/usePwa'

const { showRefresh, updateSW } = usePwa()

function update() {
  if (updateSW.value) {
    updateSW.value(true) // met à jour + reload auto
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
