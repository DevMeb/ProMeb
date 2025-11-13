<template>
    <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
      <!-- Overlay cliquable pour fermer la modale -->
  
      <!-- Boîte de dialogue -->
      <div class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800">
        <!-- En-tête -->
        <div class="flex justify-between items-center pb-4 border-b border-gray-800">
          <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
            🧾 Prestations
          </h2>
          <button 
            @click="close" 
            class="text-gray-400 hover:text-white transition-transform hover:scale-110"
          >
            ✕
          </button>
        </div>
  
        <!-- Liste des prestations -->
        <div class="mt-6">

          <!-- État vide -->
          <div 
            v-if="prestations.length === 0" 
            class="p-6 text-center rounded-xl border-2 border-dashed border-gray-800 hover:border-indigo-500 transition-colors"
          >
            <div class="text-4xl mb-3">🎉</div>
            <p class="text-gray-400">Aucune prestations !</p>
          </div>
  
          <!-- Liste des prestations -->
          <div v-else class="space-y-3">
            <label
              v-for="prestation in prestations"
              :key="prestation.id"
              class="group flex items-center p-4 rounded-xl border border-gray-800 hover:border-indigo-500 bg-gray-800/50 cursor-pointer transition-all"
            >
              <div class="ml-4 flex-1">
                <div class="flex items-center justify-center gap-3">
                  <span class="font-bold text-md bg-gray-900 text-gray-400 px-2 py-1 rounded-full">
                    {{ formatDate(prestation.date) }}
                  </span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 mt-2 font-bold">
                  <div class="flex items-center justify-center gap-2 text-lg">
                    <span class="text-gray-500">🕒</span>
                    <span class="text-gray-300">{{ prestation.heures }}h</span>
                  </div>
                  <div class="flex items-center justify-center  gap-2 text-lg">
                    <span class="text-gray-500">📍</span>
                    <span class="text-gray-300 truncate">{{ prestation.adresse }}</span>
                  </div>
                  <div class="flex items-center justify-center  gap-2 text-lg">
                    <span class="text-gray-500">👤</span>
                    <span class="text-gray-300 truncate">{{ prestation.client.nom }}</span>
                  </div>
                  <div class="flex items-center justify-center  gap-2 text-lg">
                    <span class="text-gray-500">💰</span>
                    <span class="text-gray-300 truncate">{{ prestation.taux_horaire.taux }} € / h</span>
                  </div>
                </div>
              </div>
            </label>
          </div>
        </div>
  
        <!-- Récapitulatif -->
        <div v-if="prestations.length !==0" class="mt-6 bg-gray-800 p-4 rounded-xl border border-gray-700">
          <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
            📝 Récapitulatif
          </h3>
          
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-400">Total heures :</span>
              <span class="text-indigo-400 font-bold">{{ totalHeures }}h</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-400">Montant HT :</span>
              <span class="text-green-400 font-bold">{{ totalHT }} €</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </template>
    
    <script setup>
    import { computed } from "vue";
    import { formatDate } from '@/utils'
    
    // Props et événements
    const emit = defineEmits(["close"]);
    const props = defineProps({
      prestations: Array
    })

    const prestations = computed(() => props.prestations ?? [])
    
    // Calcul du total des heures et du montant HT
    const totalHeures = computed(() => {
      return prestations.value
        .reduce((sum, prestation) => sum + parseFloat(prestation.heures), 0);
    });
    
    const totalHT = computed(() => {
      return prestations.value.reduce((total, prestation) => {
        return total + (prestation.taux_horaire.taux * prestation.heures)
      }, 0);
    });
    
    // Fonction pour fermer la modale
    const close = () => {
      emit("close");
    };
    </script>
    