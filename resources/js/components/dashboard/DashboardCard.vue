<template>
  <div
    :class="`bg-gradient-to-tr ${gradient} p-6 rounded-xl shadow-xl transform transition-all hover:scale-[1.02] hover:cursor-pointer hover:shadow-2xl relative overflow-hidden`"
  >
    <!-- Lueur subtile -->
    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>

    <div class="flex items-center mb-4 relative z-10">
      <span class="text-4xl mr-3 drop-shadow-md">
        {{ icon }}
      </span>
      <h2 class="text-xl font-semibold text-white drop-shadow-md">{{ title }}</h2>
    </div>
    
   
    <!-- Affichage normal pour les autres cartes -->
    <p class="text-4xl font-bold text-white mb-2 drop-shadow-md relative z-10">
      <span :class="{'line-through text-gray-400': title === 'CA facturé' && taxe > 0 }">
        {{ formattedValue }}
      </span>
      <span class="pl-4" v-if="title === 'CA facturé' && taxe > 0">
        {{ afterTaxe }} €
      </span>
      <span v-if="isDifference" :class="`text-lg ml-2 ${differenceColor}`">
        {{ differenceIcon }}
      </span>
    </p>
    
    <p class="text-sm text-white/80 relative z-10">{{ description }}</p>

    <!-- Badge moderne -->
    <div
      v-if="title === 'CA facturé' && taxe > 0"
      class="absolute top-3 right-3 backdrop-blur-sm bg-red-400 text-white/90 text-xs font-medium px-3 py-1.5 rounded-full shadow-sm border border-red-500"
    >
      -{{ taxe }} %
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  value: [String, Number],
  description: String,
  icon: String,
  gradient: String,
  badge: Number,
  taxe: Number,
  afterTaxe: Number,
  isDifference: Boolean,
  cursor: Boolean,
});

const formattedValue = computed(() => {
  return props.title.includes('CA') 
    ? `${Number(props.value).toLocaleString('fr-FR')} €` 
    : props.value;
});

const differenceColor = computed(() => {
  return props.value >= 0 ? 'text-green-300' : 'text-red-300';
});

const differenceIcon = computed(() => {
  return props.value >= 0 ? '▵' : '▿'; // Caractères Unicode plus discrets
});

const badgeColor = computed(() => {
  if (props.badge.includes('✔')) return 'border-green-600/30 text-green-300';
  if (props.badge.includes('⚠')) return 'border-orange-500/30 text-amber-300';
  return 'border-gray-500/30';
});
</script>