<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md  shadow-lg rounded-lg p-6">
      <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">
        {{ isEdit ? 'Modifier' : 'Créer' }} une prestation
      </h1>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Date de prestation -->
        <div>
          <label for="date_prestation" class="block text-sm font-medium text-gray-700 mb-1">
            Date de prestation
          </label>
          <input
            type="date"
            id="date_prestation"
            v-model="form.date_prestation"
            class="block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <!-- Nombre d'heures -->
        <div>
          <label for="nombre_heures" class="block text-sm font-medium text-gray-700 mb-1">
            Nombre d'heures
          </label>
          <input
            type="number"
            step="0.01"
            id="nombre_heures"
            v-model="form.nombre_heures"
            class="block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <!-- Adresse -->
        <div>
          <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">
            Adresse
          </label>
          <input
            type="text"
            id="adresse"
            v-model="form.adresse"
            class="block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <!-- Bouton de soumission -->
        <button
          type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md font-semibold transition-colors"
        >
          {{ isEdit ? 'Mettre à jour' : 'Créer' }}
        </button>
      </form>
      <!-- Message d'erreur -->
      <div v-if="error" class="mt-4 text-center text-red-600 font-medium">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePrestationsStore } from '@/stores/prestations';
import dayjs from 'dayjs';

const route = useRoute();
const router = useRouter();
const prestationsStore = usePrestationsStore();
const isEdit = route.params.id !== undefined;

const form = ref({
  date_prestation: '',
  nombre_heures: '',
  adresse: '',
});

const error = ref(null);

if (isEdit) {
  const fetchPrestationData = async () => {
    try {
      const data = await prestationsStore.fetchPrestation(route.params.id);
      form.value = {
        // Formater la date au format "YYYY-MM-DD" pour l'input de type "date"
        date_prestation: dayjs(data.date_prestation).format('YYYY-MM-DD'),
        nombre_heures: data.nombre_heures,
        adresse: data.adresse,
      };
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
    }
  };
  onMounted(fetchPrestationData);
}

const handleSubmit = async () => {
  error.value = null;
  try {
    if (isEdit) {
      await prestationsStore.updatePrestation(route.params.id, form.value);
    } else {
      await prestationsStore.createPrestation(form.value);
    }
    router.push('/prestations');
  } catch (e) {
    error.value = e.response?.data?.error || e.message;
  }
};
</script>
