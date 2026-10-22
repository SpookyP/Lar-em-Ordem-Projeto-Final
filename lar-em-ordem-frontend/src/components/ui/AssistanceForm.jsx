import { useState, useEffect } from "react";
import { Camera } from "lucide-react";
import { getCategories } from "../../services/api/assistanceService";

const MAX_PHOTO_SIZE = 5 * 1024 * 1024;
const PHOTO_TYPES = ["image/jpeg", "image/png", "image/webp"];

function getErrorMessage(err) {
  const status = err.response?.status;
  if (status === 422) {
    const errors = err.response.data.errors ?? {};
    return Object.values(errors)[0]?.[0] ?? err.response.data.message;
  }
  if (status === 429) {
    return "Demasiados pedidos. Tente novamente dentro de um minuto.";
  }
  if (err.response || err.isAxiosError) {
    return "Não foi possível enviar o pedido.";
  }
  return err.message;
}

export default function AssistanceForm({ onSubmit }) {
  const [title, setTitle] = useState("");
  const [category, setCategory] = useState("");
  const [description, setDescription] = useState("");
  const [photo, setPhoto] = useState(null);
  const [photoError, setPhotoError] = useState("");

  const [categories, setCategories] = useState([]);
  const [categoriesError, setCategoriesError] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    let active = true;
    getCategories()
      .then((list) => {
        if (active) setCategories(list);
      })
      .catch(() => {
        if (active)
          setCategoriesError("Não foi possível carregar as categorias.");
      });
    return () => {
      active = false;
    };
  }, []);

  const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!PHOTO_TYPES.includes(file.type)) {
      setPhotoError("A imagem tem de ser JPG, PNG ou WebP.");
      setPhoto(null);
      e.target.value = "";
      return;
    }

    if (file.size > MAX_PHOTO_SIZE) {
      setPhotoError("A imagem é muito grande. O limite máximo é 5MiB.");
      setPhoto(null);
      e.target.value = "";
      return;
    }

    setPhotoError("");
    setPhoto(file);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const form = e.currentTarget;

    //o input de ficheiro esta escondido, o required do browser nao avisa
    if (!photo) {
      setPhotoError("Adicione uma fotografia da avaria.");
      return;
    }

    setSubmitting(true);
    setError("");
    setNotice("");
    try {
      const warning = await onSubmit({ title, category, description, photo });
      form.reset();
      setTitle("");
      setCategory("");
      setDescription("");
      setPhoto(null);
      if (warning) setNotice(warning);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
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
          <span className="text-red">*</span> Campos de preenchimento
          obrigatório
        </p>
      </div>

      {/* Formulário */}
      <form onSubmit={handleSubmit} className="flex flex-col gap-5">
        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Resumo do problema <span className="text-red">*</span>
          </label>
          <input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            className="w-full rounded-xl border border-border bg-background px-4 py-3 text-sm text-navy focus:outline-none focus:ring-2 focus:ring-orange/50"
            placeholder="Exemplo: Avaria na maquina de lavar louça"
            maxLength={255}
            required
          />
        </div>

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
            <option value="" disabled>
              Selecionar tipo de avaria...
            </option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.label}
              </option>
            ))}
          </select>
          {categoriesError && (
            <span className="text-xs text-red font-medium">
              {categoriesError}
            </span>
          )}
        </div>

        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Descreva o problema <span className="text-red">*</span>
          </label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Descreva detalhadamente a avaria: onde na casa, quando começou, o que já tentou fazer..."
            rows={4}
            maxLength={5000}
            className="w-full rounded-xl border border-border bg-background p-4 text-sm text-navy focus:outline-none focus:ring-2 focus:ring-orange/50 resize-none"
            required
          />
          <span className="text-xs text-muted">
            Não inclua moradas nem contactos: o pedido é visível para
            prestadores da sua zona.
          </span>
        </div>

        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Fotografia da avaria <span className="text-red">*</span>
          </label>
          <label className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border p-6 cursor-pointer hover:border-orange/50 transition-colors bg-background/50">
            <div className="w-12 h-12 rounded-full bg-orange-light flex items-center justify-center text-orange mb-3">
              <Camera className="w-6 h-6" />
            </div>
            <span className="text-sm font-medium text-navy">
              {photo ? photo.name : "Adicionar Fotografia"}
            </span>
            <span className="text-xs text-muted mt-1">
              Obrigatória — tire uma foto ou escolha da galeria
            </span>
            <span className="text-xs text-muted mt-0.5">
              PNG, JPG ou WebP (Máx. 5MiB)
            </span>
            <input
              type="file"
              accept="image/jpeg, image/png, image/webp"
              className="hidden"
              onChange={handlePhotoChange}
            />
          </label>
          {photoError && (
            <span className="text-xs text-red font-medium">{photoError}</span>
          )}
        </div>

        {error && <p className="text-sm text-red font-medium">{error}</p>}
        {notice && <p className="text-sm text-orange font-medium">{notice}</p>}

        <button
          type="submit"
          disabled={submitting}
          className="w-full mt-2 rounded-xl bg-orange py-3.5 text-sm font-medium text-white shadow-sm hover:bg-orange/90 transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {submitting ? "A enviar..." : "Submeter Pedido"}
        </button>
      </form>
    </div>
  );
}
