import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToast } from "vue-toastification";
import { notify } from "@/utils";
import { creerApiCall } from "@/stores/apiCall";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const errors = ref({});
  const loading = ref({});
  const router = useRouter();
  const toast = useToast();

  // relancerLesErreurs : ce store relance l'erreur après l'avoir traitée.
  // C'est updateUser() — la seule opération de ce store qui passe par apiCall —
  // qui en dépend. login() a son propre try/catch et ne passe pas par ici.
  const { apiCall, clearErrors, setLoading } = creerApiCall({
    errors,
    loading,
    relancerLesErreurs: true,
  });

  const isAuthenticated = computed(() => !!user.value);

  /** Vérifie si l'utilisateur est connecté */
  async function checkAuth() {
    if (user.value) return true;
    try {
      const response = await axios.get("/api/user");
      user.value = response.data.user;
      return true;
    } catch (err) {
      user.value = null;
      return false;
    }
  }

  /** Connexion utilisateur */
  async function login(email, password) {
    loading.value['login'] = true;
    errors.value['login'] = null;
    try {
      await axios.get("/sanctum/csrf-cookie");
      await axios.post("/api/auth/login", { email, password });
      await checkAuth();
      toast.success("Connexion réussie !");
      await router.push("/");
    } catch (err) {
      errors.value['login'] = "Nom d’utilisateur ou mot de passe incorrect.";
      toast.error("Échec de la connexion.");
    } finally {
      loading.value['login'] = false;
    }
  }

  /** Déconnexion utilisateur */
  async function logout() {
    try {
      await axios.post("/api/auth/logout", {});
    } catch (e) {}
    user.value = null;
    toast.info("Déconnexion réussie.");
    await router.push("/login");
  }

  async function updateUser(user) {
    return apiCall({
      operation: 'update',
      request: () => axios.put(`/api/user/`, user),
      onSuccess: (response) => {
        user.value = response.data.user;
        notify('success', response.data.message);
      },
    });
  }

  return {
    user,
    errors,
    loading,
    isAuthenticated,
    checkAuth,
    login,
    logout,
    updateUser,
    clearErrors,
  };
});
