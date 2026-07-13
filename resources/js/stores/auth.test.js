import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
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

// auth.js, contrairement aux trois autres stores, appelle useRouter() et
// useToast() DIRECTEMENT (pas via @/utils) au niveau racine de son setup().
// Il faut donc mocker ces deux modules explicitement, sinon Vue avertit
// (« inject() can only be used inside setup() ») et router/toast sont
// inutilisables dans les chemins qui les invoquent réellement (login, logout).
const pushMock = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
}));

const toastMock = { success: vi.fn(), error: vi.fn(), info: vi.fn(), warning: vi.fn() };
vi.mock('vue-toastification', () => ({
  useToast: () => toastMock,
}));

/** Construit une erreur axios telle que le store la reçoit. */
function erreurAxios(status, data = {}) {
  return { response: { status, data } };
}

describe('store auth — login/logout (leur PROPRE gestion d\'erreur, pas apiCall)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('DECOUVERTE : login n\'appelle PAS apiCall — il a son propre try/catch', async () => {
    // Contrairement à ce qu'on pourrait attendre (login = juste une opération
    // apiCall de plus), login() est écrit à la main : pas de clearErrors(),
    // pas de setLoading() via apiCall, pas de notify(), pas de rethrow.
    // Seul updateUser() passe par apiCall dans ce store.
    axios.get.mockResolvedValue({}); // /sanctum/csrf-cookie
    axios.post.mockRejectedValue(erreurAxios(401, { message: 'Identifiants invalides' }));
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    const resultat = await store.login('a@b.c', 'mauvais');

    // Pas de rethrow ici (à l'inverse de apiCall) : la promesse se résout.
    expect(resultat).toBeUndefined();
    // Le message est CODÉ EN DUR dans login, il ignore err.response.data.message.
    expect(store.errors.login).toBe('Nom d’utilisateur ou mot de passe incorrect.');
    // login notifie via le `toast` importé directement, jamais via notify().
    expect(notify).not.toHaveBeenCalled();
    expect(toastMock.error).toHaveBeenCalledWith('Échec de la connexion.');
  });

  it('login (succes) authentifie, notifie et redirige vers "/"', async () => {
    axios.get
      .mockResolvedValueOnce({}) // /sanctum/csrf-cookie
      .mockResolvedValueOnce({ data: { user: { id: 1, email: 'a@b.c' } } }); // checkAuth -> /api/user
    axios.post.mockResolvedValue({});
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    await store.login('a@b.c', 'bon-mdp');

    expect(store.user).toEqual({ id: 1, email: 'a@b.c' });
    expect(toastMock.success).toHaveBeenCalledWith('Connexion réussie !');
    expect(pushMock).toHaveBeenCalledWith('/');
  });

  it('logout efface l\'utilisateur et redirige, meme si l\'appel API echoue', async () => {
    // Le catch de logout est vide (`catch (e) {}`) : un échec réseau n'empêche
    // ni la déconnexion locale, ni le toast, ni la redirection.
    axios.post.mockRejectedValue(new Error('reseau indisponible'));
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    await store.logout();

    expect(store.user).toBeNull();
    expect(toastMock.info).toHaveBeenCalledWith('Déconnexion réussie.');
    expect(pushMock).toHaveBeenCalledWith('/login');
  });
});

describe('store auth — apiCall (via updateUser, la seule operation qui l\'utilise)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('retourne la reponse au succes', async () => {
    axios.put.mockResolvedValue({ data: { user: { id: 1 }, message: 'Mis à jour' } });
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    const resultat = await store.updateUser({ id: 1 });

    expect(resultat).toBeTruthy();
    expect(resultat.data.user).toEqual({ id: 1 });
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.put.mockResolvedValue({ data: { user: { id: 1 }, message: 'Mis à jour' } });
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    await store.updateUser({ id: 1 });

    expect(store.loading.update).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.put.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    await store.updateUser({ id: 1 });

    expect(store.loading.update).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.put.mockRejectedValue(erreurAxios(422, { errors: { email: ['Email invalide.'] } }));
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    const resultat = await store.updateUser({ id: 1 });

    expect(store.errors.validationErrors).toEqual({ email: ['Email invalide.'] });
    expect(notify).not.toHaveBeenCalled();
    expect(resultat).toBeFalsy();
  });

  it('updateUser ne relance plus : il retourne une valeur falsy en cas d\'echec', async () => {
    // auth relançait ses erreurs (throw) parce qu'apiCall ne retournait rien
    // d'exploitable. Depuis l'extraction d'apiCall, il retourne la réponse :
    // l'appelant teste la valeur, comme dans les quatre autres stores.
    axios.put.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();

    const resultat = await store.updateUser({ id: 1 });

    expect(resultat).toBeFalsy();
    expect(store.errors.update).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
  });

  it('updateUser met a jour store.user', async () => {
    // Ancien bug : dans updateUser(user), le parametre `user` masquait la ref
    // `user` du store. onSuccess faisait `user.value = response.data.user` :
    // ça posait .value sur le PARAMETRE (un objet simple sans .value
    // observable), pas sur la ref du store. store.user restait donc inchange
    // apres un updateUser reussi. Le parametre a ete renomme pour ne plus
    // masquer la ref : ce test verifie desormais le comportement corrige.
    axios.put.mockResolvedValue({ data: { user: { id: 1, email: 'nouveau@mail.c' }, message: 'Mis à jour' } });
    const { useAuthStore } = await import('@/stores/auth');
    const store = useAuthStore();
    store.user = { id: 1, email: 'ancien@mail.c' };

    await store.updateUser({ id: 1, email: 'nouveau@mail.c' });

    // store.user reflete le NOUVEL objet renvoye par l'API.
    expect(store.user).toEqual({ id: 1, email: 'nouveau@mail.c' });
  });
});
