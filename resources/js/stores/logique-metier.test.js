import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { nextTick } from 'vue';
import axios from 'axios';
import { usePrestationsStore } from '@/stores/prestations';
import { useInvoicesStore } from '@/stores/factures';

vi.mock('axios');

// utils.js appelle useToast() AU CHARGEMENT DU MODULE : sans ce mock, importer
// un store planterait hors application Vue.
vi.mock('@/utils', () => ({
  notify: vi.fn(),
  formatDate: vi.fn(),
  formatNombre: vi.fn(),
  formatEuros: vi.fn(),
  validateEmail: vi.fn(),
}));

beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});

describe('store prestations — logique metier', () => {
  describe('unbilledPrestations', () => {
    it('ne retient que les prestations dont facture_id est null', () => {
      const store = usePrestationsStore();

      store.prestations = [
        { id: 1, facture_id: null },
        { id: 2, facture_id: 7 },
        { id: 3, facture_id: null },
      ];

      expect(store.unbilledPrestations.map(p => p.id)).toEqual([1, 3]);
    });
  });

  describe('filteredPrestations', () => {
    const prestationsDeBase = [
      { id: 1, date: '2026-03-05', client_id: 1, taux_horaire_id: 1 },
      { id: 2, date: '2026-03-20', client_id: 2, taux_horaire_id: 2 },
      { id: 3, date: '2026-04-01', client_id: 1, taux_horaire_id: 2 },
    ];

    it('filtre par month_year', async () => {
      const store = usePrestationsStore();
      store.prestations = prestationsDeBase;
      await nextTick();

      store.updateFilters({ month_year: '2026-03', client_id: '', taux_horaire_id: '' });
      await nextTick();

      expect(store.filteredPrestations.map(p => p.id)).toEqual([1, 2]);
    });

    it('filtre par client_id', async () => {
      const store = usePrestationsStore();
      store.prestations = prestationsDeBase;
      await nextTick();

      store.updateFilters({ month_year: '', client_id: 1, taux_horaire_id: '' });
      await nextTick();

      expect(store.filteredPrestations.map(p => p.id)).toEqual([1, 3]);
    });

    it('filtre par taux_horaire_id', async () => {
      const store = usePrestationsStore();
      store.prestations = prestationsDeBase;
      await nextTick();

      store.updateFilters({ month_year: '', client_id: '', taux_horaire_id: 2 });
      await nextTick();

      expect(store.filteredPrestations.map(p => p.id)).toEqual([2, 3]);
    });

    it('combine les filtres (ET logique)', async () => {
      const store = usePrestationsStore();
      store.prestations = prestationsDeBase;
      await nextTick();

      store.updateFilters({ month_year: '2026-03', client_id: 2, taux_horaire_id: '' });
      await nextTick();

      expect(store.filteredPrestations.map(p => p.id)).toEqual([2]);
    });
  });

  describe('isAnyFilterActive', () => {
    it('est false quand tous les filtres sont des chaines vides', () => {
      const store = usePrestationsStore();

      store.updateFilters({ month_year: '', client_id: '', taux_horaire_id: '' });

      expect(store.isAnyFilterActive).toBe(false);
    });

    it('est true des qu\'un seul filtre est renseigne', () => {
      const store = usePrestationsStore();

      store.updateFilters({ month_year: '2026-03', client_id: '', taux_horaire_id: '' });

      expect(store.isAnyFilterActive).toBe(true);
    });
  });
});

describe('store factures — logique metier', () => {
  describe('filteredInvoices — tri', () => {
    it('trie par id decroissant, meme quand l\'ordre du tableau source le contredit', async () => {
      const store = useInvoicesStore();

      // L'ordre source (2, 5, 1) contredit a la fois l'ordre croissant ET
      // l'ordre decroissant : seul un vrai tri produit [5, 2, 1].
      store.invoices = [
        { id: 2, statut: 'envoyée', prestations: [] },
        { id: 5, statut: 'envoyée', prestations: [] },
        { id: 1, statut: 'envoyée', prestations: [] },
      ];
      await nextTick();

      expect(store.filteredInvoices.map(i => i.id)).toEqual([5, 2, 1]);
    });
  });

  describe('filteredInvoices — filtres', () => {
    const facturesDeBase = [
      {
        id: 1,
        statut: 'payée',
        prestations: [{ client_id: 1, date: '2026-03-10' }],
      },
      {
        id: 2,
        statut: 'envoyée',
        prestations: [{ client_id: 2, date: '2026-04-05' }],
      },
      {
        id: 3,
        statut: 'envoyée',
        prestations: [
          { client_id: 1, date: '2026-01-01' },
          { client_id: 1, date: '2026-04-15' },
        ],
      },
    ];

    it('filtre par statut', async () => {
      const store = useInvoicesStore();
      store.invoices = facturesDeBase;
      await nextTick();

      store.updateFilters({ statut: 'envoyée', client_id: '', month_year: '' });
      await nextTick();

      expect(store.filteredInvoices.map(i => i.id).sort()).toEqual([2, 3]);
    });

    it('filtre par client, en ne regardant que la premiere prestation de la facture', async () => {
      const store = useInvoicesStore();
      store.invoices = facturesDeBase;
      await nextTick();

      store.updateFilters({ statut: '', client_id: 1, month_year: '' });
      await nextTick();

      expect(store.filteredInvoices.map(i => i.id).sort()).toEqual([1, 3]);
    });

    it('filtre par mois : retenue si AU MOINS UNE prestation tombe dans le mois', async () => {
      const store = useInvoicesStore();
      store.invoices = facturesDeBase;
      await nextTick();

      // La facture 3 n'a sa premiere prestation qu'en janvier, mais sa
      // seconde tombe en avril : elle doit malgre tout etre retenue.
      store.updateFilters({ statut: '', client_id: '', month_year: '2026-04' });
      await nextTick();

      expect(store.filteredInvoices.map(i => i.id).sort()).toEqual([2, 3]);
    });

    it('une facture sans prestation ne fait pas planter les filtres', async () => {
      const store = useInvoicesStore();
      store.invoices = [
        ...facturesDeBase,
        { id: 4, statut: 'envoyée', prestations: [] },
        { id: 5, statut: 'envoyée' }, // prestations meme absent du payload
      ];
      await nextTick();

      // Pas d'assertion `.not.toThrow()` ici : updateFilters ne fait qu'une
      // affectation, le filtrage a lieu plus tard dans le watcher — une telle
      // assertion ne pourrait jamais echouer (tautologie). La regression est
      // attrapee par l'assertion finale ci-dessous, qui exerce reellement le
      // filtrage sur les factures 4 et 5.
      store.updateFilters({ statut: '', client_id: 1, month_year: '2026-04' });
      await nextTick();

      // Ni la 4 (prestations vide) ni la 5 (prestations absent) ne matchent
      // le filtre client/mois, mais elles ne font pas planter le calcul.
      expect(store.filteredInvoices.map(i => i.id)).toEqual([3]);
    });
  });
});
