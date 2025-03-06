<template>
  <div class="p-6 max-w-7xl mx-auto">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-8">Mes Prestations</h1>

    <!-- Bouton d'ajout -->
    <div class="mt-8 flex justify-end">
      <router-link
        to="/prestations/create"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md shadow-md"
      >
        <PlusIcon class="h-5 w-5 mr-2" />
        <span>Ajouter une prestation</span>
      </router-link>
    </div>

    <!-- Animation de chargement -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-indigo-600"></div>
    </div>

    <!-- Message d'erreur -->
    <div v-if="error" class="text-center text-red-600 font-medium mb-6">
      {{ error }}
    </div>

    <!-- Grille des prestations -->
    <div v-if="!loading && prestations.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="prestation in prestations"
        :key="prestation.id"
        class="bg-white rounded-lg shadow-md p-6 flex flex-col transition transform hover:scale-105"
      >
        <div class="flex items-center space-x-2 mb-3">
          <CalendarIcon class="h-6 w-6 text-indigo-500" />
          <span class="text-gray-700 font-medium">{{ formatDate(prestation.date_prestation) }}</span>
        </div>
        <div class="flex items-center space-x-2 mb-3">
          <ClockIcon class="h-6 w-6 text-green-500" />
          <span class="text-gray-700 font-medium">{{ prestation.nombre_heures }} h</span>
        </div>
        <div class="flex items-center space-x-2 mb-4">
          <MapPinIcon class="h-6 w-6 text-red-500" />
          <span class="text-gray-700 font-medium">{{ prestation.adresse }}</span>
        </div>
        <div class="mt-auto flex justify-between">
          <router-link
            :to="`/prestations/${prestation.id}/edit`"
            class="flex items-center space-x-1 text-indigo-600 hover:text-indigo-800 font-semibold"
          >
            <PencilSquareIcon class="h-5 w-5" />
            <span>Modifier</span>
          </router-link>
          <button
            @click="handleDelete(prestation.id)"
            class="flex items-center space-x-1 text-red-600 hover:text-red-800 font-semibold"
          >
            <TrashIcon class="h-5 w-5" />
            <span>Supprimer</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Message si aucune prestation -->
    <div v-else-if="!loading && !prestations.length" class="text-center text-gray-500 mt-8">
      Aucune prestation disponible.
    </div>

    
  </div>
</template>

<script setup>
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';
import dayjs from 'dayjs';
import { usePrestationsStore } from '@/stores/prestations';
import {
  CalendarIcon,
  ClockIcon,
  MapPinIcon,
  PencilSquareIcon,
  TrashIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline';

const prestationsStore = usePrestationsStore();
const { prestations, loading, error } = storeToRefs(prestationsStore);
const { fetchPrestations, deletePrestation } = prestationsStore;

onMounted(() => {
  fetchPrestations();
});

const handleDelete = async (id) => {
  if (confirm('Voulez-vous vraiment supprimer cette prestation ?')) {
    try {
      await deletePrestation(id);
    } catch (e) {
      console.error(e);
    }
  }
};

const formatDate = (date) => {
  return dayjs(date).format('DD/MM/YYYY');
};
</script>
