import { useState, useEffect } from "react";
import { Camera } from "lucide-react";
import { getCategories } from "../../services/api/assistanceService";
import { TextInput, SearchSelect, TextArea } from "../form";

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

    if (!title.trim() || !category || !description.trim()) {
      setError("Preencha todos os campos obrigatórios.");
      return;
    }

    const form = e.currentTarget;
    setSubmitting(true);
    setError("");
    try {
      await onSubmit({ title, category, description, photo });
      form.reset();
      setTitle("");
      setCategory("");
      setDescription("");
      setPhoto(null);
      setPhotoError("");
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  const categoryOptions = categories.map((c) => ({
    value: String(c.id),
    label: c.label,
  }));

  return (
    <div className="rounded-2xl bg-card p-8 shadow-sm">
      <div className="mb-6">
        <h2 className="font-display text-xl text-navy">
          Novo Pedido de Assistência
        </h2>
        <p className="mt-1 text-sm text-muted">
          <span className="text-red">*</span> Campos de preenchimento
          obrigatório
        </p>
      </div>
      <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <TextInput
            label="Resumo do problema"
            required
            value={title}
            onChange={setTitle}
            maxLength={255}
            placeholder="Exemplo: Avaria na máquina de lavar louça"
          />

          <div>
            <SearchSelect
              label="Categoria da avaria"
              required
              options={categoryOptions}
              value={category}
              onChange={setCategory}
              placeholder="Selecionar tipo de avaria..."
            />
            {categoriesError && (
              <span className="mt-1 block text-xs font-medium text-red">
                {categoriesError}
              </span>
            )}
          </div>
        </div>
        <TextArea
          label="Descreva o problema"
          required
          value={description}
          onChange={setDescription}
          rows={4}
          maxLength={5000}
          placeholder="Descreva detalhadamente a avaria: onde, quando começou, o que já tentou fazer..."
          hint="Nota: Não inclua moradas nem contactos: o pedido é visível para prestadores da sua zona."
        />

        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-navy">
            Fotografia da avaria
          </label>
          <label className="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border bg-background/50 p-6 transition-colors hover:border-orange/50">
            <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-orange-light text-orange">
              <Camera className="h-6 w-6" />
            </div>
            <span className="text-sm font-medium text-navy">
              {photo ? photo.name : "Adicionar Fotografia"}
            </span>
            <span className="mt-1 text-xs text-muted">
              Opcional — tire uma foto ou escolha da galeria
            </span>
            <span className="mt-0.5 text-xs text-muted">
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
            <span className="text-xs font-medium text-red">{photoError}</span>
          )}
        </div>

        {error && <p className="text-sm text-red font-medium">{error}</p>}

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
