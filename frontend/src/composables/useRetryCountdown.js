import { computed, onScopeDispose, ref } from "vue";

export function useRetryCountdown() {
  const remaining = ref(0);
  let timer;
  let disposed = false;

  const message = computed(() =>
    remaining.value > 0
      ? `Muitas tentativas. Aguarde ${remaining.value} ${remaining.value === 1 ? "segundo" : "segundos"} e tente novamente.`
      : "",
  );

  function start(error) {
    if (disposed) return;
    clearInterval(timer);
    const response = error.response;
    const candidates = [
      response?.data?.retry_after,
      response?.headers?.["retry-after"],
    ];
    const seconds =
      candidates
        .map(Number)
        .find((value) => Number.isFinite(value) && value > 0) ?? 60;
    const deadline = Date.now() + Math.ceil(seconds) * 1000;
    const tick = () => {
      remaining.value = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
      if (remaining.value === 0) clearInterval(timer);
    };
    tick();
    timer = setInterval(tick, 1000);
  }

  onScopeDispose(() => {
    disposed = true;
    clearInterval(timer);
  });

  return { remaining, message, start };
}
