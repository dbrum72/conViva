export const expensesRoute = {
  path: "expenses",
  name: "expenses",
  component: () => import("./pages/ExpensesPage.vue"),
  meta: { breadcrumb: "Despesas" },
};
