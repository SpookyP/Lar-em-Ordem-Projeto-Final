import axiosClient from "./axiosClient";

//Mudar a pass de um utilizador autenticado
export async function updatePassword(data) {
  await axiosClient.put("/user/password", data);
}

//Adicionar perfil a uma conta
export async function addProfile(data) {
  const response = await axiosClient.post("/api/user/profile", data);
  return response.data;
}

//Alterar os dados do utilizador (nome, email e campos do perfil ativo)
export async function updateProfileInformation(data) {
  await axiosClient.put("/user/profile-information", data);
}

//Apagar um perfil (role) da conta
export async function deleteProfile(role) {
  await axiosClient.delete("/api/user/profile", { data: { role } });
}

//Apagar a conta inteira
export async function deleteAccount() {
  await axiosClient.delete("/api/user");
}
