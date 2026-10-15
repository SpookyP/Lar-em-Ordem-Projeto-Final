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
