import { useEffect, useState } from "react";
import { Building, Mail, MapPin, Phone, PiggyBank, UserRound } from "lucide-react";
import { useAuth } from "../../../hooks/useAuth";
import {
  getPropertyCondominium,
  removeProperty,
} from "../../../services/api/condominiumService";
import { JoinCondominium } from "../../../components/condominium/JoinCondominium";
import { priorityInfo } from "../../../data/condominiumInfo";
import { addressLine, formatMoney } from "../../../utils/format";
import { formatDate } from "../../../utils/date";
import { getErrorMessage } from "../../../utils/apiError";

// the administrator's contact may be an email or a phone number: make it clickable either way
function ContactLink({ contact }) {
  if (!contact) return null;
  const isEmail = contact.includes("@");
  const Icon = isEmail ? Mail : Phone;
  const href = isEmail ? `mailto:${contact}` : `tel:${contact.replace(/[^\d+]/g, "")}`;

  return (
    <a href={href} className="flex items-center gap-2 text-navy hover:underline">
      <Icon size={16} aria-hidden="true" />
      {contact}
    </a>
  );
}

function Card({ title, children }) {
  return (
    <section className="flex flex-col gap-4 rounded-2xl bg-card p-6 shadow-sm">
      <h2 className="font-medium text-navy">{title}</h2>
      {children}
    </section>
  );
}

function Notices({ notices }) {
  if (notices.length === 0) {
    return <p className="text-sm text-muted">Não há avisos em vigor.</p>;
  }

  return (
    <ul className="flex flex-col gap-3">
      {notices.map((notice) => {
        const priority = priorityInfo(notice.priority);
        return (
          <li key={notice.id} className="flex flex-col gap-1.5 rounded-xl border border-border p-4">
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="font-medium text-text">{notice.title}</h3>
              <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${priority.className}`}>
                {priority.label}
              </span>
            </div>
            <p className="whitespace-pre-line text-sm text-text">{notice.message}</p>
            <p className="text-xs text-muted">
              Publicado a {formatDate(notice.publishedAt)}
              {notice.expiresAt ? ` · em vigor até ${formatDate(notice.expiresAt)}` : ""}
            </p>
          </li>
        );
      })}
    </ul>
  );
}

function LeaveCondominium({ condominium, propertyId, onLeft }) {
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function leave() {
    setBusy(true);
    setError("");
    try {
      await removeProperty(condominium.id, propertyId);
      onLeft();
    } catch (err) {
      setError(getErrorMessage(err, "Não foi possível sair do condomínio."));
      setBusy(false);
    }
  }

  return (
    <div className="flex flex-col gap-2 text-sm">
      {error && (
        <p role="alert" className="text-red">
          {error}
        </p>
      )}
      {confirming ? (
        <div className="flex flex-col gap-2 rounded-xl bg-surface p-3">
          <p className="text-muted">
            A tua casa deixa de ver os avisos e os documentos de {condominium.name}. Podes voltar a
            entrar com o código de convite.
          </p>
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={leave}
              disabled={busy}
              className="rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
            >
              {busy ? "A sair..." : "Sim, sair"}
            </button>
            <button
              type="button"
              onClick={() => setConfirming(false)}
              className="text-muted underline hover:text-navy"
            >
              Cancelar
            </button>
          </div>
        </div>
      ) : (
        <button
          type="button"
          onClick={() => setConfirming(true)}
          className="self-start text-red hover:opacity-80"
        >
          Sair do condomínio
        </button>
      )}
    </div>
  );
}

export default function Condominium() {
  const { activeHouse } = useAuth();
  const propertyId = activeHouse?.id;

  // the answer is kept with the request it belongs to, so switching homes shows "A carregar..."
  // without resetting state inside the effect (same idea as usePagedList)
  const [version, setVersion] = useState(0);
  const [result, setResult] = useState({ key: null, data: null, error: "" });
  const [message, setMessage] = useState("");

  const requestKey = `${propertyId}|${version}`;
  const reload = () => setVersion((v) => v + 1);

  useEffect(() => {
    if (!propertyId) return;
    let active = true;
    getPropertyCondominium(propertyId)
      .then((data) => active && setResult({ key: requestKey, data, error: "" }))
      .catch((err) => {
        if (active) {
          setResult({
            key: requestKey,
            data: null,
            error: getErrorMessage(err, "Não foi possível carregar o condomínio."),
          });
        }
      });
    return () => {
      active = false;
    };
  }, [propertyId, requestKey]);

  // undefined while loading, null when the home has no condominium
  const condominium = result.key === requestKey ? result.data : undefined;
  const error = result.key === requestKey ? result.error : "";

  if (!propertyId) {
    return (
      <div className="mx-auto max-w-3xl rounded-2xl bg-card p-8 text-center text-sm text-muted shadow-sm">
        Escolhe uma casa para ver o condomínio.
      </div>
    );
  }

  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-6">
      <h1 className="font-display text-2xl text-navy">Condomínio</h1>

      {message && (
        <p role="status" className="rounded-lg bg-green-light px-3 py-2 text-sm text-green">
          {message}
        </p>
      )}
      {error && (
        <p role="alert" className="text-sm text-red">
          {error}
        </p>
      )}

      {condominium === undefined ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : condominium === null ? (
        !error && (
          <section className="flex flex-col gap-5 rounded-2xl bg-card p-6 shadow-sm">
            <div className="flex items-start gap-4">
              <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-navy/10 text-navy">
                <Building size={24} aria-hidden="true" />
              </span>
              <div>
                <h2 className="font-medium text-navy">A tua casa ainda não está ligada ao condomínio</h2>
                <p className="mt-1 text-sm text-muted">
                  Com o código de convite da administração passas a receber os avisos do prédio e a
                  ver os documentos do condomínio, como atas e regulamento.
                </p>
              </div>
            </div>
            <JoinCondominium
              propertyId={propertyId}
              onJoined={(joined) => {
                setMessage(`A tua casa está agora ligada a ${joined.name}.`);
                // the join answer may not bring the address and notices: ask for the full page
                reload();
              }}
            />
          </section>
        )
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          <Card title={condominium.name}>
            <div className="flex flex-col gap-3 text-sm text-text">
              <p className="flex items-start gap-2">
                <MapPin size={16} className="mt-0.5 shrink-0 text-muted" aria-hidden="true" />
                {addressLine(condominium.address)}
              </p>
              {condominium.reserveFund != null && (
                <p className="flex items-center gap-2">
                  <PiggyBank size={16} className="text-muted" aria-hidden="true" />
                  Fundo comum de reserva: {formatMoney(condominium.reserveFund)}
                </p>
              )}
            </div>
          </Card>

          <Card title="Administração">
            <div className="flex flex-col gap-3 text-sm">
              <p className="flex items-center gap-2 text-text">
                <UserRound size={16} className="text-muted" aria-hidden="true" />
                {condominium.admin?.name ?? "Sem administrador"}
              </p>
              <ContactLink contact={condominium.admin?.contact} />
            </div>
          </Card>

          <div className="lg:col-span-2">
            <Card title="Avisos em vigor">
              <Notices notices={condominium.notices} />
            </Card>
          </div>

          <div className="lg:col-span-2">
            <LeaveCondominium
              condominium={condominium}
              propertyId={propertyId}
              onLeft={() => {
                setMessage(`A tua casa saiu de ${condominium.name}.`);
                setResult({ key: requestKey, data: null, error: "" });
              }}
            />
          </div>
        </div>
      )}
    </div>
  );
}