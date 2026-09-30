import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { useCareStore } from "@/state/care";
import { useAuthStore } from "@/state/auth";
import { careApi } from "@/services/care";
import FinancePage from "@/views/care/FinancePage.vue";
import CareEntryDialog from "@/components/care/CareEntryDialog.vue";
import CarePaymentDialog from "@/components/care/CarePaymentDialog.vue";
vi.mock("@/services/care", () => ({
  careApi: { finance: vi.fn(), entries: vi.fn() },
}));
const expense = {
  id: 1,
  care_recipient_id: 2,
  recipient: { id: 2, name: "Luna" },
  title: "Consulta",
  amount_cents: 10000,
  status: "pending",
  finance_group: "confirmed",
  can_change: true,
  proposals: [],
  shares: [
    {
      id: 3,
      user_id: 1,
      user: { name: "Autor" },
      amount_cents: 10000,
      can_pay: true,
    },
  ],
};
const response = (entries = [expense]) => ({
  data: {
    data: entries,
    recipients: [{ id: 2, name: "Luna", can_create: true }],
    summary: { confirmed_cents: 10000, paid_cents: 0, unpaid_cents: 10000 },
  },
});
beforeEach(() => {
  setActivePinia(createPinia());
  vi.resetAllMocks();
  careApi.finance.mockResolvedValue(response());
  const auth = useAuthStore();
  auth.user = { id: 1, name: "Autor" };
  auth.organization = { id: 1, slug: "grupo" };
});
function mountPage() {
  return mount(FinancePage, {
    global: {
      stubs: {
        CareShell: { template: "<main><slot name='actions'/><slot/></main>" },
        CareEntryDialog: true,
        CarePaymentDialog: true,
        AppConfirmDialog: true,
        RouterLink: { template: "<a><slot/></a>" },
      },
    },
  });
}
it("centraliza criação, edição e pagamento e recarrega após salvar", async () => {
  const wrapper = mountPage();
  await flushPromises();
  expect(wrapper.text()).toContain("A pagar");
  expect(careApi.entries).not.toHaveBeenCalled();
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Nova despesa")
    .trigger("click");
  expect(wrapper.findComponent(CareEntryDialog).props("open")).toBe(true);
  expect(
    wrapper.findComponent(CareEntryDialog).props("availableKinds"),
  ).toEqual(["expense"]);
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Editar despesa")
    .trigger("click");
  expect(wrapper.findComponent(CareEntryDialog).props("entry").id).toBe(1);
  await wrapper
    .findAll("button")
    .find((b) => b.text() === "Registrar pagamento")
    .trigger("click");
  expect(
    wrapper.findComponent(CarePaymentDialog).props("payment").share.id,
  ).toBe(3);
  wrapper.findComponent(CarePaymentDialog).vm.$emit("saved");
  await flushPromises();
  expect(careApi.finance).toHaveBeenCalledTimes(2);
  wrapper.unmount();
});
it("separa propostas e histórico e respeita capacidades de leitura", async () => {
  careApi.finance.mockResolvedValue({
    data: {
      ...response().data,
      recipients: [{ id: 2, name: "Luna", can_create: false }],
      data: [
        {
          ...expense,
          id: 4,
          title: "Em análise",
          status: "awaiting_approval",
          finance_group: "proposals",
          can_change: false,
          shares: [],
        },
        {
          ...expense,
          id: 5,
          title: "Cancelada",
          status: "cancelled",
          finance_group: "history",
          can_change: false,
          shares: [],
        },
        {
          ...expense,
          status: "completed",
          can_change: false,
          shares: [
            {
              ...expense.shares[0],
              can_pay: false,
              paid_at: "2026-09-28T12:00:00Z",
            },
          ],
        },
      ],
    },
  });
  const wrapper = mountPage();
  await flushPromises();
  expect(wrapper.text()).toContain("Propostas aguardando aceite");
  expect(wrapper.text()).toContain("Histórico de recusas");
  expect(wrapper.text()).toContain("Quitada");
  expect(wrapper.text()).not.toContain("Cadastro encerrado");
  expect(wrapper.text()).not.toContain("Nova despesa");
  expect(wrapper.text()).not.toContain("Registrar pagamento");
  expect(wrapper.text()).not.toContain("Editar despesa");
  wrapper.unmount();
});
it("descarta totais e despesas antigos após trocar de contexto", async () => {
  let finish;
  careApi.finance.mockReturnValue(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const store = useCareStore();
  const request = store.loadExpenses();
  store.clear();
  finish(response());
  await request;
  expect(store.expenses).toEqual([]);
  expect(store.financeRecipients).toEqual([]);
  expect(store.financeSummary).toBeNull();
});

it("consulta mês inicial, períodos usuais e intervalo personalizado, mantendo o filtro após salvar", async () => {
  const wrapper = mountPage();
  await flushPromises();
  expect(careApi.finance).toHaveBeenLastCalledWith({ period: "month" });
  await wrapper.find("#finance-period").setValue("last_month");
  await flushPromises();
  expect(careApi.finance).toHaveBeenLastCalledWith({ period: "last_month" });
  const calls = careApi.finance.mock.calls.length;
  await wrapper.find("#finance-period").setValue("custom");
  expect(careApi.finance).toHaveBeenCalledTimes(calls);
  await wrapper.find("#finance-from").setValue("2026-09-01");
  await wrapper.find("#finance-to").setValue("2026-09-15");
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(careApi.finance).toHaveBeenLastCalledWith({
    period: "custom",
    from: "2026-09-01",
    to: "2026-09-15",
  });
  wrapper.findComponent(CareEntryDialog).vm.$emit("saved");
  await flushPromises();
  expect(careApi.finance).toHaveBeenLastCalledWith({
    period: "custom",
    from: "2026-09-01",
    to: "2026-09-15",
  });
  await wrapper.find("#finance-period").setValue("all");
  await flushPromises();
  expect(careApi.finance).toHaveBeenLastCalledWith({ period: "all" });
  wrapper.unmount();
});
