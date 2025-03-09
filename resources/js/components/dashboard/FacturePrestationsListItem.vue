<!-- FacturePrestationsListItem.vue -->
<template>
    <div class="group bg-gray-800 p-4 rounded-lg hover:bg-gray-750 transition-all duration-200 shadow-sm">
      <div class="flex flex-col gap-2">
        <!-- En-tête -->
        <div class="flex justify-between items-start">
          <div>
            <span class="text-sm font-mono text-purple-400">#{{ prestation.id }}</span>
            <span class="ml-2 text-xs text-gray-400">{{ formatDate(prestation.date) }}</span>
          </div>
          <span class="text-sm bg-gray-900 text-gray-400 px-2 py-1 rounded-full">
            ⏱️ {{ prestation.heures }}h
          </span>
        </div>
  
        <!-- Détails -->
        <div class="grid grid-cols-2 gap-2 text-sm">
          <div class="flex items-center gap-2">
            <span class="text-gray-500">⌛</span>
            <span class="text-gray-300">{{ prestation.horaires }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-gray-500">📍</span>
            <span class="text-gray-300 truncate">{{ prestation.adresse }}</span>
          </div>
        </div>
  
        <!-- Séparateur décoratif -->
        <div class="border-t border-gray-700 my-2 opacity-50 group-hover:opacity-75 transition"></div>
  
        <!-- Stats supplémentaires -->
        <div class="flex justify-between text-xs text-gray-400">
          <span>Créé le {{ formatDate(prestation.created_at) }}</span>
          <span class="flex items-center gap-1">
            🔄
            <span v-if="prestation.updated_at">{{ formatDate(prestation.updated_at) }}</span>
            <span v-else>Non modifié</span>
          </span>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { formatDate } from '@/utils';
  const props = defineProps({ 
      prestation: Object,
  });
  </script>