import { createRouter, createWebHistory } from "vue-router";
import { authGuard, authRoutes } from "@/modules/auth";
import { groupEntryRoutes, groupMemberRoute } from "@/modules/groups";
import { permissionGuard } from "./guards/permission.js";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...authRoutes,
    {
      path: "/",
      name: "home",
      component: () => import("@/views/public/LandingPage.vue"),
    },
    ...groupEntryRoutes,
    {
      path: "/",
      component: DefaultLayout,
      meta: { requiresAuth: true, requiresOrganization: true },
      children: [
        {
          path: "decisions",
          name: "decisions",
          component: () => import("@/views/care/DecisionsPage.vue"),
          meta: { breadcrumb: "Central de decisões" },
        },
        {
          path: "decisions/:type/:proposal",
          name: "decision",
          component: () => import("@/views/care/DecisionPage.vue"),
          meta: { breadcrumb: "Proposta" },
        },
        {
          path: "dashboard",
          name: "dashboard",
          component: () => import("@/views/care/DashboardPage.vue"),
          meta: { breadcrumb: "Hoje" },
        },
        {
          path: "recipients",
          name: "recipients",
          component: () => import("@/views/care/RecipientListPage.vue"),
          meta: { breadcrumb: "Assistidos" },
        },
        {
          path: "recipients/:id",
          name: "recipient",
          component: () => import("@/views/care/RecipientPage.vue"),
          meta: { breadcrumb: "Cuidados" },
        },
        {
          path: "agenda",
          name: "agenda",
          component: () => import("@/views/care/AgendaPage.vue"),
          meta: { breadcrumb: "Agenda" },
        },
        {
          path: "finance",
          name: "finance",
          component: () => import("@/views/care/FinancePage.vue"),
          meta: { breadcrumb: "Despesas" },
        },
        {
          path: "notifications",
          name: "notifications",
          component: () => import("@/modules/notifications/pages/NotificationsPage.vue"),
          meta: { breadcrumb: "Notificações" },
        },
        groupMemberRoute,
        {
          path: "settings",
          name: "settings",
          component: () => import("@/views/care/PermissionsPage.vue"),
          meta: { permission: "roles.view", breadcrumb: "Permissões" },
        },
      ],
    },
    { path: "/:pathMatch(.*)*", redirect: "/dashboard" },
  ],
});
router.beforeEach(authGuard);
router.beforeEach(permissionGuard);
export default router;
