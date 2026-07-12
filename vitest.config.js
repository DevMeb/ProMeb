import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';

// Config dédiée, distincte de vite.config.js : celui-ci charge trois plugins
// (Laravel, PWA, Tailwind) inutiles ici et sources de fragilité.
export default defineConfig({
  test: {
    // Aucun rendu de composant : les stores n'ont pas besoin d'un DOM.
    environment: 'node',
    include: ['resources/js/**/*.test.js'],
  },
  resolve: {
    alias: {
      // Dans l'application, cet alias est injecté par laravel-vite-plugin,
      // et non déclaré dans vite.config.js. Vitest ne le verrait donc pas.
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
});
