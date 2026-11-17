export const groupEntryRoutes = [
  {
    path: "/invitations/accept/:token",
    name: "invitations.accept",
    component: () => import("./pages/InvitationAcceptPage.vue"),
  },
  {
    path: "/organizations/select",
    name: "organizations.select",
    component: () => import("./pages/OrganizationSelectPage.vue"),
    meta: { requiresAuth: true },
  },
];

export const groupMemberRoute = {
  path: "organization-members",
  name: "organization-members",
  component: () => import("./pages/OrganizationMemberListPage.vue"),
  meta: {
    permission: "organization-members.view",
    breadcrumb: "Grupo e cuidadores",
  },
};
