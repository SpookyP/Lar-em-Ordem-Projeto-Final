import { useEffect } from "react";
import { Link, useParams } from "react-router-dom";
import {
  ArrowLeft,
  Building2,
  Calendar,
  Globe,
  Phone,
  Tag,
  X,
} from "lucide-react";
import { getOffer } from "../../../services/api/offerService";
import { getPartner } from "../../../services/api/partnerService";
import { getOfferStatus } from "../../../utils/offerStatus";
import { createPortal } from "react-dom";
import { formatDate } from "../../../utils/date";
import { toHref } from "../../../utils/url";
import { useGoBack } from "../../../hooks/useGoBack";
import { useFetch } from "../../../hooks/useFetch";
import { LoadingState } from "../../../components/ui/LoadingState";

export default function OfferDetail() {
  const { id } = useParams();

  const goBack = useGoBack("/offers");

  function close() {
    goBack();
  }

  const {
    data: offer,
    loading,
    error,
  } = useFetch(() => getOffer(id), [id], {
    errorMessage: "Não foi possível carregar esta oferta.",
  });

  const partnerId = offer?.partner?.id;
  const { data: partner } = useFetch(() => getPartner(partnerId), [partnerId], {
    enabled: Boolean(partnerId),
  });

  useEffect(() => {
    function onKey(e) {
      if (e.key === "Escape") goBack();
    }
    window.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [goBack]);

  const status = offer ? getOfferStatus(offer) : null;
  const start = offer ? formatDate(offer.startDate) : null;
  const end = offer ? formatDate(offer.endDate) : null;
  const contact = partner ?? offer?.partner;
  const hasContacts = contact && (contact.phone || contact.website);

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/60" onClick={close} />

      <div className="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-card shadow-xl">
        {loading ? (
          <LoadingState label="A carregar oferta..." className="p-6" />
        ) : error || !offer ? (
          <div className="flex flex-col items-center gap-4 px-6 py-16 text-center">
            <span className="flex h-14 w-14 items-center justify-center rounded-full bg-red-light text-red">
              <Tag size={26} aria-hidden="true" />
            </span>
            <div className="space-y-1">
              <h2 className="font-display text-xl text-navy">
                Oferta indisponível
              </h2>
              <p className="text-sm text-muted">
                Esta oferta já não existe ou foi removida.
              </p>
            </div>
            <Link
              to="/offers"
              className="inline-flex items-center gap-1.5 rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-navy-light"
            >
              <ArrowLeft size={16} aria-hidden="true" /> Voltar às ofertas
            </Link>
          </div>
        ) : (
          <>
            <div className="relative bg-navy px-6 py-8 text-white">
              <button
                type="button"
                onClick={close}
                aria-label="Fechar"
                className="absolute right-4 top-4 rounded-full p-1 text-white/80 transition hover:bg-white/15 hover:text-white"
              >
                <X size={20} />
              </button>

              <div className="flex flex-wrap items-center gap-2 pr-8">
                <span className="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
                  <Tag size={14} /> {offer.category?.name}
                </span>
                <span className="inline-flex items-center gap-1.5 rounded-full bg-teal px-3 py-1 text-xs font-medium text-white">
                  <span className="h-1.5 w-1.5 rounded-full bg-teal-light" />
                  {status.label}
                </span>
              </div>
              <h1 className="mt-3 font-display text-3xl wrap-break-word">
                {offer.title}
              </h1>
              {offer.partner && (
                <p className="mt-2 inline-flex items-center gap-1.5 text-white/80">
                  <Building2 size={16} /> {offer.partner.companyName}
                </p>
              )}
            </div>

            <div className="space-y-6 px-6 py-6">
              {offer.description && (
                <p className="whitespace-pre-line wrap-break-word text-text">
                  {offer.description}
                </p>
              )}

              {(start || end) && (
                <p className="flex items-center gap-2 text-sm text-muted">
                  <Calendar size={16} />
                  {start && end
                    ? `De ${start} até ${end}`
                    : end
                      ? `Válida até ${end}`
                      : `A partir de ${start}`}
                </p>
              )}

              {hasContacts && (
                <div className="rounded-xl border border-border bg-surface p-4">
                  <h2 className="mb-3 inline-flex items-center gap-2 font-medium text-navy">
                    <Building2 size={16} /> {contact.companyName}
                  </h2>
                  <ul className="space-y-2 text-sm">
                    {contact.phone && (
                      <li>
                        <a
                          href={`tel:${contact.phone}`}
                          className="inline-flex items-center gap-2 text-text hover:text-navy"
                        >
                          <Phone size={16} className="text-muted" />{" "}
                          {contact.phone}
                        </a>
                      </li>
                    )}
                    {contact.website && (
                      <li>
                        <a
                          href={toHref(contact.website)}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="flex items-start gap-2 text-text hover:text-navy"
                        >
                          <Globe
                            size={16}
                            className="mt-0.5 shrink-0 text-muted"
                          />
                          <span className="min-w-0 break-all">
                            {contact.website}
                          </span>
                        </a>
                      </li>
                    )}
                  </ul>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </div>,
    document.body,
  );
}
