import { createRouter, createWebHistory } from "vue-router";
import routes from "./routes"; // Import des routes séparées
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Middleware de navigation
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Si l'utilisateur n'est pas déjà chargé, vérifie l'authentification
  let isAuth = authStore.isAuthenticated;
  if (!isAuth) {
    isAuth = await authStore.checkAuth();
  }

  if (to.meta.requiresAuth && !isAuth) {
    return next({ name: "Login" });
  } else if (to.meta.guestOnly && isAuth) {
    return next({ name: "Dashboard" });
  } else {
    return next();
  }
});

export default router;
