// @vitest-environment jsdom
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { reactive } from "vue";
import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from "vitest";
import { historyRoute, useHistoryStore } from "@/modules/history";

const { client, auth } = vi.hoisted(() => ({
  client: { get: vi.fn(), post: vi.fn() },
  auth: { current: null },
}));
vi.mock("@/modules/http", () => ({ authenticatedClient: client }));
vi.mock("@/modules/auth", () => ({ useAuthStore: () => auth.current }));

const filters = {
  from: "2026-11-01",
  to: "2026-11-18",
  area: "routine",
  timezone: "America/Sao_Paulo",
};
const report = {
  id: 3,
  status: "pending",
  format: "pdf",
  filters,
  requested_at: "2026-11-18T14:00:00Z",
};
const response = (fields = {}) => ({
  data: {
    areas: ["routine", "health"],
    filters,
    events: [],
    page: 1,
    has_more: false,
    export: null,
    ...fields,
  },
});
const row = {
  id: "execution:9",
  title: "Título da ocorrência",
  type: "correction",
  area: "health",
  status: "completed",
  author: "Responsável",
  recorded_at: "2026-11-18T14:00:00Z",
  occurred_at: "2026-11-18T10:00:00Z",
  version: 1,
  operation: "save",
  related_id: 2,
  outcome: "administered",
  reason: "Motivo comprovado",
  notes: "Observação",
};
const stubs = {
  CareShell: {
    props: ["title", "requestStatus"],
    template:
      '<section><h1>{{ title }}</h1><p role="alert">{{ requestStatus.error }}</p><button @click="$emit(\'retry\')">Repetir</button><slot /></section>',
  },
  AppForm: { template: "<form><slot /></form>" },
  AppButton: { template: "<button><slot /></button>" },
  AppCard: {
    props: ["title"],
    template: "<div><h2>{{title}}</h2><slot /></div>",
  },
  AppDate: {
    props: ["modelValue"],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
};

describe("Histórico de cuidados", () => {
  let pinia;
  let wrapper;
  let page;
  const button = (label) =>
    wrapper.findAll("button").find((item) => item.text() === label);

  beforeAll(async () => {
    ({ default: page } = await historyRoute.component());
  }, 30000);

  beforeEach(() => {
    vi.resetAllMocks();
    auth.current = reactive({ organization: { id: 1 } });
    pinia = createPinia();
    setActivePinia(pinia);
  });

  afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  async function openPage() {
    wrapper = mount(page, { global: { plugins: [pinia], stubs } });
    await flushPromises();
  }

  it("consulta, filtra, navega e exibe os dados de uma correção", async () => {
    client.get
      .mockResolvedValueOnce(
        response({
          events: [
            row,
            { ...row, id: "execution:10", recorded_at: "2026-11-18T03:30:00Z" },
            { ...row, id: "execution:11", recorded_at: "2026-11-18T02:30:00Z" },
          ],
          has_more: true,
        }),
      )
      .mockResolvedValueOnce(
        response({
          page: 2,
          events: [
            {
              ...row,
              version: null,
              related_id: null,
              occurred_at: null,
              reason: null,
              notes: null,
              outcome: null,
            },
          ],
        }),
      )
      .mockResolvedValueOnce(response())
      .mockResolvedValueOnce(response({ filters: { ...filters, area: null } }));
    await openPage();
    expect(wrapper.get(".history-event-category").text()).toBe(
      "Correção · Saúde",
    );
    expect(wrapper.get(".history-event .history-badge").text()).toBe(
      "Registrado",
    );
    expect(
      wrapper.findAll(".history-day > h3").map((day) => day.text()),
    ).toEqual(["18 de novembro de 2026", "17 de novembro de 2026"]);
    expect(wrapper.get(".history-day").findAll(".history-event")).toHaveLength(
      2,
    );
    expect(wrapper.text()).toContain("Motivo comprovado");
    expect(wrapper.text()).toContain("Ocorreu em 18/11/2026, 07:00");
    await button("Próxima").trigger("click");
    await flushPromises();
    expect(wrapper.text()).not.toContain("Motivo comprovado");
    await button("Anterior").trigger("click");
    await flushPromises();
    expect(wrapper.text()).toContain("Nenhum evento neste período.");
    await wrapper.get("select").setValue("");
    await wrapper.get("#history-from").setValue("2026-11-02");
    await wrapper.get("#history-to").setValue("2026-11-17");
    await wrapper.get("form").trigger("submit");
    await flushPromises();
    expect(client.get).toHaveBeenLastCalledWith("/history", {
      params: { from: "2026-11-02", to: "2026-11-17", area: null, page: 1 },
    });
    expect(wrapper.text()).toContain("Todas as áreas disponíveis");
    expect(wrapper.get("select").element.selectedOptions[0].text).toBe(
      "Todas as áreas disponíveis",
    );
    expect(wrapper.get(".history-summary").text()).toContain(
      "01/11/2026 a 18/11/2026",
    );
  });

  it("solicita o PDF, consulta o estado e baixa o relatório", async () => {
    const file = new Blob(["conteúdo"]);
    client.get
      .mockResolvedValueOnce(response({ export: report }))
      .mockResolvedValueOnce({ data: { ...report, status: "ready" } })
      .mockResolvedValueOnce({ data: file });
    client.post.mockResolvedValueOnce({ data: report });
    const objectUrl = vi.fn().mockReturnValue("blob:history");
    const revoke = vi.fn();
    vi.stubGlobal("URL", {
      createObjectURL: objectUrl,
      revokeObjectURL: revoke,
    });
    const click = vi
      .spyOn(HTMLAnchorElement.prototype, "click")
      .mockImplementation(() => {});
    await openPage();
    const store = useHistoryStore();
    expect(store.report.id).toBe(3);
    expect(button("Solicitar CSV")).toBeUndefined();
    await button("Solicitar PDF").trigger("click");
    await flushPromises();
    expect(client.post).toHaveBeenCalledWith("/history/exports", {
      from: filters.from,
      to: filters.to,
      area: "routine",
      format: "pdf",
    });
    await button("Verificar exportação").trigger("click");
    await flushPromises();
    expect(client.get).toHaveBeenCalledWith("/history/exports/3");
    vi.useFakeTimers({ toFake: ["setTimeout"] });
    await button("Baixar").trigger("click");
    await flushPromises();
    expect(client.get).toHaveBeenCalledWith("/history/exports/3/download", {
      responseType: "blob",
    });
    expect(objectUrl).toHaveBeenCalledWith(file);
    expect(click.mock.contexts[0].download).toBe("historico-conviva-3.pdf");
    expect(wrapper.get('[role="status"]').text()).toContain(
      "Solicitado em 18/11/2026, 11:00",
    );
    vi.advanceTimersByTime(1000);
    expect(revoke).toHaveBeenCalledWith("blob:history");
    expect(wrapper.get('[role="status"]').text()).toContain("PDF");

    client.get.mockResolvedValueOnce({ data: file });
    store.report = { id: 2, status: "ready", format: "csv", filters };
    await flushPromises();
    expect(wrapper.get('[role="status"]').text()).not.toContain(
      "Solicitado em",
    );
    await button("Baixar").trigger("click");
    await flushPromises();
    expect(click.mock.contexts[1].download).toBe("historico.csv");
    vi.advanceTimersByTime(1000);
  });

  it("permite tentar novamente e redefine os filtros quando muda o grupo", async () => {
    client.get
      .mockRejectedValueOnce(new Error("offline"))
      .mockResolvedValue(response());
    await openPage();
    expect(wrapper.get('[role="alert"]').text()).toContain("Não foi possível");
    await button("Repetir").trigger("click");
    await flushPromises();
    expect(wrapper.get('[role="alert"]').text()).toBe("");
    auth.current.organization = null;
    await flushPromises();
    expect(client.get).toHaveBeenCalledTimes(2);
    auth.current.organization = { id: 2 };
    await flushPromises();
    expect(client.get).toHaveBeenLastCalledWith("/history", {
      params: { from: "", to: "", area: null, page: 1 },
    });
  });

  it("descarta respostas da organização anterior, inclusive downloads", async () => {
    const store = useHistoryStore();
    let resolve;
    const wait = () =>
      new Promise((done) => {
        resolve = done;
      });
    client.get.mockImplementationOnce(wait);
    const firstLoad = store.load();
    store.clear();
    resolve(response());
    await expect(firstLoad).resolves.toBeUndefined();

    client.get.mockResolvedValue(response({ export: report }));
    await store.load();
    client.post.mockImplementationOnce(wait);
    const pending = store.requestExport();
    store.clear();
    resolve({ data: report });
    await pending;
    expect(store.report).toBeNull();

    await store.load();
    client.get.mockImplementationOnce(wait);
    const refresh = store.refreshExport();
    store.clear();
    resolve({ data: report });
    await refresh;
    expect(store.report).toBeNull();

    await store.load();
    client.get.mockImplementationOnce(wait);
    const download = store.download();
    store.clear();
    const objectUrl = vi.fn();
    vi.stubGlobal("URL", { createObjectURL: objectUrl });
    resolve({ data: new Blob(["privado"]) });
    await download;
    expect(objectUrl).not.toHaveBeenCalled();
    expect(store.timeline).toBeNull();
  });
});
