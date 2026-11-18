import AssistanceForm from "../../../components/ui/AssistanceForm";
import ProblemList from "../../../components/ui/ProblemList";
import { useAuth } from "../../../hooks/useAuth";
import { useAssistanceRequests } from "../../../hooks/useAssistanceRequests";
import { createAssistanceRequest } from "../../../services/api/assistanceService";
import { useState } from "react";

export default function Assistance() {
  const { activeHouse } = useAuth();
  const [status, setStatus] = useState("");
  const list = useAssistanceRequests(activeHouse?.id, status);

  async function handleNewRequest({ title, category, description, photo }) {
    if (!activeHouse) {
      throw new Error("Selecione uma casa antes de abrir um pedido.");
    }

    await createAssistanceRequest({
      property_id: activeHouse.id,
      problem_category_id: Number(category),
      title,
      description,
      images: photo ? [photo] : [],
    });

    list.setPage(1);
    list.reload();
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <h1 className="font-display text-4xl text-orange">
          Central de Assistência
        </h1>
        <p className="font-display text-xl italic text-muted translate-y-6 sm:text-right">
          Abra e acompanhe os pedidos de assistência da sua casa.
        </p>
      </div>

      <div className="mt-10 flex flex-col gap-6 lg:grid lg:grid-cols-5 lg:items-start">
        <div className="lg:col-span-3">
          <AssistanceForm onSubmit={handleNewRequest} />
        </div>
        <div className="lg:col-span-2">
          <ProblemList
            requests={list.items}
            loading={list.loading}
            error={list.error}
            page={list.page}
            totalPages={list.totalPages}
            total={list.total}
            onPageChange={list.setPage}
            status={status}
            onStatusChange={setStatus}
          />
        </div>
      </div>
    </div>
  );
}
