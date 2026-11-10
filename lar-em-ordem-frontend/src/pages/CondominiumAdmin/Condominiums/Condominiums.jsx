import { useState } from "react";
import { Link } from "react-router-dom";
import { Check, Copy, House, MapPin, Pencil, Plus, Trash2 } from "lucide-react";
import { deleteCondominium } from "../../../services/api/condominiumService";
import { useMyCondominiumsPage } from "../../../hooks/useMyCondominiumsPage";
import { Pager } from "../../../components/ui/Pager";
import { formatMoney } from "../../../utils/format";
import { getErrorMessage } from "../../../utils/apiError";

function addressLine(address) {
  if (!address) return "";
  return [address.street, address.door, address.postal_code, address.county]
    .filter(Boolean)
    .join(", ");
}

function InviteCode({ code }) {
  const [copied, setCopied] = useState(false);

  async function copy() {
    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // clipboard blocked (http or browser setting): the code is still visible to copy by hand
    }
  }

  return (
    <div className="flex items-center justify-between gap-3 rounded-xl bg-surface px-4 py-3">
      <div>
        <p className="text-xs text-muted">Código de convite</p>
        <p className="font-mono text-lg tracking-widest text-navy">{code}</p>
      </div>
      <button
        type="button"
        onClick={copy}
        className="flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-sm text-muted transition hover:text-navy"
      >
        {copied ? <Check size={15} /> : <Copy size={15} />}
        {copied ? "Copiado" : "Copiar"}
      </button>
    </div>
  );
}

export default function Condominiums() {
  const [busyId, setBusyId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [actionError, setActionError] = useState("");

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

  async function handleDelete(condominium) {
    setBusyId(condominium.id);
    setActionError("");
    try {
      await deleteCondominium(condominium.id);
      setConfirmingId(null);
      if (condominiums.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        reload();
      }
    } catch (err) {
      setActionError(getErrorMessage(err, "Não foi possível eliminar o condomínio."));
    } finally {
      setBusyId(null);
    }
  }

  const isEmpty = !loading && !error && total === 0;

  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-6">
      <div className="flex items-center justify-between gap-3">
        <h1 className="font-display text-2xl text-navy">Os meus condomínios</h1>
        <Link
          to="/admin/condominiums/new"
          className="flex items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light"
        >
          <Plus size={18} />
          Novo condomínio
        </Link>
      </div>

      {(error || actionError) && (
        <p role="alert" className="text-sm text-red">
          {error || actionError}
        </p>
      )}

      {loading && condominiums.length === 0 ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : isEmpty ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <p className="text-sm text-muted">Ainda não geres nenhum condomínio.</p>
          <Link
            to="/admin/condominiums/new"
            className="mt-3 inline-block font-semibold text-navy hover:underline"
          >
            Criar o primeiro condomínio
          </Link>
        </div>
      ) : (
        <>
          <div className="grid gap-4 lg:grid-cols-2">
            {condominiums.map((condominium) => (
              <article
                key={condominium.id}
                className="flex flex-col gap-4 rounded-2xl bg-card p-5 shadow-sm"
              >
                <div className="flex flex-col gap-1">
                  <h2 className="font-display text-lg text-navy">{condominium.name}</h2>
                  <p className="flex items-start gap-1.5 text-sm text-muted">
                    <MapPin size={15} className="mt-0.5 shrink-0" aria-hidden="true" />
                    {addressLine(condominium.address)}
                  </p>
                </div>

                <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-text">
                  <span className="flex items-center gap-1.5">
                    <House size={15} className="text-muted" aria-hidden="true" />
                    {condominium.propertiesCount}{" "}
                    {condominium.propertiesCount === 1 ? "fração ligada" : "frações ligadas"}
                  </span>
                  {condominium.reserveFund != null && (
                    <span>Fundo de reserva: {formatMoney(condominium.reserveFund)}</span>
                  )}
                </div>

                {condominium.inviteCode && <InviteCode code={condominium.inviteCode} />}

                <div className="mt-auto flex items-center gap-4 border-t border-border pt-3 text-sm">
                  {confirmingId === condominium.id ? (
                    <div className="flex w-full flex-col gap-2">
                      <p className="text-muted">
                        Eliminar este condomínio? As casas ficam desligadas, os documentos são
                        apagados e os avisos deixam de identificar o prédio.
                      </p>
                      <div className="flex items-center gap-3">
                        <button
                          onClick={() => handleDelete(condominium)}
                          disabled={busyId === condominium.id}
                          className="rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
                        >
                          {busyId === condominium.id ? "A eliminar..." : "Sim, eliminar"}
                        </button>
                        <button
                          onClick={() => setConfirmingId(null)}
                          className="text-muted underline hover:text-navy"
                        >
                          Cancelar
                        </button>
                      </div>
                    </div>
                  ) : (
                    <>
                      <Link
                        to={`/admin/condominiums/${condominium.id}/edit`}
                        className="flex items-center gap-1.5 text-muted hover:text-navy"
                      >
                        <Pencil size={15} /> Editar
                      </Link>
                      <button
                        onClick={() => setConfirmingId(condominium.id)}
                        className="ml-auto flex items-center gap-1.5 text-red hover:opacity-80"
                      >
                        <Trash2 size={15} /> Eliminar
                      </button>
                    </>
                  )}
                </div>
              </article>
            ))}
          </div>

          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}
    </div>
  );
}