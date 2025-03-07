<template>
    <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
      <!-- Overlay cliquable pour fermer la modale -->
      <div @click.self="close" class="absolute inset-0"></div>
  
      <!-- Boîte de dialogue -->
      <div class="bg-white p-6 rounded-lg shadow-lg w-[40rem] max-h-[80vh] overflow-auto animate-fade-in transform transition-transform scale-95">
        <!-- En-tête -->
        <div class="flex justify-between items-center border-b pb-3">
          <h2 class="text-xl font-semibold text-gray-800">Créer une Facture</h2>
          <button @click="close" class="text-gray-500 hover:text-gray-700 transition">✖️</button>
        </div>
  
        <!-- Liste des prestations non facturées -->
        <div class="mt-4">
          <h3 class="text-lg font-semibold text-gray-700">Sélectionner les Prestations</h3>
          
          <div v-if="unbilledPrestations.length === 0" class="p-4 bg-gray-100 rounded-md text-center text-gray-600">
            Aucune prestation non facturée n'est disponible.
          </div>
  
          <ul v-else class="space-y-2 mt-2 max-h-60 overflow-y-auto">
            <li
              v-for="prestation in unbilledPrestations"
              :key="prestation.id"
              class="flex items-center p-3 bg-gray-50 rounded-md shadow-sm hover:shadow-md transition"
            >
              <input
                type="checkbox"
                v-model="selectedPrestations"
                :value="prestation"
                class="mr-3 h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <div>
                <p class="text-gray-700 font-medium">
                  📅 {{ formatDate(prestation.date_prestation) }}
                </p>
                <p class="text-gray-600">
                  ⏱️ <span class="font-semibold">{{ prestation.nombre_heures }}</span> h - 📍 {{ prestation.adresse }}
                </p>
              </div>
            </li>
          </ul>
        </div>
  
        <!-- Récapitulatif -->
        <div class="mt-6 bg-gray-100 p-4 rounded-md shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-3">Récapitulatif</h3>
          <div v-if="selectedPrestations.length">
            <p class="text-gray-700">
              🕒 <span class="font-medium">Total d'heures :</span> {{ totalHeures }} h
            </p>
            <p class="text-gray-700">
              💰 <span class="font-medium">Total HT :</span> {{ totalHT }} €
            </p>
          </div>
          <div v-else class="text-gray-500 text-center">
            Aucune prestation sélectionnée.
          </div>
        </div>
  
        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-3">
          <button @click="close" class="px-4 py-2 bg-gray-500 text-white rounded-md font-semibold hover:bg-gray-400 transition">
            Annuler
          </button>
          <button
            @click="createInvoiceHandler"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-500 transition"
            :disabled="loading.add || selectedPrestations.length === 0"
          >
            <span v-if="loading.add" class="animate-spin mr-2">⏳</span>
            Créer la Facture
          </button>
        </div>
  
        <!-- Message d'erreur -->
        <div v-if="errors.add" class="text-red-500 text-center mt-3">
          {{ errors.add }}
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from "vue";
  import { storeToRefs } from "pinia";
  import dayjs from "dayjs";
  import { usePrestationsStore } from "@/stores/prestations";
  import { useInvoicesStore } from "@/stores/factures";
  
  // Props et événements
  const emit = defineEmits(["close"]);
  const prestationsStore = usePrestationsStore();
  const { prestations } = storeToRefs(prestationsStore);
  const { fetchPrestations } = prestationsStore;

  const invoicesStore = useInvoicesStore();
  const { addInvoice, loading, errors } = invoicesStore;
  // Charger les prestations
  onMounted(() => {
    fetchPrestations();
  });
  
  // Filtrer les prestations non facturées
  const unbilledPrestations = computed(() => {
    return prestations.value.filter((prestation) => !prestation.facture_id);
  });
  
  // Sélection des prestations
  const selectedPrestations = ref([]);
  
  // Calcul du total des heures et du montant HT
  const totalHeures = computed(() => {
    return selectedPrestations.value
      .reduce((sum, prestation) => sum + parseFloat(prestation.nombre_heures), 0)
      .toFixed(2);
  });
  
  const tauxHoraire = 20;
  const totalHT = computed(() => {
    return (totalHeures.value * tauxHoraire).toFixed(2);
  });
  
  // Formater la date
  const formatDate = (date) => dayjs(date).format("DD/MM/YYYY");
  
  // Fonction de création de facture
  async function createInvoiceHandler() {
    if (!selectedPrestations.value.length) return;
  
    const payload = {
      prestations: selectedPrestations.value.map((p) => p.id),
      total_heures: parseFloat(totalHeures.value),
      total_ht: parseFloat(totalHT.value),
    };
  
    try {
      await addInvoice(payload);
      emit("close"); // Fermer la modale après succès
    } catch (err) {
      console.error("Erreur lors de la création de la facture :", err);
    }
  }
  
  // Fonction pour fermer la modale
  const close = () => {
    emit("close");
  };
  </script>
  