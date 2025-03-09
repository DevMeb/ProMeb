<template>
    <!-- Chiffre d'affaires -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gray-800 p-6 rounded-lg shadow">
          <div class="flex items-center mb-4">
            <span class="text-3xl mr-3">💶</span>
            <h2 class="text-xl font-semibold text-white">CA facturé</h2>
          </div>
          <p class="text-4xl font-bold text-indigo-300">{{ dashboardData.caDetails.ca_facture }} €</p>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow">
          <div class="flex items-center mb-4">
            <span class="text-3xl mr-3">💰</span>
            <h2 class="text-xl font-semibold text-white">CA attendu</h2>
          </div>
          <p class="text-4xl font-bold text-indigo-300">{{ dashboardData.caDetails.ca_attendu }} €</p>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow">
          <div class="flex items-center mb-4">
            <span class="text-3xl mr-3">📊</span>
            <h2 class="text-xl font-semibold text-white">Différence</h2>
          </div>
          <p class="text-4xl font-bold text-indigo-300">{{ dashboardData.caDetails.difference }} €</p>
        </div>
      </div>
</template>
<script setup>
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
const dashbordStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashbordStore);
</script>