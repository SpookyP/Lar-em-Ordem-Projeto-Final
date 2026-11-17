import { useState } from "react";
import { createPortal } from "react-dom";
import { Link, useNavigate } from "react-router-dom";
import { ArrowRight, Building, KeyRound, MapPin, Pencil, Plus, Trash2, X } from "lucide-react";
import { deleteCondominium } from "../../../services/api/condominiumService";
import { useMyCondominiumsPage } from "../../../hooks/useMyCondominiumsPage";
import { Pager } from "../../../components/ui/Pager";
import { ConfirmDialog } from "../../../components/ui/ConfirmDialog";
import { InviteCode } from "../../../components/condominium/InviteCode";
import { ClaimCondominium } from "../../../components/condominium/ClaimCondominium";
import { CondominiumForm } from "../../../components/condominium/CondominiumForm";
import { getErrorMessage } from "../../../utils/apiError";
import { addressLine, formatMoney } from "../../../utils/format";

function Stat({ label, value, hint }) {
  return (
    <div className="flex flex-col gap-1 px-7 py-6">
      <p className="text-xs font-semibold uppercase tracking-wider text-muted">{label}</p>
      <p className="font-display text-3xl text-navy">{value}</p>
      {hint && <p className="text-sm text-muted">{hint}</p>}
    </div>
  );
}

