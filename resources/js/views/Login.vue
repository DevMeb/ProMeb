<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <!-- Décommentez la ligne ci-dessous pour afficher le logo -->
      <!-- <img class="mx-auto h-40 w-auto" :src="logoUrl" alt="Hotel Longchamps" /> -->
      <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-white">
        Connectez-vous à votre compte
      </h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form @submit.prevent="handleLogin" class="space-y-6">
        <!-- Champ Email -->
        <div>
          <label for="email" class="block text-sm font-medium leading-6 text-white">Email</label>
          <div class="mt-2">
            <input
              id="email"
              name="email"
              type="email"
              v-model="email"
              required
              class="block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6"
            />
          </div>
        </div>

        <!-- Champ Mot de passe -->
        <div>
          <label for="password" class="block text-sm font-medium leading-6 text-white">Mot de passe</label>
          <div class="mt-2">
            <input
              id="password"
              name="password"
              type="password"
              v-model="password"
              autocomplete="current-password"
              required
              class="block w-full rounded-md border-0 bg-white/5 py-1.5 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6"
            />
          </div>
        </div>

        <!-- Bouton de connexion -->
        <div>
          <button
            type="submit"
            :disabled="authStore.loading.login"
            class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading.login" class="animate-spin mr-2">⏳</span>
            Se connecter
          </button>
        </div>

        <!-- Message d'erreur -->
        <div v-if="authStore.errors.login" class="text-center text-red-500 text-sm">
          {{ authStore.errors['login'] }}
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
// import logoUrl from '@/../images/hotel-logo.png'; // Décommentez si nécessaire

const email = ref("");
const password = ref("");
const authStore = useAuthStore();

const handleLogin = async () => {
  // Réinitialise les erreurs avant la tentative de connexion
  authStore.errors['login'] = null;
  await authStore.login(email.value, password.value);
};
</script>
