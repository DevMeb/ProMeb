const loadView = (view) => () => import(`@/views/${view}.vue`);

export default [
  { path: "/", name: "Home", component: loadView("Dashboard"), meta: { requiresAuth: true } },
  { path: "/login", name: "Login", component: loadView("Login"), meta: { guestOnly: true } },
  { path: "/:pathMatch(.*)*", name: "NotFound", component: loadView("NotFound") }, // Page 404
];