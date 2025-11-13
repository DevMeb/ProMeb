<template>
  <section
    class="rounded-2xl border border-gray-200 bg-white/80 p-5 shadow-sm backdrop-blur-sm max-w-xl"
  >
    <header class="mb-4 flex items-center justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
          Statut de l’application
        </h2>
        <p class="text-sm text-gray-500">
          Informations PWA pour Plan’Chef (debug / technique).
        </p>
      </div>

      <!-- Badge Online / Offline -->
      <div
        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
        :class="onlineAndConnected ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
      >
        <span
          class="h-2.5 w-2.5 rounded-full"
          :class="onlineAndConnected ? 'bg-emerald-500' : 'bg-rose-500'"
        ></span>
        <span>{{ onlineAndConnected ? 'En ligne et connecté' : 'Hors ligne' }}</span>
      </div>
    </header>

    <div class="grid gap-4 md:grid-cols-3">
      <!-- Bloc Online / Offline -->
      <div class="space-y-2 rounded-xl border border-gray-100 bg-gray-50/60 p-3">
        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-600">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200">
            <span class="text-[10px]">🌐</span>
          </span>
          Connexion
        </h3>
        <p class="text-sm text-gray-600">
          Statut réseau :
          <span
            class="font-medium"
            :class="onlineAndConnected ? 'text-emerald-600' : 'text-rose-600'"
          >
            {{ onlineAndConnected ? 'connecté' : 'hors ligne' }}
          </span>
        </p>
        <p class="text-xs text-gray-400">
          La vérification combine <code>navigator.onLine</code> et une requête distante.
        </p>
      </div>

      <!-- Bloc Installation -->
      <div class="space-y-2 rounded-xl border border-gray-100 bg-gray-50/60 p-3">
        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-600">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200">
            <span class="text-[10px]">📲</span>
          </span>
          Installation
        </h3>

        <div v-if="installEvent" class="space-y-2">
          <p class="text-sm text-gray-600">
            Cette application peut être installée sur ton appareil.
          </p>
          <button
            type="button"
            @click="install"
            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 active:scale-[0.98] transition"
          >
            Installer Plan’Chef
          </button>
        </div>
        <div v-else class="space-y-1">
          <p class="text-sm text-gray-500">
            Impossible de proposer l’installation (ou l’app est déjà installée).
          </p>
          <p class="text-xs text-gray-400">
            Le navigateur ne déclenche pas toujours l’événement d’installation (ex. Safari).
          </p>
        </div>
      </div>

      <!-- Bloc Mise à jour -->
      <div class="space-y-2 rounded-xl border border-gray-100 bg-gray-50/60 p-3">
        <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-600">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200">
            <span class="text-[10px]">🔄</span>
          </span>
          Mises à jour
        </h3>

        <div v-if="showRefresh" class="space-y-2">
          <p class="text-sm text-gray-600">
            Une nouvelle version de Plan’Chef est disponible.
          </p>
          <button
            type="button"
            @click="updateSW(true)"
            class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-400 active:scale-[0.98] transition"
          >
            Mettre à jour maintenant
          </button>
        </div>
        <div v-else class="space-y-1">
          <p class="text-sm text-gray-500">
            Aucune mise à jour n’est nécessaire pour le moment.
          </p>
          <p class="text-xs text-gray-400">
            Le service worker vérifie régulièrement les nouvelles versions.
          </p>
        </div>
      </div>
    </div>

    <!-- Bandeau image offline (optionnel) -->
    <div class="mt-5 rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-3 flex gap-3">
      <div class="flex-1">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-600">
          Contenu hors ligne
        </h3>
        <p class="mt-1 text-sm text-gray-600">
          Certains assets (comme cette image) sont mis en cache par le service worker et restent
          disponibles hors connexion.
        </p>
        <p class="mt-1 text-xs text-gray-400">
          Tu pourras plus tard utiliser cette zone pour expliquer ce qui fonctionne en mode offline.
        </p>
      </div>
      <div class="hidden sm:block w-32 h-20 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <img
          src="../../images/hills.jpg"
          alt="Exemple de contenu hors ligne"
          class="h-full w-full object-cover"
        />
      </div>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { usePwa } from '@/composables/usePwa'
import { watch } from 'vue'

const { installEvent, onlineAndConnected, updateSW, showRefresh } = usePwa()

function install() {
  if (installEvent.value) {
    installEvent.value.prompt()
  }
}

watch(
  () => onlineAndConnected.value,
  (newVal) => {
    if (newVal) {
      console.log('Plan’Chef est maintenant en ligne')
    } else {
      console.log('Plan’Chef est hors ligne')
    }
  }
)
</script>

<style scoped>
/* plus besoin des anciens styles, tout passe par Tailwind */
</style>
