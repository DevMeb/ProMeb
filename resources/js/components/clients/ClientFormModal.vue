<template>
    <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
      <!-- Zone cliquable en arrière-plan pour fermer la modal -->
      <div @click.self="closeModal" class="fixed inset-0"></div>
  
      <div class="bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <span v-if="clientData.id" class="mr-2">✏️</span>
            <span v-else class="mr-2">➕</span>
            {{ clientData.id ? 'Éditer le client' : 'Ajouter un client' }}
          </h2>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700 transition">✖️</button>
        </div>
  
        <form @submit.prevent="submitForm" class="mt-4 space-y-4">
          <!-- Nom du Client -->
          <div>
            <label for="nom" class="block text-sm font-medium text-gray-700">Nom du client *</label>
            <input
              ref="firstInput"
              type="text"
              id="nom"
              v-model="clientData.nom"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.nom }"
            />
            <p v-if="errors.validationErrors?.nom" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.nom.join(', ') }}
            </p>
          </div>
  
          <!-- Adresse -->
          <div>
            <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label>
            <input
              type="text"
              id="adresse"
              v-model="clientData.adresse"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.adresse }"
            />
            <p v-if="errors.validationErrors?.adresse" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.adresse.join(', ') }}
            </p>
          </div>
  
          <!-- Code Postal -->
          <div>
            <label for="code_postal" class="block text-sm font-medium text-gray-700">Code Postal</label>
            <input
              type="text"
              id="code_postal"
              v-model="clientData.code_postal"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.code_postal }"
            />
            <p v-if="errors.validationErrors?.code_postal" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.code_postal.join(', ') }}
            </p>
          </div>
  
          <!-- Ville -->
          <div>
            <label for="ville" class="block text-sm font-medium text-gray-700">Ville</label>
            <input
              type="text"
              id="ville"
              v-model="clientData.ville"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.ville }"
            />
            <p v-if="errors.validationErrors?.ville" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.ville.join(', ') }}
            </p>
          </div>
  
          <!-- Pays -->
          <div>
            <label for="pays" class="block text-sm font-medium text-gray-700">Pays</label>
            <input
              type="text"
              id="pays"
              v-model="clientData.pays"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.pays }"
            />
            <p v-if="errors.validationErrors?.pays" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.pays.join(', ') }}
            </p>
          </div>
  
          <!-- SIREN -->
          <div>
            <label for="siren" class="block text-sm font-medium text-gray-700">SIREN</label>
            <input
              type="text"
              id="siren"
              v-model="clientData.siren"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.siren }"
            />
            <p v-if="errors.validationErrors?.siren" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.siren.join(', ') }}
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
              :disabled="clientData.id ? loading.update : loading.add"
            >
              <span v-if="clientData.id ? loading.update : loading.add" class="animate-spin mr-2">⏳</span>
              {{ clientData.id ? 'Mettre à jour' : 'Ajouter' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watchEffect } from 'vue';
  import { useClientsStore } from '@/stores/clients';
  import { storeToRefs } from 'pinia';
  
  // Définir les propriétés et l'émission d'événements
  const props = defineProps({
    client: Object,
  });
  const emit = defineEmits(['close']);
  
  // Accès au store des clients
  const clientsStore = useClientsStore();
  const { addClient, updateClient, clearErrors } = clientsStore;
  const { errors, loading } = storeToRefs(clientsStore);
  
  // Données réactives pour le formulaire
  const clientData = ref({
    id: null,
    nom: '',
    adresse: '',
    code_postal: '',
    ville: '',
    pays: '',
    siren: '',
  });
  
  // Initialiser les données avec les props si elles existent (édition) ou avec des valeurs par défaut
  watchEffect(() => {
    clientData.value = props.client
      ? { ...props.client }
      : {
          id: null,
          nom: '',
          adresse: '',
          code_postal: '',
          ville: '',
          pays: '',
          siren: '',
        };
  });
  
  // Soumission du formulaire
  const submitForm = async () => {
    const success = clientData.value.id
      ? await updateClient(clientData.value)
      : await addClient(clientData.value);
    if (success) {
      closeModal();
    }
  };
  
  // Fermeture de la modal
  const closeModal = () => {
    clearErrors('validationErrors');
    emit('close');
  };
  </script>
  