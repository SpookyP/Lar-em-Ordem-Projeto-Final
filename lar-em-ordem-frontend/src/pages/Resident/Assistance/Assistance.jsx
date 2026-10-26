import AssistanceForm from "../../../components/ui/AssistanceForm";
import ProblemList from "../../../components/ui/ProblemList";
import { useAuth } from "../../../hooks/useAuth";
import { useAssistanceRequests } from "../../../hooks/useAssistanceRequests";
import { createAssistanceRequest } from "../../../services/api/assistanceService";

export default function Assistance() {
  const { activeHouse } = useAuth();
  const list = useAssistanceRequests(activeHouse?.id);

  async function handleNewRequest({ title, category, description, photo }) {
    if (!activeHouse) {
      throw new Error("Selecione uma casa antes de abrir um pedido.");
    }

    await createAssistanceRequest({
      property_id: activeHouse.id,
      problem_category_id: Number(category),
      title,
      description,
      images: [photo],
    });

    list.setPage(1);
    list.reload();
  }

  return (
    <div className="flex flex-col gap-6 max-w-3xl mx-auto">
      <header>
        <h1 className="font-display text-3xl text-orange">
          Central de Assistência
        </h1>
      </header>

      <div className="flex flex-col gap-6">
        <AssistanceForm onSubmit={handleNewRequest} />
        <ProblemList
          requests={list.items}
          loading={list.loading}
          error={list.error}
          page={list.page}
          totalPages={list.totalPages}
          total={list.total}
          onPageChange={list.setPage}
        />
      </div>
    </div>
  );
}