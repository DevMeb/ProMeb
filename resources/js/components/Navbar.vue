<template>
  <Disclosure as="nav" class="bg-gray-800" v-slot="{ open }">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
      <div class="relative flex h-16 items-center justify-between">
        
        <!-- Bouton menu mobile -->
        <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
          <DisclosureButton
            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800"
          >
            <span class="sr-only">Ouvrir le menu</span>
            <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true" />
            <XMarkIcon v-else class="block size-6" aria-hidden="true" />
          </DisclosureButton>
        </div>

        <!-- Logo & Navigation -->
        <div class="flex flex-1 items-center justify-center sm:items-stretch sm:justify-start">
          <div class="flex shrink-0 items-center">
            <img
              class="h-8 w-auto"
              src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
              alt="Logo"
            />
          </div>

          <!-- Navigation desktop -->
          <div class="hidden sm:ml-6 sm:block">
            <div class="flex space-x-4">
              <RouterLink
                v-for="item in navigation"
                :key="item.name"
                :to="item.href"
                class="rounded-md px-3 py-2 text-sm font-medium transition duration-200"
                :class="{
                  'bg-gray-900 text-white': isActive(item.href),
                  'text-gray-300 hover:bg-gray-700 hover:text-white': !isActive(item.href)
                }"
                >{{ item.name }}</RouterLink
              >
            </div>
          </div>
        </div>

        <!-- Profil utilisateur ou Connexion -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
          
          <!-- Si l'utilisateur est connecté, afficher le menu utilisateur -->
          <Menu v-if="isAuthenticated" as="div" class="relative ml-3">
            <div>
              <MenuButton class="relative flex items-center space-x-2 bg-gray-800 rounded-full text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                <span class="sr-only">Ouvrir le menu utilisateur</span>
                <img class="size-8 rounded-full" :src="user.avatar || defaultAvatar" alt="Avatar utilisateur" />
                <span class="hidden sm:inline-block text-gray-300 text-sm font-medium">{{ user.name }} {{ user.prenom }}</span>
              </MenuButton>
            </div>

            <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black/5 focus:outline-none">
                <MenuItem v-slot="{ active }">
                  <RouterLink
                    to="/settings"
                    :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                  >
                    ⚙️ Paramètres
                  </RouterLink>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="logout"
                    :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-gray-700']"
                  >
                    🚪 Se déconnecter
                  </button>
                </MenuItem>
              </MenuItems>
            </transition>
          </Menu>

          <!-- Si l'utilisateur n'est pas connecté, afficher le bouton Connexion -->
          <RouterLink v-else to="/login" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 transition">
            🔑 Se connecter
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Navigation mobile -->
    <DisclosurePanel class="sm:hidden">
      <div class="space-y-1 px-2 pt-2 pb-3">
        <DisclosureButton
          v-for="item in navigation"
          :key="item.name"
          as="RouterLink"
          :to="item.href"
          class="block rounded-md px-3 py-2 text-base font-medium transition duration-200"
          :class="{
            'bg-gray-900 text-white': isActive(item.href),
            'text-gray-300 hover:bg-gray-700 hover:text-white': !isActive(item.href)
          }"
        >
          {{ item.name }}
        </DisclosureButton>
      </div>
    </DisclosurePanel>
  </Disclosure>
</template>

<script setup>
import { useRoute, RouterLink } from "vue-router";
import { Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { Bars3Icon, XMarkIcon } from "@heroicons/vue/24/outline";
import { useAuthStore } from "@/stores/auth"; // Importation du store Auth

// Store d'authentification
const authStore = useAuthStore();
const { user, isAuthenticated, logout } = authStore;

// Définition des liens de navigation
const navigation = [
  { name: "Tableau de bord", href: "/" },
  { name: "Taux Horaires", href: "/taux-horaires" },
  { name: "Clients", href: "/clients" },
  { name: "Prestations", href: "/prestations" },
  { name: "Factures", href: "/factures" },
];

// Image par défaut si l'utilisateur n'a pas d'avatar
const defaultAvatar = "https://i.pravatar.cc/300";

// Vérifier si un lien est actif
const route = useRoute();
const isActive = (href) => route.path === href;
</script>

