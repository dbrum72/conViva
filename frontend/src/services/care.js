import client from "./client.js";
export const careApi = {
  execute: (id, entry, description) =>
    client.post("/recipients/" + id + "/entries/" + entry + "/execution", {
      description,
    }),
  decide: (id, entry, proposal, data) =>
    client.post(
      "/recipients/" +
        id +
        "/entries/" +
        entry +
        "/proposals/" +
        proposal +
        "/decision",
      data,
    ),
  withdraw: (id, entry, proposal) =>
    client.post(
      "/recipients/" +
        id +
        "/entries/" +
        entry +
        "/proposals/" +
        proposal +
        "/withdraw",
    ),
  recipients: () => client.get("/recipients"),
  recipient: (id) => client.get("/recipients/" + id),
  saveRecipient: (data) =>
    data.id
      ? client.put("/recipients/" + data.id, data)
      : client.post("/recipients", data),
  archive: (id) => client.delete("/recipients/" + id),
  entries: (id) => client.get("/recipients/" + id + "/entries"),
  saveEntry: (id, data) =>
    data.id
      ? client.put("/recipients/" + id + "/entries/" + data.id, data)
      : client.post("/recipients/" + id + "/entries", data),
  complete: (id, entry) =>
    client.patch("/recipients/" + id + "/entries/" + entry + "/complete"),
  removeEntry: (id, entry) =>
    client.delete("/recipients/" + id + "/entries/" + entry),
  documents: (id) => client.get("/recipients/" + id + "/documents"),
  upload: (id, file) => {
    const data = new FormData();
    data.append("file", file);
    return client.post("/recipients/" + id + "/documents", data);
  },
  download: (id, doc) =>
    client.get("/recipients/" + id + "/documents/" + doc + "/download", {
      responseType: "blob",
    }),
  removeDocument: (id, doc) =>
    client.delete("/recipients/" + id + "/documents/" + doc),
  accesses: (id) => client.get("/recipients/" + id + "/accesses"),
  grant: (id, data) => client.put("/recipients/" + id + "/accesses", data),
  revoke: (id, user) =>
    client.delete("/recipients/" + id + "/accesses/" + user),
  agenda: () => client.get("/agenda"),
  notifications: () => client.get("/notifications"),
  read: (id) => client.post("/notifications/" + id + "/read"),
  pay: (id, entry, share) =>
    client.post(
      "/recipients/" + id + "/entries/" + entry + "/shares/" + share + "/pay",
    ),
};
