import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { createRouter, createMemoryHistory } from "vue-router";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { careApi } from "@/services/care";
import HeaderBar from "@/components/layout/HeaderBar/index.vue";
import menu from "@/config/menu";
vi.mock("@/services/care", () => ({
  careApi: { unreadCount: vi.fn(), read: vi.fn() },
}));
beforeEach(() => {
  setActivePinia(createPinia());
  vi.resetAllMocks();
});
it("atualiza o total após leitura sem limitar à lista carregada", async () => {
  const store = useCareStore();
  careApi.unreadCount
    .mockResolvedValueOnce({ data: { unread_count: 105 } })
    .mockResolvedValueOnce({ data: { unread_count: 104 } });
  careApi.read.mockResolvedValue({});
  await store.refreshUnreadNotifications();
  expect(store.unreadNotifications).toBe(105);
  store.notifications = [{ id: 1, read_at: null }];
  await store.read(1);
  expect(store.unreadNotifications).toBe(104);
  expect(store.notifications[0].read_at).toBeTruthy();
});
it("descarta contagens atrasadas e limpa o total ao trocar de contexto", async () => {
  let resolve;
  careApi.unreadCount
    .mockReturnValueOnce(
      new Promise((r) => {
        resolve = r;
      }),
    )
    .mockResolvedValueOnce({ data: { unread_count: 2 } });
  const store = useCareStore();
  const old = store.refreshUnreadNotifications();
  await store.refreshUnreadNotifications();
  resolve({ data: { unread_count: 9 } });
  await old;
  expect(store.unreadNotifications).toBe(2);
  careApi.unreadCount.mockReturnValueOnce(
    new Promise((r) => {
      resolve = r;
    }),
  );
  const pending = store.refreshUnreadNotifications();
  store.clear();
  resolve({ data: { unread_count: 7 } });
  await pending;
  expect(store.unreadNotifications).toBeNull();
});
it("usa o sino com contador acessível e remove o item da sidebar", async () => {
  const auth = useAuthStore();
  auth.user = { id: 1, name: "Responsável" };
  auth.organization = { id: 1, slug: "grupo" };
  careApi.unreadCount.mockResolvedValue({ data: { unread_count: 12 } });
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/", component: {} },
      { path: "/notifications", name: "notifications", component: {} },
    ],
  });
  await router.push("/");
  const wrapper = mount(HeaderBar, { global: { plugins: [router] } });
  await flushPromises();
  expect(menu.some((item) => item.name === "notifications")).toBe(false);
  expect(wrapper.find(".app-header-bar__badge").text()).toBe("12");
  expect(
    wrapper.find(".app-header-bar__notifications").attributes("aria-label"),
  ).toContain("12 não lidas");
  await wrapper.find(".app-header-bar__notifications").trigger("click");
  await flushPromises();
  expect(router.currentRoute.value.name).toBe("notifications");
  useCareStore().unreadNotifications = 0;
  expect(
    (await wrapper.vm.$nextTick(),
    wrapper.find(".app-header-bar__badge").exists()),
  ).toBe(false);
  wrapper.unmount();
});
