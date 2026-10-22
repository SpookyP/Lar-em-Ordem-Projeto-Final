import { useState } from "react";
import { AssistanceCard } from "./AssistanceCard";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { categoryInfo } from "../../data/assistanceInfo";
import { formatDate } from "../../utils/date";

const ITEMS_PER_PAGE = 5;

export default function ProblemList({ requests, loading, error }) {
  const [currentPage, setCurrentPage] = useState(1);

  //paginacao local, a API devolve a lista toda
  const totalPages = Math.max(1, Math.ceil(requests.length / ITEMS_PER_PAGE));
  const page = Math.min(currentPage, totalPages);
  const startIndex = (page - 1) * ITEMS_PER_PAGE;
  const currentProblems = requests.slice(startIndex, startIndex + ITEMS_PER_PAGE);

  return (
    <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-xl text-navy">Histórico de Pedidos</h2>
        <span className="text-xs text-muted">
          Total: {requests.length} pedidos
        </span>
      </div>

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : error && requests.length === 0 ? (
        <p className="text-sm text-red">{error}</p>
      ) : currentProblems.length === 0 ? (
        <p className="text-sm text-muted">
          Ainda não existem pedidos registados.
        </p>
      ) : (
        <div className="flex flex-col gap-2.5">
          {currentProblems.map((r) => {
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
                to={`/assistencia/${r.id}`}
              />
            );
          })}
        </div>
      )}

      {/* Barra de Paginação */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between pt-3 border-t border-border/60 text-xs text-muted">
          <span>
            Página <strong className="text-navy">{page}</strong> de{" "}
            <strong className="text-navy">{totalPages}</strong>
          </span>
          <div className="flex items-center gap-1.5">
            <button
              onClick={() => setCurrentPage(Math.max(page - 1, 1))}
              disabled={page === 1}
              className="p-1.5 rounded-lg border border-border bg-surface hover:bg-border/50 disabled:opacity-40 disabled:cursor-not-allowed transition text-navy cursor-pointer"
              title="Página anterior"
            >
              <ChevronLeft size={14} />
            </button>
            <button
              onClick={() => setCurrentPage(Math.min(page + 1, totalPages))}
              disabled={page === totalPages}
              className="p-1.5 rounded-lg border border-border bg-surface hover:bg-border/50 disabled:opacity-40 disabled:cursor-not-allowed transition text-navy cursor-pointer"
              title="Página seguinte"
            >
              <ChevronRight size={14} />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}