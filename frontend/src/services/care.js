import client from "./client.js";
export const careApi = {
  decisions: (params) => client.get("/decisions", { params }),
  decision: (type, id) => client.get(`/decisions/${type}/${id}`),
  proposeProfile: (id, data) =>
    client.post(`/recipients/${id}/profile-proposals`, data),
  decideProfile: (id, proposal, data) =>
    client.post(
      `/recipients/${id}/profile-proposals/${proposal}/decision`,
      data,
    ),
  withdrawProfile: (id, proposal) =>
    client.post(`/recipients/${id}/profile-proposals/${proposal}/withdraw`),
  execute: (id, entry, description, occurredAt) =>
    client.post("/recipients/" + id + "/entries/" + entry + "/execution", {
      description,
      ...(occurredAt ? { occurred_at: occurredAt } : {}),
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
  agenda: (params) => client.get("/agenda", { params }),
  executeOccurrence: (id, data) =>
    client.post(`/occurrences/${id}/execution`, data),
  cancelOccurrence: (id, scope) =>
    client.post(`/occurrences/${id}/cancellation`, { scope }),
  unreadCount: () => client.get("/notifications/unread-count"),
  finance: (params = {}) => client.get("/finance", { params }),
  notifications: () => client.get("/notifications"),
  read: (id) => client.post("/notifications/" + id + "/read"),
  paymentReceipt: (id, entry, share) =>
    client.get(`/recipients/${id}/entries/${entry}/shares/${share}/receipt`, {
      responseType: "blob",
    }),
  pay: (id, entry, share, receipt = null) => {
    const data = new FormData();
    if (receipt) data.append("receipt", receipt);
    return client.post(
      `/recipients/${id}/entries/${entry}/shares/${share}/pay`,
      data,
    );
  },
};
