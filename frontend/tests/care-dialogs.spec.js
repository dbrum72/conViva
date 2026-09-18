import { afterEach, expect, it, vi } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { reactive } from "vue";
import AppDialog from "@/components/ui/AppDialog/index.vue";
import CareExecutionDialog from "@/components/care/CareExecutionDialog.vue";

let store;
vi.mock("@/state/care", () => ({ useCareStore: () => store }));
let wrapper;
afterEach(() => {
  wrapper?.unmount();
  document.body.innerHTML = "";
});

it("contém o foco, permite Escape e devolve foco ao acionador", async () => {
  const trigger = document.createElement("button");
  document.body.append(trigger);
  trigger.focus();
  wrapper = mount(AppDialog, {
    attachTo: document.body,
    props: { open: false, title: "Teste" },
    slots: { default: '<input aria-label="Nome" /><button>Salvar</button>' },
  });
  await wrapper.setProps({ open: true });
  await flushPromises();
  const buttons = document.querySelectorAll('[role="dialog"] button');
  expect(document.activeElement).toBe(buttons[0]);
  document.dispatchEvent(
    new KeyboardEvent("keydown", {
      key: "Tab",
      shiftKey: true,
      bubbles: true,
      cancelable: true,
    }),
  );
  expect(document.activeElement).toBe(buttons[buttons.length - 1]);
  document.dispatchEvent(
    new KeyboardEvent("keydown", { key: "Escape", bubbles: true }),
  );
  expect(wrapper.emitted("close")).toHaveLength(1);
  await wrapper.setProps({ open: false });
  await flushPromises();
  expect(document.activeElement).toBe(trigger);
});

it("mantém execução aberta e exibe falha para nova tentativa", async () => {
  store = reactive({
    pending: 0,
    error: "",
    execute: vi.fn(async () => {
      store.error = "Não foi possível salvar";
      throw new Error("network");
    }),
  });
  wrapper = mount(CareExecutionDialog, {
    attachTo: document.body,
    props: { entry: { id: 7, title: "Refeição" }, recipientId: 2 },
  });
  document
    .querySelector("form")
    .dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
  await flushPromises();
  expect(store.execute).toHaveBeenCalledWith(2, 7, "");
  expect(wrapper.emitted("close")).toBeUndefined();
  expect(document.querySelector('[role="alert"]').textContent).toContain(
    "Não foi possível salvar",
  );
});
