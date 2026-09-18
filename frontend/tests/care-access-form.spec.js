import { afterEach, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import CareAccessForm from "@/components/care/CareAccessForm.vue";
import { AppCheckbox } from "@/components/forms";

let wrapper;
it("mostra outro responsável somente para leitura e bloqueia o envio", async () => {
  setup();
  await wrapper.setProps({
    currentUserId: 2,
    members: [
      { id: 1, name: "Responsável", role: "responsavel" },
      { id: 2, name: "Cuidador", role: "cuidador" },
    ],
  });
  await wrapper.get("select").setValue("1");
  expect(checked()).toEqual(["access-area-health", "access-area-documents"]);
  expect(wrapper.get('[role="status"]').text()).toContain(
    "somente para leitura",
  );
  expect(
    wrapper.findAll("input").every((input) => input.element.disabled),
  ).toBe(true);
  expect(wrapper.get('button[type="submit"]').element.disabled).toBe(true);
  await wrapper.get("form").trigger("submit");
  expect(wrapper.emitted("save")).toBeUndefined();
  await wrapper.get("select").setValue("2");
  expect(wrapper.get('button[type="submit"]').element.disabled).toBe(false);
});
afterEach(() => wrapper?.unmount());
const accesses = [
  {
    user_id: 1,
    areas: ["health", "documents"],
    can_edit: true,
    expires_at: "2027-02-03T15:45:30.000Z",
  },
  { user_id: 2, areas: ["finance"], can_edit: false, expires_at: null },
];
function setup(ready = true) {
  wrapper = mount(CareAccessForm, {
    props: {
      recipientId: 9,
      ready,
      busy: false,
      accesses,
      members: [1, 2, 3].map((id) => ({
        id,
        name: `Pessoa ${id}`,
        email: `${id}@example.test`,
      })),
    },
  });
}
function checked() {
  return wrapper
    .findAll("fieldset input:checked")
    .map((input) => input.attributes("id"));
}
it("carrega cada membro e salva os valores vigentes sem alterar a validade", async () => {
  setup();
  expect(wrapper.findAllComponents(AppCheckbox)).toHaveLength(5);
  await wrapper.get("select").setValue("1");
  expect(checked()).toEqual(["access-area-health", "access-area-documents"]);
  expect(wrapper.get("#access-can-edit").element.checked).toBe(true);
  await wrapper.get("form").trigger("submit");
  expect(wrapper.emitted("save")[0][0]).toEqual(accesses[0]);
  await wrapper.get("select").setValue("2");
  expect(checked()).toEqual(["access-area-finance"]);
  expect(wrapper.get("#access-can-edit").element.checked).toBe(false);
  expect(wrapper.get('input[type="datetime-local"]').element.value).toBe("");
  await wrapper.get("select").setValue("3");
  expect(checked()).toEqual([]);
});
it("edita uma cópia e sincroniza atualizações e revogações", async () => {
  setup();
  await wrapper.get("select").setValue("1");
  await wrapper.get("#access-area-routine").setValue(true);
  expect(accesses[0].areas).toEqual(["health", "documents"]);
  await wrapper.setProps({
    accesses: [{ ...accesses[0], areas: ["finance"] }],
  });
  expect(checked()).toEqual(["access-area-finance"]);
  await wrapper.setProps({ accesses: [] });
  expect(checked()).toEqual([]);
  expect(wrapper.get("#access-can-edit").element.checked).toBe(false);
});
it("impede gravação sem carregamento e limpa a seleção ao trocar de assistido", async () => {
  setup();
  await wrapper.get("select").setValue("1");
  await wrapper.setProps({ ready: false });
  await wrapper.get("form").trigger("submit");
  expect(wrapper.emitted("save")).toBeUndefined();
  expect(wrapper.get('button[type="submit"]').element.disabled).toBe(true);
  await wrapper.setProps({ ready: true });
  expect(checked()).toEqual(["access-area-health", "access-area-documents"]);
  await wrapper.setProps({ recipientId: 10 });
  expect(wrapper.get("select").element.value).toBe("");
  expect(checked()).toEqual([]);
});
