import { defineStore } from "pinia";
import { ref } from "vue";
import { careApi } from "@/services/care.js";
export const useCareStore = defineStore("care", () => {
  const recipients = ref([]),
    recipient = ref(null),
    entries = ref([]),
    documents = ref([]),
    accesses = ref([]),
    accessesLoaded = ref(false),
    agenda = ref([]),
    notifications = ref([]),
    expenses = ref([]),
    error = ref(""),
    pending = ref(0);
  let generation = 0;
  async function run(fn) {
    const g = generation;
    pending.value++;
    error.value = "";
    try {
      return await fn();
    } catch (e) {
      if (g === generation)
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
  async function loadAgenda() {
    const g = generation;
    const { data } = await run(() => careApi.agenda());
    if (g === generation) agenda.value = data;
  }
  async function loadNotifications() {
    const g = generation;
    const { data } = await run(() => careApi.notifications());
    if (g === generation) notifications.value = data;
  }
  async function read(id) {
    const g = generation;
    await run(() => careApi.read(id));
    if (g !== generation) return;
    const n = notifications.value.find((n) => n.id === id);
    if (n) n.read_at = new Date().toISOString();
  }
  async function loadExpenses() {
    const g = generation;
    await loadRecipients();
    if (g !== generation) return;
    const all = await run(() =>
      Promise.all(
        recipients.value
          .filter((p) => p.capabilities.finance.view)
          .map(async (p) =>
            (await careApi.entries(p.id)).data
              .filter(
                (e) =>
                  e.kind === "expense" &&
                  ["pending", "completed"].includes(e.status),
              )
              .map((e) => ({ ...e, recipient: p })),
          ),
      ),
    );
    if (g === generation) expenses.value = all.flat();
  }
  async function pay(id, entry, share) {
    const g = generation;
    await run(() => careApi.pay(id, entry, share));
    if (g !== generation) return;
    await loadEntries(id);
  }
  async function execute(id, entry, description) {
    const g = generation;
    await run(() => careApi.execute(id, entry, description));
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
  function clear() {
    generation++;
    recipients.value = [];
    recipient.value = null;
    entries.value = [];
    documents.value = [];
    accesses.value = [];
    accessesLoaded.value = false;
    agenda.value = [];
    notifications.value = [];
    expenses.value = [];
    error.value = "";
    pending.value = 0;
  }
  return {
    recipients,
    recipient,
    entries,
    documents,
    accesses,
    accessesLoaded,
    agenda,
    notifications,
    expenses,
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
    clear,
  };
});
