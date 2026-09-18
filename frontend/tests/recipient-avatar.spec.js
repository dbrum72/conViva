import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { useRecipientAvatarStore } from "@/state/recipient-avatar.js";
import {
  fetchAvatar,
  uploadAvatar,
  deleteAvatar,
} from "@/services/recipient-avatar.js";

vi.mock("@/services/recipient-avatar.js", () => ({
  fetchAvatar: vi.fn(),
  uploadAvatar: vi.fn(),
  deleteAvatar: vi.fn(),
}));
beforeEach(() => {
  vi.resetAllMocks();
  setActivePinia(createPinia());
  URL.createObjectURL = vi.fn(() => "blob:personal-photo");
  URL.revokeObjectURL = vi.fn();
});

it("descarta uma foto recebida após trocar de grupo ou sair da conta", async () => {
  let resolve;
  fetchAvatar.mockReturnValue(
    new Promise((done) => {
      resolve = done;
    }),
  );
  const store = useRecipientAvatarStore();
  const request = store.load();
  store.clear();
  resolve({ status: 200, data: new Blob(["old"]) });
  await request;
  expect(store.url).toBe("");
  expect(URL.createObjectURL).not.toHaveBeenCalled();
});

it("não aplica um upload antigo ao novo grupo", async () => {
  let resolve;
  uploadAvatar.mockReturnValue(
    new Promise((done) => {
      resolve = done;
    }),
  );
  const store = useRecipientAvatarStore();
  const request = store.save(new File(["photo"], "photo.png"));
  store.clear();
  resolve();
  expect(await request).toBe(false);
  expect(store.url).toBe("");
});

it("mantém a foto anterior ao falhar e libera a URL ao remover", async () => {
  const store = useRecipientAvatarStore();
  await store.save(new File(["photo"], "photo.png"));
  uploadAvatar.mockRejectedValue({
    response: { data: { errors: { image: ["Foto inválida"] } } },
  });
  expect(await store.save(new File(["invalid"], "fake.png"))).toBe(false);
  expect(store.error).toBe("Foto inválida");
  expect(store.url).toBe("blob:personal-photo");
  expect(await store.save(null)).toBe(true);
  expect(deleteAvatar).toHaveBeenCalledOnce();
  expect(URL.revokeObjectURL).toHaveBeenCalledWith("blob:personal-photo");
  expect(store.url).toBe("");
});
