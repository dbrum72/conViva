import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises, enableAutoUnmount } from "@vue/test-utils";
import { defineComponent } from "vue";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { careApi } from "@/services/care";
import AgendaPage from "@/views/care/AgendaPage.vue";
import CareExecutionDialog from "@/components/care/CareExecutionDialog.vue";
import CareEntryDialog from "@/components/care/CareEntryDialog.vue";
import CareScheduleFields from "@/components/care/CareScheduleFields.vue";
import ProposalComparison from "@/components/care/ProposalComparison.vue";
vi.mock("vue-router", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/services/care", () => ({
  careApi: {
    agenda: vi.fn(),
    executeOccurrence: vi.fn(),
    cancelOccurrence: vi.fn(),
  },
}));
const page = (rows = [], extras = {}) => ({
  data: {
    data: rows,
    current_page: 1,
    last_page: 1,
    total: rows.length,
    timezone: "America/Sao_Paulo",
    executors: [],
    ...extras,
  },
});
const occurrence = {
  id: 1,
  entry_id: 2,
  title: "Passeio",
  kind: "task",
  due_at: "2026-10-01T13:00:00Z",
  ends_at: "2026-10-01T14:00:00Z",
  local_date: "2026-10-01",
  timezone: "America/Sao_Paulo",
  status: "scheduled",
  recipient: { name: "Luna" },
  care_recipient_id: 1,
  can_cancel: true,
  can_execute: true,
};
enableAutoUnmount(afterEach);
afterEach(() => vi.useRealTimers());
beforeEach(() => {
  vi.useFakeTimers({ toFake: ["Date"] });
  vi.setSystemTime(new Date("2026-10-01T12:00:00Z"));
  setActivePinia(createPinia());
  vi.clearAllMocks();
  careApi.agenda.mockResolvedValue(page());
});
it("descarta páginas atrasadas e respostas de outro grupo", async () => {
  let first, other;
  careApi.agenda
    .mockReturnValueOnce(new Promise((resolve) => (first = resolve)))
    .mockResolvedValueOnce(page([{ id: 2 }]))
    .mockReturnValueOnce(new Promise((resolve) => (other = resolve)));
  const store = useCareStore();
  const old = store.loadAgenda({ page: 1 });
  await store.loadAgenda({ page: 2 });
  first(page([{ id: 1 }]));
  await old;
  expect(store.agenda).toEqual([{ id: 2 }]);
  const pending = store.loadAgenda();
  store.clear();
  other(page([{ id: 3 }]));
  await pending;
  expect(store.agenda).toEqual([]);
  expect(store.agendaPage).toBeNull();
});
it("não confirma ação da sessão anterior e preserva erro de execução", async () => {
  const store = useCareStore();
  careApi.executeOccurrence.mockRejectedValueOnce({
    response: { data: { message: "Alteração pendente" } },
  });
  await expect(
    store.executeOccurrence(1, { occurred_at: "2026-01-01" }),
  ).rejects.toBeDefined();
  expect(store.error).toBe("Alteração pendente");
  let finish;
  careApi.cancelOccurrence.mockReturnValue(
    new Promise((resolve) => (finish = resolve)),
  );
  const pending = store.cancelOccurrence(1, "future");
  store.clear();
  finish({ data: { id: 1 } });
  expect(await pending).toBeNull();
});
it("edita regra semanal sem alterar o objeto aprovado por referência", async () => {
  const rule = {
    frequency: "weekly",
    timezone: "America/Sao_Paulo",
    local_start: "2026-10-01T10:00",
    until: "2026-10-31",
    duration_minutes: 30,
    weekdays: [1],
  };
  const wrapper = mount(CareScheduleFields, { props: { modelValue: rule } });
  const inputs = wrapper.findAll("input[type=checkbox]");
  await inputs[2].setValue(true);
  expect(wrapper.emitted("update:modelValue")[0][0].weekdays).toEqual([1, 2]);
  expect(rule.weekdays).toEqual([1]);
  await wrapper.find("input[list]").setValue("America/Manaus");
  expect(wrapper.emitted("update:modelValue").at(-1)[0].timezone).toBe(
    "America/Manaus",
  );
});
it("mostra cancelamento futuro e fuso legíveis na comparação da central", () => {
  const wrapper = mount(ProposalComparison, {
    props: {
      proposal: {
        comparison_available: true,
        decisions: [],
        changes: [
          {
            field: "exception",
            before: null,
            after: { scope: "future", starts_at: "2026-10-01T13:00:00Z" },
          },
          {
            field: "schedule",
            before: null,
            after: {
              frequency: "daily",
              local_start: "2026-10-01T10:00",
              timezone: "America/Manaus",
              until: "2026-10-31",
              duration_minutes: 30,
            },
          },
        ],
      },
    },
  });
  expect(wrapper.text()).toContain("Esta e as futuras");
  expect(wrapper.text()).toContain("America/Manaus");
  expect(wrapper.text()).toContain("Diária");
});
const Button = defineComponent({ template: "<button><slot /></button>" });
const Dialog = defineComponent({
  props: ["open"],
  template: '<div v-if="open"><slot /></div>',
});
it("apresenta conflitos, conclusão administrativa e ações autorizadas; envia filtros ao servidor", async () => {
  careApi.agenda.mockResolvedValue(
    page([
      {
        ...occurrence,
        can_execute: false,
        can_cancel: false,
        conflict: true,
        administratively_completed: true,
      },
    ]),
  );
  const wrapper = mount(AgendaPage, {
    global: {
      stubs: {
        Teleport: true,
        Transition: false,
        CareShell: { template: "<div><slot /></div>" },
        AppCard: { template: "<article><slot /></article>" },
        AppButton: Button,
        AppDialog: Dialog,
        RouterLink: { template: "<a><slot /></a>" },
      },
    },
  });
  await flushPromises();
  expect(wrapper.text()).toContain("Sobreposição");
  expect(wrapper.text()).toContain("Cadastro encerrado");
  expect(wrapper.text()).not.toContain("Registrar execução");
  await wrapper.find("#agenda-kind").setValue("task");
  await wrapper.find("#agenda-status").setValue("executed");
  await flushPromises();
  expect(careApi.agenda).toHaveBeenLastCalledWith(
    expect.objectContaining({
      kind: "task",
      status: "executed",
      page: 1,
      per_page: 100,
    }),
  );
});
it("mantém formulário e observação em falha; fecha ao trocar o grupo", async () => {
  careApi.agenda.mockResolvedValue(page([occurrence]));
  careApi.executeOccurrence.mockRejectedValue(new Error("offline"));
  const auth = useAuthStore();
  auth.organization = { slug: "one" };
  const wrapper = mount(AgendaPage, {
    global: {
      stubs: {
        Teleport: true,
        Transition: false,
        CareShell: { template: "<div><slot /></div>" },
        AppCard: { template: "<article><slot /></article>" },
        AppButton: Button,
        AppDialog: Dialog,
        RouterLink: { template: "<a><slot /></a>" },
      },
    },
  });
  await flushPromises();
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Registrar execução")
    .trigger("click");
  await wrapper.find("textarea").setValue("Ocorreu mais cedo");
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(wrapper.find("textarea").element.value).toBe("Ocorreu mais cedo");
  auth.organization = { slug: "two" };
  await flushPromises();
  expect(wrapper.find("textarea").exists()).toBe(false);
});

