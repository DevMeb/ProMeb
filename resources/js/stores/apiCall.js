import { notify } from '@/utils';

/**
 * Fabrique l'enveloppe des appels API d'un store : chargement, erreurs,
 * notifications.
 *
 * Cette fonction était copiée dans cinq stores. Trois copies étaient identiques
 * caractère pour caractère, une avait divergé — et cette divergence a produit un
 * bug réel (une modale qui se fermait sur un échec, effaçant la saisie).
 *
 * @param {object} refs
 * @param {import('vue').Ref<object>} refs.errors   Les erreurs du store, par opération.
 * @param {import('vue').Ref<object>} refs.loading  Les états de chargement, par opération.
 * @param {boolean} [refs.relancerLesErreurs=false] Relance l'erreur après l'avoir
 *   traitée. Seul le store `auth` l'active : son `updateUser()` compte dessus.
 */
export function creerApiCall({ errors, loading, relancerLesErreurs = false }) {
  function clearErrors(operation) {
    if (operation) {
      errors.value[operation] = null;
    } else {
      errors.value = {};
    }
  }

  function setLoading(operation, state) {
    loading.value[operation] = state;
  }

  /**
   * @param {object} options
   * @param {string} options.operation   Clé sous laquelle chargement et erreur sont rangés.
   * @param {Function} options.request   L'appel axios.
   * @param {Function} [options.onSuccess] Effets de bord au succès. Sa valeur de
   *   retour est IGNORÉE : apiCall retourne toujours la réponse.
   * @param {Function} [options.onError]   Gestion d'erreur sur mesure, qui remplace
   *   le traitement par défaut. Seul `getInvoicePdf` en a besoin (réponse en blob).
   * @returns {Promise<*>} La réponse au succès, `undefined` en cas d'échec.
   */
  async function apiCall({ operation, request, onSuccess, onError }) {
    clearErrors(operation);
    setLoading(operation, true);

    try {
      const response = await request();
      if (onSuccess) onSuccess(response);
      return response;
    } catch (err) {
      if (onError) {
        // `await` : onError peut être asynchrone (getInvoicePdf lit un blob).
        // Sans lui, son message d'erreur serait publié APRÈS qu'apiCall a résolu.
        await onError(err);
      } else if (err.response?.status === 422) {
        errors.value.validationErrors = err.response.data.errors;
      } else {
        errors.value[operation] = err.response?.data?.message || "Une erreur est survenue.";
        notify('error', errors.value[operation]);
      }

      if (relancerLesErreurs) throw err;
    } finally {
      setLoading(operation, false);
    }
  }

  return { apiCall, clearErrors, setLoading };
}
