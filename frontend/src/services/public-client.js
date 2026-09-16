import axios from "axios";

const apiUrl = import.meta.env.VITE_API_URL;

if (!apiUrl) {
  throw new Error("VITE_API_URL não está configurada.");
}

const publicApiClient = axios.create({
  baseURL: apiUrl,

  headers: {
    Accept: "application/json",
  },

  timeout: 15_000,
});

export default publicApiClient;
