const baseURL = import.meta.env.VITE_API_URL;

if (!baseURL) {
  throw new Error("VITE_API_URL não está configurada.");
}

export const httpConfig = {
  baseURL,
  headers: { Accept: "application/json" },
  timeout: 15_000,
};
