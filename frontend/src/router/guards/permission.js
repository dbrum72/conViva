import { useAuthStore } from "@/state/auth.js";
import { permits } from "@/config/settings.js";

export function permissionGuard(to) {
  const authStore = useAuthStore();

  const rules = to.matched
    .map((record) => record.meta)
    .filter((meta) => meta.permission || meta.permissionsAny);

  if (!rules.length) {
    return true;
  }

  if (!authStore.contextLoaded) {
    return {
      name: "organizations.select",

      query: {
        redirect: to.fullPath,
      },
    };
  }

  if (rules.every((rule) => permits(authStore, rule))) {
    return true;
  }

  return {
    name: "dashboard",
  };
}
