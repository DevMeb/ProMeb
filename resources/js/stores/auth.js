import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToast } from "vue-toastification";
import { notify } from "@/utils";

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const errors = ref({});
  const loading = ref({});
  const router = useRouter();
  const toast = useToast();

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
    loading.value = true;
    error.value = null;
    try {
      await axios.get("/sanctum/csrf-cookie");
      await axios.post("/api/auth/login", { email, password });
      await checkAuth();
      toast.success("Connexion réussie !");
      await router.push("/");
    } catch (err) {
      error.value = "Nom d’utilisateur ou mot de passe incorrect.";
      toast.error("Échec de la connexion.");
    } finally {
      loading.value = false;
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

  async function apiCall({ operation, request, onSuccess }) {
      clearErrors(operation);
      setLoading(operation, true);
      try {
        const response = await request();
        if (onSuccess) onSuccess(response);
        return response;
      } catch (err) {
        if (err.response?.status === 422) {
          errors.value.validationErrors = err.response.data.errors;
        } else {
          errors.value[operation] = err.response?.data?.message || "Une erreur est survenue.";
          notify('error', errors.value[operation]);
        }
        throw err;
      } finally {
        setLoading(operation, false);
      }
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
