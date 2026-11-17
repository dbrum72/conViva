import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount, flushPromises } from "@vue/test-utils";
import { useExpensesStore } from "@/modules/expenses/state/expenses.js";
import { useAuthStore } from "@/modules/auth";
import { expensesApi } from "@/modules/expenses/api.js";
import { careApi } from "@/modules/care/api/care.js";
import ExpensesPage from "@/modules/expenses/pages/ExpensesPage.vue";
import CareEntryDialog from "@/modules/care/components/CareEntryDialog.vue";
import CarePaymentDialog from "@/modules/expenses/components/CarePaymentDialog.vue";
vi.mock("@/modules/care/api/care.js", () => ({
  careApi: { entries: vi.fn() },
}));
vi.mock("@/modules/expenses/api.js", () => ({
  expensesApi: { list: vi.fn() },
}));
const expense = {
  id: 1,
  care_recipient_id: 2,
  recipient: { id: 2, name: "Luna" },
  title: "Consulta",
  amount_cents: 10000,
  status: "pending",
  expense_group: "confirmed",
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
  expensesApi.list.mockResolvedValue(response());
  const auth = useAuthStore();
  auth.user = { id: 1, name: "Autor" };
  auth.organization = { id: 1, slug: "grupo" };
});
function mountPage() {
  return mount(ExpensesPage, {
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
  expect(expensesApi.list).toHaveBeenCalledTimes(2);
  wrapper.unmount();
});
it("separa propostas e histórico e respeita capacidades de leitura", async () => {
  expensesApi.list.mockResolvedValue({
    data: {
      ...response().data,
      recipients: [{ id: 2, name: "Luna", can_create: false }],
      data: [
        {
          ...expense,
          id: 4,
          title: "Em análise",
          status: "awaiting_approval",
          expense_group: "proposals",
          can_change: false,
          shares: [],
        },
        {
          ...expense,
          id: 5,
          title: "Cancelada",
          status: "cancelled",
          expense_group: "history",
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
  expensesApi.list.mockReturnValue(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const store = useExpensesStore();
  const request = store.loadExpenses();
  store.clear();
  finish(response());
  await request;
  expect(store.expenses).toEqual([]);
  expect(store.expensesRecipients).toEqual([]);
  expect(store.expensesSummary).toBeNull();
});

it("preserva os filtros e os totais ao mudar de página", async () => {
  expensesApi.list.mockImplementation(async ({ page = 1 }) => ({
    data: {
      ...response([{ ...expense, title: `Despesa da página ${page}` }]).data,
      categories: ["Saúde"],
      participants: [{ id: 1, name: "Autor" }],
      pagination: { current_page: page, last_page: 2, total: 21 },
    },
  }));
  const wrapper = mountPage();
  await flushPromises();
  await wrapper.find("#expenses-category").setValue("Saúde");
  await flushPromises();
  await wrapper.find("#expenses-participant").setValue("1");
  await flushPromises();
  await wrapper.find("#expenses-status").setValue("pending");
  await flushPromises();
  await wrapper
    .findAll("button")
    .find((button) => button.text() === "Próxima")
    .trigger("click");
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({
    period: "month",
    category: "Saúde",
    participant: 1,
    status: "pending",
    page: 2,
  });
  expect(wrapper.text()).toContain("Despesa da página 2");
  expect(wrapper.text()).toContain("Página 2 de 2");
  expect(useExpensesStore().expensesSummary.confirmed_cents).toBe(10000);
  wrapper.unmount();
});

it("consulta mês inicial, períodos usuais e intervalo personalizado, mantendo o filtro após salvar", async () => {
  const wrapper = mountPage();
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({ period: "month" });
  await wrapper.find("#expenses-period").setValue("last_month");
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({
    period: "last_month",
  });
  const calls = expensesApi.list.mock.calls.length;
  await wrapper.find("#expenses-period").setValue("custom");
  expect(expensesApi.list).toHaveBeenCalledTimes(calls);
  await wrapper.find("#expenses-from").setValue("2026-09-01");
  await wrapper.find("#expenses-to").setValue("2026-09-15");
  await wrapper.find("form").trigger("submit");
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({
    period: "custom",
    from: "2026-09-01",
    to: "2026-09-15",
  });
  wrapper.findComponent(CareEntryDialog).vm.$emit("saved");
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({
    period: "custom",
    from: "2026-09-01",
    to: "2026-09-15",
  });
  await wrapper.find("#expenses-period").setValue("all");
  await flushPromises();
  expect(expensesApi.list).toHaveBeenLastCalledWith({ period: "all" });
  wrapper.unmount();
});