export default function Condominiums() {
  const [busy, setBusy] = useState(false);
  const [deleting, setDeleting] = useState(null);
  const [actionError, setActionError] = useState("");
  const [claiming, setClaiming] = useState(false);
  const [message, setMessage] = useState("");
  const [form, setForm] = useState(null);
  const navigate = useNavigate();

  const {
    items: condominiums,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useMyCondominiumsPage({ perPage: 6 });

  async function handleDelete() {
    setBusy(true);
    setActionError("");
    try {
      await deleteCondominium(deleting.id);
      if (condominiums.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        reload();
      }
    } catch (err) {
      setActionError(getErrorMessage(err, "Não foi possível eliminar o condomínio."));
    } finally {
      setBusy(false);
      setDeleting(null);
    }
  }

  function handleClaimed(condominium) {
    setClaiming(false);
    setMessage(`Agora geres o condomínio ${condominium.name}.`);
    if (page !== 1) setPage(1);
    else reload();
  }

  function handleSaved(saved) {
    if (form.condominium) {
      setForm(null);
      reload();
    } else {
      navigate(`/admin/condominiums/${saved.id}`);
    }
  }

  const isEmpty = !loading && !error && total === 0;
  const scope = totalPages > 1 ? "nesta página" : "soma de todos";
  const linkedFractions = condominiums.reduce((sum, c) => sum + c.propertiesCount, 0);
  const reserveFund = condominiums.reduce((sum, c) => sum + Number(c.reserveFund ?? 0), 0);

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-7">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal">
            Administração
          </p>
          <h1 className="mt-1 font-display text-4xl text-navy">Os meus condomínios</h1>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => {
              setMessage("");
              setClaiming(true);
            }}
            className="flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 font-medium text-navy transition hover:bg-surface"
          >
            <KeyRound size={18} />
            Receber
          </button>
          <button
            type="button"
            onClick={() => setForm({ condominium: null })}
            className="flex items-center gap-2 rounded-xl bg-navy px-4 py-2.5 font-semibold text-white transition hover:bg-navy-light"
          >
            <Plus size={18} />
            Novo condomínio
          </button>
        </div>
      </div>

      {message && (
        <p role="status" className="rounded-xl bg-green-light px-3.5 py-2.5 text-sm text-green">
          {message}
        </p>
      )}

      {(error || actionError) && (
        <p role="alert" className="rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red">
          {error || actionError}
        </p>
      )}

      {condominiums.length > 0 && (
        <section
          aria-label="Resumo"
          className="grid divide-y divide-border rounded-2xl border border-border bg-card sm:grid-cols-3 sm:divide-x sm:divide-y-0"
        >
          <Stat label="Condomínios" value={total} />
          <Stat label="Frações ligadas" value={linkedFractions} hint={scope} />
          <Stat label="Fundo de reserva" value={formatMoney(reserveFund)} hint={scope} />
        </section>
      )}

      {loading && condominiums.length === 0 ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            {condominiums.map((condominium) => (
              <article
                key={condominium.id}
                className="flex min-w-0 flex-col rounded-2xl border border-border bg-card shadow-sm"
              >
                <div className="flex flex-col gap-5 p-6">
                  <div className="flex items-start gap-4">
                    <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-navy text-white">
                      <Building size={22} aria-hidden="true" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <h2 className="truncate font-display text-xl text-navy">
                        {condominium.name}
                      </h2>
                      <p className="mt-0.5 flex items-center gap-1.5 text-sm text-muted">
                        <MapPin size={14} className="shrink-0" aria-hidden="true" />
                        <span className="truncate">{addressLine(condominium.address)}</span>
                      </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                      <button
                        type="button"
                        onClick={() => setForm({ condominium })}
                        aria-label={`Editar ${condominium.name}`}
                        className="rounded-lg p-2 text-muted transition hover:bg-surface hover:text-navy"
                      >
                        <Pencil size={17} />
                      </button>
                      <button
                        type="button"
                        onClick={() => setDeleting(condominium)}
                        aria-label={`Eliminar ${condominium.name}`}
                        className="rounded-lg p-2 text-muted transition hover:bg-red-light hover:text-red"
                      >
                        <Trash2 size={17} />
                      </button>
                    </div>
                  </div>

                  <dl className="grid grid-cols-2 divide-x divide-border rounded-xl bg-surface py-3 text-center">
                    <div>
                      <dt className="text-[11px] font-semibold uppercase tracking-wider text-muted">
                        Frações ligadas
                      </dt>
                      <dd className="mt-1 font-semibold text-text">
                        {condominium.propertiesCount}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-[11px] font-semibold uppercase tracking-wider text-muted">
                        Reserva
                      </dt>
                      <dd className="mt-1 font-semibold text-text">
                        {condominium.reserveFund != null
                          ? formatMoney(condominium.reserveFund)
                          : "Sem valor"}
                      </dd>
                    </div>
                  </dl>

                  {condominium.inviteCode && <InviteCode code={condominium.inviteCode} />}
                </div>

                <Link
                  to={`/admin/condominiums/${condominium.id}`}
                  className="mt-auto flex items-center justify-between border-t border-border px-6 py-4 font-medium text-navy transition hover:bg-surface"
                >
                  Abrir condomínio
                  <ArrowRight size={18} aria-hidden="true" />
                </Link>
              </article>
            ))}

            <button
              type="button"
              onClick={() => setForm({ condominium: null })}
              className="flex min-h-64 flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-border p-6 text-center transition hover:border-navy/40 hover:bg-card"
            >
              <span className="flex size-14 items-center justify-center rounded-full bg-card text-muted shadow-sm">
                <Plus size={22} aria-hidden="true" />
              </span>
              <span className="font-semibold text-navy">
                {isEmpty ? "Criar o primeiro condomínio" : "Adicionar condomínio"}
              </span>
              <span className="max-w-56 text-sm text-muted">
                Crie um novo edifício e convide os condóminos.
              </span>
            </button>
          </div>

          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}

      {claiming &&
        createPortal(
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/60" onClick={() => setClaiming(false)} />
            <div
              role="dialog"
              aria-modal="true"
              aria-labelledby="claim-title"
              className="relative z-10 w-full max-w-lg rounded-2xl bg-card p-7 shadow-xl"
            >
              <div className="flex items-start justify-between gap-4">
                <h2 id="claim-title" className="font-display text-2xl text-navy">
                  Receber condomínio
                </h2>
                <button
                  type="button"
                  onClick={() => setClaiming(false)}
                  aria-label="Fechar"
                  className="rounded-lg p-1 text-muted transition hover:bg-surface hover:text-navy"
                >
                  <X size={20} />
                </button>
              </div>
              <p className="mt-1 mb-5 text-sm text-muted">
                Passas a gerir o condomínio, com os avisos, os documentos e as frações.
              </p>
              <ClaimCondominium onClaimed={handleClaimed} onCancel={() => setClaiming(false)} />
            </div>
          </div>,
          document.body,
        )}

      {form && (
        <CondominiumForm
          condominium={form.condominium}
          onSaved={handleSaved}
          onClose={() => setForm(null)}
        />
      )}

      {deleting && (
        <ConfirmDialog
          title={`Eliminar ${deleting.name}?`}
          message="As casas ficam desligadas, os documentos são apagados e os avisos deixam de identificar o prédio."
          confirmLabel="Sim, eliminar"
          loadingLabel="A eliminar..."
          danger
          loading={busy}
          onConfirm={handleDelete}
          onCancel={() => setDeleting(null)}
        />
      )}
    </div>
  );
}