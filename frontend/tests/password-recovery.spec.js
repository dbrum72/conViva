import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import PasswordRecoveryPage from "@/views/auth/PasswordRecoveryPage.vue";
import {
  requestPasswordLink,
  resetPassword,
} from "@/services/password-recovery.js";

const { route, clearAuth } = vi.hoisted(() => ({
  route: { name: "password.forgot", fullPath: "/forgot-password", query: {} },
  clearAuth: vi.fn(),
}));
vi.mock("vue-router", () => ({ useRoute: () => route }));
vi.mock("@/state/auth.js", () => ({ useAuthStore: () => ({ clearAuth }) }));
vi.mock("@/services/password-recovery.js", () => ({
  requestPasswordLink: vi.fn(),
  resetPassword: vi.fn(),
}));
const wrappers = [];
beforeEach(() => {
  wrappers.splice(0).forEach((w) => w.unmount());
  setActivePinia(createPinia());
  vi.clearAllMocks();
  Object.assign(route, {
    name: "password.forgot",
    fullPath: "/forgot-password",
    query: {},
  });
});
function page() {
  const wrapper = mount(PasswordRecoveryPage, {
    global: {
      stubs: {
        AuthShell: { template: "<main><slot /></main>" },
        RouterLink: { template: "<a><slot /></a>" },
      },
    },
  });
  wrappers.push(wrapper);
  return wrapper;
}
it("mostra resposta genérica após solicitar recuperação", async () => {
  requestPasswordLink.mockResolvedValue({
    data: { message: "Se houver uma conta, enviaremos instruções." },
  });
  const wrapper = page();
  await wrapper.get('input[type="email"]').setValue("teste@example.test");
  await wrapper.get("form").trigger("submit");
  await flushPromises();
  expect(requestPasswordLink).toHaveBeenCalledWith("teste@example.test");
  expect(wrapper.get('[role="status"]').text()).toContain(
    "Se houver uma conta",
  );
});
it("mantém o formulário e oferece novo link quando o token expira", async () => {
  Object.assign(route, {
    name: "password.reset",
    fullPath: "/reset-password",
    query: { token: "expired", email: "teste@example.test" },
  });
  resetPassword.mockRejectedValue({
    response: { status: 422, data: { errors: { token: ["Link expirado"] } } },
  });
  const wrapper = page();
  await wrapper.get("form").trigger("submit");
  await flushPromises();
  expect(wrapper.get('[role="alert"]').text()).toContain("Link expirado");
  expect(wrapper.text()).toContain("Solicitar novo link");
  expect(clearAuth).not.toHaveBeenCalled();
});
it("limpa a sessão local apenas após redefinição bem-sucedida", async () => {
  Object.assign(route, {
    name: "password.reset",
    fullPath: "/reset-password",
    query: { token: "valid", email: "teste@example.test" },
  });
  resetPassword.mockResolvedValue({ data: { message: "Senha atualizada" } });
  const wrapper = page();
  await wrapper.get("form").trigger("submit");
  await flushPromises();
  expect(clearAuth).toHaveBeenCalledOnce();
  expect(wrapper.text()).toContain("Senha atualizada");
});
