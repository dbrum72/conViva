import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "./router";

import "./assets/styles/app.css";

import AppIcon from "@/components/ui/AppIcon/index.vue";
import { useAuthStore } from "@/state/auth.js";
import { setSessionExpiredHandler } from "@/services/client.js";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

const authStore = useAuthStore(pinia);
setSessionExpiredHandler(() => {
  authStore.clearAuth();
  if (
    router.currentRoute.value.matched.some((record) => record.meta.requiresAuth)
  ) {
    router.replace({ name: "login", query: { reason: "expired" } });
  }
});

await authStore.hydrate();

app.use(router);

app.component("AppIcon", AppIcon);

await router.isReady();

app.mount("#app");
