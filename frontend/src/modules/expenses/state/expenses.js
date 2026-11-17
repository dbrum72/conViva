import { defineStore } from "pinia";
import { ref } from "vue";
import { createContextRequests } from "@/state/context-requests.js";
import { expensesApi } from "../api.js";

export const useExpensesStore = defineStore("expenses", () => {
  const { error, pending, version, run, resetRequests } =
    createContextRequests();
  const expenses = ref([]);
  const expensesRecipients = ref([]);
  const expensesSummary = ref(null);
  const expensesPeriod = ref(null);
  const expensesPagination = ref(null);
  const expensesCategories = ref([]);
  const expensesParticipants = ref([]);
  let requestNumber = 0;

  async function loadExpenses(filters = {}) {
    const g = version(),
      request = ++requestNumber;
    expenses.value = [];
    expensesRecipients.value = [];
    expensesSummary.value = null;
    expensesPeriod.value = null;
    const { data } = await run(
      () => expensesApi.list(filters),
      () => request === requestNumber,
    );
    if (g !== version() || request !== requestNumber) return;
    expenses.value = data.data;
    expensesRecipients.value = data.recipients;
    expensesSummary.value = data.summary;
    expensesPeriod.value = data.period;
    expensesPagination.value = data.pagination ?? null;
    expensesCategories.value = data.categories ?? [];
    expensesParticipants.value = data.participants ?? [];
    return expensesPagination.value;
  }
  async function pay(id, entry, share, receipt = null) {
    const g = version();
    await run(() => expensesApi.pay(id, entry, share, receipt));
    return g === version();
  }
  async function downloadPaymentReceipt(id, entry, share) {
    const g = version();
    const { data } = await run(() =>
      expensesApi.paymentReceipt(id, entry, share.id),
    );
    if (g !== version()) return;
    const url = URL.createObjectURL(data);
    const link = document.createElement("a");
    link.href = url;
    link.download = share.receipt.filename;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  function clear() {
    resetRequests();
    expenses.value = [];
    expensesRecipients.value = [];
    expensesSummary.value = null;
    expensesPeriod.value = null;
    expensesPagination.value = null;
    expensesCategories.value = [];
    expensesParticipants.value = [];
  }

  return {
    expenses,
    expensesRecipients,
    expensesSummary,
    expensesPeriod,
    expensesPagination,
    expensesCategories,
    expensesParticipants,
    error,
    pending,
    loadExpenses,
    pay,
    downloadPaymentReceipt,
    clear,
  };
});
