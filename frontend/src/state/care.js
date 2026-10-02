import { defineStore } from "pinia";
import { ref } from "vue";
import { careApi } from "@/services/care.js";
export const useCareStore = defineStore("care", () => {
  const recipients = ref([]),
    recipient = ref(null),
    unavailabilities = ref([]),
    unavailabilityPage = ref(null),
    unavailabilityError = ref(""),
    entries = ref([]),
    documents = ref([]),
    accesses = ref([]),
    accessesLoaded = ref(false),
    agenda = ref([]),
    agendaPage = ref(null),
    notifications = ref([]),
    unreadNotifications = ref(null),
    expenses = ref([]),
    financeRecipients = ref([]),
    financeSummary = ref(null),
    financePeriod = ref(null),
    decisions = ref(null),
    decision = ref(null),
    error = ref(""),
    pending = ref(0);
  let generation = 0;
  let unavailabilityRequest = 0;
  let unavailabilityRecipient = null;
  let decisionRequest = 0;
  let agendaRequest = 0;
  let unreadRequest = 0;
  let financeRequest = 0;
  async function run(fn, isCurrent = () => true) {
    const g = generation;
    pending.value++;
    error.value = "";
    try {
      return await fn();
    } catch (e) {
      if (g === generation && isCurrent())
        error.value =
          Object.values(e.response?.data?.errors || {})
            .flat()
            .join(" ") ||
          e.response?.data?.message ||
          "Não foi possível concluir. Tente novamente.";
      throw e;
    } finally {
      if (g === generation) pending.value = Math.max(0, pending.value - 1);
    }
  }
  async function loadUnavailabilities(id, page = 1) {
    const g = generation,
      request = ++unavailabilityRequest;
    if (String(id) !== unavailabilityRecipient) {
      unavailabilities.value = [];
      unavailabilityPage.value = null;
    }
    unavailabilityRecipient = String(id);
    unavailabilityError.value = "";
    try {
      const { data } = await run(
        () => careApi.unavailabilities(id, page),
        () => request === unavailabilityRequest,
      );
      if (g !== generation || request !== unavailabilityRequest) return false;
      unavailabilities.value = data.data;
      unavailabilityPage.value = data;
      return true;
    } catch (e) {
      if (g === generation && request === unavailabilityRequest)
        unavailabilityError.value = error.value;
      throw e;
    }
  }
  async function changeUnavailability(id, operation) {
    const g = generation,
      target = String(id);
    unavailabilityError.value = "";
    try {
      await run(operation, () => target === unavailabilityRecipient);
      if (g !== generation || target !== unavailabilityRecipient) return false;
      await loadUnavailabilities(id);
      if (g !== generation || target !== unavailabilityRecipient) return false;
      if (String(recipient.value?.id) === target) await loadEntries(id);
      return g === generation && target === unavailabilityRecipient;
    } catch (e) {
      if (g === generation && target === unavailabilityRecipient)
        unavailabilityError.value = error.value;
      throw e;
    }
  }
  function saveUnavailability(id, data) {
    return changeUnavailability(id, () => careApi.saveUnavailability(id, data));
  }
  function cancelUnavailability(id, period) {
    return changeUnavailability(id, () =>
      careApi.cancelUnavailability(id, period),
    );
  }
  async function loadRecipients() {
    const g = generation;
    const { data } = await run(() => careApi.recipients());
    if (g === generation) recipients.value = data;
  }
  async function loadRecipient(id) {
    const g = generation;
    recipient.value = null;
    entries.value = [];
    documents.value = [];
    accesses.value = [];
    accessesLoaded.value = false;
    const { data } = await run(() => careApi.recipient(id));
    if (g !== generation) return;
    recipient.value = data;
    await loadEntries(id);
    if (g !== generation) return;
    if (data.capabilities.documents.view) {
      const response = await run(() => careApi.documents(id));
      if (g === generation) documents.value = response.data;
    }
    if (data.can_manage_access) {
      const response = await run(() => careApi.accesses(id));
      if (g === generation) {
        accesses.value = response.data;
        accessesLoaded.value = true;
      }
    }
  }
  async function loadEntries(id) {
    const g = generation;
    const { data } = await run(() => careApi.entries(id));
    if (g === generation) entries.value = data;
  }
  async function saveRecipient(data) {
    const g = generation;
    const response = await run(() => careApi.saveRecipient(data));
    if (g !== generation) return;
    await loadRecipients();
    return response.data;
  }
  async function archive(id) {
    const g = generation;
    await run(() => careApi.archive(id));
    if (g !== generation) return;
    await loadRecipients();
  }
  async function saveEntry(id, data) {
    const g = generation;
    await run(() => careApi.saveEntry(id, data));
    if (g !== generation) return;
    await loadEntries(id);
  }
  async function complete(id, entry) {
    const g = generation;
    await run(() => careApi.complete(id, entry));
    if (g !== generation) return;
    await loadEntries(id);
  }
  async function removeEntry(id, entry) {
    const g = generation;
    await run(() => careApi.removeEntry(id, entry));
    if (g !== generation) return;
    await loadEntries(id);
  }
  async function upload(id, file) {
    const g = generation;
    await run(() => careApi.upload(id, file));
    if (g !== generation) return;
    const response = await run(() => careApi.documents(id));
    if (g === generation) documents.value = response.data;
  }
  async function download(id, doc) {
    const { data } = await run(() => careApi.download(id, doc.id));
    const url = URL.createObjectURL(data);
    const a = document.createElement("a");
    a.href = url;
    a.download = doc.filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  async function removeDocument(id, doc) {
    const g = generation;
    await run(() => careApi.removeDocument(id, doc));
    if (g !== generation) return;
    documents.value = documents.value.filter((d) => d.id !== doc);
  }
  async function grant(id, data) {
    const g = generation;
    await run(() => careApi.grant(id, data));
    if (g !== generation) return;
    accessesLoaded.value = false;
    const response = await run(() => careApi.accesses(id));
    if (g === generation) {
      accesses.value = response.data;
      accessesLoaded.value = true;
    }
  }
  async function revoke(id, user) {
    const g = generation;
    await run(() => careApi.revoke(id, user));
    if (g !== generation) return;
    accesses.value = accesses.value.filter((a) => a.user_id !== user);
  }
  async function loadAgenda(filters = {}, { allPages = false } = {}) {
    const g = generation,
      request = ++agendaRequest;
    const current = () => g === generation && request === agendaRequest;
    agenda.value = [];
    agendaPage.value = null;
    await run(async () => {
      let { data } = await careApi.agenda(filters);
      const first = data;
      const rows = [...data.data];
      while (allPages && data.current_page < data.last_page) {
        if (!current()) return;
        const response = await careApi.agenda({
          ...filters,
          page: data.current_page + 1,
        });
        data = response.data;
        rows.push(...data.data);
      }
      if (current()) {
        agenda.value = [...new Map(rows.map((row) => [row.id, row])).values()];
        agendaPage.value = { ...first, data: agenda.value };
      }
    }, current);
  }
  async function executeOccurrence(id, data) {
    const g = generation;
    await run(() => careApi.executeOccurrence(id, data));
    return g === generation;
  }
  async function cancelOccurrence(id, scope) {
    const g = generation;
    const { data } = await run(() => careApi.cancelOccurrence(id, scope));
    return g === generation ? data : null;
  }
  async function refreshUnreadNotifications() {
    const g = generation,
      request = ++unreadRequest;
    try {
      const { data } = await careApi.unreadCount();
      if (g === generation && request === unreadRequest)
        unreadNotifications.value = data.unread_count;
    } catch {
      if (g === generation && request === unreadRequest)
        unreadNotifications.value = null;
    }
  }
  async function loadNotifications() {
    const g = generation;
    const { data } = await run(() => careApi.notifications());
    if (g !== generation) return;
    notifications.value = data;
    await refreshUnreadNotifications();
  }
  async function read(id) {
    const g = generation;
    await run(() => careApi.read(id));
    if (g !== generation) return;
    const n = notifications.value.find((n) => n.id === id);
    if (n) n.read_at = new Date().toISOString();
    await refreshUnreadNotifications();
  }
  async function loadExpenses(filters = {}) {
    const g = generation,
      request = ++financeRequest;
    expenses.value = [];
    financeRecipients.value = [];
    financeSummary.value = null;
    financePeriod.value = null;
    const { data } = await run(
      () => careApi.finance(filters),
      () => request === financeRequest,
    );
    if (g !== generation || request !== financeRequest) return;
    expenses.value = data.data;
    financeRecipients.value = data.recipients;
    financeSummary.value = data.summary;
    financePeriod.value = data.period;
  }
  async function pay(id, entry, share, receipt = null) {
    const g = generation;
    await run(() => careApi.pay(id, entry, share, receipt));
    if (g !== generation) return;
    await loadEntries(id);
  }
  async function downloadPaymentReceipt(id, entry, share) {
    const g = generation;
    const { data } = await run(() =>
      careApi.paymentReceipt(id, entry, share.id),
    );
    if (g !== generation) return;
    const url = URL.createObjectURL(data);
    const link = document.createElement("a");
    link.href = url;
    link.download = share.receipt.filename;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  async function execute(id, entry, description, occurredAt) {
    const g = generation;
    await run(() => careApi.execute(id, entry, description, occurredAt));
    if (g === generation) await loadEntries(id);
  }
  async function decide(id, entry, proposal, data) {
    const g = generation;
    await run(() => careApi.decide(id, entry, proposal, data));
    if (g === generation) await loadEntries(id);
  }
  async function withdraw(id, entry, proposal) {
    const g = generation;
    await run(() => careApi.withdraw(id, entry, proposal));
    if (g === generation) await loadEntries(id);
  }
  async function loadDecisions(filters = {}) {
    const g = generation,
      request = ++decisionRequest;
    decisions.value = null;
    const { data } = await run(() => careApi.decisions(filters));
    if (g === generation && request === decisionRequest) decisions.value = data;
  }
  async function loadDecision(type, id) {
    const g = generation,
      request = ++decisionRequest;
    decision.value = null;
    const { data } = await run(() => careApi.decision(type, id));
    if (g === generation && request === decisionRequest) decision.value = data;
  }
  async function respondToProposal(proposal, data) {
    const g = generation,
      request = decisionRequest;
    await run(() =>
      proposal.type === "profile"
        ? careApi.decideProfile(proposal.care_recipient_id, proposal.id, data)
        : careApi.decide(
            proposal.care_recipient_id,
            proposal.entry_id,
            proposal.id,
            data,
          ),
    );
    if (g !== generation || request !== decisionRequest) return false;
    await loadDecision(proposal.type, proposal.id);
    return g === generation;
  }
  async function withdrawProposal(proposal) {
    const g = generation,
      request = decisionRequest;
    await run(() =>
      proposal.type === "profile"
        ? careApi.withdrawProfile(proposal.care_recipient_id, proposal.id)
        : careApi.withdraw(
            proposal.care_recipient_id,
            proposal.entry_id,
            proposal.id,
          ),
    );
    if (g !== generation || request !== decisionRequest) return false;
    await loadDecision(proposal.type, proposal.id);
    return g === generation;
  }
  async function proposeProfile(id, data) {
    const g = generation;
    const response = await run(() => careApi.proposeProfile(id, data));
    if (g !== generation) return;
    await loadRecipients();
    return response.data;
  }
  function clear() {
    decisionRequest++;
    decisions.value = null;
    decision.value = null;
    generation++;
    unavailabilityRequest++;
    unavailabilityRecipient = null;
    unavailabilities.value = [];
    unavailabilityPage.value = null;
    unavailabilityError.value = "";
    recipients.value = [];
    recipient.value = null;
    entries.value = [];
    documents.value = [];
    accesses.value = [];
    accessesLoaded.value = false;
    agenda.value = [];
    agendaPage.value = null;
    agendaRequest++;
    notifications.value = [];
    unreadNotifications.value = null;
    unreadRequest++;
    expenses.value = [];
    financeRecipients.value = [];
    financeSummary.value = null;
    financePeriod.value = null;
    financeRequest++;
    error.value = "";
    pending.value = 0;
  }
  return {
    unavailabilities,
    unavailabilityPage,
    unavailabilityError,
    loadUnavailabilities,
    saveUnavailability,
    cancelUnavailability,
    decisions,
    decision,
    loadDecisions,
    loadDecision,
    respondToProposal,
    withdrawProposal,
    proposeProfile,
    recipients,
    recipient,
    entries,
    documents,
    accesses,
    accessesLoaded,
    agenda,
    agendaPage,
    executeOccurrence,
    cancelOccurrence,
    notifications,
    unreadNotifications,
    refreshUnreadNotifications,
    expenses,
    financeRecipients,
    financeSummary,
    financePeriod,
    error,
    pending,
    loadRecipients,
    loadRecipient,
    loadEntries,
    saveRecipient,
    archive,
    saveEntry,
    decide,
    execute,
    withdraw,
    complete,
    removeEntry,
    upload,
    download,
    removeDocument,
    grant,
    revoke,
    loadAgenda,
    loadNotifications,
    read,
    loadExpenses,
    pay,
    downloadPaymentReceipt,
    clear,
  };
});
