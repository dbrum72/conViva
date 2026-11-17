import { defineStore } from "pinia";
import { ref } from "vue";
import { createContextRequests } from "@/state/context-requests.js";
import { careApi } from "../api/care.js";
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
    today = ref(null),
    decisions = ref(null),
    decision = ref(null);
  const { error, pending, version, run, resetRequests } = createContextRequests();
  let unavailabilityRequest = 0;
  let unavailabilityRecipient = null;
  let decisionRequest = 0;
  let todayRequest = 0;
  async function loadUnavailabilities(id, page = 1) {
    const g = version(),
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
      if (g !== version() || request !== unavailabilityRequest) return false;
      unavailabilities.value = data.data;
      unavailabilityPage.value = data;
      return true;
    } catch (e) {
      if (g === version() && request === unavailabilityRequest)
        unavailabilityError.value = error.value;
      throw e;
    }
  }
  async function changeUnavailability(id, operation) {
    const g = version(),
      target = String(id);
    unavailabilityError.value = "";
    try {
      await run(operation, () => target === unavailabilityRecipient);
      if (g !== version() || target !== unavailabilityRecipient) return false;
      await loadUnavailabilities(id);
      if (g !== version() || target !== unavailabilityRecipient) return false;
      if (String(recipient.value?.id) === target) await loadEntries(id);
      return g === version() && target === unavailabilityRecipient;
    } catch (e) {
      if (g === version() && target === unavailabilityRecipient)
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
    const g = version();
    const { data } = await run(() => careApi.recipients());
    if (g === version()) recipients.value = data;
  }
  async function loadRecipient(id) {
    const g = version();
    recipient.value = null;
    entries.value = [];
    documents.value = [];
    accesses.value = [];
    accessesLoaded.value = false;
    const { data } = await run(() => careApi.recipient(id));
    if (g !== version()) return;
    recipient.value = data;
    await loadEntries(id);
    if (g !== version()) return;
    if (data.capabilities.documents.view) {
      await loadDocuments(id);
    }
    if (g !== version()) return;
    if (data.can_manage_access) {
      const response = await run(() => careApi.accesses(id));
      if (g === version()) {
        accesses.value = response.data;
        accessesLoaded.value = true;
      }
    }
  }
  async function loadEntries(id) {
    const g = version();
    const { data } = await run(() => careApi.entries(id));
    if (g === version()) entries.value = data;
  }
  async function saveRecipient(data) {
    const g = version();
    const response = await run(() => careApi.saveRecipient(data));
    if (g !== version()) return;
    await loadRecipients();
    return response.data;
  }
  async function archive(id) {
    const g = version();
    await run(() => careApi.archive(id));
    if (g !== version()) return;
    await loadRecipients();
  }
  async function saveEntry(id, data) {
    const g = version();
    await run(() => careApi.saveEntry(id, data));
    if (g !== version()) return;
    await loadEntries(id);
  }
  async function complete(id, entry) {
    const g = version();
    await run(() => careApi.complete(id, entry));
    if (g !== version()) return;
    await loadEntries(id);
  }
  async function removeEntry(id, entry) {
    const g = version();
    await run(() => careApi.removeEntry(id, entry));
    if (g !== version()) return;
    await loadEntries(id);
  }
  async function loadDocuments(id, filters) {
    const g = version();
    const response = await run(() => careApi.documents(id, filters));
    if (g === version() && String(recipient.value?.id) === String(id))
      documents.value = response.data;
  }
  async function saveDocument(id, doc, metadata, file) {
    return run(() =>
      doc
        ? careApi.updateDocument(id, doc, metadata)
        : careApi.upload(id, file, metadata),
    );
  }
  async function download(id, doc) {
    const g = version();
    const { data } = await run(() => careApi.download(id, doc.id));
    if (g !== version()) return;
    const url = URL.createObjectURL(data);
    const a = document.createElement("a");
    a.href = url;
    a.download = doc.filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  async function removeDocument(id, doc) {
    const g = version();
    await run(() => careApi.removeDocument(id, doc));
    if (g !== version()) return;
    documents.value = documents.value.filter((d) => d.id !== doc);
  }
  async function grant(id, data) {
    const g = version();
    await run(() => careApi.grant(id, data));
    if (g !== version()) return;
    accessesLoaded.value = false;
    const response = await run(() => careApi.accesses(id));
    if (g === version()) {
      accesses.value = response.data;
      accessesLoaded.value = true;
    }
  }
  async function revoke(id, user) {
    const g = version();
    await run(() => careApi.revoke(id, user));
    if (g !== version()) return;
    accesses.value = accesses.value.filter((a) => a.user_id !== user);
  }
  async function loadToday() {
    const g = version();
    const request = ++todayRequest;
    const current = () => g === version() && request === todayRequest;
    await run(async () => {
      try {
        const { data } = await careApi.today();
        if (current()) today.value = data;
      } catch (error) {
        if (current()) today.value = null;
        throw error;
      }
    }, current);
  }
  async function recordFact(id, data) {
    const g = version();
    await run(() => careApi.recordFact(id, data));
    return g === version();
  }
  async function readHandover(id, version) {
    const g = version();
    await run(() => careApi.readHandover(id, version));
    if (g === version()) await loadToday();
  }
  async function executeOccurrence(id, data, execution = null) {
    const g = version();
    await run(() =>
      execution
        ? careApi.correctOccurrence(id, execution, data)
        : careApi.executeOccurrence(id, data),
    );
    return g === version();
  }
  async function cancelOccurrence(id, scope) {
    const g = version();
    const { data } = await run(() => careApi.cancelOccurrence(id, scope));
    return g === version() ? data : null;
  }
  async function execute(
    id,
    entry,
    description,
    occurredAt,
    { refreshEntries = true } = {},
  ) {
    const g = version();
    await run(() => careApi.execute(id, entry, description, occurredAt));
    if (g !== version()) return false;
    if (refreshEntries) await loadEntries(id);
    return g === version();
  }
  async function decide(id, entry, proposal, data) {
    const g = version();
    await run(() => careApi.decide(id, entry, proposal, data));
    if (g === version()) await loadEntries(id);
  }
  async function withdraw(id, entry, proposal) {
    const g = version();
    await run(() => careApi.withdraw(id, entry, proposal));
    if (g === version()) await loadEntries(id);
  }
  async function loadDecisions(filters = {}) {
    const g = version(),
      request = ++decisionRequest;
    decisions.value = null;
    const { data } = await run(() => careApi.decisions(filters));
    if (g === version() && request === decisionRequest) decisions.value = data;
  }
  async function loadDecision(type, id) {
    const g = version(),
      request = ++decisionRequest;
    decision.value = null;
    const { data } = await run(() => careApi.decision(type, id));
    if (g === version() && request === decisionRequest) decision.value = data;
  }
  async function respondToProposal(proposal, data) {
    const g = version(),
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
    if (g !== version() || request !== decisionRequest) return false;
    await loadDecision(proposal.type, proposal.id);
    return g === version();
  }
  async function withdrawProposal(proposal) {
    const g = version(),
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
    if (g !== version() || request !== decisionRequest) return false;
    await loadDecision(proposal.type, proposal.id);
    return g === version();
  }
  async function proposeProfile(id, data) {
    const g = version();
    const response = await run(() => careApi.proposeProfile(id, data));
    if (g !== version()) return;
    await loadRecipients();
    return response.data;
  }
  function clear() {
    decisionRequest++;
    decisions.value = null;
    decision.value = null;
    resetRequests();
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
    today.value = null;
    todayRequest++;
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
    today,
    loadToday,
    recordFact,
    readHandover,
    executeOccurrence,
    cancelOccurrence,
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
    loadDocuments,
    saveDocument,
    download,
    removeDocument,
    grant,
    revoke,
    clear,
  };
});
