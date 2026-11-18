import { createRouter, createWebHistory } from "vue-router";
import { authGuard, authRoutes } from "@/modules/auth";
import { careRoutes } from "@/modules/care";
import { agendaRoute } from "@/modules/agenda";
import { expensesRoute } from "@/modules/expenses";
import { historyRoute } from "@/modules/history";
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
        ...careRoutes,
        agendaRoute,
        expensesRoute,
        historyRoute,
        {
          path: "notifications",
          name: "notifications",
          component: () => import("@/modules/notifications/pages/NotificationsPage.vue"),
          meta: { breadcrumb: "Notificações" },
        },
        groupMemberRoute,
      ],
    },
    { path: "/:pathMatch(.*)*", redirect: "/dashboard" },
  ],
});
router.beforeEach(authGuard);
router.beforeEach(permissionGuard);
export default router;
