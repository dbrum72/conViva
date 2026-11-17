import { ref } from "vue";

export function createContextRequests() {
  const error = ref("");
  const pending = ref(0);
  let generation = 0;
  const version = () => generation;
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

  function resetRequests() {
    generation++;
    error.value = "";
    pending.value = 0;
  }

  return { error, pending, version, run, resetRequests };
}
