import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { useCareStore } from "@/state/care";
import { careApi } from "@/services/care";
import CareUnavailabilityDialog from "@/components/care/CareUnavailabilityDialog.vue";
import AgendaOccurrence from "@/components/care/agenda/AgendaOccurrence.vue";

vi.mock("@/services/care", () => ({
  careApi: {
    unavailabilities: vi.fn(),
    saveUnavailability: vi.fn(),
    cancelUnavailability: vi.fn(),
    entries: vi.fn(),
  },
}));
const page = (rows = []) => ({
  data: { data: rows, current_page: 1, last_page: 1 },
});
beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
  careApi.unavailabilities.mockResolvedValue(page());
  careApi.entries.mockResolvedValue({ data: [] });
});

it("descarta períodos e erros atrasados ao trocar o grupo", async () => {
  let finish;
  careApi.unavailabilities.mockReturnValueOnce(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const store = useCareStore();
  const request = store.loadUnavailabilities(1);
  store.clear();
  finish(page([{ id: 99 }]));
  await request;
  expect(store.unavailabilities).toEqual([]);
  expect(store.unavailabilityPage).toBeNull();
  let fail;
  careApi.unavailabilities.mockReturnValueOnce(
    new Promise((resolve, reject) => {
      fail = reject;
    }),
  );
  const older = store.loadUnavailabilities(1);
  await store.loadUnavailabilities(2);
  fail(new Error("offline"));
  await expect(older).rejects.toThrow();
  expect(store.unavailabilityError).toBe("");
  expect(store.error).toBe("");
});

it("não recarrega períodos do grupo antigo após gravação atrasada", async () => {
  const store = useCareStore();
  await store.loadUnavailabilities(1);
  let finish;
  careApi.saveUnavailability.mockReturnValueOnce(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const request = store.saveUnavailability(1, {});
  store.clear();
  finish({ data: {} });
  expect(await request).toBe(false);
  expect(careApi.unavailabilities).toHaveBeenCalledTimes(1);
  expect(careApi.entries).not.toHaveBeenCalled();
});

it("envia datas sem horários no fuso do grupo e conserva formulário após erro", async () => {
  const wrapper = mount(CareUnavailabilityDialog, {
    global: { stubs: { Teleport: true } },
    props: {
      open: true,
      recipientId: 1,
      recipientName: "Luna",
      groupName: "Família",
      timezone: "America/Sao_Paulo",
    },
  });
  await flushPromises();
  await wrapper.findAll("input")[0].setValue("2026-10-02");
  await wrapper.findAll("input")[1].setValue("2026-10-03");
  await wrapper.find("textarea").setValue("  Compromisso pessoal  ");
  careApi.saveUnavailability.mockRejectedValueOnce({
    response: {
      data: {
        errors: { ends_at: ["O término deve ser posterior ao início."] },
      },
    },
  });
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(wrapper.text()).toContain("O término deve ser posterior");
  expect(wrapper.findAll("input")[0].element.value).toBe("2026-10-02");
  expect(wrapper.find("textarea").element.value).toBe(
    "  Compromisso pessoal  ",
  );
  careApi.saveUnavailability.mockResolvedValueOnce({ data: { id: 1 } });
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(careApi.saveUnavailability).toHaveBeenLastCalledWith(1, {
    starts_at: "2026-10-02",
    ends_at: "2026-10-03",
    timezone: "America/Sao_Paulo",
    reason: "Compromisso pessoal",
  });
  expect(wrapper.text()).toContain("Indisponibilidade registrada");
  expect(wrapper.findAll("input")[0].element.value).toBe("");
  expect(wrapper.find("textarea").element.value).toBe("");
  wrapper.unmount();
});

it("exibe motivo do impedimento na agenda sem oferecer execução", () => {
  const wrapper = mount(AgendaOccurrence, {
    props: {
      timezone: "America/Sao_Paulo",
      item: {
        id: 1,
        kind: "task",
        title: "Passeio",
        status: "scheduled",
        recipient: { name: "Luna" },
        care_recipient_id: 1,
        due_at: "2026-10-02T12:00:00Z",
        ends_at: "2026-10-02T13:00:00Z",
        timezone: "America/Sao_Paulo",
        can_execute: false,
        execution_block: "Execução impedida: você informou indisponibilidade.",
      },
    },
    global: { stubs: { RouterLink: true } },
  });
  expect(wrapper.text()).toContain("Execução impedida");
  expect(wrapper.findAll("button")).toHaveLength(0);
  wrapper.unmount();
});
