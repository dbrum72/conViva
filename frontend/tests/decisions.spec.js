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
  it("exibe o histórico e encaminha todas as respostas à central", () => {
    const wrapper = render();
    expect(wrapper.text()).toContain("Consulta");
    expect(wrapper.text()).toContain("ainda não está confirmado");
    expect(wrapper.text()).toContain("Ver e responder na central de decisões");
    expect(wrapper.find("form").exists()).toBe(false);
    expect(wrapper.find("button").exists()).toBe(false);
  });
});
