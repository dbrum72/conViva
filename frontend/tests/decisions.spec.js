import { mount } from "@vue/test-utils";
import { describe, it, expect } from "vitest";
import CareDecisions from "@/components/care/CareDecisions.vue";
const entry = {
  id: 1,
  created_by: 1,
  revision: 0,
  author: { name: "Autor" },
  proposals: [
    {
      id: 8,
      version: 1,
      operation: "save",
      status: "pending",
      payload: {
        data: { title: "Consulta", description: "Proposta às 10h" },
        shares: [],
      },
      decisions: [
        {
          id: 2,
          user_id: 2,
          status: "pending",
          user: { name: "Outro responsável" },
        },
      ],
    },
  ],
};
function render(userId = 2, canEdit = true) {
  return mount(CareDecisions, { props: { entry, userId, canEdit } });
}
describe("decisões compartilhadas", () => {
  it("exibe a proposta antes do aceite e emite apenas a decisão do participante", async () => {
    const wrapper = render();
    expect(wrapper.text()).toContain("Consulta");
    expect(wrapper.text()).toContain("ainda não está confirmado");
    await wrapper
      .findAll("button")
      .find((b) => b.text() === "Aceitar proposta")
      .trigger("click");
    expect(wrapper.emitted("decide")[0]).toEqual([8, { decision: "accepted" }]);
  });
  it("exige justificativa não vazia para recusar", async () => {
    const wrapper = render();
    await wrapper.find("form").trigger("submit");
    expect(wrapper.emitted("decide")).toBeUndefined();
    await wrapper.find("textarea").setValue("Não tenho disponibilidade.");
    await wrapper.find("form").trigger("submit");
    expect(wrapper.emitted("decide")[0]).toEqual([
      8,
      { decision: "rejected", reason: "Não tenho disponibilidade." },
    ]);
  });
  it("não oferece decisão ao autor, a terceiros ou a observadores", () => {
    for (const [user, edit] of [
      [1, true],
      [3, true],
      [2, false],
    ]) {
      const wrapper = render(user, edit);
      expect(wrapper.find("form").exists()).toBe(false);
    }
  });
});
