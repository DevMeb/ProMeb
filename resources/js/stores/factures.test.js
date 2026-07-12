import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useInvoicesStore } from '@/stores/factures';
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

// factures.js importe useDashboardStore : setActivePinia(createPinia()) suffit
// a le faire s'instancier normalement, pas besoin de le mocker.

describe('store factures — apiCall (la divergence)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchInvoices retourne la reponse au succes (comportement standard, comme onSuccess ne retourne rien)', async () => {
    axios.get.mockResolvedValue({ data: { factures: [{ id: 1 }] } });
    const store = useInvoicesStore();

    const resultat = await store.fetchInvoices();

    // fetchInvoices : onSuccess ne retourne rien (undefined), donc apiCall
    // renvoie `onSuccess(response)` = undefined, PAS la réponse axios.
    // C'est déjà une trace de la divergence : seule addInvoice s'en sort
    // grâce à son `return true` explicite.
    expect(resultat).toBeUndefined();
    expect(store.invoices).toHaveLength(1);
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.get.mockResolvedValue({ data: { factures: [] } });
    const store = useInvoicesStore();

    await store.fetchInvoices();

    expect(store.loading.fetch).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.get.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const store = useInvoicesStore();

    await store.fetchInvoices();

    expect(store.loading.fetch).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.post.mockRejectedValue(
      erreurAxios(422, { errors: { prestations: ['Déjà facturée.'] } })
    );
    const store = useInvoicesStore();

    await store.addInvoice({ prestations: [1] });

    expect(store.errors.validationErrors).toEqual({ prestations: ['Déjà facturée.'] });
    expect(notify).not.toHaveBeenCalled();
  });

  it('range les autres erreurs dans errors[operation] ET notifie', async () => {
    axios.post.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const store = useInvoicesStore();

    const resultat = await store.addInvoice({ prestations: [1] });

    expect(store.errors.add).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
    expect(resultat).toBeFalsy();
  });

  it('DIVERGENCE : apiCall retourne le resultat de onSuccess, pas la reponse', async () => {
    // Les quatre autres stores font `if (onSuccess) onSuccess(response); return response;`
    // Celui-ci fait `return onSuccess ? onSuccess(response) : response;`
    // addInvoice retourne donc ce que retourne SON onSuccess — un `true` explicite,
    // ajouté précisément parce que sans lui l'appelant recevait `undefined` et ne
    // pouvait pas distinguer un succès d'un échec.
    axios.post.mockResolvedValue({ data: { facture: { id: 1 }, message: 'Créée' } });
    const store = useInvoicesStore();

    const resultat = await store.addInvoice({ prestations: [1] });

    expect(resultat).toBe(true); // et NON la réponse axios
  });

  it('addInvoice retourne une valeur falsy en cas d\'echec', async () => {
    axios.post.mockRejectedValue({ response: { status: 422, data: { errors: { prestations: ['Déjà facturée.'] } } } });
    const store = useInvoicesStore();

    const resultat = await store.addInvoice({ prestations: [1] });

    // C'est ce qui permet à la modale de ne PAS se fermer sur un échec.
    expect(resultat).toBeFalsy();
    expect(store.errors.validationErrors).toEqual({ prestations: ['Déjà facturée.'] });
  });

  it('paid indexe la cle de chargement par facture', async () => {
    // Une clé unique ("paid") ferait clignoter le bouton de TOUTES les lignes.
    axios.patch.mockResolvedValue({ data: { facture: { id: 12, statut: 'payé' }, message: 'Payée' } });
    const store = useInvoicesStore();

    await store.paid(12);

    expect(store.loading.paid_12).toBe(false); // la clé existe, indexée
    expect(store.loading.paid).toBeUndefined(); // et surtout : PAS de clé globale
  });

  it('getInvoicePdf gere ses erreurs via son propre onError, pas la branche generique de apiCall', async () => {
    // getInvoicePdf fournit un onError dédié : la branche générique
    // (validationErrors / errors[operation] + notify) est donc court-circuitée.
    axios.get.mockRejectedValue({
      response: { status: 422, data: {}, headers: {} },
    });
    const store = useInvoicesStore();

    // Autre bizarrerie propre a apiCall(factures) : dans le catch, `onError(err)`
    // est appelé SANS await. Le message d'erreur qu'il calcule (via un `await`
    // interne) finit bien dans errors.pdf/notify, mais la valeur de retour de
    // ce onError ('') n'est elle-même jamais renvoyée par apiCall : le catch ne
    // fait rien de son résultat. getInvoicePdf résout donc à `undefined` sur
    // échec, pas à la chaîne '' que onError retourne.
    const resultat = await store.getInvoicePdf(1);

    expect(resultat).toBeUndefined();
    expect(store.errors.pdf).toBe('Votre profil est incomplet. Complétez vos informations dans les paramètres.');
    // La branche générique n'est jamais atteinte : pas de validationErrors.
    expect(store.errors.validationErrors).toBeUndefined();
    expect(notify).toHaveBeenCalledWith('error', store.errors.pdf);
  });
});
