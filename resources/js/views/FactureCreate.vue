<template>
    <div class="p-6 max-w-7xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-800 mb-6">Créer une Facture</h1>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Colonne gauche : liste des prestations à sélectionner -->
        <div>
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Sélectionner les Prestations</h2>
          <ul class="space-y-4">
            <li
              v-for="prestation in prestations"
              :key="prestation.id"
              class="flex items-center p-4 border rounded-md hover:shadow-lg transition-shadow"
            >
              <input
                type="checkbox"
                v-model="selectedPrestations"
                :value="prestation"
                class="mr-4 h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <div>
                <div class="flex items-center space-x-2">
                  <CalendarIcon class="h-5 w-5 text-indigo-600" />
                  <span class="text-gray-700 font-medium">
                    {{ formatDate(prestation.date_prestation) }}
                  </span>
                </div>
                <div class="flex items-center space-x-2 mt-1">
                  <ClockIcon class="h-5 w-5 text-green-600" />
                  <span class="text-gray-700">
                    {{ prestation.nombre_heures }} h
                  </span>
                </div>
                <div class="flex items-center space-x-2 mt-1">
                  <MapPinIcon class="h-5 w-5 text-red-600" />
                  <span class="text-gray-700">
                    {{ prestation.adresse }}
                  </span>
                </div>
              </div>
            </li>
          </ul>
        </div>
  
        <!-- Colonne droite : récapitulatif et validation -->
        <div class="bg-white p-6 rounded-md shadow-md">
          <h2 class="text-xl font-semibold text-gray-700 mb-4">Récapitulatif</h2>
          <div v-if="selectedPrestations.length">
            <p class="text-gray-700 mb-2">
              <span class="font-medium">Total d'heures :</span> {{ totalHeures }} h
            </p>
            <p class="text-gray-700 mb-4">
              <span class="font-medium">Total HT :</span> {{ totalHT }} €
            </p>
            <button
              @click="createInvoice"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md transition-colors"
            >
              Générer la Facture
            </button>
          </div>
          <div v-else class="text-gray-500">
            Aucune prestation sélectionnée.
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue';
  import { storeToRefs } from 'pinia';
  import dayjs from 'dayjs';
  import { usePrestationsStore } from '@/stores/prestations';
  
  // Importer les icônes depuis HeroIcons (assurez-vous de les avoir installées)
  import { CalendarIcon, ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline';
  
  // Récupérer les prestations via le store
  const prestationsStore = usePrestationsStore();
  const { prestations } = storeToRefs(prestationsStore);
  const { fetchPrestations } = prestationsStore;

  
  // On suppose que fetchPrestations est déjà appelé ailleurs (par exemple dans un parent ou onMounted ici)
  // Sinon, vous pouvez l'appeler ici directement

  onMounted(() => {
  fetchPrestations();
});
  
  // Variable pour stocker les prestations sélectionnées
  const selectedPrestations = ref([]);
  
  // Calculer le total d'heures en sommant le nombre d'heures de chaque prestation sélectionnée
  const totalHeures = computed(() => {
    return selectedPrestations.value
      .reduce((acc, prestation) => acc + parseFloat(prestation.nombre_heures), 0)
      .toFixed(2);
  });
  
  // Taux horaire fixe ou récupéré depuis la configuration (exemple : 50€/h)
  const tauxHoraire = 20;
  const totalHT = computed(() => {
    return (totalHeures.value * tauxHoraire).toFixed(2);
  });
  
  // Formater la date au format d/m/Y pour l'affichage dans la liste
  const formatDate = (date) => {
    return dayjs(date).format('DD/MM/YYYY');
  };
  
  // Fonction pour générer la facture à partir des prestations sélectionnées
  const createInvoice = () => {
    // Ici, vous pouvez envoyer une requête vers votre API pour créer la facture,
    // en envoyant par exemple la liste des IDs sélectionnés, le total d'heures, et le total HT.
    // Pour cet exemple, on affiche simplement dans la console.
    console.log('Générer facture avec :', {
      prestations: selectedPrestations.value,
      totalHeures: totalHeures.value,
      totalHT: totalHT.value,
    });
    // Vous pourriez rediriger vers une page de confirmation ou de détail de facture.
  };
  </script>
  