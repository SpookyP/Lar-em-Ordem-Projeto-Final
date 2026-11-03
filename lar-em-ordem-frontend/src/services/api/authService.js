import axiosClient from "./axiosClient";

async function getCookieCsrf() {
  await axiosClient.get("/sanctum/csrf-cookie");
}

export async function login(email, password) {
  await getCookieCsrf();
  await axiosClient.post("/login", { email, password });
  return getCurrentUser();
}

export async function logout() {
  await axiosClient.post("/logout");
}

export async function getCurrentUser() {
  const response = await axiosClient.get("/api/user");
  return response.data;
}

export async function register(data) {
  await getCookieCsrf();
  return axiosClient.post("/api/auth/register", data);
}

export async function confirmRegistration(token) {
  await getCookieCsrf();
  return axiosClient.post("/api/auth/register/confirm", { token });
}

export async function resendRegistration(email) {
  await getCookieCsrf();
  return axiosClient.post("/api/auth/register/resend", { email });
}

export async function getSocialPending() {
  const response = await axiosClient.get("/api/auth/social/pending");
  return response.data;
}

export async function registerSocial(data) {
  await getCookieCsrf();
  return axiosClient.post("/api/auth/social/register", data);
}

export async function forgotPassword(email) {
  await getCookieCsrf();
  return axiosClient.post("/forgot-password", { email });
}

export async function resetPassword(data) {
  await getCookieCsrf();
  return axiosClient.post("/reset-password", data);
}

export async function verifyEmail(id, hash, params) {
  await getCookieCsrf();
  return axiosClient.post(`/api/email/verify/${id}/${hash}`, null, { params });
}

export async function resendVerification() {
  await getCookieCsrf();
  return axiosClient.post("/api/email/verification-notification");
}