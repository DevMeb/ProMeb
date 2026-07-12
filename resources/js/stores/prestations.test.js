import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { usePrestationsStore } from '@/stores/prestations';
import { notify } from '@/utils';

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

/** Construit une erreur axios telle que le store la reçoit. */
function erreurAxios(status, data = {}) {
  return { response: { status, data } };
}

describe('store prestations — apiCall (identique a clients)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('retourne la reponse au succes', async () => {
    axios.get.mockResolvedValue({ data: { prestations: [{ id: 1, heures: 7 }] } });
    const store = usePrestationsStore();

    const resultat = await store.fetchPrestations();

    expect(resultat).toBeTruthy();
    expect(resultat.data.prestations).toHaveLength(1);
    expect(store.prestations).toHaveLength(1);
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.get.mockResolvedValue({ data: { prestations: [] } });
    const store = usePrestationsStore();

    await store.fetchPrestations();

    expect(store.loading.fetch).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.get.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const store = usePrestationsStore();

    await store.fetchPrestations();

    expect(store.loading.fetch).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.post.mockRejectedValue(
      erreurAxios(422, { errors: { heures: ['Les heures sont obligatoires.'] } })
    );
    const store = usePrestationsStore();

    const resultat = await store.addPrestation({ heures: '' });

    expect(store.errors.validationErrors).toEqual({ heures: ['Les heures sont obligatoires.'] });
    expect(notify).not.toHaveBeenCalled();
    expect(resultat).toBeFalsy();
  });

  it('range les autres erreurs dans errors[operation] ET notifie', async () => {
    axios.post.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const store = usePrestationsStore();

    const resultat = await store.addPrestation({ heures: 7 });

    expect(store.errors.add).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
    expect(resultat).toBeFalsy();
  });

  it('vide l\'erreur precedente de l\'operation avant un nouvel appel', async () => {
    const store = usePrestationsStore();

    axios.post.mockRejectedValueOnce(erreurAxios(500, { message: 'Erreur serveur' }));
    await store.addPrestation({ heures: 7 });
    expect(store.errors.add).toBe('Erreur serveur');

    axios.post.mockResolvedValueOnce({ data: { prestation: { id: 1 }, message: 'Créée' } });
    await store.addPrestation({ heures: 7 });

    expect(store.errors.add).toBeNull();
  });
});
