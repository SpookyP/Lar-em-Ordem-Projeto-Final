import { useState } from "react";
import { Link } from "react-router-dom";
import { Building, MapPin } from "lucide-react";
import { useAuth } from "../../hooks/useAuth";
import { JoinCondominium } from "./JoinCondominium";
import { LeaveCondominium } from "./LeaveCondominium";
import { addressLine } from "../../utils/format";

/** Settings section: join the active home's condominium with the invite code, or leave it. */
export function CondominiumSettings() {
  const { activeHouse, activeCondominium, refreshCondominium } = useAuth();
  const [message, setMessage] = useState("");

  if (!activeHouse) {
    return <p className="text-sm text-muted">Escolhe uma casa para gerir o condomínio.</p>;
  }

  return (
    <div className="flex flex-col gap-5">
      <div>
        <h2 className="font-display text-2xl text-navy">Condomínio</h2>
        <p className="mt-1 text-sm text-muted">
          Liga a casa ativa ao condomínio do prédio para receberes os avisos e veres os documentos,
          como atas e regulamento.
        </p>
      </div>

      {message && (
        <p role="status" className="rounded-lg bg-green-light px-3 py-2 text-sm text-green">
          {message}
        </p>
      )}

      {activeCondominium === undefined ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : activeCondominium === null ? (
        <JoinCondominium
          propertyId={activeHouse.id}
          onJoined={(joined) => {
            setMessage(`A tua casa está agora ligada a ${joined.name}.`);
            // the menu shows "Condomínio" once the full condominium comes back
            refreshCondominium();
          }}
        />
      ) : (
        <div className="flex flex-col gap-4 rounded-2xl border border-border p-5">
          <div className="flex items-start gap-4">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-navy/10 text-navy">
              <Building size={22} aria-hidden="true" />
            </span>
            <div className="min-w-0">
              <p className="font-medium text-text">{activeCondominium.name}</p>
              <p className="mt-1 flex items-start gap-1.5 text-sm text-muted">
                <MapPin size={15} className="mt-0.5 shrink-0" aria-hidden="true" />
                {addressLine(activeCondominium.address)}
              </p>
              <Link to="/condominio" className="mt-2 inline-block text-sm text-navy hover:underline">
                Ver página do condomínio
              </Link>
            </div>
          </div>
          <div className="border-t border-border pt-4">
            <LeaveCondominium
              condominium={activeCondominium}
              propertyId={activeHouse.id}
              onLeft={() => {
                setMessage(`A tua casa saiu de ${activeCondominium.name}.`);
                refreshCondominium();
              }}
            />
          </div>
        </div>
      )}
    </div>
  );
}