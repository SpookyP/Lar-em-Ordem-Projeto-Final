import { useState } from "react";
import { useAuth } from "../../hooks/useAuth";
import { roleFields } from "../../data/registerFields";
import { Field } from "./Field";
import { updateProfileInformation } from "../../services/api/accountService";

export function AccountForm() {
  const { user, activeProfile, refreshUser } = useAuth();
  const role = activeProfile?.name;

  const [editing, setEditing] = useState(false);
  const [status, setStatus] = useState(null);
  const [errorMsg, setErrorMsg] = useState("");

  const fields = roleFields[role] ?? [];
  const profileData = user?.[role] ?? {};

  //estado inicial do form: nome, email + campos do perfil ja preenchidos
  const [form, setForm] = useState(() => {
    const base = { name: user?.name ?? "", email: user?.email ?? "" };
    fields.forEach((f) => {
      base[f.name] = profileData[f.name] ?? "";
    });
    return base;
  });

  function handleChange(e) {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  }

  function startEditing() {
    const base = { name: user?.name ?? "", email: user?.email ?? "" };
    fields.forEach((f) => {
      base[f.name] = profileData[f.name] ?? "";
    });
    setForm(base);
    setStatus(null);
    setErrorMsg("");
    setEditing(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setStatus("saving");
    setErrorMsg("");

    const payload = { role, name: form.name, email: form.email };
    fields.forEach((f) => {
      payload[f.name] = form[f.name];
    });

    try {
      await updateProfileInformation(payload);
      await refreshUser();
      setEditing(false);
    } catch (error) {
      setStatus("error");
      setErrorMsg(
        error.response?.data?.message ??
          "Não foi possível guardar as alterações.",
      );
    }
  }

  if (!role) {
    return <p className="text-sm text-muted">Selecione um perfil primeiro.</p>;
  }

  if (!editing) {
    return (
      <div className="flex flex-col gap-4">
        <div className="flex items-center justify-between">
          <h2 className="font-medium text-text">Os meus dados</h2>
          <button
            onClick={startEditing}
            className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-navy hover:bg-surface transition"
          >
            Editar
          </button>
        </div>

        <dl className="flex flex-col gap-3 text-sm">
          <DataRow label="Nome" value={user?.name} />
          <DataRow label="Email" value={user?.email} />
          {fields.map((f) => (
            <DataRow key={f.name} label={f.label} value={profileData[f.name]} />
          ))}
        </dl>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Editar dados</h2>

      <Field
        label="Nome"
        name="name"
        value={form.name}
        onChange={handleChange}
      />
      <Field
        label="Email"
        name="email"
        type="email"
        value={form.email}
        onChange={handleChange}
      />

      {fields.map((f) => (
        <Field
          key={f.name}
          label={f.label}
          name={f.name}
          type={f.type}
          value={form[f.name]}
          onChange={handleChange}
        />
      ))}

      {status === "error" && <p className="text-sm text-red-600">{errorMsg}</p>}

      <div className="flex gap-3">
        <button
          type="button"
          onClick={() => setEditing(false)}
          className="rounded-lg border border-border px-4 py-2 text-sm font-medium text-text hover:bg-surface transition"
        >
          Cancelar
        </button>
        <button
          type="submit"
          disabled={status === "saving"}
          className="rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-navy-dark disabled:opacity-60 transition"
        >
          {status === "saving" ? "A guardar..." : "Guardar"}
        </button>
      </div>
    </form>
  );
}

function DataRow({ label, value }) {
  return (
    <div className="flex flex-col">
      <dt className="text-muted">{label}</dt>
      <dd className="text-text">{value || "—"}</dd>
    </div>
  );
}
