import { useEffect, useMemo, useRef, useState } from "react";
import { ArrowLeft, ArrowRight, Mail, User } from "lucide-react";
import { accountTypes, roleFields } from "../../data/registerFields";
import { PasswordRequirements } from "../ui/PasswordRequirements";
import { DataRow } from "../ui/DataRow";
import {
  ChoiceCards,
  PasswordField,
  StepProgress,
  TextInput,
} from "../form";
import { getErrorMessage, getFieldErrors } from "../../utils/apiError";
import { formatPhone } from "../../utils/format";
import {
  EMPTY_COVERAGE,
  SpecialtyCoverageField,
  SpecialtySummary,
  ZoneCoverageField,
  ZoneSummary,
} from "../ui/ProviderCoverageFields";
import { ProfileFieldInput } from "./ProfileFieldInput";
import { buildPayload, buildSteps, firstStepWithErrors } from "./profileSteps";
import { validateFields } from "./profileValidation";

const EMPTY_VALUES = {
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  nif: "",
  company_name: "",
  phone: "",
  provider_email: "",
  website: "",
  description: "",
};

function fieldFor(role, name) {
  return (roleFields[role] ?? []).find((f) => f.name === name);
}

export function ProfileWizard({
  roles = accountTypes,
  withAccount = false,
  accountIntro = null,
  onSubmit,
  submitLabel,
  submittingLabel,
  footer = null,
}) {
  const [values, setValues] = useState(EMPTY_VALUES);
  const [role, setRole] = useState(roles.length === 1 ? roles[0].role : "");
  const [coverage, setCoverage] = useState(EMPTY_COVERAGE);
  const [stepIndex, setStepIndex] = useState(0);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const submittingRef = useRef(false);
  const headingRef = useRef(null);
  const shownStep = useRef(stepIndex);

  const steps = useMemo(() => buildSteps({ withAccount, role }), [withAccount, role]);
  const step = steps[stepIndex];
  const isLast = stepIndex === steps.length - 1;

  // focus the step title when the step changes, so keyboard and screen-reader users follow along
  useEffect(() => {
    if (shownStep.current === stepIndex) return;
    shownStep.current = stepIndex;
    headingRef.current?.focus();
  }, [stepIndex]);

  function clearErrors(...names) {
    setErrors((current) => {
      if (!names.some((name) => current[name])) return current;
      const next = { ...current };
      names.forEach((name) => delete next[name]);
      return next;
    });
  }

  function setValue(name, value) {
    setValues((current) => ({ ...current, [name]: value }));
    clearErrors(name);
  }

  function goTo(index) {
    setFormError("");
    setStepIndex(index);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (submittingRef.current) return;

    if (!isLast) {
      const stepErrors =
        step.kind === "type"
          ? role
            ? {}
            : { role: "Escolha um tipo de perfil." }
          : validateFields(step.fields, values);

      if (Object.keys(stepErrors).length > 0) {
        setErrors((current) => ({ ...current, ...stepErrors }));
        return;
      }

      goTo(stepIndex + 1);
      return;
    }

    submittingRef.current = true;
    setSubmitting(true);
    setFormError("");

    try {
      await onSubmit(buildPayload({ withAccount, values, role, coverage }));
    } catch (error) {
      const fieldErrors = getFieldErrors(error);

      if (Object.keys(fieldErrors).length > 0) {
        const index = firstStepWithErrors(steps, fieldErrors);
        setErrors(fieldErrors);
        setStepIndex(index);
        setFormError(
          index === steps.length - 1
            ? Object.values(fieldErrors)[0]
            : "Verifique os campos assinalados.",
        );
      } else {
        setFormError(getErrorMessage(error, "Não foi possível concluir. Tente novamente."));
      }
    } finally {
      submittingRef.current = false;
      setSubmitting(false);
    }
  }

  const roleOptions = roles.map((t) => ({
    value: t.role,
    label: t.label,
    description: t.description,
    icon: t.icon,
  }));

  function renderStep() {
    switch (step.kind) {
      case "account":
        return (
          <>
            {accountIntro}
            <TextInput
              label="Nome"
              icon={User}
              value={values.name}
              onChange={(v) => setValue("name", v)}
              error={errors.name}
              autoComplete="name"
              maxLength={255}
              required
            />
            <TextInput
              label="Email"
              type="email"
              icon={Mail}
              value={values.email}
              onChange={(v) => setValue("email", v)}
              error={errors.email}
              autoComplete="email"
              autoCapitalize="none"
              spellCheck={false}
              maxLength={255}
              required
            />
            <PasswordField
              label="Password"
              value={values.password}
              onChange={(v) => setValue("password", v)}
              error={errors.password}
              autoComplete="new-password"
              required
            />
            {values.password && <PasswordRequirements password={values.password} />}
            <PasswordField
              label="Confirmar password"
              value={values.password_confirmation}
              onChange={(v) => setValue("password_confirmation", v)}
              error={errors.password_confirmation}
              autoComplete="new-password"
              required
            />
          </>
        );

      case "type":
        return (
          <ChoiceCards
            label="Tipo de perfil"
            options={roleOptions}
            value={role}
            onChange={(next) => {
              setRole(next);
              clearErrors("role");
            }}
            error={errors.role}
          />
        );

      case "details":
        return step.fields.map((name) => {
          const field = fieldFor(role, name);
          if (!field) return null;
          return (
            <ProfileFieldInput
              key={name}
              field={field}
              value={values[name]}
              onChange={(v) => setValue(name, v)}
              error={errors[name]}
            />
          );
        });

      case "zones":
        return (
          <ZoneCoverageField
            label="Concelhos"
            value={coverage.zone_ids}
            onChange={(zone_ids) => {
              setCoverage((c) => ({ ...c, zone_ids }));
              clearErrors("zone_ids");
            }}
            error={errors.zone_ids}
          />
        );

      case "specialties":
        return (
          <SpecialtyCoverageField
            value={coverage}
            onChange={(next) => {
              setCoverage((c) => ({ ...c, ...next }));
              clearErrors("service_specialty_ids", "custom_specialties");
            }}
            errors={errors}
          />
        );

      default:
        return (
          <ReviewStep
            steps={steps}
            values={values}
            role={role}
            roles={roles}
            coverage={coverage}
            onEdit={goTo}
          />
        );
    }
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-6">
      <StepProgress current={stepIndex} total={steps.length} totalKnown={Boolean(role)} />

      <div className="flex flex-col gap-1">
        <h2
          ref={headingRef}
          tabIndex={-1}
          className="text-lg font-semibold text-navy outline-none"
        >
          {step.title}
        </h2>
        {step.description && <p className="text-sm text-muted">{step.description}</p>}
      </div>

      <div className="flex flex-col gap-4">{renderStep()}</div>

      {formError && (
        <p role="alert" className="rounded-lg bg-red-light px-3 py-2 text-sm text-red">
          {formError}
        </p>
      )}

      <div className="flex items-center gap-3">
        {stepIndex > 0 && (
          <button
            type="button"
            onClick={() => goTo(stepIndex - 1)}
            disabled={submitting}
            className="inline-flex items-center gap-1.5 rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-text transition hover:bg-surface disabled:opacity-60"
          >
            <ArrowLeft size={16} aria-hidden="true" />
            Voltar
          </button>
        )}
        <button
          type="submit"
          disabled={submitting}
          className="ml-auto inline-flex items-center gap-1.5 rounded-xl bg-navy px-5 py-2.5 text-sm font-medium text-white transition hover:bg-navy-dark disabled:opacity-60"
        >
          {isLast ? (
            submitting ? submittingLabel : submitLabel
          ) : (
            <>
              Continuar
              <ArrowRight size={16} aria-hidden="true" />
            </>
          )}
        </button>
      </div>

      {footer}
    </form>
  );
}

