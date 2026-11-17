import { useGroupContext } from "@/modules/groups";
import { computed, ref } from "vue";

import { defineStore } from "pinia";

import {
  login as loginRequest,
  logout as logoutRequest,
  me as meRequest,
  refresh as refreshRequest,
  register as registerRequest,
} from "../api/auth.js";

import {
  getAccessToken,
  removeAccessToken,
  setAccessToken,
} from "../session/token.js";

export const useAuthStore = defineStore("auth", () => {
  let identityGeneration = 0;
  const token = ref(null);

  const user = ref(null);

  const last_login_at = ref(null);

  const hydrated = ref(false);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));
  const { applyMemberships, resetGroupContext, ...groups } = useGroupContext({
    isAuthenticated,
    identityVersion: () => identityGeneration,
    fetchMe,
  });

  const userName = computed(() => user.value?.name ?? "");

  const userEmail = computed(() => user.value?.email ?? "");

  function applyIdentityPayload(payload) {
    user.value = payload?.user ?? null;

    last_login_at.value =
      payload?.user?.last_login_at ?? (user.value ? last_login_at.value : null);

    applyMemberships(payload);
  }

  function applyAuthPayload(payload) {
    identityGeneration++;
    const accessToken = payload?.access_token ?? payload?.token ?? null;

    token.value = accessToken;

    applyIdentityPayload(payload);

    if (accessToken) {
      setAccessToken(accessToken);
    } else {
      removeAccessToken();
    }
  }

  function clearAuth() {
    identityGeneration++;
    token.value = null;

    user.value = null;

    last_login_at.value = null;

    resetGroupContext();

    removeAccessToken();
  }

  function restoreToken() {
    token.value = getAccessToken();

    hydrated.value = true;

    return token.value;
  }

  async function login(credentials) {
    const response = await loginRequest(credentials);

    applyAuthPayload(response.data);

    last_login_at.value =
      response.data?.user?.last_login_at ?? new Date().toISOString();

    await groups.initializeContext();

    return response.data;
  }

  async function register(payload) {
    const response = await registerRequest(payload);

    applyAuthPayload(response.data);

    await groups.initializeContext();

    return response.data;
  }

  async function fetchMe() {
    const generation = identityGeneration;
    const response = await meRequest();
    if (generation !== identityGeneration) return null;

    const payload = response.data;

    applyIdentityPayload(payload);

    return payload;
  }

  async function refresh() {
    const response = await refreshRequest();

    applyAuthPayload(response.data);

    return response.data;
  }

  async function logout() {
    try {
      if (token.value) {
        await logoutRequest();
      }
    } finally {
      clearAuth();
    }
  }

  async function hydrate() {
    if (hydrated.value) {
      return;
    }

    restoreToken();

    if (!token.value) {
      return;
    }

    try {
      await fetchMe();

      await groups.initializeContext();
    } catch {
      clearAuth();
    }
  }

  return {
    ...groups,
    token,
    user,
    last_login_at,
    hydrated,
    isAuthenticated,

    userName,
    userEmail,

    applyIdentityPayload,
    applyAuthPayload,

    clearAuth,
    restoreToken,
    login,
    register,
    fetchMe,
    refresh,
    logout,
    hydrate,
  };
});
