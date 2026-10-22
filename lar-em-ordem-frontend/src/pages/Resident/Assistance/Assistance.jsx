import AssistanceForm from "../../../components/ui/AssistanceForm";
import ProblemList from "../../../components/ui/ProblemList";
import { useAuth } from "../../../hooks/useAuth";
import { useAssistanceRequests } from "../../../hooks/useAssistanceRequests";
import {
  createAssistanceRequest,
  uploadAttachments,
} from "../../../services/api/assistanceService";

export default function Assistance() {
  const { activeHouse } = useAuth();
  const { requests, loading, error, addRequest } = useAssistanceRequests();

  async function handleNewRequest({ title, category, description, photo }) {
    if (!activeHouse) {
      throw new Error("Selecione uma casa antes de abrir um pedido.");
    }

    const created = await createAssistanceRequest({
      property_id: activeHouse.id,
      problem_category_id: Number(category),
      title,
      description,
    });
    addRequest(created);

    try {
      await uploadAttachments(created.id, [photo]);
    } catch {
      return "O pedido foi criado, mas não foi possível enviar a fotografia.";
    }
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
        <ProblemList requests={requests} loading={loading} error={error} />
      </div>
    </div>
  );
}
