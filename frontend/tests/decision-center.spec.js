import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { createRouter, createMemoryHistory } from "vue-router";
import { useCareStore } from "@/state/care";
import { careApi } from "@/services/care";
import DecisionPage from "@/views/care/DecisionPage.vue";
vi.mock("@/services/care", () => ({
  careApi: {
    decisions: vi.fn(),
    decision: vi.fn(),
    decideProfile: vi.fn(),
    withdrawProfile: vi.fn(),
    decide: vi.fn(),
    proposeProfile: vi.fn(),
    recipients: vi.fn(),
  },
}));
beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});

it("descarta detalhe e listagem recebidos depois de trocar de grupo", async () => {
  let detail, list;
  careApi.decision.mockReturnValue(
    new Promise((resolve) => {
      detail = resolve;
    }),
  );
  careApi.decisions.mockReturnValue(
    new Promise((resolve) => {
      list = resolve;
    }),
  );
  const store = useCareStore();
  const requests = [store.loadDecision("profile", 1), store.loadDecisions()];
  store.clear();
  detail({ data: { id: 1 } });
  list({ data: { data: [{ id: 1 }] } });
  await Promise.all(requests);
  expect(store.decision).toBeNull();
  expect(store.decisions).toBeNull();
});

it("não permite que uma página antiga substitua o filtro mais recente", async () => {
  let old;
  careApi.decisions
    .mockReturnValueOnce(
      new Promise((resolve) => {
        old = resolve;
      }),
    )
    .mockResolvedValueOnce({ data: { data: [{ id: 2 }] } });
  const store = useCareStore(),
    first = store.loadDecisions({ scope: "mine" });
  await store.loadDecisions({ scope: "sent" });
  old({ data: { data: [{ id: 1 }] } });
  await first;
  expect(store.decisions.data[0].id).toBe(2);
});

it("não recarrega uma proposta do grupo anterior após resposta atrasada", async () => {
  let done;
  careApi.decideProfile.mockReturnValue(
    new Promise((resolve) => {
      done = resolve;
    }),
  );
  const store = useCareStore(),
    pending = store.respondToProposal(
      { type: "profile", id: 1, care_recipient_id: 2 },
      { decision: "accepted" },
    );
  store.clear();
  done({});
  await pending;
  expect(careApi.decision).not.toHaveBeenCalled();
});

it("mantém a proposta e expõe conflito sem presumir aceite", async () => {
  const store = useCareStore();
  store.decision = {
    type: "profile",
    id: 1,
    care_recipient_id: 2,
    status: "pending",
  };
  careApi.decideProfile.mockRejectedValue({
    response: { status: 409, data: { message: "A rede mudou." } },
  });
  await expect(
    store.respondToProposal(store.decision, { decision: "accepted" }),
  ).rejects.toBeDefined();
  expect(store.decision.status).toBe("pending");
  expect(store.error).toBe("A rede mudou.");
});

it("exibe comparação, bloqueio e recusa motivada sem oferecer aceite bloqueado", async () => {
  const proposal = {
    id: 1,
    type: "profile",
    title: "Cadastro",
    version: 1,
    status: "pending",
    care_recipient_id: 2,
    author: { id: 3, name: "Autor" },
    decisions: [
      { id: 1, user_id: 4, status: "pending", user: { name: "Responsável" } },
    ],
    blockers: [{ message: "A rede de responsáveis mudou." }],
    comparison_available: true,
    changes: [{ field: "name", before: "Nome atual", after: "Nome proposto" }],
    can_accept: false,
    can_reject: true,
    can_withdraw: false,
    has_current_version: true,
  };
  careApi.decision.mockResolvedValue({ data: proposal });
  careApi.decideProfile.mockResolvedValue({});
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/decisions", name: "decisions", component: {} },
      { path: "/decisions/:type/:proposal", component: DecisionPage },
    ],
  });
  await router.push("/decisions/profile/1");
  await router.isReady();
  const wrapper = mount(DecisionPage, {
    global: {
      plugins: [router],
      stubs: { CareShell: { template: "<div><slot /></div>" } },
    },
  });
  await flushPromises();
  expect(wrapper.text()).toContain("Nome atual");
  expect(wrapper.text()).toContain("Nome proposto");
  expect(wrapper.text()).toContain("A rede de responsáveis mudou.");
  expect(wrapper.text()).not.toContain("Aceitar proposta");
  expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true);
  await wrapper.find("textarea").setValue("Manter cadastro atual");
  await wrapper.find("form").trigger("submit");
  expect(careApi.decideProfile).toHaveBeenCalledWith(2, 1, {
    decision: "rejected",
    reason: "Manter cadastro atual",
  });
});
