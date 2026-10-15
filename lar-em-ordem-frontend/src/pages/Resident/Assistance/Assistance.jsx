import AssistanceForm from "../../../components/ui/AssistanceForm";
import ProblemList from "../../../components/ui/ProblemList";

export default function Assistance() {
  const handleNewRequest = (formData) => {
    console.log("Dados do novo pedido:", formData);
  };

  return (
    <div className="flex flex-col gap-6 max-w-3xl mx-auto">
      <header>
        <h1 className="font-display text-3xl text-orange">
          Central de Assistência
        </h1>
      </header>

      <div className="flex flex-col gap-6">
        <AssistanceForm onSubmit={handleNewRequest} />
        <ProblemList />
      </div>
    </div>
  );
}