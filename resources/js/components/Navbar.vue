<template>
  <Disclosure as="nav" class="bg-gray-800" v-slot="{ open }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 justify-between">
        <div class="flex">
          <div class="-ml-2 mr-2 flex items-center md:hidden">
            <!-- Mobile menu button -->
            <DisclosureButton class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
              <span class="sr-only">Open main menu</span>
              <Bars3Icon v-if="!open" class="block h-6 w-6" aria-hidden="true" />
              <XMarkIcon v-else class="block h-6 w-6" aria-hidden="true" />
            </DisclosureButton>
          </div>
          <div class="flex flex-shrink-0 items-center">
            <!-- <router-link to="/">
              <img class="h-16 w-auto" :src="logoUrl" alt="Hotel Longchamps" />
            </router-link> -->
          </div>
          <div class="hidden md:ml-6 md:flex md:items-center md:space-x-4">
            <router-link
              v-for="item in navigation"
              :key="item.name"
              :to="item.href"
              :class="[
                item.current ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                'rounded-md px-3 py-2 text-sm font-medium'
              ]"
              :aria-current="item.current ? 'page' : undefined"
            >
              {{ item.name }}
            </router-link>
          </div>
        </div>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <button
              @click="logout"
              type="button"
              class="relative inline-flex items-center gap-x-1.5 rounded-md bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </div>

    <DisclosurePanel class="md:hidden">
      <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.href"
          class="block rounded-md px-3 py-2 text-base font-medium"
          :class="item.current ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
          :aria-current="item.current ? 'page' : undefined"
        >
          {{ item.name }}
        </router-link>
      </div>
    </DisclosurePanel>
  </Disclosure>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';

//import logoUrl from '@/../images/hotel-logo.png';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

// Déconnexion asynchrone
const logout = async () => {
  await authStore.logout();
};

// Liste des liens de navigation
const navigation = ref([
  { name: 'Dashboard', href: '/', current: false },
]);

// Met à jour l'état 'current' en fonction du chemin actif
const updateCurrentLink = () => {
  navigation.value.forEach((item) => {
    item.current = item.href === route.path;
  });
};

watch(route, updateCurrentLink, { immediate: true });
</script>
