import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises, enableAutoUnmount } from "@vue/test-utils";
import { useAuthStore } from "@/state/auth";
import { useCareStore } from "@/state/care";
import { careApi } from "@/services/care";
import AgendaPage from "@/views/care/AgendaPage.vue";
import CareUnavailabilityDialog from "@/components/care/CareUnavailabilityDialog.vue";

vi.mock("vue-router", () => ({ useRouter: () => ({ push: vi.fn() }) }));
vi.mock("@/services/care", () => ({
  careApi: {
    agenda: vi.fn(),
    unavailabilities: vi.fn(),
    saveUnavailability: vi.fn(),
    cancelUnavailability: vi.fn(),
  },
}));
const response = (recipient = { id: 1, name: "Luna", can_manage: true }) => ({
  data: {
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
    timezone: "America/Sao_Paulo",
    executors: [],
    availability_recipient: recipient,
  },
});
const periods = (rows = []) => ({
  data: { data: rows, current_page: 1, last_page: 1 },
});
enableAutoUnmount(afterEach);
beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
  useAuthStore().organization = {
    slug: "one",
    name: "Família",
    timezone: "America/Sao_Paulo",
  };
  careApi.agenda.mockResolvedValue(response());
  careApi.unavailabilities.mockResolvedValue(periods());
});
function page() {
  return mount(AgendaPage, {
    global: {
      stubs: {
        Teleport: true,
        RouterLink: true,
        CareShell: {
          template: '<section><slot name="actions" /><slot /></section>',
        },
      },
    },
  });
}

it("mostra afastamentos em mês, semana, dia e lista sem ações de cuidado, respeitando o término exclusivo", async () => {
  vi.useFakeTimers({ toFake: ["Date"] });
  vi.setSystemTime(new Date("2026-10-02T12:00:00Z"));
  const result = response();
  result.data.unavailabilities = [
    {
      id: "unavailability-1",
      kind: "unavailability",
      title: "Indisponibilidade · Ana",
      participant_name: "Ana",
      due_at: "2026-10-02T11:00:00Z",
      starts_at: "2026-10-02",
      ends_at: "2026-10-03",
      interval_ends_at: "2026-10-04T03:00:00Z",
      timezone: "America/Sao_Paulo",
      reason: "Viagem",
      recipient: { name: "Luna" },
    },
  ];
  careApi.agenda.mockResolvedValue(result);
  const wrapper = page();
  try {
    await flushPromises();
    expect(
      wrapper
        .find('[data-date="2026-10-02"] .agenda-kind-unavailability')
        .exists(),
    ).toBe(true);
    expect(
      wrapper
        .find('[data-date="2026-10-03"] .agenda-kind-unavailability')
        .exists(),
    ).toBe(true);
    expect(
      wrapper
        .find('[data-date="2026-10-04"] .agenda-kind-unavailability')
        .exists(),
    ).toBe(false);
    expect(wrapper.find(".agenda-occurrence").text()).toContain(
      "Motivo: Viagem",
    );
    expect(wrapper.find(".agenda-occurrence-time").text()).toContain("02/10/2026 — 03/10/2026 · Dias completos");
    expect(wrapper.find('[data-date="2026-10-02"] .agenda-preview-time').text()).toBe("Dia inteiro");
    expect(wrapper.find(".agenda-occurrence-actions").exists()).toBe(false);
    for (const label of ["Semana", "Dia", "Lista"]) {
      await wrapper
        .findAll("button")
        .find((button) => button.text() === label)
        .trigger("click");
      await flushPromises();
      expect(wrapper.find(".agenda-kind-unavailability").exists()).toBe(true);
      expect(wrapper.text()).toContain("Indisponibilidade · Ana");
      expect(wrapper.find(".agenda-occurrence-actions").exists()).toBe(false);
    }
  } finally {
    vi.useRealTimers();
  }
});
async function open(wrapper) {
  await flushPromises();
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Minha disponibilidade")
    .trigger("click");
  await flushPromises();
}

