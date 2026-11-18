import { defineStore } from "pinia";
import { ref } from "vue";
import { createContextRequests } from "@/state/context-requests.js";
import { historyApi } from "./api.js";

export const useHistoryStore = defineStore("history", () => {
  const { error, pending, version, run, resetRequests } =
    createContextRequests();
  const timeline = ref(null);
  const report = ref(null);

  async function load(filters = {}) {
    const context = version();
    const { data } = await run(() => historyApi.list(filters));
    if (context !== version()) return;
    timeline.value = data;
    report.value = data.export;
    return data.filters;
  }

  async function requestExport() {
    const context = version();
    const { from, to, area } = timeline.value.filters;
    const { data } = await run(() =>
      historyApi.create({ from, to, area, format: "pdf" }),
    );
    if (context === version()) report.value = data;
  }

  async function refreshExport() {
    const context = version();
    const { data } = await run(() => historyApi.status(report.value.id));
    if (context === version()) report.value = data;
  }

  async function download() {
    const context = version();
    const { id, format } = report.value;
    const { data } = await run(() => historyApi.download(id));
    if (context !== version()) return;

    const url = URL.createObjectURL(data);
    const link = document.createElement("a");
    link.href = url;
    link.download =
      format === "pdf" ? `historico-conviva-${id}.pdf` : `historico.${format}`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  function clear() {
    resetRequests();
    timeline.value = null;
    report.value = null;
  }

  return {
    error,
    pending,
    timeline,
    report,
    load,
    requestExport,
    refreshExport,
    download,
    clear,
  };
});
