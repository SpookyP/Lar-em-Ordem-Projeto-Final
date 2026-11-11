import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { Mail, MapPin, Phone, PiggyBank, Settings, UserRound } from "lucide-react";
import { useAuth } from "../../../hooks/useAuth";
import { usePropertyDocumentsPage } from "../../../hooks/usePropertyDocumentsPage";
import { CategoryFilter } from "../../../components/condominium/CategoryFilter";
import { DocumentCard } from "../../../components/condominium/DocumentCard";
import { Pager } from "../../../components/ui/Pager";
import { priorityInfo } from "../../../data/condominiumInfo";
import { addressLine, formatMoney } from "../../../utils/format";
import { formatDate } from "../../../utils/date";

// the administrator's contact may be an email or a phone number: make it clickable either way
function ContactLink({ contact }) {
  if (!contact) return null;
  const isEmail = contact.includes("@");
  const Icon = isEmail ? Mail : Phone;
  const href = isEmail ? `mailto:${contact}` : `tel:${contact.replace(/[^\d+]/g, "")}`;

  return (
    <a href={href} className="flex items-center gap-2 text-navy hover:underline">
      <Icon size={16} aria-hidden="true" />
      {contact}
    </a>
  );
}

function Card({ title, children }) {
  return (
    <section className="flex flex-col gap-4 rounded-2xl bg-card p-6 shadow-sm">
      <h2 className="font-medium text-navy">{title}</h2>
      {children}
    </section>
  );
}

function Notices({ notices }) {
  if (notices.length === 0) {
    return <p className="text-sm text-muted">Não há avisos em vigor.</p>;
  }

  return (
    <ul className="flex flex-col gap-3">
      {notices.map((notice) => {
        const priority = priorityInfo(notice.priority);
        return (
          <li key={notice.id} className="flex flex-col gap-1.5 rounded-xl border border-border p-4">
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="font-medium text-text">{notice.title}</h3>
              <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${priority.className}`}>
                {priority.label}
              </span>
            </div>
            <p className="whitespace-pre-line text-sm text-text">{notice.message}</p>
            <p className="text-xs text-muted">
              Publicado a {formatDate(notice.publishedAt)}
              {notice.expiresAt ? ` · em vigor até ${formatDate(notice.expiresAt)}` : ""}
            </p>
          </li>
        );
      })}
    </ul>
  );
}

// documents the administration shared (minutes, rules, budgets...), read-only for the resident
function Documents({ propertyId }) {
  const [categoryId, setCategoryId] = useState("");
  const {
    items: documents,
    extra,
    page,
    totalPages,
    total,
    loading,
    error,
    setPage,
  } = usePropertyDocumentsPage(propertyId, { categoryId, perPage: 5 });

  const categories = extra?.categories ?? [];

  return (
    <section className="flex flex-col gap-4">
      <h2 className="font-medium text-navy">Documentos</h2>

      {categories.length > 0 && (
        <CategoryFilter categories={categories} value={categoryId} onChange={setCategoryId} />
      )}

      {error ? (
        <p role="alert" className="text-sm text-red">
          {error}
        </p>
      ) : loading && documents.length === 0 ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : total === 0 ? (
        <div className="rounded-2xl bg-card p-8 text-center shadow-sm">
          <p className="text-sm text-muted">
            {categoryId
              ? "Não há documentos nesta categoria."
              : "A administração ainda não partilhou nenhum documento."}
          </p>
        </div>
      ) : (
        <>
          <div className="flex flex-col gap-4">
            {documents.map((doc) => (
              <DocumentCard key={doc.id} doc={doc} />
            ))}
          </div>
          <Pager page={page} totalPages={totalPages} onChange={setPage} />
        </>
      )}
    </section>
  );
}

export default function Condominium() {
  // loaded once per home by AuthContext, the same answer that decides the menu item
  const { activeHouse, activeCondominium: condominium } = useAuth();

  if (condominium === undefined) {
    return <p className="text-sm text-muted">A carregar...</p>;
  }

  // no condominium (or the home just left it): joining is done in the settings
  if (condominium === null) {
    return <Navigate to="/settings" state={{ section: "condominium" }} replace />;
  }

  return (
    <div className="mx-auto flex max-w-5xl flex-col gap-6">
      <div className="flex items-center justify-between gap-3">
        <h1 className="font-display text-2xl text-navy">Condomínio</h1>
        <Link
          to="/settings"
          state={{ section: "condominium" }}
          className="flex items-center gap-1.5 text-sm text-muted hover:text-navy"
        >
          <Settings size={16} aria-hidden="true" /> Gerir
        </Link>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card title={condominium.name}>
          <div className="flex flex-col gap-3 text-sm text-text">
            <p className="flex items-start gap-2">
              <MapPin size={16} className="mt-0.5 shrink-0 text-muted" aria-hidden="true" />
              {addressLine(condominium.address)}
            </p>
            {condominium.reserveFund != null && (
              <p className="flex items-center gap-2">
                <PiggyBank size={16} className="text-muted" aria-hidden="true" />
                Fundo comum de reserva: {formatMoney(condominium.reserveFund)}
              </p>
            )}
          </div>
        </Card>

        <Card title="Administração">
          <div className="flex flex-col gap-3 text-sm">
            <p className="flex items-center gap-2 text-text">
              <UserRound size={16} className="text-muted" aria-hidden="true" />
              {condominium.admin?.name ?? "Sem administrador"}
            </p>
            <ContactLink contact={condominium.admin?.contact} />
          </div>
        </Card>

        <div className="lg:col-span-2">
          <Card title="Avisos em vigor">
            <Notices notices={condominium.notices ?? []} />
          </Card>
        </div>
        
        <div className="lg:col-span-2">
          <Documents propertyId={activeHouse.id} />
        </div>
      </div>
    </div>
  );
}