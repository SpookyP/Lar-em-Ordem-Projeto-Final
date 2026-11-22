import { useEffect, useState } from "react";
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

const EXAMPLE_BARS = ["h-20", "h-14", "h-16", "h-12", "h-10", "h-16"];
const SLIDES = ["O que pode fazer", "Exemplo"];
const SLIDE_MS = 7000;

const slideClass = (active) =>
  `col-start-1 row-start-1 transition-opacity duration-700 ${
    active ? "opacity-100" : "pointer-events-none opacity-0"
  }`;

export function AuthSplit({ children }) {
  const [slide, setSlide] = useState(0);

  useEffect(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const timer = setInterval(
      () => setSlide((current) => (current + 1) % SLIDES.length),
      SLIDE_MS,
    );
    return () => clearInterval(timer);
  }, [slide]);

  return (
    <div className="grid flex-1 lg:min-h-0 lg:grid-cols-2">
      <div className="flex px-6 py-6 scrollbar-thin lg:min-h-0 lg:overflow-y-auto">
        <div className="m-auto w-full max-w-md">{children}</div>
      </div>

      <aside className="relative hidden min-h-0 overflow-hidden bg-navy px-12 py-8 text-white lg:flex lg:flex-col lg:justify-center">
        <House
          size={420}
          strokeWidth={0.4}
          aria-hidden="true"
          className="pointer-events-none absolute -right-24 -top-10 text-white/10"
        />

        <div className="relative flex max-w-xl flex-col gap-4">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal-light">
            Sobre o projeto
          </p>
          <h2 className="font-display text-4xl leading-tight 2xl:text-5xl">
            A sua casa, <em className="text-orange">em ordem.</em>
          </h2>
          <p className="leading-relaxed text-white/80">
            O Lar em Ordem junta moradores, condomínios, prestadores de serviços
            e parceiros numa só plataforma, para que cuidar de uma casa deixe de
            ser uma pilha de papéis e faturas.
          </p>

          <div className="mt-2 grid items-center">
            <ul
              aria-hidden={slide !== 0}
              className={`grid gap-x-8 gap-y-5 sm:grid-cols-2 ${slideClass(slide === 0)}`}
            >
              {FEATURES.map(({ icon: Icon, title, text }) => (
                <li key={title} className="flex items-start gap-3.5">
                  <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10">
                    <Icon size={18} aria-hidden="true" />
                  </span>
                  <div>
                    <h3 className="font-semibold">{title}</h3>
                    <p className="mt-1 text-sm leading-relaxed text-white/70">
                      {text}
                    </p>
                  </div>
                </li>
              ))}
            </ul>

            <div
              aria-hidden="true"
              className={`relative h-56 max-w-md ${slideClass(slide === 1)}`}
            >
              <div className="absolute left-0 top-0 w-80 rounded-2xl bg-card p-6 text-text shadow-xl">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-muted">
                      Eletricidade
                    </p>
                    <p className="mt-1 font-display text-2xl text-navy">
                      54 kWh
                    </p>
                  </div>
                  <span className="rounded-full bg-green-light px-2.5 py-1 text-[11px] font-semibold text-green">
                    −12% vs. mês anterior
                  </span>
                </div>
                <div className="mt-5 flex items-end gap-3">
                  {EXAMPLE_BARS.map((height, index) => (
                    <span
                      key={index}
                      className={`flex-1 rounded-lg ${height} ${
                        index === EXAMPLE_BARS.length - 1
                          ? "bg-teal"
                          : "bg-border"
                      }`}
                    />
                  ))}
                </div>
              </div>

              <div className="absolute bottom-0 right-0 flex items-center gap-3.5 rounded-2xl bg-card px-5 py-4 text-text shadow-xl">
                <span className="flex size-10 items-center justify-center rounded-xl bg-orange-light text-orange">
                  <Wrench size={18} />
                </span>
                <div>
                  <p className="text-sm font-semibold">Fuga na cozinha</p>
                  <p className="text-xs text-muted">3 propostas recebidas</p>
                </div>
              </div>
            </div>
          </div>

          <div className="mt-2 flex gap-2">
            {SLIDES.map((label, index) => (
              <button
                key={label}
                type="button"
                onClick={() => setSlide(index)}
                aria-label={label}
                aria-pressed={slide === index}
                className={`h-1.5 rounded-full transition-all ${
                  slide === index
                    ? "w-8 bg-white"
                    : "w-4 bg-white/30 hover:bg-white/50"
                }`}
              />
            ))}
          </div>
        </div>
      </aside>
    </div>
  );
}
