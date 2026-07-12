import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useClientsStore } from '@/stores/clients';
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

describe('store clients — apiCall (le comportement de référence)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('retourne la reponse au succes', async () => {
    axios.get.mockResolvedValue({ data: { clients: [{ id: 1, nom: 'EBS' }] } });
    const store = useClientsStore();

    const resultat = await store.fetchClients();

    // Le comportement de référence : apiCall retourne la RÉPONSE.
    expect(resultat).toBeTruthy();
    expect(resultat.data.clients).toHaveLength(1);
    expect(store.clients).toHaveLength(1);
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.get.mockResolvedValue({ data: { clients: [] } });
    const store = useClientsStore();

    await store.fetchClients();

    expect(store.loading.fetch).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.get.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const store = useClientsStore();

    await store.fetchClients();

    expect(store.loading.fetch).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.post.mockRejectedValue(
      erreurAxios(422, { errors: { nom: ['Le nom est obligatoire.'] } })
    );
    const store = useClientsStore();

    const resultat = await store.addClient({ nom: '' });

    expect(store.errors.validationErrors).toEqual({ nom: ['Le nom est obligatoire.'] });
    // Un 422 ne déclenche AUCUN toast : c'est ce qui a rendu un bloc d'erreur
    // inaffichable dans la modale de facture.
    expect(notify).not.toHaveBeenCalled();
    expect(resultat).toBeFalsy();
  });

  it('range les autres erreurs dans errors[operation] ET notifie', async () => {
    axios.post.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const store = useClientsStore();

    const resultat = await store.addClient({ nom: 'EBS' });

    expect(store.errors.add).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
    expect(resultat).toBeFalsy();
  });

  it('vide l\'erreur precedente de l\'operation avant un nouvel appel', async () => {
    const store = useClientsStore();

    axios.post.mockRejectedValueOnce(erreurAxios(500, { message: 'Erreur serveur' }));
    await store.addClient({ nom: 'EBS' });
    expect(store.errors.add).toBe('Erreur serveur');

    axios.post.mockResolvedValueOnce({ data: { client: { id: 1 }, message: 'Créé' } });
    await store.addClient({ nom: 'EBS' });

    expect(store.errors.add).toBeNull();
  });
});
