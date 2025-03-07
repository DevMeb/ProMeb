<template>
    <div class="bg-gray-900">
      <div class="mx-auto max-w-7xl">
        <div class="bg-gray-900 py-10">
          <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
              <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-white">Factures</h1>
                <p class="mt-2 text-sm text-gray-300">
                  Liste de toutes les factures.
                </p>
              </div>
              <div class="mt-4">
                <button @click="showFormModal = true" class="bg-indigo-500 text-white px-4 py-2 rounded-md">Ajouter une facture</button>
              </div>
            </div>
            <!-- 📋 Liste des factures -->
            <FacturesList />
            <FactureFormModal v-if="showFormModal" @close="showFormModal = false" />
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { FacturesList, FactureFormModal } from '@/components/factures/';

  import { ref } from 'vue';

  const showFormModal = ref(false)
  </script>