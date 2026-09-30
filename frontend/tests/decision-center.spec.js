import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { createRouter, createMemoryHistory } from "vue-router";
import { useCareStore } from "@/state/care";
import { careApi } from "@/services/care";
import DecisionsPage from "@/views/care/DecisionsPage.vue";
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
  careApi.decisions.mockResolvedValue({
    data: { data: [proposal], current_page: 1, last_page: 1 },
  });
  careApi.decideProfile.mockResolvedValue({});
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/decisions", name: "decisions", component: {} },
      {
        path: "/decisions/:type/:proposal",
        name: "decision",
        component: DecisionPage,
      },
    ],
  });
  await router.push("/decisions/profile/1");
  await router.isReady();
  const wrapper = mount(DecisionsPage, {
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
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Recusar proposta")
    .trigger("click");
  expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true);
  await wrapper.find("textarea").setValue("Manter cadastro atual");
  await wrapper.find("form").trigger("submit");
  expect(careApi.decideProfile).toHaveBeenCalledWith(2, 1, {
    decision: "rejected",
    reason: "Manter cadastro atual",
  });
});

async function central(proposal, url = "/decisions") {
  careApi.decisions.mockResolvedValue({
    data: { data: [proposal], current_page: 1, last_page: 1 },
  });
  careApi.decision.mockResolvedValue({ data: proposal });
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: "/decisions", name: "decisions", component: DecisionsPage },
      {
        path: "/decisions/:type/:proposal",
        name: "decision",
        component: DecisionPage,
      },
    ],
  });
  await router.push(url);
  await router.isReady();
  const wrapper = mount(DecisionsPage, {
    global: {
      plugins: [router],
      stubs: { CareShell: { template: "<div><slot /></div>" } },
    },
  });
  await flushPromises();
  return wrapper;
}
const actionable = {
  id: 8,
  type: "profile",
  care_recipient_id: 2,
  title: "Ficha",
  status: "pending",
  version: 1,
  blockers: [],
  changes: [],
  decisions: [],
  can_accept: true,
  can_reject: true,
};
it("aceita na listagem e atualiza as pendências sem navegar", async () => {
  careApi.decideProfile.mockResolvedValue({});
  const wrapper = await central(actionable);
  careApi.decisions.mockResolvedValue({
    data: { data: [], current_page: 1, last_page: 1 },
  });
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Aceitar proposta")
    .trigger("click");
  await flushPromises();
  expect(careApi.decideProfile).toHaveBeenCalledWith(2, 8, {
    decision: "accepted",
  });
  expect(wrapper.text()).toContain("Seu aceite foi registrado");
  expect(wrapper.text()).toContain("Nenhuma proposta");
});
it("exige motivo não vazio e preserva a justificativa quando a API falha", async () => {
  careApi.decideProfile.mockRejectedValue({
    response: { data: { message: "Tente novamente" } },
  });
  const wrapper = await central(actionable);
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Recusar proposta")
    .trigger("click");
  await wrapper.find("textarea").setValue("   ");
  await wrapper.find("form").trigger("submit");
  expect(careApi.decideProfile).not.toHaveBeenCalled();
  await wrapper.find("textarea").setValue("  Manter a ficha  ");
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(careApi.decideProfile).toHaveBeenCalledWith(2, 8, {
    decision: "rejected",
    reason: "Manter a ficha",
  });
  expect(wrapper.find("textarea").element.value).toBe("  Manter a ficha  ");
  expect(useCareStore().error).toBe("Tente novamente");
});
it("responde a cuidados pela mesma central e não oferece ações sem capacidade", async () => {
  careApi.decide.mockResolvedValue({});
  const wrapper = await central({ ...actionable, type: "entry", entry_id: 4 });
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Aceitar proposta")
    .trigger("click");
  await flushPromises();
  expect(careApi.decide).toHaveBeenCalledWith(2, 4, 8, {
    decision: "accepted",
  });
  wrapper.unmount();
  const readOnly = await central({
    ...actionable,
    can_accept: false,
    can_reject: false,
  });
  expect(readOnly.text()).not.toContain("Aceitar proposta");
  expect(readOnly.text()).not.toContain("Recusar proposta");
});

it("abre a proposta indicada pelo link na central e evita cartão duplicado", async () => {
  const wrapper = await central(
    actionable,
    "/decisions?type=profile&proposal=8",
  );
  expect(careApi.decision).toHaveBeenCalledWith("profile", "8");
  expect(wrapper.text()).toContain("Proposta selecionada pelo link");
  expect(
    wrapper.findAll("button").filter((b) => b.text() === "Aceitar proposta"),
  ).toHaveLength(1);
});

it.each(["accepted", "pending"])(
  "remove a proposta vinculada das minhas pendências após meu aceite (situação global: %s)",
  async (status) => {
    careApi.decideProfile.mockResolvedValue({});
    const wrapper = await central(
      actionable,
      "/decisions?type=profile&proposal=8",
    );
    expect(wrapper.find("select").element.value).toBe("all");
    await wrapper.find("select").setValue("mine");
    await flushPromises();
    careApi.decisions.mockResolvedValue({
      data: { data: [], current_page: 1, last_page: 1 },
    });
    careApi.decision.mockResolvedValue({
      data: { ...actionable, status, can_accept: false, can_reject: false },
    });
    await wrapper
      .findAll("button")
      .find((b) => b.text() === "Aceitar proposta")
      .trigger("click");
    await flushPromises();
    expect(wrapper.text()).toContain("Seu aceite foi registrado");
    expect(wrapper.text()).toContain("Nenhuma proposta para estes filtros");
    expect(
      wrapper.findAll("h2").some((heading) => heading.text() === "Ficha"),
    ).toBe(false);
    expect(careApi.decisions).toHaveBeenLastCalledWith(
      expect.objectContaining({ scope: "mine" }),
    );
    wrapper.unmount();
  },
);

it.each([
  [0, "sent"],
  [1, "rejected"],
  [2, "entry"],
])(
  "não injeta a proposta do link ao mudar o filtro %s",
  async (index, value) => {
    const wrapper = await central(
      actionable,
      "/decisions?type=profile&proposal=8",
    );
    careApi.decisions.mockResolvedValue({
      data: { data: [], current_page: 1, last_page: 1 },
    });
    await wrapper.findAll("select")[index].setValue(value);
    await flushPromises();
    expect(wrapper.text()).toContain("Nenhuma proposta para estes filtros");
    expect(wrapper.text()).not.toContain("Proposta selecionada pelo link");
    wrapper.unmount();
  },
);

it("distingue o aceite automático do autor da proposta ainda pendente de outro participante", async () => {
  const wrapper = await central(
    {
      ...actionable,
      author: { id: 1, name: "Cricilene" },
      author_acceptance: { status: "accepted" },
      can_accept: false,
      can_reject: false,
      decisions: [
        { id: 1, user_id: 2, user: { name: "Dario" }, status: "pending" },
      ],
    },
    "/decisions?type=profile&proposal=8",
  );
  const author = wrapper
    .findAll("li")
    .find((item) => item.text().includes("Cricilene (autor)"));
  expect(author.find(".decision-status-badge--accepted").text()).toBe("Aceita");
  expect(author.text()).toContain("Aceite automático ao enviar a proposta");
  const peer = wrapper
    .findAll("li")
    .find((item) => item.text().includes("Dario"));
  expect(peer.find(".decision-status-badge--pending").text()).toBe(
    "Aguardando aceite",
  );
  expect(wrapper.text()).toContain("Situação da proposta:");
  expect(wrapper.text()).not.toContain("Aceitar proposta");
  wrapper.unmount();
});
