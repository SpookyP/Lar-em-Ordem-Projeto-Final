import {
  Zap,
  ShieldCheck,
  FileSignature,
  BadgeCheck,
  FileText,
  Building2,
  Users,
  MoreHorizontal,
  Pencil,
  Trash2,
  ExternalLink,
} from "lucide-react";
import { formatDate } from "../../utils/date";
import { useState } from "react";

const STATUS = {
  valid: {
    label: "Válido",
    border: "border-t-green",
    tile: "bg-green-light",
    badge: "bg-green-light text-green",
    validityLabel: "Em vigor até",
  },
  expiring: {
    label: "A expirar",
    border: "border-t-amber",
    tile: "bg-amber-light",
    badge: "bg-amber-light text-amber",
    validityLabel: "Expira a",
  },
  expired: {
    label: "Expirado",
    border: "border-t-red",
    tile: "bg-red-light",
    badge: "bg-red-light text-red",
    validityLabel: "Caducou a",
  },
};

function pickIcon(categoryName) {
  const n = (categoryName ?? "").toLowerCase();
  if (n.includes("energ")) return Zap;
  if (n.includes("seguro")) return ShieldCheck;
  if (n.includes("contrato")) return FileSignature;
  if (n.includes("garantia")) return BadgeCheck;
  if (n.includes("caderneta") || n.includes("predial")) return Building2;
  if (n.includes("condomin")) return Users;
  return FileText;
}

export function DocumentCard({ doc, onEdit, onDelete, onOpen }) {
  const [menuOpen, setMenuOpen] = useState(false);
  const st = STATUS[doc.status] ?? STATUS.valid;
  const Icon = pickIcon(doc.category?.name);
  const dateLabel = formatDate(doc.expirationDate);

  return (
    <div
      className={`flex w-72 shrink-0 flex-col rounded-2xl border border-border border-t-4 ${st.border} bg-card p-5 shadow-sm`}
    >
      <div className="flex items-start justify-between">
        <div
          className={`flex h-11 w-11 items-center justify-center rounded-xl ${st.tile}`}
        >
          <Icon size={20} className="text-text" />
        </div>

        <div className="relative">
          <button
            type="button"
            onClick={() => setMenuOpen((o) => !o)}
            aria-label="Opções"
            className="rounded-lg p-1 text-muted transition hover:bg-surface hover:text-navy"
          >
            <MoreHorizontal size={18} />
          </button>

          {menuOpen && (
            <>
              <button
                type="button"
                aria-hidden="true"
                onClick={() => setMenuOpen(false)}
                className="fixed inset-0 z-10 cursor-default"
              />
              <div className="absolute right-0 top-9 z-20 w-44 overflow-hidden rounded-xl border border-border bg-card py-1 shadow-lg">
                <button
                  type="button"
                  onClick={() => {
                    setMenuOpen(false);
                    onOpen?.(doc);
                  }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-navy transition hover:bg-surface"
                >
                  <ExternalLink size={15} /> Abrir documento
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setMenuOpen(false);
                    onEdit?.(doc);
                  }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-navy transition hover:bg-surface"
                >
                  <Pencil size={15} /> Editar
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setMenuOpen(false);
                    onDelete?.(doc);
                  }}
                  className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red transition hover:bg-red-light"
                >
                  <Trash2 size={15} /> Apagar
                </button>
              </div>
            </>
          )}
        </div>
      </div>

      <p className="mt-4 text-xs font-semibold uppercase tracking-wide text-muted">
        {doc.category?.name ?? "Documento"}
      </p>
      <h3 className="mt-0.5 font-display text-lg text-navy wrap-break-word">
        {doc.name}
      </h3>
      {doc.description ? (
        <p className="mt-1 text-sm text-muted wrap-break-word">
          {doc.description}
        </p>
      ) : null}

      <div className="mt-4 flex items-end justify-between gap-2 border-t border-border pt-3">
        <div className="min-w-0">
          <p className="text-xs text-muted">{st.validityLabel}</p>
          <p className="truncate font-semibold text-navy">
            {dateLabel || "Sem validade"}
          </p>
        </div>
        <span
          className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${st.badge}`}
        >
          {st.label}
        </span>
      </div>
    </div>
  );
}
