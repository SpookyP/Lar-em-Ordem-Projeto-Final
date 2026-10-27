const SOCIAL_ERRORS = {
  cancelado: "Cancelou o início de sessão. Pode tentar novamente.",
  conta_existente:
    "Já existe uma conta com este email. Entre com a sua password.",
  conta_desativada: "Esta conta foi desativada.",
  sem_email:
    "O fornecedor não partilhou o seu email, por isso não foi possível criar a conta.",
  indisponivel: "Este método de início de sessão não está disponível.",
  falhou: "Não foi possível iniciar sessão. Tente novamente.",
};

//codigo do URL -> mensagem ("" quando nao ha erro)
export function socialErrorMessage(code) {
  if (!code) return "";
  //hasOwn evita que codigos como "constructor" apanhem coisas do Object
  return Object.hasOwn(SOCIAL_ERRORS, code)
    ? SOCIAL_ERRORS[code]
    : SOCIAL_ERRORS.falhou;
}