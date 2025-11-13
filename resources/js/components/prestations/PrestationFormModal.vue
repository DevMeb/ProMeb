<template>
  <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
    <!-- Zone cliquable en arrière-plan pour fermer la modal -->
    <div @click.self="closeModal" class="fixed inset-0"></div>

    <div class="bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in">
      <div class="flex items-center justify-between border-b pb-2">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          <span v-if="prestationData.id" class="mr-2">✏️</span>
          <span v-else class="mr-2">➕</span>
          {{ prestationData.id ? 'Éditer la prestation' : 'Ajouter une prestation' }}
        </h2>
        <button @click="closeModal" class="text-gray-500 hover:text-gray-700 transition">✖️</button>
      </div>

      <form @submit.prevent="submitForm" class="mt-4 space-y-4">
        <!-- Client -->
        <div>
          <label for="client" class="block text-sm font-medium text-gray-700">Client</label>
          <div class="relative">
            <select
              id="client"
              v-model="prestationData.client_id"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.client_id }"
            >
              <option :disabled="clients.length === 0" value="">Sélectionner un client</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">
                {{ client.nom }}
              </option>
            </select>
            <p v-if="errors.validationErrors?.client_id" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.client_id.join(', ') }}
            </p>
          </div>
          <button
            type="button"
            @click="openClientModal"
            class="mt-2 text-indigo-600 text-sm flex items-center hover:underline"
          >
            ➕ Ajouter un client
          </button>
        </div>

        <!-- Taux horaire -->
        <div>
          <label for="taux-horaire" class="block text-sm font-medium text-gray-700">Taux Horaire</label>
          <div class="relative">
            <select
              id="taux-horaire"
              v-model="prestationData.taux_horaire_id"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.taux_horaire_id }"
            >
              <option :disabled="tauxHoraires.length === 0" value="">Sélectionner un taux horaire</option>
              <option v-for="tauxHoraire in tauxHoraires" :key="tauxHoraire.id" :value="tauxHoraire.id">
                {{ tauxHoraire.taux }}
              </option>
            </select>
            <p v-if="errors.validationErrors?.taux_horaire_id" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.taux_horaire_id.join(', ') }}
            </p>
          </div>
          <button
            type="button"
            @click="openTauxHoraireModal"
            class="mt-2 text-indigo-600 text-sm flex items-center hover:underline"
          >
            ➕ Ajouter un taux horaire
          </button>
        </div>

        <!-- Date de prestation -->
        <div>
          <label for="date" class="block text-sm font-medium text-gray-700">Date de prestation</label>
          <input
            type="date"
            id="date"
            v-model="prestationData.date"
            class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.validationErrors?.date }"
          />
          <p v-if="errors.validationErrors?.date" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.date.join(', ') }}
          </p>
        </div>

        <!-- Nombre d'heures -->
        <div>
          <label for="heures" class="block text-sm font-medium text-gray-700">Nombre d'heures</label>
          <input
            type="number"
            id="heures"
            v-model="prestationData.heures"
            class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.validationErrors?.heures }"
          />
          <p v-if="errors.validationErrors?.heures" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.heures.join(', ') }}
          </p>
        </div>

        <!-- Adresse -->
        <div>
          <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label>
          <input
            type="text"
            id="adresse"
            v-model="prestationData.adresse"
            class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.validationErrors?.adresse }"
          />
          <p v-if="errors.validationErrors?.adresse" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.adresse.join(', ') }}
          </p>
        </div>

        <!-- Horaires -->
        <div>
          <label for="horaires" class="block text-sm font-medium text-gray-700">Horaires</label>
          <input
            type="text"
            id="horaires"
            v-model="prestationData.horaires"
            class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.validationErrors?.horaires }"
          />
          <p v-if="errors.validationErrors?.horaires" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.horaires.join(', ') }}
          </p>
        </div>

        <!-- Boutons -->
        <div class="flex justify-end space-x-3 mt-4">
          <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">
            Annuler
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded flex items-center hover:bg-indigo-700 transition"
            :disabled="prestationData.id ? loading.update : loading.add"
          >
            <span v-if="prestationData.id ? loading.update : loading.add" class="animate-spin mr-2">⏳</span>
            {{ prestationData.id ? 'Mettre à jour' : 'Ajouter' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Modale d'ajout d'un client -->
    <ClientFormModal v-if="showClientModal" @close="closeClientModal" />
    <TauxHoraireFormModal v-if="showTauxHoraireModal" @close="closeTauxHoraireModal" />
  </div>
</template>

<script setup>
import { ref, watchEffect } from 'vue';
import { usePrestationsStore } from '@/stores/prestations';
import { useClientsStore } from '@/stores/clients';
import { useTauxHorairesStore } from '@/stores/taux-horaires';
import { storeToRefs } from 'pinia';
import { ClientFormModal } from '@/components/clients';
import { TauxHoraireFormModal } from '@/components/taux-horaires';

// Définir les propriétés et l'émission d'événements
const props = defineProps({
  prestation: Object,
});
const emit = defineEmits(['close']);

// Accès aux stores
const prestationsStore = usePrestationsStore();
const { addPrestation, updatePrestation, clearErrors } = prestationsStore;
const { errors, loading } = storeToRefs(prestationsStore);

const clientsStore = useClientsStore();
const { fetchClients } = clientsStore;
const { clients } = storeToRefs(clientsStore);

const tauxHorairesStore = useTauxHorairesStore()
const { fetchTauxHoraires } = tauxHorairesStore
const { tauxHoraires } = storeToRefs(tauxHorairesStore)

// Données réactives pour le formulaire
const prestationData = ref({
  id: null,
  date: '',
  heures: '',
  adresse: '',
  horaires: '',
  client_id: '',
  taux_horaire_id: '',
});

// Modale d'ajout d'un client
const showTauxHoraireModal = ref(false);
const openTauxHoraireModal = () => {
  showTauxHoraireModal.value = true;
};
const closeTauxHoraireModal = () => {
  showTauxHoraireModal.value = false;
  fetchTauxHoraires(); // Rafraîchir la liste des taux horaires après ajout
};

const showClientModal = ref(false);
const openClientModal = () => {
  showClientModal.value = true;
};
const closeClientModal = () => {
  showClientModal.value = false;
  fetchClients(); // Rafraîchir la liste des clients après ajout
};

// Initialiser les données avec les props si elles existent (édition) ou avec des valeurs par défaut
watchEffect(() => {
  prestationData.value = props.prestation
    ? { ...props.prestation }
    : { id: null, date: '', heures: '', adresse: '', horaires: '', client_id: '', taux_horaire_id: '' };

  fetchClients();
  fetchTauxHoraires()
});

// Soumission du formulaire
const submitForm = async () => {
  const success = prestationData.value.id
    ? await updatePrestation(prestationData.value)
    : await addPrestation(prestationData.value);
  if (success) {
    closeModal();
  }
};

// Fermeture de la modal
const closeModal = () => {
  clearErrors('validationErrors');
  emit("close");
};
</script>
