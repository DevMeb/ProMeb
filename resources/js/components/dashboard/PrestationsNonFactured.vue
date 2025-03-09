<template>
  <!-- Section Prestations non facturées -->
  <div class="bg-gray-800 p-6 rounded-2xl shadow-xl mb-8 border border-gray-700">
    <!-- En-tête -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div class="flex-1">
        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
          <span class="bg-red-500/20 p-3 rounded-full text-red-400">📥</span>
          <span class="bg-gradient-to-r from-red-400 to-orange-400 bg-clip-text text-transparent">
            Prestations en attente
          </span>
        </h2>
        <p class="text-gray-400 mt-2 text-sm">
          Services réalisés non encore facturés • 
          <span class="font-semibold text-indigo-400">
            {{ dashboardData.prestations_non_factured.length }} élément(s)
          </span>
        </p>
      </div>

      <!-- Bouton d'action -->
      <button 
        v-if="dashboardData.prestations_non_factured.length > 0"
        @click="showFormModal = true" 
        class="bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white font-semibold px-6 py-3 rounded-xl flex items-center gap-2 transition-all shadow-lg hover:shadow-indigo-500/20 group"
      >
        <span class="text-lg transition-transform group-hover:scale-110">📄</span>
        Générer une facture
      </button>
    </div>

    <!-- État vide -->
    <div 
      v-if="dashboardData.prestations_non_factured.length === 0" 
      class="text-center p-8 rounded-xl border-2 border-dashed border-gray-600 hover:border-indigo-500 transition-colors"
    >
      <div class="text-6xl mb-4">🎉</div>
      <h3 class="text-xl font-semibold text-gray-200 mb-2">Tout est à jour !</h3>
      <p class="text-gray-400">Aucune prestation en attente de facturation</p>
    </div>

    <!-- Liste des prestations -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div 
        v-for="prestation in dashboardData.prestations_non_factured" 
        :key="prestation.id" 
        class="group relative bg-gray-900 p-5 rounded-xl border border-gray-800 hover:border-indigo-500 transition-all"
      >
        <div class="flex gap-4">
          <!-- Bloc de statut -->
          <div class="bg-indigo-500/20 p-3 rounded-lg shrink-0">
            <span class="text-2xl">⏳</span>
          </div>
          
          <!-- Contenu -->
          <div class="space-y-2 flex-1">
            <!-- Ligne supérieure -->
            <div class="flex items-center justify-between">
              <span class="font-mono text-sm text-indigo-400">#{{ prestation.id }}</span>
              <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded-full">
                {{ formatDate(prestation.date) }}
              </span>
            </div>

            <!-- Détails -->
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div class="flex items-center gap-2">
                <span class="text-gray-500">🕒</span>
                <span class="text-gray-300">{{ prestation.heures }}h</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-gray-500">⏰</span>
                <span class="text-gray-300">{{ prestation.horaires }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-gray-500">📍</span>
                <span class="text-gray-300 truncate">{{ prestation.adresse }}</span>
              </div>
            </div>

            <!-- Séparateur animé -->
            <div class="border-t border-gray-800 my-2 opacity-50 group-hover:opacity-75 transition"></div>

            <!-- Métadonnées -->
            <div class="flex justify-between text-xs text-gray-400">
              <span>Créé le {{ formatDate(prestation.created_at) }}</span>
              <span class="flex items-center gap-1">
                📌 Statut : Non facturé
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modale -->
    <FactureFormModal 
      v-if="showFormModal" 
      @close="showFormModal = false" 
      class="animate-fade-in"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { formatDate } from '@/utils';
import { FactureFormModal } from '@/components/factures';

const dashboardStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashboardStore);

const showFormModal = ref(false);
</script>