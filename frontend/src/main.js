import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "./router";

import "./assets/styles/app.css";

import AppIcon from "@/components/ui/AppIcon/index.vue";
import { useAuthStore } from "@/state/auth.js";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

const authStore = useAuthStore(pinia);

await authStore.hydrate();

app.use(router);

app.component("AppIcon", AppIcon);

await router.isReady();

app.mount("#app");
