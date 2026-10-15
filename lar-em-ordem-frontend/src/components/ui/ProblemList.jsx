import { useState } from "react";
import { AssistanceCard } from "./AssistanceCard";
import { Droplet, Zap, Flame, Wrench, ChevronLeft, ChevronRight } from "lucide-react";

function getCategoryIcon(category) {
  switch (category?.toLowerCase()) {
    case "canalizacao":
      return { icon: Droplet, color: "bg-teal-light text-teal" };
    case "eletricidade":
      return { icon: Zap, color: "bg-amber-light text-amber" };
    case "gas":
      return { icon: Flame, color: "bg-orange-light text-orange" };
    default:
      return { icon: Wrench, color: "bg-navy/10 text-navy" };
  }
}

// Dados fictícios expandidos para testar a paginação
const fakeProblems = [
  { id: 1, category: "canalizacao", description: "Fuga de água por baixo do lava-louça da cozinha.", created_at: "2026-10-10", status: "Pendente" },
  { id: 2, category: "eletricidade", description: "Disjuntor principal vai abaixo sempre que ligo o forno.", created_at: "2026-10-12", status: "Em Progresso" },
  { id: 3, category: "gas", description: "Revisão periódica da caldeira e verificação de pressões.", created_at: "2026-10-13", status: "Concluído" },
  { id: 4, category: "canalizacao", description: "Torneira da casa de banho a pingar constantemente.", created_at: "2026-10-14", status: "Pendente" },
];

export default function ProblemList() {
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 3; // Número de itens por página

  // Lógica de paginação local (quando ligares à API Laravel, substituis isto pelos dados da resposta)
  const totalPages = Math.ceil(fakeProblems.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentProblems = fakeProblems.slice(startIndex, startIndex + itemsPerPage);

  return (
    <div className="rounded-2xl bg-card p-6 shadow-sm flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-xl text-navy">
          Histórico de Pedidos
        </h2>
        <span className="text-xs text-muted">
          Total: {fakeProblems.length} pedidos
        </span>
      </div>

      {currentProblems.length === 0 ? (
        <p className="text-sm text-muted">Ainda não existem pedidos registados.</p>
      ) : (
        <div className="flex flex-col gap-2.5">
          {currentProblems.map((problem) => {
            const { icon, color } = getCategoryIcon(problem.category);
            return (
              <AssistanceCard
                key={problem.id}
                id={problem.id}
                icon={icon}
                iconColor={color}
                category={problem.category}
                description={problem.description}
                postedAt={new Date(problem.created_at).toLocaleDateString()}
                status={problem.status}
                to={`/assistance/${problem.id}`}
              />
            );
          })}
        </div>
      )}

      {/* Barra de Paginação */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between pt-3 border-t border-border/60 text-xs text-muted">
          <span>
            Página <strong className="text-navy">{currentPage}</strong> de <strong className="text-navy">{totalPages}</strong>
          </span>
          <div className="flex items-center gap-1.5">
            <button
              onClick={() => setCurrentPage((p) => Math.max(p - 1, 1))}
              disabled={currentPage === 1}
              className="p-1.5 rounded-lg border border-border bg-surface hover:bg-border/50 disabled:opacity-40 disabled:cursor-not-allowed transition text-navy cursor-pointer"
              title="Página anterior"
            >
              <ChevronLeft size={14} />
            </button>
            <button
              onClick={() => setCurrentPage((p) => Math.min(p + 1, totalPages))}
              disabled={currentPage === totalPages}
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