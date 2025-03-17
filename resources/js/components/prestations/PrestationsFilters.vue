<template>
  <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4">
    <!-- Instruction pour l'utilisateur -->
    <div class="flex-1">
      <p class="text-gray-300 text-sm mb-2">
        Filtrer les prestations.
      </p>
    </div>

    <!-- Filtrer par Mois & Année -->
    <div>
      <label class="text-sm text-gray-300 font-semibold">Mois & Année :</label>
      <input type="month" v-model="activeFilters.month_year" class="filter-input" />
    </div>

    <!-- Filtrer par Client -->
    <div>
      <label class="text-sm text-gray-300 font-semibold">Client :</label>
      <select v-model="activeFilters.client_id" class="filter-input">
        <option value="">Tous les clients</option>
        <option v-for="client in clients" :key="client.id" :value="client.id">
          {{ client.nom }}
        </option>
      </select>
    </div>

    <!-- Filtrer par Taux Horaire -->
    <div>
      <label class="text-sm text-gray-300 font-semibold">Taux Horaire :</label>
      <select v-model="activeFilters.taux_horaire_id" class="filter-input">
        <option value="">Tous les taux horaires</option>
        <option v-for="tauxHoraire in tauxHoraires" :key="tauxHoraire.id" :value="tauxHoraire.id">
          {{ tauxHoraire.taux }}
        </option>
      </select>
    </div>

    <!-- Bouton de réinitialisation -->
    <button @click="resetFilters" v-if="isAnyFilterActive" class="btn-secondary">
      Réinitialiser
    </button>
  </div>
</template>

<script setup>
import { onMounted, watch } from "vue";
import { usePrestationsStore } from "@/stores/prestations";
import { useClientsStore } from "@/stores/clients";
import { useTauxHorairesStore } from "@/stores/taux-horaires";
import { storeToRefs } from "pinia";

const prestationsStore = usePrestationsStore();
const clientsStore = useClientsStore(); // Accès au store des clients
const tauxHorairesStore = useTauxHorairesStore();

const { activeFilters, isAnyFilterActive } = storeToRefs(prestationsStore);
const { updateFilters, } = prestationsStore;

const { fetchClients } = clientsStore;
const { clients } = storeToRefs(clientsStore); // Liste des clients

const { fetchTauxHoraires } = tauxHorairesStore
const { tauxHoraires } = storeToRefs(tauxHorairesStore)

onMounted(() => {
  fetchClients()
  fetchTauxHoraires()
})

// Surveiller les changements de filtres et les appliquer
watch(activeFilters, (newFilters) => {
  updateFilters(newFilters);
}, { deep: true });

// Réinitialisation des filtres
const resetFilters = () => {
  updateFilters({
    month_year: "",
    client_id: "",
    taux_horaire_id: "",
  });
};
</script>
