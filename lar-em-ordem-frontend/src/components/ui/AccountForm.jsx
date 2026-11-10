import { useState, useEffect } from "react";
import { Lock, Mail, User } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { displayValue, roleFields } from "../../data/registerFields";
import { updateProfileInformation } from "../../services/api/accountService";
import { getMyProviderCoverage } from "../../services/api/catalogService";
import { DataRow } from "./DataRow";
import { TextInput } from "../form";
import { ROLES } from "../../utils/roles";
import { EditableSection } from "../profile/EditableSection";
import { ProfileFieldInput } from "../profile/ProfileFieldInput";
import {
  EMPTY_COVERAGE,
  SpecialtyCoverageField,
  SpecialtySummary,
  ZoneCoverageField,
  ZoneSummary,
} from "./ProviderCoverageFields";

const PROVIDER_LABELS = { google: "o Google", microsoft: "a Microsoft" };

const DETAILS_TITLES = {
  [ROLES.RESIDENT]: "Dados fiscais",
  [ROLES.SERVICE_PROVIDER]: "Dados da empresa",
  [ROLES.PARTNER]: "Dados da empresa",
  [ROLES.CONDOMINIUM_ADMIN]: "Administração",
};

// the description and the choice cards take the full width on larger screens
function spanClass(field) {
  return field.type === "textarea" || field.type === "choice" ? "sm:col-span-2" : "";
}

function PersonalSection({ user, role, onSaved }) {
  const socialProvider = user?.social_provider ?? null;
  const [draft, setDraft] = useState({ name: "", email: "" });
  const emailChanged =
    !socialProvider && draft.email.trim() !== (user?.email ?? "");

  return (
    <EditableSection
      title="Dados pessoais"
      description="O seu nome e o email com que entra na conta."
      onEdit={() => setDraft({ name: user?.name ?? "", email: user?.email ?? "" })}
      onSave={async () => {
        const payload = { role, name: draft.name.trim() };
        if (!socialProvider) payload.email = draft.email.trim();
        await updateProfileInformation(payload);
        await onSaved();
      }}
      view={
        <dl className="grid gap-4 text-sm sm:grid-cols-2">
          <DataRow label="Nome" value={user?.name} />
          <DataRow label="Email" value={user?.email} />
        </dl>
      }
    >
      {({ errors }) => (
        <>
          <TextInput
            label="Nome"
            icon={User}
            value={draft.name}
            onChange={(name) => setDraft((d) => ({ ...d, name }))}
            error={errors.name}
            autoComplete="name"
            required
          />
          {socialProvider ? (
            <TextInput
              label="Email"
              icon={Lock}
              value={user?.email}
              onChange={() => {}}
              disabled
              hint={`Esta conta usa ${PROVIDER_LABELS[socialProvider] ?? "um fornecedor externo"}: o email não pode ser alterado.`}
            />
          ) : (
            <TextInput
              label="Email"
              type="email"
              icon={Mail}
              value={draft.email}
              onChange={(email) => setDraft((d) => ({ ...d, email }))}
              error={errors.email}
              autoComplete="email"
              required
              hint={
                emailChanged
                  ? "Vai receber um email para confirmar o novo endereço."
                  : undefined
              }
            />
          )}
        </>
      )}
    </EditableSection>
  );
}

function DetailsSection({ user, role, onSaved }) {
  const [draft, setDraft] = useState({});
  const fields = roleFields[role] ?? [];
  const profile = user?.[role] ?? {};

  if (fields.length === 0) return null;

  return (
    <EditableSection
      title={DETAILS_TITLES[role] ?? "Dados do perfil"}
      onEdit={() =>
        setDraft(Object.fromEntries(fields.map((f) => [f.name, profile[f.name] ?? ""])))
      }
      onSave={async () => {
        await updateProfileInformation({ role, ...draft });
        await onSaved();
      }}
      view={
        <dl className="grid gap-4 text-sm sm:grid-cols-2">
          {fields.map((f) => (
            <DataRow
              key={f.name}
              label={f.label}
              value={displayValue(f, profile[f.name])}
              className={spanClass(f)}
            />
          ))}
        </dl>
      }
    >
      {({ errors }) => (
        <div className="grid gap-4 sm:grid-cols-2">
          {fields.map((f) => (
            <ProfileFieldInput
              key={f.name}
              field={f}
              value={draft[f.name]}
              onChange={(value) => setDraft((d) => ({ ...d, [f.name]: value }))}
              error={errors[f.name]}
              className={spanClass(f)}
            />
          ))}
        </div>
      )}
    </EditableSection>
  );
}

function ProviderCoverageSections() {
  const [coverage, setCoverage] = useState(null);
  const [failed, setFailed] = useState(false);
  const [draft, setDraft] = useState(EMPTY_COVERAGE);

  useEffect(() => {
    let active = true;
    getMyProviderCoverage()
      .then((data) => active && setCoverage(data))
      .catch(() => active && setFailed(true));
    return () => {
      active = false;
    };
  }, []);

  if (failed) {
    return (
      <p className="text-sm text-red">
        Não foi possível carregar as zonas e especialidades.
      </p>
    );
  }
  if (!coverage) {
    return <p className="text-sm text-muted">A carregar zonas e especialidades...</p>;
  }

  // each card sends only its own keys; the API leaves the others untouched
  async function save(partial) {
    await updateProfileInformation({ role: ROLES.SERVICE_PROVIDER, ...partial });
    setCoverage((current) => ({ ...current, ...partial }));
  }

  return (
    <>
      <EditableSection
        title="Zonas de atuação"
        description="Concelhos onde aceita pedidos."
        onEdit={() => setDraft((d) => ({ ...d, zone_ids: coverage.zone_ids }))}
        onSave={() => save({ zone_ids: draft.zone_ids })}
        view={<ZoneSummary zoneIds={coverage.zone_ids} />}
      >
        {({ errors }) => (
          <ZoneCoverageField
            value={draft.zone_ids}
            onChange={(zone_ids) => setDraft((d) => ({ ...d, zone_ids }))}
            error={errors.zone_ids}
          />
        )}
      </EditableSection>

      <EditableSection
        title="Especialidades"
        description="Os tipos de trabalho que faz."
        onEdit={() =>
          setDraft((d) => ({
            ...d,
            service_specialty_ids: coverage.service_specialty_ids,
            custom_specialties: coverage.custom_specialties,
          }))
        }
        onSave={() =>
          save({
            service_specialty_ids: draft.service_specialty_ids,
            custom_specialties: draft.custom_specialties,
          })
        }
        view={<SpecialtySummary value={coverage} />}
      >
        {({ errors }) => (
          <SpecialtyCoverageField
            value={draft}
            onChange={(next) => setDraft((d) => ({ ...d, ...next }))}
            errors={errors}
          />
        )}
      </EditableSection>
    </>
  );
}

export function AccountForm() {
  const { user, activeProfile, refreshUser } = useAuth();
  const role = activeProfile?.name;

  if (!role) {
    return <p className="text-sm text-muted">Selecione um perfil primeiro.</p>;
  }

  return (
    <div className="flex flex-col gap-4">
      <h2 className="font-medium text-text">Os meus dados</h2>
      <PersonalSection user={user} role={role} onSaved={refreshUser} />
      <DetailsSection user={user} role={role} onSaved={refreshUser} />
      {role === ROLES.SERVICE_PROVIDER && <ProviderCoverageSections />}
    </div>
  );
}