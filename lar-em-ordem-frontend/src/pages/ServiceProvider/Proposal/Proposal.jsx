import { useState } from "react";
import { useMyProposals } from "../../../hooks/useMyProposals";
import { useProposalCounts } from "../../../hooks/useProposalCounts";
import { withdrawProposal } from "../../../services/api/proposalService";
import { PROPOSAL_STATUS } from "../../../data/assistanceInfo";
import { ProviderProposalCard } from "../../../components/ui/ProviderProposalCard";
import { ConfirmDialog } from "../../../components/ui/ConfirmDialog";
import { Pager } from "../../../components/ui/Pager";
import { getErrorMessage } from "../../../utils/apiError";

const FILTERS = [
  { id: "all", label: "Todas" },
  ...Object.entries(PROPOSAL_STATUS).map(([id, { label }]) => ({ id, label })),
];

// the header and every row share these columns, so they line up like a table
const COLUMNS =
  "lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1.1fr)_minmax(0,0.9fr)_7rem]";

const HEADINGS = ["Pedido", "Valor", "Disponibilidade", "Validade", "Estado", "Ações"];

export default function Proposals() {
  const [filter, setFilter] = useState("all");
  const [withdrawing, setWithdrawing] = useState(null);
  const [busy, setBusy] = useState(false);
  const [actionError, setActionError] = useState("");
  const [countsVersion, setCountsVersion] = useState(0);
  const counts = useProposalCounts(countsVersion);
  const {
    items: proposals,
    page,
    totalPages,
    loading,
    error,
    setPage,
    reload,
  } = useMyProposals(filter);

  async function handleWithdraw() {
    setBusy(true);
    setActionError("");
    try {
      await withdrawProposal(withdrawing.id);
      reload();
      setCountsVersion((v) => v + 1);
    } catch (err) {
      setActionError(getErrorMessage(err, "Não foi possível retirar a proposta."));
    } finally {
      setBusy(false);
      setWithdrawing(null);
    }
  }

  return (
    <div className="mx-auto flex max-w-6xl flex-col gap-7">
      <header>
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal">Prestador</p>
        <h1 className="mt-1 font-display text-4xl text-navy">As minhas propostas</h1>
      </header>

      <section className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div className="flex flex-wrap gap-2 p-4 sm:p-6">
          {FILTERS.map((f) => {
            const active = filter === f.id;
            return (
              <button
                key={f.id}
                type="button"
                onClick={() => setFilter(f.id)}
                aria-pressed={active}
                className={`flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition ${
                  active ? "bg-navy text-white" : "bg-surface text-muted hover:text-navy"
                }`}
              >
                {f.label}
                {counts && (
                  <span
                    className={`min-w-5 rounded-full px-1.5 text-center text-xs font-semibold ${
                      active ? "bg-white/20 text-white" : "bg-card text-text"
                    }`}
                  >
                    {counts[f.id]}
                  </span>
                )}
              </button>
            );
          })}
        </div>

        {actionError && (
          <p role="alert" className="mx-4 mb-4 rounded-xl bg-red-light px-3.5 py-2.5 text-sm text-red sm:mx-6">
            {actionError}
          </p>
        )}

        <div
          className={`hidden gap-4 border-y border-border bg-surface px-6 py-3 text-xs font-semibold uppercase tracking-wider text-muted lg:grid ${COLUMNS}`}
        >
          {HEADINGS.map((heading) => (
            <span key={heading} className={heading === "Ações" ? "text-center" : heading === "Pedido" ? "pl-15" : ""}>
              {heading}
            </span>
          ))}
        </div>

        {loading ? (
          <p className="p-4 text-sm text-muted sm:p-6">A carregar...</p>
        ) : error ? (
          <p role="alert" className="p-4 text-sm text-red sm:p-6">
            {error}
          </p>
        ) : proposals.length === 0 ? (
          <p className="p-4 text-sm text-muted sm:p-6">
            {filter === "all"
              ? "Ainda não enviou nenhuma proposta."
              : "Nenhuma proposta neste estado."}
          </p>
        ) : (
          <div className="divide-y divide-border border-t border-border lg:border-t-0">
            {proposals.map((proposal) => (
              <ProviderProposalCard
                key={proposal.id}
                proposal={proposal}
                columns={COLUMNS}
                busy={busy && withdrawing?.id === proposal.id}
                onWithdraw={setWithdrawing}
              />
            ))}
          </div>
        )}

        <div className="px-4 pb-4 sm:px-6 sm:pb-6">
          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </div>
      </section>

      {withdrawing && (
        <ConfirmDialog
          title="Retirar a proposta?"
          message="O cliente deixa de a poder aceitar. Depois de retirada, não pode enviar outra proposta para este pedido."
          confirmLabel="Sim, retirar"
          loadingLabel="A retirar..."
          danger
          loading={busy}
          onConfirm={handleWithdraw}
          onCancel={() => setWithdrawing(null)}
        />
      )}
    </div>
  );
}