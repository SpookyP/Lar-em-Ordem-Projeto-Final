import { useAuth } from "../../hooks/useAuth";
import { useState } from "react";
import { Field } from "./Field";
import { addProfile } from "../../services/api/accountService";
import { accountTypes, roleFields } from "../../data/registerFields";

export function ProfileForm() {
  const { user, refreshUser } = useAuth();
  const [role, setRole] = useState("");
  const [form, setForm] = useState({});
  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");

  const currentRoles = user?.roles ?? [];
  const availableTypes = accountTypes.filter(
    (type) => !currentRoles.includes(type.role),
  );

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!role) {
      setStatus("error");
      setErrorMsg("Escolha um perfil.");
      return;
    }
    setStatus("saving");
    setErrorMsg("");

    const payload = { role };
    roleFields[role].forEach((field) => {
      payload[field.name] = form[field.name];
    });

    try {
      await addProfile(payload);
      await refreshUser();
      setStatus("success");
      setRole("");
      setForm({});
    } catch (erro) {
      setStatus("error");
      setErrorMsg(
        erro.response?.data?.message ?? "Não foi possível adicionar o perfil.",
      );
    }
  }

  if (availableTypes.length === 0) {
    return (
      <p className="text-sm text-muted">Já tens todos os perfis disponíveis.</p>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Adicionar perfil</h2>

      {/* escolher o perfil */}
      <div>
        <p className="mb-2 text-sm text-muted">Tipo de perfil</p>
        <div className="flex flex-col gap-2">
          {availableTypes.map((type) => (
            <button
              key={type.role}
              type="button"
              onClick={() => setRole(type.role)}
              className={`rounded-lg border px-3 py-2 text-left transition ${
                role === type.role
                  ? "border-navy bg-navy/5"
                  : "border-border hover:border-navy/40"
              }`}
            >
              <span className="font-medium text-text">{type.label}</span>
              <span className="block text-sm text-muted">
                {type.description}
              </span>
            </button>
          ))}
        </div>
      </div>

      {/* campos específicos da role escolhida */}
      {role &&
        roleFields[role].map((field) => (
          <Field
            key={field.name}
            label={field.label}
            name={field.name}
            type={field.type}
            value={form[field.name] ?? ""}
            onChange={handleChange}
          />
        ))}

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}
      {status === "success" && (
        <p className="text-sm text-green-600">Perfil adicionado com sucesso.</p>
      )}

      <button
        type="submit"
        disabled={status === "saving"}
        className="self-start rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
      >
        {status === "saving" ? "A guardar..." : "Adicionar perfil"}
      </button>
    </form>
  );
}
