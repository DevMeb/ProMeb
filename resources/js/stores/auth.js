import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToast } from "vue-toastification";

export const useAuthStore = defineStore("auth", () => {
  // On se base sur l'objet utilisateur pour déterminer l'authentification
  const user = ref(null);
  const error = ref(null);
  const loading = ref(false);
  const router = useRouter();
  const toast = useToast();

  // L'utilisateur est authentifié si "user" est non null
  const isAuthenticated = computed(() => !!user.value);

  /**
   * Vérifie si l'utilisateur est connecté.
   * Si l'utilisateur est déjà chargé, on retourne true pour éviter des appels réseau superflus.
   */
  async function checkAuth() {
    if (user.value) return true;
    try {
      const response = await axios.get("/api/user");
      user.value = response.data;
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
      // Récupère le cookie CSRF pour initialiser la session
      await axios.get("/sanctum/csrf-cookie");
      // Envoie la requête de connexion
      await axios.post("/api/auth/login", { email, password });
      
      // Met à jour l'état utilisateur en appelant checkAuth()
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
      // Appel à l'endpoint de déconnexion pour invalider la session côté serveur
      await axios.post("/api/auth/logout", {});
    } catch (e) {
      // On peut ignorer l'erreur si l'appel échoue
    }
    user.value = null;
    toast.info("Déconnexion réussie.");
    await router.push("/login");
  }

  return {
    user,
    error,
    loading,
    isAuthenticated,
    checkAuth,
    login,
    logout,
  };
});
