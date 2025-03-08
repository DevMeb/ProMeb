<template>
  <div class="bg-gray-800 text-gray-300 p-4 rounded-lg shadow flex flex-col sm:flex-row justify-between items-center text-white mt-4">
    <div class="mb-2 sm:mb-0">
      Nombre de prestations : <span class="font-bold text-lg">{{ prestationCount }}</span>
    </div>
    <div>
      Total d'heures : <span class="font-bold text-lg">{{ totalHours }}</span> h
    </div>
  </div>
</template>

<script setup>
import { usePrestationsStore } from '@/stores/prestations';
import { storeToRefs } from 'pinia';

const prestationsStore = usePrestationsStore();
const { prestationCount, totalHours } = storeToRefs(prestationsStore);
</script>
