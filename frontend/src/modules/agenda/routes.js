export const agendaRoute = {
  path: "agenda",
  name: "agenda",
  component: () => import("./pages/AgendaPage.vue"),
  meta: { breadcrumb: "Agenda" },
};
