const SOCIAL_ERRORS = {
  cancelado: "Cancelou o início de sessão. Pode tentar novamente.",
  conta_existente:
    "Já existe uma conta com este email. Entre com a sua password.",
  conta_desativada: "Esta conta foi desativada.",
  sem_email:
    "O fornecedor não partilhou o seu email, por isso não foi possível criar a conta.",
  indisponivel: "Este método de início de sessão não está disponível.",
  falhou: "Não foi possível iniciar sessão. Tente novamente.",
  sessao_expirada: "A sua sessão terminou. Inicie sessão novamente.",
};

// URL code -> message ("" when there is no error)
export function socialErrorMessage(code) {
  if (!code) return "";
  // hasOwn stops codes such as "constructor" from matching Object's properties
  return Object.hasOwn(SOCIAL_ERRORS, code)
    ? SOCIAL_ERRORS[code]
    : SOCIAL_ERRORS.falhou;
}