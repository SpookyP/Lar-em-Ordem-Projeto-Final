import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { ChevronLeft, Eye, House, MapPin, Pencil } from "lucide-react";
import {
  getCondominium,
  regenerateInviteCode,
  removeProperty,
} from "../../../services/api/condominiumService";
import { InviteCode } from "../../../components/condominium/InviteCode";
import { addressLine, formatMoney } from "../../../utils/format";
import { formatDate } from "../../../utils/date";
import { getErrorMessage } from "../../../utils/apiError";
import { fractionLabel, priorityInfo } from "../../../data/condominiumInfo";

function Card({ title, description, action, children }) {
  return (
    <section className="flex flex-col gap-4 rounded-2xl bg-card p-6 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="font-medium text-navy">{title}</h2>
          {description && <p className="mt-1 text-sm text-muted">{description}</p>}
        </div>
        {action}
      </div>
      {children}
    </section>
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
          <li key={property.id} className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0">
            <div className="flex items-center justify-between gap-3">
              <span className="flex items-center gap-2 text-sm text-text">
                <House size={16} className="text-muted" aria-hidden="true" />
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
          <li key={notice.id} className="flex flex-col gap-1 rounded-xl border border-border p-4">
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
      // only the code is taken from the answer: the rest of the page is already loaded
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
    <div className="mx-auto flex max-w-5xl flex-col gap-6">
      <div className="flex items-center justify-between gap-3">
        <div className="flex min-w-0 items-center gap-3">
          <Link
            to="/admin/condominiums"
            aria-label="Voltar"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card text-muted shadow-sm transition hover:text-navy"
          >
            <ChevronLeft size={20} />
          </Link>
          <h1 className="truncate font-display text-2xl text-navy">
            {condominium?.name ?? "Condomínio"}
          </h1>
        </div>
        {condominium && (
          <Link
            to={`/admin/condominiums/${id}/edit`}
            className="flex shrink-0 items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light"
          >
            <Pencil size={16} /> Editar
          </Link>
        )}
      </div>

      {error && (
        <p role="alert" className="text-sm text-red">
          {error}
        </p>
      )}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        condominium && (
          <div className="grid gap-6 lg:grid-cols-2">
            <Card title="Dados do condomínio">
              <dl className="flex flex-col gap-3 text-sm">
                <div className="flex items-start gap-2 text-text">
                  <MapPin size={16} className="mt-0.5 shrink-0 text-muted" aria-hidden="true" />
                  <span>
                    {addressLine(condominium.address)}
                    {condominium.address?.location ? ` (${condominium.address.location})` : ""}
                  </span>
                </div>
                {condominium.reserveFund != null && (
                  <div className="flex justify-between gap-3">
                    <dt className="text-muted">Fundo comum de reserva</dt>
                    <dd className="font-medium text-text">{formatMoney(condominium.reserveFund)}</dd>
                  </div>
                )}
                <div className="flex justify-between gap-3">
                  <dt className="text-muted">Frações ligadas</dt>
                  <dd className="font-medium text-text">{condominium.propertiesCount}</dd>
                </div>
              </dl>
            </Card>

            <Card
              title="Convidar condóminos"
              description="Cada morador liga a sua casa ao condomínio com este código, na página da casa."
            >
              {codeError && (
                <p role="alert" className="text-sm text-red">
                  {codeError}
                </p>
              )}
              <InviteCode code={condominium.inviteCode} onRegenerate={handleRegenerate} />
            </Card>

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
                  className="shrink-0 text-sm font-medium text-navy hover:underline"
                >
                  Gerir avisos
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
                  className="shrink-0 text-sm font-medium text-navy hover:underline"
                >
                  Gerir documentos
                </Link>
              }
            />
          </div>
        )
      )}
    </div>
  );
}