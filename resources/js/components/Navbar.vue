<template>
  <Disclosure as="nav" class="bg-gray-800" v-slot="{ open }">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
      <div class="relative flex h-16 items-center justify-between">
        <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
          <!-- Mobile menu button-->
          <DisclosureButton class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:ring-2 focus:ring-white focus:outline-hidden focus:ring-inset">
            <span class="absolute -inset-0.5" />
            <span class="sr-only">Open main menu</span>
            <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true" />
            <XMarkIcon v-else class="block size-6" aria-hidden="true" />
          </DisclosureButton>
        </div>
        <div class="flex flex-1 items-center justify-center sm:items-stretch sm:justify-start">
          <div class="flex shrink-0 items-center">
            <img class="h-8 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company" />
          </div>
          <div class="hidden sm:ml-6 sm:block">
            <div class="flex space-x-4">
              <RouterLink 
                v-for="item in navigation" 
                :key="item.name" 
                :to="item.href" 
                :class="[isActive(item.href) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white', 'rounded-md px-3 py-2 text-sm font-medium']" 
                :aria-current="isActive(item.href) ? 'page' : undefined">
                {{ item.name }}
              </RouterLink>
            </div>
          </div>
        </div>
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
          <!-- Profile dropdown -->
          <Menu as="div" class="relative ml-3">
            <div>
              <MenuButton class="relative flex rounded-full bg-gray-800 text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                <span class="absolute -inset-1.5" />
                <span class="sr-only">Open user menu</span>
                <img class="size-8 rounded-full" :src="user.avatar || defaultAvatar" alt="Avatar utilisateur" />
              </MenuButton>
            </div>
            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
              <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 ring-1 shadow-lg ring-black/5 focus:outline-hidden">
                <MenuItem v-slot="{ active }">
                  <a href="/settings" :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700']">⚙️ Paramètres</a>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <a @click="logout" :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700']">🚪 Se déconnecter</a>
                </MenuItem>
              </MenuItems>
            </transition>
          </Menu>
        </div>
      </div>
    </div>

    <DisclosurePanel class="sm:hidden">
      <div class="space-y-1 px-2 pt-2 pb-3">
        <DisclosureButton 
          v-for="item in navigation" 
          :key="item.name" 
          as="a" 
          :href="item.href" 
          :class="[isActive(item.href) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white', 'block rounded-md px-3 py-2 text-base font-medium']" 
          :aria-current="isActive(item.href) ? 'page' : undefined">
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

