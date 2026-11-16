import { Building, FolderClosed, House, Wrench, Zap } from "lucide-react";

const FEATURES = [
  {
    icon: FolderClosed,
    title: "Cofre",
    text: "Os documentos da casa num só lugar, com alertas antes de a validade terminar.",
  },
  {
    icon: Zap,
    title: "Consumos",
    text: "Carregue as faturas e veja os consumos mês a mês.",
  },
  {
    icon: Wrench,
    title: "Assistência",
    text: "Descreva o problema e receba propostas de prestadores da sua zona.",
  },
  {
    icon: Building,
    title: "Condomínio",
    text: "Avisos e documentos do prédio partilhados com todos os condóminos.",
  },
];

export function AuthSplit({ children }) {
  return (
    <div className="grid flex-1 lg:grid-cols-2">
      <div className="flex items-center justify-center px-6 py-10">{children}</div>

      <aside className="relative hidden overflow-hidden bg-navy px-14 py-16 text-white lg:flex lg:flex-col lg:justify-center">
        <House
          size={420}
          strokeWidth={0.4}
          aria-hidden="true"
          className="pointer-events-none absolute -right-24 -top-10 text-white/10"
        />

        <div className="relative flex max-w-xl flex-col gap-6">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal-light">
            Sobre o projeto
          </p>
          <h2 className="font-display text-5xl leading-tight">
            A sua casa, <em className="text-orange">em ordem.</em>
          </h2>
          <p className="leading-relaxed text-white/80">
            O Lar em Ordem junta moradores, condomínios, prestadores de serviços e
            parceiros numa só plataforma, para que cuidar de uma casa deixe de ser uma
            pilha de papéis e faturas.
          </p>

          <ul className="mt-4 grid gap-x-8 gap-y-7 sm:grid-cols-2">
            {FEATURES.map(({ icon: Icon, title, text }) => (
              <li key={title} className="flex items-start gap-3.5">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10">
                  <Icon size={18} aria-hidden="true" />
                </span>
                <div>
                  <h3 className="font-semibold">{title}</h3>
                  <p className="mt-1 text-sm leading-relaxed text-white/70">{text}</p>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </aside>
    </div>
  );
}