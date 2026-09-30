import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import RecipientProfileFields from "../src/components/care/RecipientProfileFields.vue";
import RecipientProfileCard from "../src/components/care/RecipientProfileCard.vue";
const global = {
  stubs: { RouterLink: true, AppCard: { template: "<div><slot /></div>" } },
};
describe("ficha por tipo e área", () => {
  it("oferece campos próprios sem exigir dados de outro tipo", async () => {
    const wrapper = mount(RecipientProfileFields, {
      props: { modelValue: {}, kind: "child", healthEditable: false },
    });
    expect(wrapper.text()).toContain("Escola");
    expect(wrapper.text()).not.toContain("Referências de saúde");
    await wrapper.setProps({ kind: "pet", healthEditable: true });
    expect(wrapper.text()).not.toContain("Escola");
    expect(wrapper.text()).toContain("Contato veterinário");
    expect(wrapper.text()).toContain("Identificação opcional");
    await wrapper.setProps({ kind: "adult" });
    expect(wrapper.text()).toContain("autonomia");
    expect(wrapper.text()).not.toContain("Identificação opcional");
  });
  it("emite as instruções e contatos preenchidos", async () => {
    const wrapper = mount(RecipientProfileFields, {
      props: { modelValue: {}, kind: "adult" },
    });
    await wrapper.find("textarea").setValue("Prefere ler");
    expect(
      wrapper.emitted("update:modelValue")[0][0].routine_profile.preferences,
    ).toBe("Prefere ler");
    await wrapper
      .findAll("button")
      .find((b) => b.text() === "Adicionar contato")
      .trigger("click");
    expect(
      wrapper.emitted("update:modelValue").at(-1)[0].routine_profile.contacts,
    ).toHaveLength(1);
  });
  it("oferece revisão conforme a capacidade do backend, sem exigir autoria do cadastro", async () => {
    const recipient = {
      kind: "child",
      created_by: 1,
      can_manage_access: true,
      can_manage_profile: true,
      capabilities: {},
    };
    const wrapper = mount(RecipientProfileCard, {
      props: { recipient },
      global,
    });
    expect(wrapper.find("button").text()).toBe("Editar ficha");
    await wrapper.find("button").trigger("click");
    expect(wrapper.emitted("edit")).toHaveLength(1);
    await wrapper.setProps({
      recipient: { ...recipient, can_manage_profile: false },
    });
    expect(wrapper.find("button").exists()).toBe(false);
  });
  it("apresenta somente as seções permitidas e acompanha troca de assistido", async () => {
    const recipient = {
      kind: "child",
      capabilities: { routine: { view: true }, health: { view: false } },
      routine_profile: { school: "Escola A" },
      health_profile: { instructions: "Restrito" },
    };
    const wrapper = mount(RecipientProfileCard, {
      props: { recipient },
      global,
    });
    expect(wrapper.text()).toContain("Escola A");
    expect(wrapper.text()).not.toContain("Restrito");
    await wrapper.setProps({
      recipient: {
        kind: "pet",
        capabilities: { health: { view: true } },
        health_profile: { contacts: [{ name: "Vet", phone: "123" }] },
      },
    });
    expect(wrapper.text()).not.toContain("Escola A");
    expect(wrapper.text()).toContain("Vet");
  });
});
