import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft, ArrowRight, Bell, Building, Eye, House, MapPin, Pencil } from "lucide-react";
import {
  getCondominium,
  regenerateInviteCode,
  removeProperty,
} from "../../../services/api/condominiumService";
import { InviteCode } from "../../../components/condominium/InviteCode";
import { TransferCondominium } from "../../../components/condominium/TransferCondominium";
import { CondominiumForm } from "../../../components/condominium/CondominiumForm";
import { addressLine, formatMoney } from "../../../utils/format";
import { formatDate } from "../../../utils/date";
import { getErrorMessage } from "../../../utils/apiError";
import { fractionLabel, priorityInfo } from "../../../data/condominiumInfo";

function Card({ title, description, action, children }) {
  return (
    <section className="flex flex-col gap-4 rounded-2xl border border-border bg-card p-7 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-navy">{title}</h2>
          {description && <p className="mt-1 text-sm text-muted">{description}</p>}
        </div>
        {action}
      </div>
      {children}
    </section>
  );
}

function HeroStat({ label, value }) {
  return (
    <div className="flex flex-col gap-1.5 px-8 py-6">
      <p className="text-xs font-semibold uppercase tracking-wider text-white/60">{label}</p>
      <p className="font-display text-2xl">{value}</p>
    </div>
  );
}

function Fractions({ condominium, onRemoved }) {
  const [confirmingId, setConfirmingId] = useState(null);
  const [busyId, setBusyId] = useState(null);
  const [error, setError] = useState("");

  async function handleRemove(property) {
    setBusyId(property.id);
    setError("");
    try {
      await removeProperty(condominium.id, property.id);
      setConfirmingId(null);
      onRemoved(property.id);
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível retirar a fração."));
    } finally {
      setBusyId(null);
    }
  }

  if (condominium.properties.length === 0) {
    return (
      <p className="text-sm text-muted">
        Ainda nenhuma casa entrou. Partilha o código de convite com os condóminos.
      </p>
    );
  }

  return (
    <>
      {error && (
        <p role="alert" className="text-sm text-red">
          {error}
        </p>
      )}
      <ul className="flex flex-col divide-y divide-border">
        {condominium.properties.map((property) => (
          <li key={property.id} className="flex flex-col gap-2 py-3.5 first:pt-0 last:pb-0">
            <div className="flex items-center justify-between gap-3">
              <span className="flex items-center gap-3 text-sm font-medium text-text">
                <span className="flex size-10 items-center justify-center rounded-full bg-surface text-muted">
                  <House size={17} aria-hidden="true" />
                </span>
                {fractionLabel(property)}
              </span>
              {confirmingId !== property.id && (
                <button
                  type="button"
                  onClick={() => setConfirmingId(property.id)}
                  className="text-sm text-red hover:opacity-80"
                >
                  Retirar
                </button>
              )}
            </div>

            {confirmingId === property.id && (
              <div className="flex flex-col gap-2 rounded-xl bg-surface p-3 text-sm">
                <p className="text-muted">
                  A casa deixa de ver os avisos e os documentos do condomínio. O morador pode
                  voltar a entrar com o código de convite.
                </p>
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={() => handleRemove(property)}
                    disabled={busyId === property.id}
                    className="rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
                  >
                    {busyId === property.id ? "A retirar..." : "Sim, retirar"}
                  </button>
                  <button
                    type="button"
                    onClick={() => setConfirmingId(null)}
                    className="text-muted underline hover:text-navy"
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            )}
          </li>
        ))}
      </ul>
    </>
  );
}

