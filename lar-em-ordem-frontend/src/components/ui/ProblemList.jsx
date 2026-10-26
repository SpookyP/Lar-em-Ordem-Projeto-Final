import { AssistanceCard } from "./AssistanceCard";
import { Pager } from "./Pager";
import { categoryInfo } from "../../data/assistanceInfo";
import { formatDate } from "../../utils/date";

export default function ProblemList({
  requests,
  loading,
  error,
  page,
  totalPages,
  total,
  onPageChange,
}) {
  return (
    <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-xl text-navy">Histórico de Pedidos</h2>
        <span className="text-xs text-muted">Total: {total} pedidos</span>
      </div>

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : error ? (
        <p className="text-sm text-red">{error}</p>
      ) : requests.length === 0 ? (
        <p className="text-sm text-muted">
          Ainda não existem pedidos registados.
        </p>
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
                description={r.description}
                postedAt={formatDate(r.createdAt)}
                status={r.statusLabel}
                proposals={r.proposalsCount}
                to={`/assistencia/${r.id}`}
              />
            );
          })}
        </div>
      )}

      <Pager page={page} totalPages={totalPages} onChange={onPageChange} />
    </div>
  );
}