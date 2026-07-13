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

describe('store factures — apiCall (alignee sur la fabrique partagee)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchInvoices retourne la reponse axios (alignee sur la fabrique, la valeur de retour de onSuccess est ignoree)', async () => {
    // Avant l'alignement, apiCall(factures) faisait
    // `return onSuccess ? onSuccess(response) : response;`. Comme le onSuccess
    // de fetchInvoices ne retournait rien, le resultat etait `undefined`, pas
    // la réponse axios — une trace de la divergence. Desormais apiCall
    // retourne toujours la réponse, comme dans les quatre autres stores.
    axios.get.mockResolvedValue({ data: { factures: [{ id: 1 }] } });
    const store = useInvoicesStore();

    const resultat = await store.fetchInvoices();

    expect(resultat).toEqual({ data: { factures: [{ id: 1 }] } });
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

  it('apiCall retourne la reponse (alignee sur la fabrique) : addInvoice n\'a plus besoin d\'un `return true` explicite', async () => {
    // Avant : apiCall(factures) faisait `return onSuccess ? onSuccess(response) : response;`.
    // addInvoice retournait donc ce que retournait SON onSuccess — d'où un `return true`
    // explicite ajouté en pansement, sans lequel l'appelant recevait `undefined` et
    // FactureFormModal ne pouvait pas distinguer un succès d'un échec : elle se
    // fermait dans les deux cas, effaçant la sélection de prestations de l'utilisateur.
    // Désormais apiCall retourne toujours la réponse — truthy au succès — ce qui
    // suffit à `if (succes) close()`.
    axios.post.mockResolvedValue({ data: { facture: { id: 1 }, message: 'Créée' } });
    const store = useInvoicesStore();

    const resultat = await store.addInvoice({ prestations: [1] });

    expect(resultat).toEqual({ data: { facture: { id: 1 }, message: 'Créée' } });
    expect(resultat).toBeTruthy();
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

  it('monte le chargement a true PENDANT la requete (fetch)', async () => {
    // Meme lacune que sur le store clients : les tests "redescend a false"
    // n'attrapent pas la disparition de setLoading(operation, true), qui vit
    // a l'interieur d'apiCall, en amont du try/finally.
    let resoudre;
    axios.get.mockReturnValue(new Promise((r) => { resoudre = r; }));
    const store = useInvoicesStore();

    const promesse = store.fetchInvoices();
    expect(store.loading.fetch).toBe(true);

    resoudre({ data: { factures: [] } });
    await promesse;

    expect(store.loading.fetch).toBe(false);
  });

  it('monte loading.paid_<id> (cle indexee) a true PENDANT la requete', async () => {
    // Le composant teste `loading[\`paid_${id}\`] === true` : c'est bien cette
    // cle indexee, et non une cle globale "paid", qui doit monter a true en vol.
    let resoudre;
    axios.patch.mockReturnValue(new Promise((r) => { resoudre = r; }));
    const store = useInvoicesStore();

    const promesse = store.paid(12);
    expect(store.loading.paid_12).toBe(true);
    expect(store.loading.paid).toBeUndefined();

    resoudre({ data: { facture: { id: 12, statut: 'payé' }, message: 'Payée' } });
    await promesse;

    expect(store.loading.paid_12).toBe(false);
  });

  it('getInvoicePdf gere ses erreurs via son propre onError, pas la branche generique de apiCall', async () => {
    // getInvoicePdf fournit un onError dédié : la branche générique
    // (validationErrors / errors[operation] + notify) est donc court-circuitée.
    axios.get.mockRejectedValue({
      response: { status: 422, data: {}, headers: {} },
    });
    const store = useInvoicesStore();

    // apiCall (fabrique) attend `onError` : son message d'erreur (calculé via
    // un `await` interne, lecture d'un blob JSON) finit dans errors.pdf/notify
    // avant qu'apiCall ne résolve. apiCall lui-même ne renvoie rien sur échec
    // (`undefined`) ; c'est getInvoicePdf qui traduit cette absence de réponse
    // en chaîne vide, après l'appel.
    const resultat = await store.getInvoicePdf(1);

    expect(resultat).toBe('');
    expect(store.errors.pdf).toBe('Votre profil est incomplet. Complétez vos informations dans les paramètres.');
    // La branche générique n'est jamais atteinte : pas de validationErrors.
    // apiCall vide désormais systématiquement validationErrors (mis à `null`)
    // avant chaque appel, donc la clé existe mais ne porte aucune erreur.
    expect(store.errors.validationErrors).toBeNull();
    expect(notify).toHaveBeenCalledWith('error', store.errors.pdf);
  });

  it('onError est attendu : errors.pdf est pose AVANT que getInvoicePdf resolve', async () => {
    // Un vrai Blob.text() ne resout pas en une microtache. Sans `await onError`,
    // apiCall resout avant qu'onError ait lu le blob : la modale s'affiche vide
    // (ni message, ni spinner), puis l'erreur surgit apres coup.
    axios.get.mockRejectedValue({
      response: {
        status: 403,
        headers: { 'content-type': 'application/json' },
        data: { text: () => new Promise((r) =>
          setTimeout(() => r(JSON.stringify({ message: 'Profil incomplet.' })), 0)) },
      },
    });
    const store = useInvoicesStore();

    await store.getInvoicePdf(1);

    expect(store.errors.pdf).toBe('Profil incomplet.');
  });
});
