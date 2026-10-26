import { useState } from "react";
import { useMyProposals } from "../../../hooks/useMyProposals";
import {
  updateProposal,
  withdrawProposal,
} from "../../../services/api/proposalService";
import { PROPOSAL_STATUS } from "../../../data/assistanceInfo";
import { ProposalForm } from "../../../components/ui/ProposalForm";
import { ProviderProposalCard } from "../../../components/ui/ProviderProposalCard";
import { Pager } from "../../../components/ui/Pager";
import { getErrorMessage } from "../../../utils/apiError";

const FILTERS = [
  { id: "all", label: "Todas" },
  ...Object.entries(PROPOSAL_STATUS).map(([id, { label }]) => ({ id, label })),
];

export default function Proposals() {
  const [filter, setFilter] = useState("all");
  const [editing, setEditing] = useState(null);
  const [busyId, setBusyId] = useState(null);
  const [actionError, setActionError] = useState("");
  const {
    items: proposals,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useMyProposals(filter);

  async function handleSave(data) {
    await updateProposal(editing.id, data);
    setEditing(null);
    reload();
  }

  async function handleWithdraw(proposal) {
    setBusyId(proposal.id);
    setActionError("");
    try {
      await withdrawProposal(proposal.id);
      reload();
    } catch (err) {
      setActionError(getErrorMessage(err));
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="flex flex-col gap-6 max-w-3xl mx-auto">
      <header>
        <h1 className="font-display text-3xl text-orange">
          As minhas propostas
        </h1>
      </header>

      {editing && (
        <div className="rounded-2xl bg-card p-8 shadow-sm flex flex-col gap-6">
          <h2 className="font-display text-xl text-navy">
            Editar proposta
            {editing.request && (
              <span className="block text-sm font-sans text-muted">
                {editing.request.title}
              </span>
            )}
          </h2>
          <ProposalForm
            key={editing.id}
            proposal={editing}
            submitLabel="Guardar alterações"
            onSubmit={handleSave}
            onCancel={() => setEditing(null)}
          />
        </div>
      )}

      <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h2 className="font-display text-xl text-navy">Propostas enviadas</h2>
          <span className="text-xs text-muted">
            Total: {total} {total === 1 ? "proposta" : "propostas"}
          </span>
        </div>

        <div className="flex flex-wrap gap-2">
          {FILTERS.map((f) => (
            <button
              key={f.id}
              onClick={() => setFilter(f.id)}
              className={`rounded-full px-3 py-1 text-xs transition ${
                filter === f.id
                  ? "bg-navy text-white"
                  : "bg-surface text-muted hover:text-navy"
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>

        {actionError && (
          <p className="text-sm text-red font-medium">{actionError}</p>
        )}

        {loading ? (
          <p className="text-sm text-muted">A carregar...</p>
        ) : error ? (
          <p className="text-sm text-red">{error}</p>
        ) : proposals.length === 0 ? (
          <p className="text-sm text-muted">
            {filter === "all"
              ? "Ainda não enviou nenhuma proposta."
              : "Nenhuma proposta neste estado."}
          </p>
        ) : (
          <div className="flex flex-col gap-2.5">
            {proposals.map((proposal) => (
              <ProviderProposalCard
                key={proposal.id}
                proposal={proposal}
                busy={busyId === proposal.id}
                onEdit={setEditing}
                onWithdraw={handleWithdraw}
              />
            ))}
          </div>
        )}

        <Pager page={page} totalPages={totalPages} onChange={setPage} />
      </div>
    </div>
  );
}