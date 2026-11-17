import { publicClient } from "@/modules/http";

export const requestPasswordLink = (email) =>
  publicClient.post("/auth/forgot-password", { email });

export const resetPassword = (payload) =>
  publicClient.post("/auth/reset-password", payload);
