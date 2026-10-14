import { useState } from "react";
import { Camera } from "lucide-react";

export default function AssistanceForm({ onSubmit }) {
  const [category, setCategory] = useState("");
  const [description, setDescription] = useState("");
  const [photo, setPhoto] = useState(null);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (onSubmit) {
      onSubmit({ category, description, photo });
    }
  };

  return (
    <div className="rounded-2xl bg-card p-8 shadow-sm">
      {/* Cabeçalho do cartão */}
      <div className="flex items-start justify-between mb-6">
        <h2 className="font-display text-xl text-navy">
          Novo Pedido de Assistência
        </h2>
        <p className="text-sm text-muted">
          <span className="text-red">*</span> Campos de preenchimento obrigatório
        </p>
      </div>

      {/* Formulário */}
      <form onSubmit={handleSubmit} className="flex flex-col gap-5">
        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Categoria da avaria <span className="text-red">*</span>
          </label>
          <select
            value={category}
            onChange={(e) => setCategory(e.target.value)}
            className="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-navy focus:outline-none focus:ring-2 focus:ring-orange/50"
            required
          >
            <option value="" disabled>Selecionar tipo de avaria...</option>
            <option value="canalizacao">Canalização</option>
            <option value="eletricidade">Eletricidade</option>
            <option value="gas">Gás</option>
            <option value="outro">Outro</option>
          </select>
        </div>

        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Descreva o problema <span className="text-red">*</span>
          </label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Descreva detalhadamente a avaria: localização, quando começou, o que já tentou fazer..."
            rows={4}
            className="w-full rounded-xl border border-border bg-background p-4 text-sm text-navy focus:outline-none focus:ring-2 focus:ring-orange/50 resize-none"
            required
          />
        </div>

        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Fotografia da avaria <span className="text-red">*</span>
          </label>
          <label className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border p-6 cursor-pointer hover:border-orange/50 transition-colors bg-background/50">
            <div className="w-12 h-12 rounded-full bg-orange-light flex items-center justify-center text-orange mb-3">
              <Camera className="w-6 h-6" />
            </div>
            <span className="text-sm font-medium text-navy">Adicionar Fotografia</span>
            <span className="text-xs text-muted mt-1">Obrigatória — tire uma foto ou escolha da galeria</span>
            <input 
              type="file" 
              accept="image/*" 
              className="hidden" 
              onChange={(e) => setPhoto(e.target.files[0])} 
              required 
            />
          </label>
        </div>

        <button
          type="submit"
          className="w-full mt-2 rounded-xl bg-orange py-3.5 text-sm font-medium text-white shadow-sm hover:bg-orange/90 transition-colors cursor-pointer"
        >
          Submeter Pedido
        </button>
      </form>
    </div>
  );
}