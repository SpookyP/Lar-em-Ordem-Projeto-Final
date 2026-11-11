import { useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import { ChevronLeft, Pencil, Plus, Trash2 } from "lucide-react";
import { deleteDocument } from "../../../services/api/condominiumService";
import { useCondominiumDocumentsPage } from "../../../hooks/useCondominiumDocumentsPage";
import { DocumentCard } from "../../../components/condominium/DocumentCard";
import { DocumentForm } from "../../../components/condominium/DocumentForm";
import { Pager } from "../../../components/ui/Pager";
import { getErrorMessage } from "../../../utils/apiError";

function CategoryFilter({ categories, value, onChange }) {
  const chip = (active) =>
    `rounded-full px-3 py-1.5 text-sm transition ${
      active ? "bg-navy text-white" : "bg-card text-muted shadow-sm hover:text-navy"
    }`;

  return (
    <div className="flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoria">
      <button type="button" onClick={() => onChange("")} className={chip(value === "")} aria-pressed={value === ""}>
        Todas
      </button>
      {categories.map((category) => (
        <button
          key={category.id}
          type="button"
          onClick={() => onChange(category.id)}
          className={chip(value === category.id)}
          aria-pressed={value === category.id}
        >
          {category.name}
        </button>
      ))}
    </div>
  );
}

export default function CondominiumDocuments() {
  const { id } = useParams();
  // the name comes from the condominium page, so this page needs no extra request for it
  const name = useLocation().state?.name;

  const [categoryId, setCategoryId] = useState("");
  // "new" for the upload form, a document for the edit form, null when closed
  const [editing, setEditing] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [busyId, setBusyId] = useState(null);
  const [actionError, setActionError] = useState("");
  const [message, setMessage] = useState("");

  const {
    items: documents,
    extra,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useCondominiumDocumentsPage(id, { categoryId, perPage: 5 });

  const categories = extra?.categories ?? [];

  function handleSaved() {
    setMessage(editing === "new" ? "Documento carregado." : "Documento atualizado.");
    setEditing(null);
    if (page !== 1) setPage(1);
    else reload();
  }

  async function handleDelete(doc) {
    setBusyId(doc.id);
    setActionError("");
    setMessage("");
    try {
      await deleteDocument(doc.id);
      setConfirmingId(null);
      if (documents.length === 1 && page > 1) setPage(page - 1);
      else reload();
    } catch (err) {
      setActionError(getErrorMessage(err, "Não foi possível apagar o documento."));
    } finally {
      setBusyId(null);
    }
  }

  const isEmpty = !loading && !error && total === 0;

  return (
    <div className="mx-auto flex max-w-3xl flex-col gap-6">
      <div className="flex items-center justify-between gap-3">
        <div className="flex min-w-0 items-center gap-3">
          <Link
            to={`/admin/condominiums/${id}`}
            aria-label="Voltar"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card text-muted shadow-sm transition hover:text-navy"
          >
            <ChevronLeft size={20} />
          </Link>
          <div className="min-w-0">
            <h1 className="font-display text-2xl text-navy">Documentos</h1>
            {name && <p className="truncate text-sm text-muted">{name}</p>}
          </div>
        </div>
        {editing === null && (
          <button
            type="button"
            onClick={() => {
              setMessage("");
              setEditing("new");
            }}
            disabled={categories.length === 0}
            className="flex shrink-0 items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light disabled:opacity-60"
          >
            <Plus size={18} /> Novo documento
          </button>
        )}
      </div>

      {message && (
        <p role="status" className="rounded-lg bg-green-light px-3 py-2 text-sm text-green">
          {message}
        </p>
      )}
      {(error || actionError) && (
        <p role="alert" className="text-sm text-red">
          {error || actionError}
        </p>
      )}

      {editing !== null && (
        <section className="rounded-2xl bg-card p-6 shadow-sm">
          <h2 className="mb-4 font-medium text-navy">
            {editing === "new" ? "Novo documento" : "Editar documento"}
          </h2>
          <DocumentForm
            // a new key per document, so switching documents starts a fresh form
            key={editing === "new" ? "new" : editing.id}
            condominiumId={id}
            doc={editing === "new" ? null : editing}
            categories={categories}
            onSaved={handleSaved}
            onCancel={() => setEditing(null)}
          />
        </section>
      )}

      {categories.length > 0 && (
        <CategoryFilter categories={categories} value={categoryId} onChange={setCategoryId} />
      )}

      {loading && documents.length === 0 ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : isEmpty ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <p className="text-sm text-muted">
            {categoryId ? "Não há documentos nesta categoria." : "Ainda não carregaste nenhum documento."}
          </p>
        </div>
      ) : (
        <>
          <div className="flex flex-col gap-4">
            {documents.map((doc) => (
              <DocumentCard key={doc.id} doc={doc}>
                <div className="flex items-center gap-4 border-t border-border pt-3 text-sm">
                  {confirmingId === doc.id ? (
                    <>
                      <span className="text-muted">Apagar o documento e o ficheiro?</span>
                      <button
                        type="button"
                        onClick={() => handleDelete(doc)}
                        disabled={busyId === doc.id}
                        className="ml-auto rounded-full bg-red px-3 py-1 text-white transition hover:opacity-90 disabled:opacity-60"
                      >
                        {busyId === doc.id ? "A apagar..." : "Sim, apagar"}
                      </button>
                      <button
                        type="button"
                        onClick={() => setConfirmingId(null)}
                        className="text-muted underline hover:text-navy"
                      >
                        Cancelar
                      </button>
                    </>
                  ) : (
                    <>
                      <button
                        type="button"
                        onClick={() => {
                          setMessage("");
                          setEditing(doc);
                          window.scrollTo({ top: 0, behavior: "smooth" });
                        }}
                        className="flex items-center gap-1.5 text-muted hover:text-navy"
                      >
                        <Pencil size={15} /> Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => setConfirmingId(doc.id)}
                        className="ml-auto flex items-center gap-1.5 text-red hover:opacity-80"
                      >
                        <Trash2 size={15} /> Apagar
                      </button>
                    </>
                  )}
                </div>
              </DocumentCard>
            ))}
          </div>
          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}
    </div>
  );
}