import { beforeEach, expect, it, vi } from "vitest";
import {
  authenticatedClient as client,
  setSessionExpiredHandler,
} from "@/modules/http";
import { getAccessToken, setAccessToken } from "@/modules/auth/session/token.js";
import { getCurrentTenant, setCurrentTenant } from "@/modules/groups/context/tenant.js";

beforeEach(() => {
  sessionStorage.clear();
});

it("limpa token e grupo e notifica a sessão expirada", async () => {
  setAccessToken("old-token");
  setCurrentTenant("group-a");
  const expired = vi.fn();
  setSessionExpiredHandler(expired);
  await expect(
    client.get("/auth/me", {
      adapter: (config) =>
        Promise.reject({ config, response: { status: 401 } }),
    }),
  ).rejects.toBeDefined();
  expect(getAccessToken()).toBeNull();
  expect(getCurrentTenant()).toBeNull();
  expect(expired).toHaveBeenCalledOnce();
});

it("não encerra nova sessão por uma resposta 401 atrasada", async () => {
  setAccessToken("old-token");
  const expired = vi.fn();
  setSessionExpiredHandler(expired);
  await expect(
    client.get("/auth/me", {
      adapter: (config) => {
        setAccessToken("new-token");
        return Promise.reject({ config, response: { status: 401 } });
      },
    }),
  ).rejects.toBeDefined();
  expect(getAccessToken()).toBe("new-token");
  expect(expired).not.toHaveBeenCalled();
});
