<template>
    <div class="container mx-auto max-w-3xl p-6 bg-gray-800 text-white rounded-lg shadow-lg mt-4">
      <!-- Titre -->
      <h2 class="text-3xl font-semibold text-center mb-6 flex items-center justify-center gap-2">
        <span class="text-indigo-400 text-4xl">👤</span>
      </h2>
  
      <p class="text-gray-300 text-center mb-6">
        Ces informations seront utilisées pour la facturation.
      </p>
  
      <form @submit.prevent="submitProfileUpdate" class="space-y-6">
        <!-- Grid Layout pour les champs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Nom -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🏷️</span> Nom :
            </label>
            <input type="text" v-model="userData.name" class="input-field" />
            <p v-if="errors.validationErrors?.name" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.name.join(', ') }}
            </p>
          </div>
  
          <!-- Prénom -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">📝</span> Prénom :
            </label>
            <input type="text" v-model="userData.prenom" class="input-field" />
            <p v-if="errors.validationErrors?.prenom" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.prenom.join(', ') }}
            </p>
          </div>
  
          <!-- Email -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">📧</span> Email :
            </label>
            <input type="email" v-model="userData.email" class="input-field cursor-not-allowed opacity-50" disabled />
          </div>
  
          <!-- Adresse -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">📍</span> Adresse :
            </label>
            <input type="text" v-model="userData.adresse" class="input-field" />
            <p v-if="errors.validationErrors?.adresse" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.adresse.join(', ') }}
            </p>
          </div>
  
          <!-- Code Postal -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🏙️</span> Code Postal :
            </label>
            <input type="text" v-model="userData.code_postal" class="input-field" />
            <p v-if="errors.validationErrors?.code_postal" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.code_postal.join(', ') }}
            </p>
          </div>
  
          <!-- Ville -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🌆</span> Ville :
            </label>
            <input type="text" v-model="userData.ville" class="input-field" />
            <p v-if="errors.validationErrors?.ville" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.ville.join(', ') }}
            </p>
          </div>
  
          <!-- Pays -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🌍</span> Pays :
            </label>
            <input type="text" v-model="userData.pays" class="input-field" />
            <p v-if="errors.validationErrors?.pays" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.pays.join(', ') }}
            </p>
          </div>
  
          <!-- Téléphone -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">📞</span> Téléphone :
            </label>
            <input type="text" v-model="userData.telephone" class="input-field" />
            <p v-if="errors.validationErrors?.telephone" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.telephone.join(', ') }}
            </p>
          </div>
  
          <!-- SIREN -->
          <div>
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🏢</span> SIREN :
            </label>
            <input type="text" v-model="userData.siren" class="input-field" />
            <p v-if="errors.validationErrors?.siren" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.siren.join(', ') }}
            </p>
          </div>
  
          <!-- Nom de la société -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium flex items-center gap-2">
              <span class="text-indigo-400">🏛️</span> Nom de la société :
            </label>
            <input type="text" v-model="userData.nom_societe" class="input-field" />
            <p v-if="errors.validationErrors?.nom_societe" class="text-red-500 text-xs mt-1">
            {{ errors.validationErrors.nom_societe.join(', ') }}
            </p>
          </div>
        </div>
  
        <!-- Boutons -->
        <div class="flex justify-center gap-4 mt-6">
          <button type="submit" class="btn-primary flex items-center" :disabled="loading.update">
            <span v-if="loading.update" class="animate-spin mr-2">⏳</span>
            💾 Sauvegarder
          </button>
        </div>
      </form>
    </div>
</template>
  
  <script setup>
  import { ref, onMounted } from "vue";
  import { useAuthStore } from "@/stores/auth";
  import { storeToRefs } from "pinia";
  
  const authStore = useAuthStore();
  const { user, loading, errors } = storeToRefs(authStore);
  const { updateUser } = authStore;
  
  const userData = ref({
    name: "",
    prenom: "",
    email: "",
    adresse: "",
    code_postal: "",
    ville: "",
    pays: "",
    telephone: "",
    siren: "",
    nom_societe: "",
  });
  
  // Charger les infos utilisateur lors du montage du composant
  onMounted(() => {
    if (user.value) {
      userData.value = { ...user.value };
    }
  });
  
  // Soumission du formulaire
  const submitProfileUpdate = async () => {
    await updateUser(userData.value);
  };
  </script>
  
  <style scoped>
  .input-field {
    width: 100%;
    padding: 0.5rem;
    border-radius: 5px;
    border: 1px solid #ccc;
    background-color: #2d2d2d;
    color: white;
  }
  
  .btn-primary {
    background-color: #4f46e5;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    cursor: pointer;
  }
  
  .btn-secondary {
    background-color: #374151;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    cursor: pointer;
  }
  </style>
  