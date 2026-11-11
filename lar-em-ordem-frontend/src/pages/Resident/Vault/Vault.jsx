import { useEffect, useMemo, useRef, useState } from "react";
import { Search, ChevronLeft, ChevronRight, UploadCloud } from "lucide-react";
import { DocumentCard } from "../../../components/ui/DocumentCard";
import {
  getPropertyDocuments,
  deleteDocument,
  getDocumentDownloadUrl,
} from "../../../services/api/documentService";
import { useAuth } from "../../../hooks/useAuth";
import { Navigate } from "react-router-dom";
import { isOwnerHouse } from "../../../utils/ownerHouse";
import { DocumentUploadModal } from "../../../components/ui/DocumentUploadModal";
import { DocumentEditModal } from "../../../components/ui/DocumentEditModal";
import { ConfirmDialog } from "../../../components/ui/ConfirmDialog";

const FILTERS = [
  { value: "all", label: "Todos" },
  { value: "valid", label: "Válidos" },
  { value: "expiring", label: "A expirar" },
  { value: "expired", label: "Expirados" },
];

export default function Vault() {
  const { activeHouse } = useAuth();
  const isOwner = isOwnerHouse(activeHouse);
  const propertyId = isOwner ? (activeHouse?.id ?? null) : null;

  const [docs, setDocs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [refreshKey, setRefreshKey] = useState(0);
  const [uploadOpen, setUploadOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState("all");
  const scrollRef = useRef(null);
  const [editingDoc, setEditingDoc] = useState(null);
  const [deletingDoc, setDeletingDoc] = useState(null);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!propertyId) return;
    let active = true;
    setLoading(true);
    setError("");
    getPropertyDocuments({ propertyId, perPage: 15 })
      .then((res) => {
        if (active) setDocs(res.items);
      })
      .catch(() => {
        if (active) setError("Não foi possível carregar os documentos.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [propertyId, refreshKey]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    return docs.filter((d) => {
      const matchFilter = filter === "all" || d.status === filter;
      const matchQuery =
        !q ||
        [d.name, d.description, d.category?.name]
          .filter(Boolean)
          .join(" ")
          .toLowerCase()
          .includes(q);
      return matchFilter && matchQuery;
    });
  }, [docs, query, filter]);

  if (!isOwner) {
    return <Navigate to="/dashboard" replace />;
  }

  const total = docs.length;
  const attention = docs.filter((d) => d.status !== "valid").length;

  //recarregar lista de documentos
  function reload() {
    setRefreshKey((k) => k + 1);
  }

  //carrosel de docs
  function scroll(dir) {
    scrollRef.current?.scrollBy({ left: dir * 320, behavior: "smooth" });
  }

  async function confirmDelete() {
    if (!deletingDoc) return;
    setDeleting(true);
    try {
      await deleteDocument(deletingDoc.id);
      setDeletingDoc(null);
      reload();
    } catch {
      setError("Não foi possível apagar o documento.");
    } finally {
      setDeleting(false);
    }
  }

  async function handleOpen(doc) {
    const win = window.open("", "_blank");
    try {
      const url = await getDocumentDownloadUrl(doc.id);
      if (url && win) win.location.href = url;
      else if (win) win.close();
    } catch {
      if (win) win.close();
      setError("Não foi possível abrir o documento.");
    }
  }

  return (
    <div className="flex flex-col gap-8">
      <div>
        <h1 className="font-display text-4xl text-navy">Cofre Digital</h1>
        <p className="mt-1 text-muted">
          {total}{" "}
          {total === 1 ? "documento arquivado" : "documentos arquivados"} ·{" "}
          {attention} a precisar de atenção
        </p>
      </div>

      <div className="rounded-2xl border border-border bg-card p-4">
        <div className="relative">
          <Search
            size={18}
            className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted"
          />
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Procurar por nome, descrição ou categoria…"
            className="w-full rounded-xl border border-border bg-surface py-2.5 pl-11 pr-4 text-sm text-text outline-none focus:border-navy"
          />
        </div>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          {FILTERS.map((f) => (
            <button
              key={f.value}
              onClick={() => setFilter(f.value)}
              className={`rounded-full px-3 py-1.5 text-sm font-medium transition ${
                filter === f.value
                  ? "bg-navy text-white"
                  : "bg-surface text-muted hover:text-navy"
              }`}
            >
              {f.label}
            </button>
          ))}
          <span className="ml-auto text-sm text-muted">
            {filtered.length} resultados
          </span>
        </div>
      </div>

      <section className="flex flex-col gap-4">
        <div className="flex items-center justify-between">
          <h2 className="font-display text-xl text-navy">Os meus documentos</h2>
          <div className="flex gap-2">
            <button
              onClick={() => scroll(-1)}
              aria-label="Anterior"
              className="rounded-full border border-border bg-card p-2 text-navy transition hover:bg-surface"
            >
              <ChevronLeft size={18} />
            </button>
            <button
              onClick={() => scroll(1)}
              aria-label="Seguinte"
              className="rounded-full border border-border bg-card p-2 text-navy transition hover:bg-surface"
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>

        {!propertyId ? (
          <p className="text-sm text-muted">Nenhuma casa selecionada.</p>
        ) : loading ? (
          <p className="text-sm text-muted">A carregar documentos…</p>
        ) : error ? (
          <p className="text-sm text-red">{error}</p>
        ) : filtered.length === 0 ? (
          <p className="text-sm text-muted">
            {docs.length === 0
              ? "Ainda não há documentos relativos a esta casa."
              : "Nenhum documento corresponde à pesquisa."}
          </p>
        ) : (
          <div ref={scrollRef} className="flex gap-4 overflow-x-auto pb-2">
            {filtered.map((doc) => (
              <DocumentCard
                key={doc.id}
                doc={doc}
                onEdit={setEditingDoc}
                onDelete={setDeletingDoc}
                onOpen={handleOpen}
              />
            ))}
          </div>
        )}
      </section>

      <div className="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed border-border p-8 text-center">
        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-surface text-navy">
          <UploadCloud size={24} />
        </div>
        <div>
          <p className="font-semibold text-navy">Carregar Novo Documento</p>
          <p className="text-sm text-muted">
            Arraste e solte aqui, ou clique para selecionar · PDF, JPG, PNG
            (máx. 10 MB)
          </p>
        </div>
        <button
          type="button"
          onClick={() => setUploadOpen(true)}
          className="rounded-xl bg-navy px-5 py-2.5 font-semibold text-white transition hover:bg-navy-light"
        >
          Escolher ficheiro
        </button>
      </div>

      {uploadOpen && (
        <DocumentUploadModal
          propertyId={propertyId}
          onClose={() => setUploadOpen(false)}
          onUploaded={reload}
        />
      )}

      {editingDoc && (
        <DocumentEditModal
          doc={editingDoc}
          onClose={() => setEditingDoc(null)}
          onSaved={reload}
        />
      )}

      {deletingDoc && (
        <ConfirmDialog
          title="Apagar documento"
          message={`Queres mesmo apagar "${deletingDoc.name}"? Esta ação não pode ser desfeita.`}
          confirmLabel="Apagar"
          loadingLabel="A apagar…"
          danger
          loading={deleting}
          onConfirm={confirmDelete}
          onCancel={() => setDeletingDoc(null)}
        />
      )}
    </div>
  );
}
