import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { useAuthStore } from "@/modules/auth";
import { useCareStore } from "@/modules/care/state/care.js";
import { useAgendaStore } from "@/modules/agenda";
import { useExpensesStore } from "@/modules/expenses";
import { context, listCareGroups } from "@/modules/groups/api/groups.js";
vi.mock("@/modules/groups/api/groups.js", () => ({
  context: vi.fn(),
  listCareGroups: vi.fn(),
  createGroup: vi.fn(),
}));
vi.mock("@/modules/auth/api/auth.js", () => ({
  login: vi.fn(),
  logout: vi.fn(),
  me: vi.fn(),
  refresh: vi.fn(),
  register: vi.fn(),
}));
beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
  sessionStorage.clear();
});

it("lista todos os assistidos sem substituir as permissões do grupo atual", async () => {
  const auth = useAuthStore();
  auth.applyContextPayload({
    organization: { id: 1, slug: "a" },
    roles: ["responsavel"],
    permissions: ["care.write"],
  });
  listCareGroups.mockResolvedValue({
    data: [
      { id: 1, recipient: { name: "Ana" } },
      { id: 2, recipient: { name: "Pedro" } },
    ],
  });
  await auth.fetchCareGroups();
  expect(auth.careGroups).toHaveLength(2);
  expect(auth.roles).toEqual(["responsavel"]);
  expect(auth.currentTenant).toBe("a");
});

it("remove registros e permissões anteriores antes de abrir outro assistido", async () => {
  const auth = useAuthStore(),
    care = useCareStore(),
    agenda = useAgendaStore(),
    expenses = useExpensesStore();
  auth.applyContextPayload({
    organization: { id: 1, slug: "a" },
    roles: ["responsavel"],
    permissions: ["care.write"],
  });
  care.recipient = { id: 10 };
  care.entries = [{ id: 20 }];
  agenda.agenda = [{ id: 30 }];
  expenses.expenses = [{ id: 40 }];
  expenses.expensesSummary = { confirmed_cents: 5000 };
  let finish;
  context.mockReturnValue(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const selecting = auth.selectOrganization({ id: 2, slug: "b" });
  expect(auth.permissions).toEqual([]);
  expect(care.recipient).toBeNull();
  expect(care.entries).toEqual([]);
  expect(agenda.agenda).toEqual([]);
  expect(expenses.expenses).toEqual([]);
  expect(expenses.expensesSummary).toBeNull();
  finish({
    data: {
      organization: { id: 2, slug: "b" },
      roles: ["observador"],
      permissions: [],
    },
  });
  await selecting;
  expect(auth.roles).toEqual(["observador"]);
  expect(auth.hasPermission("care.write")).toBe(false);
  expect(auth.currentTenant).toBe("b");
});

it("ignora resposta de contexto antigo após uma nova seleção", async () => {
  const auth = useAuthStore();
  let finish;
  context.mockReturnValueOnce(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const oldRequest = auth.selectOrganization({ slug: "a" });
  context.mockResolvedValueOnce({
    data: {
      organization: { slug: "b" },
      roles: ["observador"],
      permissions: [],
    },
  });
  await auth.selectOrganization({ slug: "b" });
  finish({
    data: {
      organization: { slug: "a" },
      roles: ["responsavel"],
      permissions: ["care.write"],
    },
  });
  await oldRequest;
  expect(auth.currentTenant).toBe("b");
  expect(auth.roles).toEqual(["observador"]);
});

it("não restaura contexto quando a sessão expira durante a consulta", async () => {
  const auth = useAuthStore();
  let finish;
  context.mockReturnValueOnce(
    new Promise((resolve) => {
      finish = resolve;
    }),
  );
  const request = auth.selectOrganization({ slug: "a" });
  auth.clearAuth();
  finish({
    data: {
      organization: { slug: "a" },
      roles: ["responsavel"],
      permissions: ["care.write"],
    },
  });
  await request;
  expect(auth.organization).toBeNull();
  expect(auth.permissions).toEqual([]);
});