it("abre disponibilidade com assistido/grupo e fuso mesmo com agenda vazia e recarrega após salvar", async () => {
  const wrapper = page();
  await open(wrapper);
  const dialog = wrapper.findComponent(CareUnavailabilityDialog);
  expect(dialog.props()).toMatchObject({
    open: true,
    recipientId: 1,
    recipientName: "Luna",
    groupName: "Família",
    timezone: "America/Sao_Paulo",
  });
  expect(careApi.unavailabilities).toHaveBeenCalledWith(1, 1);
  expect(dialog.findAll('input').every((input) => input.attributes('type') === 'date')).toBe(true);
  await dialog.findAll("input")[0].setValue("2026-10-02");
  await dialog.findAll("input")[1].setValue("2026-10-03");
  careApi.saveUnavailability.mockResolvedValue({ data: { id: 1 } });
  await dialog.find("form").trigger("submit");
  await flushPromises();
  expect(careApi.saveUnavailability).toHaveBeenCalledWith(
    1,
    expect.objectContaining({ starts_at: '2026-10-02', ends_at: '2026-10-03', timezone: "America/Sao_Paulo" }),
  );
  expect(careApi.agenda).toHaveBeenCalledTimes(2);
  expect(dialog.props("open")).toBe(true);
});

it("fecha o modal ao trocar o grupo e não usa os períodos anteriores", async () => {
  const wrapper = page();
  await open(wrapper);
  useCareStore().clear();
  careApi.agenda.mockResolvedValue(
    response({ id: 2, name: "Outro assistido", can_manage: true }),
  );
  useAuthStore().organization = {
    slug: "two",
    name: "Outro grupo",
    timezone: "America/Manaus",
  };
  await flushPromises();
  expect(wrapper.findComponent(CareUnavailabilityDialog).props("open")).toBe(
    false,
  );
  await open(wrapper);
  expect(wrapper.findComponent(CareUnavailabilityDialog).props()).toMatchObject(
    {
      recipientId: 2,
      recipientName: "Outro assistido",
      groupName: "Outro grupo",
      timezone: "America/Manaus",
    },
  );
  expect(careApi.unavailabilities).toHaveBeenLastCalledWith(2, 1);
});

it("não oferece o modal sem autorização fornecida pelo servidor", async () => {
  careApi.agenda.mockResolvedValue(response(null));
  const wrapper = page();
  await flushPromises();
  expect(
    wrapper.findAll("button").some((b) => b.text() === "Minha disponibilidade"),
  ).toBe(false);
  expect(careApi.unavailabilities).not.toHaveBeenCalled();
});

it("mantém confirmação de cancelamento e atualiza os avisos da agenda", async () => {
  careApi.unavailabilities.mockResolvedValue(
    periods([
      {
        id: 9,
        can_cancel: true,
        starts_at: "2026-10-02",
        ends_at: "2026-10-03",
        timezone: "America/Sao_Paulo",
        cancelled_at: null,
      },
    ]),
  );
  const wrapper = page();
  await open(wrapper);
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Cancelar período")
    .trigger("click");
  await flushPromises();
  expect(wrapper.text()).toContain("Cancelar minha indisponibilidade");
  expect(careApi.cancelUnavailability).not.toHaveBeenCalled();
  careApi.cancelUnavailability.mockResolvedValue({ data: {} });
  await wrapper
    .find(".app-confirm-dialog__actions")
    .findAll("button")[1]
    .trigger("click");
  await flushPromises();
  expect(careApi.cancelUnavailability).toHaveBeenCalledWith(1, 9);
  expect(careApi.agenda).toHaveBeenCalledTimes(2);
});

it("permite consultar o motivo de outro participante sem oferecer alteração", async () => {
  careApi.agenda.mockResolvedValue(
    response({ id: 1, name: "Luna", can_manage: false }),
  );
  careApi.unavailabilities.mockResolvedValue(
    periods([
      {
        id: 9,
        user: { id: 2, name: "Outro responsável" },
        reason: "Viagem informada",
        can_cancel: false,
        starts_at: "2026-10-02",
        ends_at: "2026-10-03",
        timezone: "America/Sao_Paulo",
        cancelled_at: null,
      },
    ]),
  );
  const wrapper = page();
  await flushPromises();
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Disponibilidade do grupo")
    .trigger("click");
  await flushPromises();
  const dialog = wrapper.findComponent(CareUnavailabilityDialog);
  expect(dialog.text()).toContain("Viagem informada");
  expect(dialog.text()).toContain("Outro responsável");
  expect(dialog.find("form").exists()).toBe(false);
  expect(
    dialog.findAll("button").some((b) => b.text() === "Cancelar período"),
  ).toBe(false);
});
