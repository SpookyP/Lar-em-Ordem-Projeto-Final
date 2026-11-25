import axiosClient from "./axiosClient";

// Change an authenticated user's password
export async function updatePassword(data) {
  await axiosClient.put("/user/password", data);
}

// Add a profile to an account
export async function addProfile(data) {
  const response = await axiosClient.post("/api/user/profile", data);
  return response.data;
}

// Change the user's details (name, email and the active profile's fields)
export async function updateProfileInformation(data) {
  await axiosClient.put("/user/profile-information", data);
}

// Delete a profile (role) from the account
export async function deleteProfile(role) {
  await axiosClient.delete("/api/user/profile", { data: { role } });
}

// Delete the whole account
export async function deleteAccount() {
  await axiosClient.delete("/api/user");
}

// Change the data sharing answer; returns the updated consents
export async function updatePrivacy(data) {
  const response = await axiosClient.put("/api/user/privacy", data);
  return response.data;
}

// Download a copy of all the account's data (GDPR); returns the JSON file
export async function downloadMyData() {
  const response = await axiosClient.get("/api/user/export", { responseType: "blob" });
  return response.data;
}
