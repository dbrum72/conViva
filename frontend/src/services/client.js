import axios from "axios";

import { getAccessToken, removeAccessToken } from "./auth-token.js";

import { getCurrentTenant, removeCurrentTenant } from "./tenant.js";

const apiUrl = import.meta.env.VITE_API_URL;

if (!apiUrl) {
  throw new Error("VITE_API_URL não está configurada.");
}

const apiClient = axios.create({
  baseURL: apiUrl,

  headers: {
    Accept: "application/json",
  },

  timeout: 15_000,
});

apiClient.interceptors.request.use(
  (config) => {
    const token = getAccessToken();
    const tenant = getCurrentTenant();

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (tenant) {
      config.headers["X-Tenant"] = tenant;
    }

    return config;
  },

  (error) => Promise.reject(error),
);

apiClient.interceptors.response.use(
  (response) => response,

  (error) => {
    const status = error.response?.status;

    if (status === 401) {
      removeAccessToken();
      removeCurrentTenant();
    }

    return Promise.reject(error);
  },
);

export default apiClient;
