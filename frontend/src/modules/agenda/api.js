import { authenticatedClient as client } from "@/modules/http";

export const agendaApi = {
  agenda: (params) => client.get("/agenda", { params }),
};