const pageWrapper = () =>
  mount(AgendaPage, {
    global: {
      stubs: {
        Teleport: true,
        Transition: false,
        RouterLink: { template: "<a><slot /></a>" },
      },
    },
  });
const clickText = async (wrapper, text) => {
  await wrapper
    .findAll("button")
    .find((button) => button.text() === text)
    .trigger("click");
  await flushPromises();
};
it("abre mês com sete colunas, seleciona o dia e navega entre períodos sem precisar aplicar filtros", async () => {
  careApi.agenda.mockResolvedValue(page([occurrence]));
  const wrapper = pageWrapper();
  await flushPromises();
  expect(wrapper.findAll(".agenda-date")).toHaveLength(42);
  expect(
    wrapper.findAll(".agenda-weekdays span").map((day) => day.text()),
  ).toEqual(["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"]);
  expect(wrapper.find(".agenda-date").attributes("data-date")).toBe(
    "2026-09-28",
  );
  expect(wrapper.find(".agenda-selected-day").text()).toContain("Passeio");
  const calls = careApi.agenda.mock.calls.length;
  await wrapper.find('[data-date="2026-10-02"]').trigger("click");
  expect(wrapper.find(".agenda-selected-day").text()).toContain(
    "Nenhum registro neste dia",
  );
  expect(careApi.agenda.mock.calls).toHaveLength(calls);
  await wrapper.find('[aria-label="Próximo período"]').trigger("click");
  await flushPromises();
  expect(wrapper.find(".agenda-period h2").text()).toContain("novembro");
  expect(careApi.agenda).toHaveBeenLastCalledWith(
    expect.objectContaining({ from: "2026-10-26", to: "2026-12-06" }),
  );
  await clickText(wrapper, "Hoje");
  expect(wrapper.find(".agenda-period h2").text()).toContain("outubro");
  await clickText(wrapper, "Semana");
  expect(wrapper.findAll(".agenda-week-day")).toHaveLength(7);
  expect(careApi.agenda).toHaveBeenLastCalledWith(
    expect.objectContaining({ from: "2026-09-28", to: "2026-10-04" }),
  );
  await clickText(wrapper, "Dia");
  expect(wrapper.find(".agenda-list").text()).toContain("Passeio");
  await clickText(wrapper, "Lista");
  expect(wrapper.find(".agenda-list-range").exists()).toBe(true);
  expect(careApi.agenda).toHaveBeenLastCalledWith(
    expect.objectContaining({
      from: "2026-10-01",
      to: "2026-10-31",
      per_page: 50,
    }),
  );
});
it("oferece navegação por setas e resumo de dias com muitos cuidados", async () => {
  careApi.agenda.mockResolvedValue(
    page(
      Array.from({ length: 5 }, (_, i) => ({
        ...occurrence,
        id: i + 1,
        title: `Cuidado ${i + 1}`,
      })),
    ),
  );
  const wrapper = pageWrapper();
  await flushPromises();
  const day = wrapper.find('[data-date="2026-10-01"]');
  expect(day.findAll(".agenda-preview")).toHaveLength(3);
  expect(day.text()).toContain("+2 registros");
  expect(
    wrapper.findAll(".agenda-selected-day .agenda-occurrence"),
  ).toHaveLength(5);
  await day.trigger("keydown", { key: "ArrowRight" });
  expect(
    wrapper.find('[data-date="2026-10-02"]').attributes("aria-pressed"),
  ).toBe("true");
});
it("reúne todas as páginas do calendário e mantém paginação na lista", async () => {
  careApi.agenda
    .mockResolvedValueOnce(page([{ id: 1 }], { last_page: 2, total: 2 }))
    .mockResolvedValueOnce(
      page([{ id: 2 }], { current_page: 2, last_page: 2, total: 2 }),
    );
  const store = useCareStore();
  await store.loadAgenda(
    { from: "2026-10-01", to: "2026-10-31", page: 1, per_page: 100 },
    { allPages: true },
  );
  expect(store.agenda.map((row) => row.id)).toEqual([1, 2]);
  expect(careApi.agenda).toHaveBeenLastCalledWith(
    expect.objectContaining({ page: 2 }),
  );
  careApi.agenda.mockClear();
  careApi.agenda.mockResolvedValue(page([{ id: 1 }], { last_page: 2 }));
  await store.loadAgenda({ page: 1 });
  expect(careApi.agenda).toHaveBeenCalledTimes(1);
});
it("não publica calendário parcial quando outra página falha ou o contexto muda", async () => {
  const store = useCareStore();
  careApi.agenda
    .mockResolvedValueOnce(page([{ id: 1 }], { last_page: 2 }))
    .mockRejectedValueOnce(new Error("offline"));
  await expect(
    store.loadAgenda({ page: 1 }, { allPages: true }),
  ).rejects.toThrow();
  expect(store.agenda).toEqual([]);
  expect(store.agendaPage).toBeNull();
  let finish;
  careApi.agenda
    .mockResolvedValueOnce(page([{ id: 1 }], { last_page: 2 }))
    .mockReturnValueOnce(new Promise((resolve) => (finish = resolve)));
  const pending = store.loadAgenda({ page: 1 }, { allPages: true });
  await flushPromises();
  store.clear();
  finish(page([{ id: 2 }], { current_page: 2, last_page: 2 }));
  await pending;
  expect(store.agenda).toEqual([]);
});
it("posiciona a ocorrência na data do fuso do grupo e inclui sua continuação no dia seguinte", async () => {
  const auth = useAuthStore();
  auth.organization = { slug: "group", timezone: "America/Sao_Paulo" };
  careApi.agenda.mockResolvedValue(
    page([
      {
        ...occurrence,
        due_at: "2026-10-02T02:30:00Z",
        ends_at: "2026-10-02T04:00:00Z",
      },
    ]),
  );
  const wrapper = pageWrapper();
  await flushPromises();
  expect(wrapper.find('[data-date="2026-10-01"]').text()).toContain("23:30");
  expect(wrapper.find('[data-date="2026-10-02"]').text()).toContain("Passeio");
  await wrapper.find('[data-date="2026-10-02"]').trigger("click");
  expect(wrapper.find(".agenda-selected-day").text()).toContain("Passeio");
});

