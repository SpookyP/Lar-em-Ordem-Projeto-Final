import { useState } from "react";
import { Link, useLocation, useParams } from "react-router-dom";
import { ChevronLeft, Eye, Pencil, Plus, Trash2 } from "lucide-react";
import { deleteNotice } from "../../../services/api/condominiumService";
import { useCondominiumNoticesPage } from "../../../hooks/useCondominiumNoticesPage";
import { NoticeForm } from "../../../components/condominium/NoticeForm";
import { Pager } from "../../../components/ui/Pager";
import { isNoticeCurrent, priorityInfo } from "../../../data/condominiumInfo";
import { formatDate } from "../../../utils/date";
import { getErrorMessage } from "../../../utils/apiError";
import { ConfirmDialog } from "../../../components/ui/ConfirmDialog";
import { LoadingState } from "../../../components/ui/LoadingState";
import { EmptyState } from "../../../components/ui/EmptyState";

function NoticeItem({ notice, onEdit, onAskDelete }) {
  const priority = priorityInfo(notice.priority);
  const current = isNoticeCurrent(notice);

  return (
    <article className="flex flex-col gap-3 rounded-2xl bg-card p-5 shadow-sm">
      <div className="flex flex-wrap items-center gap-2">
        <h2 className="font-display text-lg text-navy">{notice.title}</h2>
        <span
          className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${priority.className}`}
        >
          {priority.label}
        </span>
        <span
          className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${
            current ? "bg-green-light text-green" : "bg-surface text-muted"
          }`}
        >
          {current ? "Em vigor" : "Expirado"}
        </span>
      </div>

      <p className="whitespace-pre-line text-sm text-text">{notice.message}</p>

      <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
        <span>Publicado a {formatDate(notice.publishedAt)}</span>
        <span>
          {notice.expiresAt
            ? `${current ? "Em vigor até" : "Terminou a"} ${formatDate(notice.expiresAt)}`
            : "Sem data de fim"}
        </span>
        {notice.readersCount != null && (
          <span className="flex items-center gap-1">
            <Eye size={13} aria-hidden="true" />
            Lido por {notice.readersCount}{" "}
            {notice.readersCount === 1 ? "residente" : "residentes"}
          </span>
        )}
      </div>

      <div className="flex items-center gap-4 border-t border-border pt-3 text-sm">
        <button
          type="button"
          onClick={onEdit}
          className="flex items-center gap-1.5 text-muted hover:text-navy"
        >
          <Pencil size={15} /> Editar
        </button>
        <button
          type="button"
          onClick={onAskDelete}
          className="ml-auto flex items-center gap-1.5 text-red hover:opacity-80"
        >
          <Trash2 size={15} /> Apagar
        </button>
      </div>
    </article>
  );
}

export default function CondominiumNotices() {
  const { id } = useParams();
  // the name comes from the condominium page, so this page needs no extra request for it
  const name = useLocation().state?.name;

  // "new" for the publish form, a notice for the edit form, null when closed
  const [editing, setEditing] = useState(null);
  const [deletingNotice, setDeletingNotice] = useState(null);
  const [busyId, setBusyId] = useState(null);
  const [actionError, setActionError] = useState("");
  const [message, setMessage] = useState("");

  const {
    items: notices,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
    reload,
  } = useCondominiumNoticesPage(id, { perPage: 5 });

  function handleSaved() {
    setMessage(editing === "new" ? "Aviso publicado." : "Aviso atualizado.");
    setEditing(null);
    if (page !== 1) setPage(1);
    else reload();
  }

  async function handleDelete(notice) {
    setBusyId(notice.id);
    setActionError("");
    setMessage("");
    try {
      await deleteNotice(notice.id);
      setDeletingNotice(null);
      if (notices.length === 1 && page > 1) setPage(page - 1);
      else reload();
    } catch (err) {
      setActionError(getErrorMessage(err, "Não foi possível apagar o aviso."));
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
            <h1 className="font-display text-2xl text-navy">Avisos</h1>
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
            className="flex shrink-0 items-center gap-2 rounded-xl bg-navy px-4 py-2 font-bold text-white transition hover:bg-navy-light"
          >
            <Plus size={18} /> Novo aviso
          </button>
        )}
      </div>

      {message && (
        <p
          role="status"
          className="rounded-lg bg-green-light px-3 py-2 text-sm text-green"
        >
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
            {editing === "new" ? "Novo aviso" : "Editar aviso"}
          </h2>
          <NoticeForm
            // a new key per notice, so switching notices starts a fresh form
            key={editing === "new" ? "new" : editing.id}
            condominiumId={id}
            notice={editing === "new" ? null : editing}
            onSaved={handleSaved}
            onCancel={() => setEditing(null)}
          />
        </section>
      )}

      {loading && notices.length === 0 ? (
        <LoadingState />
      ) : isEmpty ? (
        editing === null && (
          <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
            <EmptyState message="Ainda não publicou nenhum aviso." />
          </div>
        )
      ) : (
        <>
          <div className="flex flex-col gap-4">
            {notices.map((notice) => (
              <NoticeItem
                key={notice.id}
                notice={notice}
                onEdit={() => {
                  setMessage("");
                  setEditing(notice);
                  window.scrollTo({ top: 0, behavior: "smooth" });
                }}
                onAskDelete={() => setDeletingNotice(notice)}
              />
            ))}
          </div>
          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}
      {deletingNotice && (
        <ConfirmDialog
          title="Apagar aviso"
          message={`Tem a certeza que pretende apagar o aviso "${deletingNotice.title}"? Esta ação não pode ser desfeita.`}
          confirmLabel="Apagar"
          loadingLabel="A apagar…"
          danger
          loading={busyId === deletingNotice.id}
          onConfirm={() => handleDelete(deletingNotice)}
          onCancel={() => setDeletingNotice(null)}
        />
      )}
    </div>
  );
}
