import { authenticatedClient } from "@/modules/http";

export const historyApi = {
  list: (params) => authenticatedClient.get("/history", { params }),
  create: (data) => authenticatedClient.post("/history/exports", data),
  status: (id) => authenticatedClient.get(`/history/exports/${id}`),
  download: (id) =>
    authenticatedClient.get(`/history/exports/${id}/download`, {
      responseType: "blob",
    }),
};
