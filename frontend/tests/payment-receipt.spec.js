import { afterEach, expect, it, vi } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { reactive } from "vue";
import CarePaymentDialog from "@/components/care/CarePaymentDialog.vue";
import { AppFileUpload } from "@/components/forms";
let store, wrapper;
vi.mock("@/state/care", () => ({ useCareStore: () => store }));
afterEach(() => {
  wrapper?.unmount();
  document.body.innerHTML = "";
});
const payment = () => ({
  entry: { id: 7, title: "Despesa" },
  share: {
    id: 8,
    amount_cents: 12345,
    can_attach_receipt: true,
    paid_at: null,
  },
});
function render(value = payment()) {
  store = reactive({
    pending: 0,
    error: "",
    pay: vi.fn().mockResolvedValue(undefined),
  });
  wrapper = mount(CarePaymentDialog, {
    attachTo: document.body,
    props: { payment: value, recipientId: 2 },
  });
}
async function submit() {
  document
    .querySelector("form")
    .dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
  await flushPromises();
}
it("registra pagamento sem exigir comprovante", async () => {
  render();
  await submit();
  expect(store.pay).toHaveBeenCalledWith(2, 7, 8, null);
  expect(wrapper.emitted("close")).toHaveLength(1);
});
it("envia o comprovante selecionado e mantém o arquivo quando há falha", async () => {
  render();
  const file = new File(["%PDF-1.4"], "recibo.pdf", {
    type: "application/pdf",
  });
  wrapper.findComponent(AppFileUpload).vm.$emit("update:modelValue", [file]);
  await flushPromises();
  store.pay.mockImplementation(async () => {
    store.error = "Falha no envio";
    throw new Error("offline");
  });
  await submit();
  expect(store.pay).toHaveBeenCalledWith(2, 7, 8, file);
  expect(wrapper.emitted("close")).toBeUndefined();
  expect(document.body.textContent).toContain("Falha no envio");
  expect(document.body.textContent).toContain("recibo.pdf");
});
it("limpa o arquivo ao abrir outra parcela e oculta upload sem permissão", async () => {
  render();
  wrapper
    .findComponent(AppFileUpload)
    .vm.$emit("update:modelValue", [new File(["a"], "recibo.pdf")]);
  await flushPromises();
  const next = payment();
  next.share.id = 9;
  next.share.can_attach_receipt = false;
  await wrapper.setProps({ payment: next });
  expect(wrapper.findComponent(AppFileUpload).exists()).toBe(false);
  await submit();
  expect(store.pay).toHaveBeenCalledWith(2, 7, 9, null);
});
it("exige arquivo ao anexar comprovante a um pagamento existente", async () => {
  const existing = payment();
  existing.share.paid_at = "2026-09-27";
  render(existing);
  await submit();
  expect(store.pay).not.toHaveBeenCalled();
});
