import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useTauxHorairesStore } from '@/stores/taux-horaires';
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

describe('store taux-horaires — apiCall (identique a clients)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('retourne la reponse au succes', async () => {
    axios.get.mockResolvedValue({ data: { taux_horaires: [{ id: 1, taux: 50 }] } });
    const store = useTauxHorairesStore();

    const resultat = await store.fetchTauxHoraires();

    expect(resultat).toBeTruthy();
    expect(resultat.data.taux_horaires).toHaveLength(1);
    expect(store.tauxHoraires).toHaveLength(1);
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.get.mockResolvedValue({ data: { taux_horaires: [] } });
    const store = useTauxHorairesStore();

    await store.fetchTauxHoraires();

    expect(store.loading.fetch).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.get.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const store = useTauxHorairesStore();

    await store.fetchTauxHoraires();

    expect(store.loading.fetch).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.post.mockRejectedValue(
      erreurAxios(422, { errors: { taux: ['Le taux est obligatoire.'] } })
    );
    const store = useTauxHorairesStore();

    const resultat = await store.addTauxHoraire({ taux: '' });

    expect(store.errors.validationErrors).toEqual({ taux: ['Le taux est obligatoire.'] });
    expect(notify).not.toHaveBeenCalled();
    expect(resultat).toBeFalsy();
  });

  it('range les autres erreurs dans errors[operation] ET notifie', async () => {
    axios.post.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const store = useTauxHorairesStore();

    const resultat = await store.addTauxHoraire({ taux: 50 });

    expect(store.errors.add).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
    expect(resultat).toBeFalsy();
  });

  it('vide l\'erreur precedente de l\'operation avant un nouvel appel', async () => {
    const store = useTauxHorairesStore();

    axios.post.mockRejectedValueOnce(erreurAxios(500, { message: 'Erreur serveur' }));
    await store.addTauxHoraire({ taux: 50 });
    expect(store.errors.add).toBe('Erreur serveur');

    axios.post.mockResolvedValueOnce({ data: { taux_horaire: { id: 1 }, message: 'Créé' } });
    await store.addTauxHoraire({ taux: 50 });

    expect(store.errors.add).toBeNull();
  });
});
