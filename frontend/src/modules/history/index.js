export { useHistoryStore } from "./store.js";

export const historyRoute = {
  path: "history",
  name: "history",
  component: () => import("./HistoryPage.vue"),
  meta: { breadcrumb: "Histórico" },
};
