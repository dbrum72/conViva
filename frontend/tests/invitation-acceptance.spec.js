import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import InvitationAcceptPage from "@/views/auth/InvitationAcceptPage.vue";
import {
  getInvitationAcceptance,
  acceptOrganizationInvitation,
} from "@/services/organization-invitations.js";

vi.mock("vue-router", () => ({
  RouterLink: { template: "<a><slot /></a>" },
  useRoute: () => ({ params: { token: "synthetic" } }),
  useRouter: () => ({ push: vi.fn(), replace: vi.fn() }),
}));
vi.mock("@/state/auth.js", () => ({
  useAuthStore: () => ({ user: null, clearAuth: vi.fn() }),
}));
vi.mock("@/services/organization-invitations.js", () => ({
  getInvitationAcceptance: vi.fn(),
  acceptOrganizationInvitation: vi.fn(),
}));
let wrapper;
const invitation = {
  email: "synthetic@example.test",
  role: "observador",
  registration_required: false,
  organization: { name: "Grupo sintético" },
  recipients: [{ id: 1, name: "Assistido sintético" }],
  care_accesses: [
    { care_recipient_id: 1, areas: ["documents"], can_edit: false },
  ],
};
beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});
afterEach(() => wrapper?.unmount());
function page() {
  wrapper = mount(InvitationAcceptPage, {
    global: { stubs: { AppLogo: true, RouterLink: true } },
  });
  return wrapper;
}
it("explica perfil, assistido e leitura antes de aceitar via store", async () => {
  getInvitationAcceptance.mockResolvedValue(invitation);
  acceptOrganizationInvitation.mockResolvedValue({
    organization: invitation.organization,
  });
  page();
  await flushPromises();
  expect(wrapper.text()).toContain("Observador");
  expect(wrapper.text()).toContain("Assistido sintético");
  expect(wrapper.text()).toContain("Somente leitura");
  await wrapper.get("form").trigger("submit");
  await flushPromises();
  expect(acceptOrganizationInvitation).toHaveBeenCalledWith("synthetic", {});
  expect(wrapper.text()).toContain("Convite aceito");
});
it("oferece nova tentativa quando a conexão falha", async () => {
  getInvitationAcceptance
    .mockRejectedValueOnce(new Error("offline"))
    .mockResolvedValueOnce(invitation);
  page();
  await flushPromises();
  const retry = wrapper
    .findAll("button")
    .find((button) => button.text() === "Tentar novamente");
  await retry.trigger("click");
  await flushPromises();
  expect(wrapper.text()).toContain("Você foi convidado");
});
it("orienta solicitar novo convite quando o anterior expirou", async () => {
  getInvitationAcceptance.mockRejectedValue({ response: { status: 410 } });
  page();
  await flushPromises();
  expect(wrapper.text()).toContain(
    "Peça à pessoa que convidou você um novo convite",
  );
  expect(wrapper.find("form").exists()).toBe(false);
});
