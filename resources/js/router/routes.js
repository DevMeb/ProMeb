const loadView = (view) => () => import(`@/views/${view}.vue`);

export default [
  { path: "/", name: "Home", component: loadView("Dashboard"), meta: { requiresAuth: true } },
  { path: "/login", name: "Login", component: loadView("Login"), meta: { guestOnly: true } },
  { path: "/:pathMatch(.*)*", name: "NotFound", component: loadView("NotFound") }, // Page 404
  { path: '/prestations', name: 'PrestationList', component: loadView('PrestationList'), meta: { requiresAuth: true } },
  { path: '/prestations/create', name: 'PrestationCreate', component: loadView('PrestationForm'), meta: { requiresAuth: true } },
  { path: '/prestations/:id', name: 'PrestationDetail', component: loadView('PrestationDetail'), meta: { requiresAuth: true } },
  { path: '/prestations/:id/edit', name: 'PrestationEdit', component: loadView('PrestationForm'), meta: { requiresAuth: true } },
];