it("usa vermelho apenas quando a API sinaliza vencimento sem execução", async () => {
  careApi.agenda.mockResolvedValue(
    page([
      {
        ...occurrence,
        id: 1,
        kind: "event",
        title: "Vencido",
        is_overdue: true,
      },
      {
        ...occurrence,
        id: 2,
        kind: "event",
        title: "Agendado",
        is_overdue: false,
      },
      {
        ...occurrence,
        id: 3,
        kind: "event",
        title: "Realizado",
        status: "executed",
        is_overdue: false,
      },
    ]),
  );
  const wrapper = pageWrapper();
  await flushPromises();
  expect(wrapper.findAll(".agenda-preview.agenda-is-overdue")).toHaveLength(1);
  expect(wrapper.findAll(".agenda-occurrence.agenda-is-overdue")).toHaveLength(
    1,
  );
  expect(wrapper.find(".agenda-occurrence.agenda-is-overdue").text()).toContain(
    "Vencido · sem execução registrada",
  );
  await clickText(wrapper, "Semana");
  expect(wrapper.findAll(".agenda-week-item.agenda-is-overdue")).toHaveLength(
    1,
  );
});

it("exige escolha explícita para publicar e preserva a escolha na edição", async () => {
  const store = useCareStore();
  const save = vi.spyOn(store, "saveEntry").mockResolvedValue({});
  const auth = useAuthStore();
  auth.user = { id: 1 };
  const wrapper = mount(CareEntryDialog, {
    props: {
      open: true,
      recipientId: 1,
      members: [],
      availableKinds: ["task"],
    },
    global: { stubs: { teleport: true } },
  });
  const publication = () => wrapper.find("#care-publish-to-agenda");
  expect(publication().element.checked).toBe(false);
  await publication().setValue(true);
  await wrapper.find("form").trigger("submit");
  expect(save).toHaveBeenLastCalledWith(
    1,
    expect.objectContaining({ publish_to_agenda: true }),
  );
  await wrapper.setProps({ open: false });
  await wrapper.setProps({
    open: true,
    entry: {
      id: 2,
      kind: "task",
      title: "Publicado",
      publish_to_agenda: true,
      revision: 1,
    },
  });
  expect(publication().element.checked).toBe(true);
  await publication().setValue(false);
  await wrapper.find("form").trigger("submit");
  expect(save).toHaveBeenLastCalledWith(
    1,
    expect.objectContaining({ id: 2, publish_to_agenda: false }),
  );
});

