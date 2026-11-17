import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { flushPromises, mount } from "@vue/test-utils";
import PersonalRecipientAvatar from "@/components/care/PersonalRecipientAvatar.vue";
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

it("salva o recorte enquadrado pelos controles como a foto pessoal", async () => {
  fetchAvatar.mockResolvedValue({ status: 204 });
  uploadAvatar.mockResolvedValue({});
  const drawImage = vi.fn();
  const context = vi.spyOn(HTMLCanvasElement.prototype, "getContext")
    .mockReturnValue({ drawImage });
  const encode = vi.spyOn(HTMLCanvasElement.prototype, "toBlob")
    .mockImplementation((done) => done(new Blob(["crop"], { type: "image/png" })));
  const wrapper = mount(PersonalRecipientAvatar, {
    props: { enabled: true, initials: "A", contextKey: "1:1" },
    global: { stubs: { teleport: true } },
  });
  try {
    await flushPromises();
    await wrapper.get("button.personal-avatar").trigger("click");
    const input = wrapper.get("input[type=file]");
    Object.defineProperty(input.element, "files", {
      value: [new File(["photo"], "original.jpeg", { type: "image/jpeg" })],
    });
    await input.trigger("change");
    const preview = wrapper.get(".avatar-crop img");
    Object.defineProperties(preview.element, {
      naturalWidth: { value: 1200 },
      naturalHeight: { value: 800 },
    });
    await preview.trigger("load");
    await wrapper.get('input[id$="-zoom"]').setValue("200");
    await wrapper.get('input[id$="-horizontal"]').setValue("100");
    await wrapper.get('input[id$="-vertical"]').setValue("25");
    await wrapper.get("form").trigger("submit");
    await flushPromises();
    expect(drawImage).toHaveBeenCalledWith(preview.element, 800, 100, 400, 400, 0, 0, 400, 400);
    expect(uploadAvatar.mock.calls[0][0]).toMatchObject({ name: "avatar.png", type: "image/png" });
  } finally {
    wrapper.unmount();
    context.mockRestore();
    encode.mockRestore();
  }
});
