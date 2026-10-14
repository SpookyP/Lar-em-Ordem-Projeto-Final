import axiosClient from "./axiosClient";

//Mudar a pass de um utilizador autenticado
export async function updatePassword(data) {
  await axiosClient.post("/password", data);
}
