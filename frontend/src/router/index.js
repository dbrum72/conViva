import { createRouter, createWebHistory } from "vue-router";
import { authGuard } from "./guards/auth.js";
import { permissionGuard } from "./guards/permission.js";
import DefaultLayout from "@/layouts/DefaultLayout.vue";
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: "/",
      name: "home",
      component: () => import("@/views/public/LandingPage.vue"),
    },
    {
      path: "/login",
      name: "login",
      component: () => import("@/views/auth/LoginPage.vue"),
      meta: { guestOnly: true },
    },
    {
      path: "/register",
      name: "register",
      component: () => import("@/views/auth/RegisterPage.vue"),
      meta: { guestOnly: true },
    },
    {
      path: "/invitations/accept/:token",
      name: "invitations.accept",
      component: () => import("@/views/auth/InvitationAcceptPage.vue"),
    },
    {
      path: "/organizations/select",
      name: "organizations.select",
      component: () => import("@/views/auth/OrganizationSelectPage.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/",
      component: DefaultLayout,
      meta: { requiresAuth: true, requiresOrganization: true },
      children: [
        {
          path: "dashboard",
          name: "dashboard",
          component: () => import("@/views/care/DashboardPage.vue"),
          meta: { breadcrumb: "Visão geral" },
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
          component: () => import("@/views/care/NotificationsPage.vue"),
          meta: { breadcrumb: "Notificações" },
        },
        {
          path: "organization-members",
          name: "organization-members",
          component: () =>
            import("@/views/organization-members/OrganizationMemberListPage.vue"),
          meta: {
            permission: "organization-members.view",
            breadcrumb: "Grupo e cuidadores",
          },
        },
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