function ReviewStep({ steps, values, role, roles, coverage, onEdit }) {
  const roleLabel = roles.find((t) => t.role === role)?.label;

  return (
    <div className="divide-y divide-border rounded-xl border border-border">
      {steps.slice(0, -1).map((block, index) => (
        <div key={block.id} className="flex items-start justify-between gap-4 p-4">
          <div className="min-w-0 flex-1">
            <p className="mb-2 text-sm font-medium text-text">{block.title}</p>
            <ReviewContent
              block={block}
              values={values}
              role={role}
              roleLabel={roleLabel}
              coverage={coverage}
            />
          </div>
          <button
            type="button"
            onClick={() => onEdit(index)}
            className="shrink-0 text-sm font-medium text-navy hover:underline"
          >
            Alterar
          </button>
        </div>
      ))}
    </div>
  );
}

function ReviewContent({ block, values, role, roleLabel, coverage }) {
  if (block.kind === "account") {
    return (
      <dl className="flex flex-col gap-2 text-sm">
        <DataRow label="Nome" value={values.name.trim()} />
        <DataRow label="Email" value={values.email.trim()} />
      </dl>
    );
  }
  if (block.kind === "type") return <p className="text-sm text-text">{roleLabel}</p>;
  if (block.kind === "zones") return <ZoneSummary zoneIds={coverage.zone_ids} />;
  if (block.kind === "specialties") return <SpecialtySummary value={coverage} />;

  return (
    <dl className="flex flex-col gap-2 text-sm">
      {block.fields.map((name) => {
        const field = fieldFor(role, name);
        if (!field) return null;
        const value = String(values[name] ?? "").trim();
        return (
          <DataRow
            key={name}
            label={field.label}
            value={field.type === "tel" ? formatPhone(value) : value}
          />
        );
      })}
    </dl>
  );
}