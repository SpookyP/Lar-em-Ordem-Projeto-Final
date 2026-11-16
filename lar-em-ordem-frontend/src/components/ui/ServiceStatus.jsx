import { useEffect, useState } from "react";
import { getHealth } from "../../services/api/healthService";

const SERVICE_NAMES = { database: "base de dados", storage: "ficheiros" };

export function ServiceStatus() {
  const [status, setStatus] = useState({ state: "checking", down: [] });

  useEffect(() => {
    let active = true;
    getHealth()
      .then((services) => {
        if (!active) return;
        const down = Object.keys(SERVICE_NAMES).filter((name) => services[name] === false);
        setStatus({ state: down.length ? "partial" : "ok", down });
      })
      .catch(() => {
        if (active) setStatus({ state: "offline", down: [] });
      });
    return () => {
      active = false;
    };
  }, []);

  const view = {
    checking: { dot: "bg-border", text: "A verificar os serviços..." },
    ok: { dot: "bg-green", text: "Todos os serviços a funcionar." },
    partial: {
      dot: "bg-amber",
      text: `Com problemas: ${status.down.map((name) => SERVICE_NAMES[name]).join(" e ")}. Algumas funções podem falhar.`,
    },
    offline: {
      dot: "bg-red",
      text: "Não foi possível contactar o servidor. Tente novamente dentro de momentos.",
    },
  }[status.state];

  return (
    <p role="status" className="flex items-start gap-2 text-xs text-muted">
      <span aria-hidden="true" className={`mt-1 size-2 shrink-0 rounded-full ${view.dot}`} />
      {view.text}
    </p>
  );
}