function CurrentNotices({ notices }) {
  if (notices.length === 0) {
    return <p className="text-sm text-muted">Não há avisos em vigor.</p>;
  }

  return (
    <ul className="flex flex-col gap-3">
      {notices.map((notice) => {
        const priority = priorityInfo(notice.priority);
        return (
          <li key={notice.id} className="flex items-start gap-3.5 rounded-xl border border-border p-4">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-light text-orange">
              <Bell size={18} aria-hidden="true" />
            </span>
            <div className="flex min-w-0 flex-1 flex-col gap-1">
              <div className="flex flex-wrap items-center gap-2">
                <h3 className="font-medium text-text">{notice.title}</h3>
                <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${priority.className}`}>
                  {priority.label}
                </span>
              </div>
              <p className="text-xs text-muted">
                Publicado a {formatDate(notice.publishedAt)}
                {notice.expiresAt ? ` · em vigor até ${formatDate(notice.expiresAt)}` : ""}
              </p>
              {notice.readersCount != null && (
                <p className="flex items-center gap-1.5 text-xs text-muted">
                  <Eye size={13} aria-hidden="true" />
                  Lido por {notice.readersCount}{" "}
                  {notice.readersCount === 1 ? "residente" : "residentes"}
                </p>
              )}
            </div>
          </li>
        );
      })}
    </ul>
  );
}

export default function CondominiumDetail() {
  const { id } = useParams();
  const [condominium, setCondominium] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [codeError, setCodeError] = useState("");
  const [editing, setEditing] = useState(false);

  useEffect(() => {
    let active = true;
    getCondominium(id)
      .then((data) => active && setCondominium(data))
      .catch((err) =>
        active && setError(getErrorMessage(err, "Não foi possível carregar o condomínio.")),
      )
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [id]);

  async function handleRegenerate() {
    setCodeError("");
    try {
      const updated = await regenerateInviteCode(id);
      setCondominium((current) => ({ ...current, inviteCode: updated.inviteCode }));
    } catch (err) {
      setCodeError(getErrorMessage(err, "Não foi possível gerar um novo código."));
    }
  }

  function handleRemoved(propertyId) {
    setCondominium((current) => ({
      ...current,
      properties: current.properties.filter((p) => p.id !== propertyId),
      propertiesCount: Math.max(0, current.propertiesCount - 1),
    }));
  }

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-6">
      <nav aria-label="Caminho" className="flex items-center gap-2 text-sm text-muted">
        <Link to="/admin/condominiums" className="flex items-center gap-2 transition hover:text-navy">
          <ArrowLeft size={16} aria-hidden="true" />
          Condomínios
        </Link>
        <span aria-hidden="true">/</span>
        <span className="truncate font-medium text-text">{condominium?.name ?? "Condomínio"}</span>
      </nav>

      {error && (
        <p role="alert" className="rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red">
          {error}
        </p>
      )}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        condominium && (
          <>
            <header className="relative overflow-hidden rounded-3xl bg-navy text-white">
              <Building
                size={300}
                strokeWidth={0.5}
                aria-hidden="true"
                className="pointer-events-none absolute -right-6 -top-6 text-white/10"
              />
              <div className="relative flex flex-wrap items-start justify-between gap-4 px-8 pb-8 pt-9">
                <div className="min-w-0">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal-light">
                    Condomínio
                  </p>
                  <h1 className="mt-2 font-display text-4xl">{condominium.name}</h1>
                  <p className="mt-3 flex items-start gap-2 text-white/80">
                    <MapPin size={17} className="mt-0.5 shrink-0" aria-hidden="true" />
                    <span>
                      {addressLine(condominium.address)}
                      {condominium.address?.location ? ` (${condominium.address.location})` : ""}
                    </span>
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setEditing(true)}
                  className="flex shrink-0 items-center gap-2 rounded-xl bg-card px-4 py-2.5 font-semibold text-navy transition hover:bg-surface"
                >
                  <Pencil size={16} /> Editar dados
                </button>
              </div>
              <div className="relative grid divide-y divide-white/10 border-t border-white/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <HeroStat
                  label="Fundo comum de reserva"
                  value={
                    condominium.reserveFund != null
                      ? formatMoney(condominium.reserveFund)
                      : "Sem valor"
                  }
                />
                <HeroStat label="Frações ligadas" value={condominium.propertiesCount} />
                <HeroStat label="Avisos em vigor" value={condominium.notices.length} />
              </div>
            </header>

            <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
              <div className="flex min-w-0 flex-col gap-6 lg:col-span-2">
                <Card title="Frações" description="Casas ligadas a este condomínio.">
                  <Fractions condominium={condominium} onRemoved={handleRemoved} />
                </Card>

                <Card
                  title="Avisos em vigor"
                  description="Quantos residentes já leram cada aviso."
                  action={
                    <Link
                      to={`/admin/condominiums/${id}/notices`}
                      state={{ name: condominium.name }}
                      className="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-teal hover:underline"
                    >
                      Gerir avisos <ArrowRight size={15} aria-hidden="true" />
                    </Link>
                  }
                >
                  <CurrentNotices notices={condominium.notices} />
                </Card>

                <Card
                  title="Documentos"
                  description="Atas, regulamento interno e orçamentos, visíveis para os condóminos."
                  action={
                    <Link
                      to={`/admin/condominiums/${id}/documents`}
                      state={{ name: condominium.name }}
                      className="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-teal hover:underline"
                    >
                      Gerir documentos <ArrowRight size={15} aria-hidden="true" />
                    </Link>
                  }
                />
              </div>

              <div className="flex min-w-0 flex-col gap-6">
                <Card
                  title="Convidar condóminos"
                  description="Cada morador liga a sua casa ao condomínio com este código."
                >
                  {codeError && (
                    <p role="alert" className="text-sm text-red">
                      {codeError}
                    </p>
                  )}
                  <InviteCode code={condominium.inviteCode} onRegenerate={handleRegenerate} />
                </Card>

                <Card
                  title="Transferir condomínio"
                  description="Passa a administração para outra pessoa ou empresa, com um código válido durante 7 dias."
                >
                  <TransferCondominium
                    condominiumId={id}
                    expiresAt={condominium.transferExpiresAt}
                    onChange={(expiresAt) =>
                      setCondominium((current) => ({ ...current, transferExpiresAt: expiresAt }))
                    }
                  />
                </Card>
              </div>
            </div>
          </>
        )
      )}

      {editing && (
        <CondominiumForm
          condominium={condominium}
          onSaved={() => {
            setEditing(false);
            getCondominium(id)
              .then(setCondominium)
              .catch(() => {});
          }}
          onClose={() => setEditing(false)}
        />
      )}
    </div>
  );
}