it("envia data e horário informados na execução de cuidado avulso", async () => {
  const store = useCareStore();
  const execute = vi.spyOn(store, "execute").mockResolvedValue({});
  const wrapper = mount(CareExecutionDialog, {
    props: { recipientId: 1, entry: { id: 2, title: "Passeio" } },
    global: { stubs: { teleport: true } },
  });
  await wrapper
    .find('input[type="datetime-local"]')
    .setValue("2026-10-01T09:15");
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(execute).toHaveBeenCalledWith(
    1,
    2,
    "",
    new Date("2026-10-01T09:15").toISOString(),
  );
});

it("usa AppCheckbox em nova despesa e preserva os participantes selecionados", async () => {
  const store = useCareStore();
  const save = vi.spyOn(store, "saveEntry").mockResolvedValue({});
  useAuthStore().user = { id: 1 };
  const wrapper = mount(CareEntryDialog, {
    props: { open: true, recipientId: 1, availableKinds: ["expense"], members: [
      { id: 1, name: "Autor", role: "responsavel" },
      { id: 2, name: "Outro responsável", role: "responsavel" },
    ] },
    global: { stubs: { teleport: true } },
  });
  expect(wrapper.findAll(".app-checkbox")).toHaveLength(2);
  await wrapper.find("#care-affected-member-2").setValue(true);
  await wrapper.find("#care-publish-to-agenda").setValue(true);
  await wrapper.find("form").trigger("submit");
  expect(save).toHaveBeenLastCalledWith(1, expect.objectContaining({ affected_user_ids: [2], publish_to_agenda: true }));
  await wrapper.find("#care-affected-member-2").setValue(false);
  await wrapper.find("form").trigger("submit");
  expect(save).toHaveBeenLastCalledWith(1, expect.objectContaining({ affected_user_ids: [] }));
});
