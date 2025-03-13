const loadView = (view) => () => import(`@/views/${view}.vue`);

export default [
  { path: "/", name: "Home", component: loadView("Dashboard"), meta: { requiresAuth: true } },
  { path: "/login", name: "Login", component: loadView("Login"), meta: { guestOnly: true } },
  { path: "/:pathMatch(.*)*", name: "NotFound", component: loadView("NotFound") }, // Page 404
  { path: '/clients', name: 'Client', component: loadView('Client'), meta: { requiresAuth: true } },
  { path: '/prestations', name: 'Prestation', component: loadView('Prestation'), meta: { requiresAuth: true } },
  { path: '/factures', name: 'Facture', component: loadView('Facture'), meta: { requiresAuth: true } },
];