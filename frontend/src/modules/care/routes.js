export const careRoutes = [
  {
    path: "decisions",
    name: "decisions",
    component: () => import("./pages/DecisionsPage.vue"),
    meta: { breadcrumb: "Central de decisões" },
  },
  {
    path: "decisions/:type/:proposal",
    name: "decision",
    component: () => import("./pages/DecisionPage.vue"),
    meta: { breadcrumb: "Proposta" },
  },
  {
    path: "dashboard",
    name: "dashboard",
    component: () => import("./pages/DashboardPage.vue"),
    meta: { breadcrumb: "Hoje" },
  },
  {
    path: "recipients",
    name: "recipients",
    component: () => import("./pages/RecipientListPage.vue"),
    meta: { breadcrumb: "Assistidos" },
  },
  {
    path: "recipients/:id",
    name: "recipient",
    component: () => import("./pages/RecipientPage.vue"),
    meta: { breadcrumb: "Cuidados" },
  },
  {
    path: "settings",
    name: "settings",
    component: () => import("./pages/PermissionsPage.vue"),
    meta: { permission: "roles.view", breadcrumb: "Permissões" },
  },
];
