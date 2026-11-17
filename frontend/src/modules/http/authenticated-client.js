import axios from "axios";

import { getAccessToken, removeAccessToken } from "@/modules/auth/session/token.js";
import { getCurrentTenant, removeCurrentTenant } from "@/modules/groups/context/tenant.js";
import { httpConfig } from "./config.js";

let onSessionExpired = () => {};
export function setSessionExpiredHandler(handler) {
  onSessionExpired = handler;
}

const apiClient = axios.create(httpConfig);

apiClient.interceptors.request.use(
  (config) => {
    const token = getAccessToken();
    const tenant = getCurrentTenant();

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (tenant && !config.headers.has("X-Tenant")) {
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

    const requestToken = error.config?.headers?.Authorization;
    if (
      status === 401 &&
      requestToken &&
      requestToken === `Bearer ${getAccessToken()}`
    ) {
      removeAccessToken();
      removeCurrentTenant();
      onSessionExpired();
    }

    return Promise.reject(error);
  },
);

export default apiClient;
