import { authenticatedClient as client } from "@/modules/http";

export const expensesApi = {
  list: (params = {}) => client.get("/expenses", { params }),
  paymentReceipt: (id, entry, share) =>
    client.get(
      `/recipients/${id}/entries/${entry}/shares/${share}/receipt`,
      {
        responseType: "blob",
      },
    ),
  pay: (id, entry, share, receipt = null) => {
    const data = new FormData();
    if (receipt) data.append("receipt", receipt);
    return client.post(
      `/recipients/${id}/entries/${entry}/shares/${share}/pay`,
      data,
    );
  },
};
