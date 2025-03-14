<template>
  <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
    <!-- Overlay cliquable pour fermer la modale -->

    <!-- Boîte de dialogue -->
    <div class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800">
      <!-- En-tête -->
      <div class="flex justify-between items-center pb-4 border-b border-gray-800">
        <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
          🧾 Nouvelle Facture
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
        <h3 class="flex flex-col text-lg font-semibold text-white mb-4 flex items-center gap-4">
          📋 Prestations disponibles
          <span class="text-sm bg-gray-800 text-gray-400 px-3 py-1 rounded-full">
            {{ unbilledPrestations.length }} non facturées
          </span>
        </h3>

        <!-- <PrestationsFilters class="mb-4" /> -->

        <!-- État vide -->
        <div 
          v-if="unbilledPrestations.length === 0" 
          class="p-6 text-center rounded-xl border-2 border-dashed border-gray-800 hover:border-indigo-500 transition-colors"
        >
          <div class="text-4xl mb-3">🎉</div>
          <p class="text-gray-400">Aucune prestations à facturées !</p>
        </div>

        <!-- Liste des prestations -->
        <div v-else class="space-y-3">
          <label class="flex items-center justify-center text-xl text-white mb-4">
            <input type="checkbox" v-model="selectAll" @change="toggleAll" class="mr-2">
            Tout sélectionner
          </label>

          <label
            v-for="prestation in unbilledPrestations"
            :key="prestation.id"
            class="group flex items-center p-4 rounded-xl border border-gray-800 hover:border-indigo-500 bg-gray-800/50 cursor-pointer transition-all"
            :class="{ 'border-indigo-500 bg-indigo-500/10': selectedPrestations.includes(prestation) }"
          >
            <input
              type="checkbox"
              v-model="selectedPrestations"
              :value="prestation"
              class="mt-1 h-5 w-5 rounded border-gray-700 bg-gray-800 checked:bg-indigo-500 checked:border-indigo-500 focus:ring-indigo-500/50"
            />
            
            <div class="ml-4 flex-1">
              <div class="flex items-center justify-center gap-3">
                <span class="text-xs bg-gray-900 text-gray-400 px-2 py-1 rounded-full">
                  {{ formatDate(prestation.date) }}
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-2 mt-2">
                <div class="flex items-center justify-center  gap-2 text-sm">
                  <span class="text-gray-500">🕒</span>
                  <span class="text-gray-300">{{ prestation.heures }}h</span>
                </div>
                <div class="flex items-center justify-center  gap-2 text-sm">
                  <span class="text-gray-500">📍</span>
                  <span class="text-gray-300 truncate">{{ prestation.adresse }}</span>
                </div>
                <div class="flex items-center justify-center  gap-2 text-sm">
                  <span class="text-gray-500">👤</span>
                  <span class="text-gray-300 truncate">{{ prestation.client.nom }}</span>
                </div>
                <div class="flex items-center justify-center  gap-2 text-sm">
                  <span class="text-gray-500">💰</span>
                  <span class="text-gray-300 truncate">{{ prestation.taux_horaire.taux }} € / h</span>
                </div>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- Récapitulatif -->
      <div v-if="selectedPrestations.length !==0" class="mt-6 bg-gray-800 p-4 rounded-xl border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
          📝 Récapitulatif
        </h3>
        
        <div class="space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-gray-400">Prestations sélectionnées :</span>
            <span class="text-white font-medium">{{ selectedPrestations.length }}</span>
          </div>
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

      <!-- Actions -->
      <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
        <button 
          @click="close" 
          class="px-6 py-3 rounded-xl font-semibold transition-all bg-gray-700 hover:bg-gray-600 text-white"
        >
          Annuler
        </button>
        <button
          @click="createInvoiceHandler"
          class="px-6 py-3 rounded-xl font-semibold transition-all bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white flex items-center justify-center"
          :disabled="loading.add || selectedPrestations.length === 0"
        >
          <span v-if="loading.add" class="animate-spin mr-2">🌀</span>
          <span v-else>✅</span>
          Créer la Facture
        </button>
      </div>

      <!-- Message d'erreur -->
      <div 
        v-if="errors.add" 
        class="mt-4 p-3 bg-red-500/10 text-red-400 rounded-lg border border-red-500/30 flex items-center gap-2"
      >
        ⚠️ {{ errors.add }}
      </div>
    </div>
  </div>
</template>
  
  <script setup>
  import { ref, computed, onMounted } from "vue";
  import { storeToRefs } from "pinia";
  import { usePrestationsStore } from "@/stores/prestations";
  import { useInvoicesStore } from "@/stores/factures";
  import { PrestationsFilters } from "@/components/prestations/"
  import { formatDate } from '@/utils'
  
  // Props et événements
  const emit = defineEmits(["close"]);
  const prestationsStore = usePrestationsStore();
  const { unbilledPrestations } = storeToRefs(prestationsStore);
  const { fetchPrestations } = prestationsStore;

  const invoicesStore = useInvoicesStore();
  const { addInvoice, loading, errors } = invoicesStore;
  // Charger les prestations
  onMounted(() => {
    fetchPrestations();
  });  
  
  // Sélection des prestations
  const selectedPrestations = ref([]);
  const selectAll = ref(false);

  const toggleAll = () => {
    if (selectAll.value) {
      selectedPrestations.value = [...unbilledPrestations.value];
    } else {
      selectedPrestations.value = [];
    }
  };
  
  // Calcul du total des heures et du montant HT
  const totalHeures = computed(() => {
    return selectedPrestations.value
      .reduce((sum, prestation) => sum + parseFloat(prestation.heures), 0);
  });
  
  const totalHT = computed(() => {
    return selectedPrestations.value.reduce((total, prestation) => {
      return total + (prestation.taux_horaire.taux * prestation.heures)
    }, 0);
  });
    
  // Fonction de création de facture
  async function createInvoiceHandler() {
    if (!selectedPrestations.value.length) return;
  
    const payload = {
      prestations: selectedPrestations.value.map((p) => p.id),
    };
  
    await addInvoice(payload);
    close();
  }
  
  // Fonction pour fermer la modale
  const close = () => {
    emit("close");
  };
  </script>
  