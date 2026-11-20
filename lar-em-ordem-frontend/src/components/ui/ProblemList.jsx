import { AssistanceCard } from "./AssistanceCard";
import { Pager } from "./Pager";
import { categoryInfo } from "../../data/assistanceInfo";
import { formatDate } from "../../utils/date";
import { LoadingState } from "./LoadingState";
import { EmptyState } from "./EmptyState";

const STATUS_FILTERS = [
  { value: "", label: "Todos" },
  { value: "open", label: "Pendente" },
  { value: "closed", label: "Aceite" },
  { value: "cancelled", label: "Cancelado" },
];

export default function ProblemList({
  requests,
  loading,
  error,
  page,
  totalPages,
  total,
  onPageChange,
  status = "",
  onStatusChange,
}) {
  return (
    <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-xl text-navy">Histórico de Pedidos</h2>
        <span className="text-xs text-muted">Total: {total} pedidos</span>
      </div>

      {onStatusChange && (
        <div className="flex flex-wrap gap-2">
          {STATUS_FILTERS.map((f) => (
            <button
              key={f.value || "all"}
              type="button"
              onClick={() => onStatusChange(f.value)}
              className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                status === f.value
                  ? "bg-navy text-white"
                  : "bg-surface text-muted hover:text-navy"
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      )}

      {loading ? (
        <LoadingState />
      ) : error ? (
        <p className="text-sm text-red">{error}</p>
      ) : requests.length === 0 ? (
        <EmptyState
          message={
            status
              ? "Nenhum pedido com este estado."
              : "Ainda não existem pedidos registados."
          }
        />
      ) : (
        <div className="flex flex-col gap-2.5">
          {requests.map((r) => {
            const { icon, color } = categoryInfo(r.category.slug);
            return (
              <AssistanceCard
                key={r.id}
                id={r.id.slice(-6).toUpperCase()}
                icon={icon}
                iconColor={color}
                category={r.category.name}
                title={r.title}
                postedAt={formatDate(r.createdAt)}
                status={r.status}
                proposals={r.proposalsCount}
                to={`/assistance/${r.id}`}
              />
            );
          })}
        </div>
      )}

      <Pager page={page} totalPages={totalPages} onChange={onPageChange} />
    </div>
  );
}
