import { Link } from "react-router-dom";
import {
  Clock,
  Database,
  Info,
  Lock,
  Scale,
  Share2,
  Target,
  ToggleRight,
  Trash2,
} from "lucide-react";
import { useAuth } from "../../hooks/useAuth";

// keep in sync with PRIVACY_POLICY_VERSION in the API's .env: it is the version stored with each consent
const POLICY_VERSION = "2026-11";

// TODO (team): the real address that answers privacy requests
const CONTACT_EMAIL = "privacidade@laremordem.pt";

const HIGHLIGHTS = [
  { icon: Lock, text: "Não vendemos os seus dados nem os usamos para publicidade." },
  { icon: ToggleRight, text: "Os seus consumos só entram nas comparações se disser que sim." },
  { icon: Trash2, text: "Pode apagar a conta e os seus dados quando quiser." },
];

// Draft text, written from what the platform does: review it as a team.
const SECTIONS = [
  {
    icon: Info,
    title: "Quem somos",
    body: [
      "O Lar em Ordem é uma plataforma para gerir a casa: faturas e consumos, documentos, pedidos de assistência a prestadores de serviços, ofertas de parceiros e a ligação ao condomínio.",
      `Para qualquer questão sobre os seus dados, contacte-nos através de ${CONTACT_EMAIL}.`,
    ],
  },
  {
    icon: Database,
    title: "Que dados recolhemos",
    list: [
      "Conta: nome, email e palavra-passe (guardada cifrada). Se entrar com Google ou Microsoft, recebemos o nome e o email dessa conta.",
      "Perfis: NIF e, para prestadores e parceiros, nome da empresa, telefone, email de contacto, website e zonas onde trabalham.",
      "Habitações: morada, tipo e tipologia da casa.",
      "Faturas e consumos que carrega, e os respetivos ficheiros PDF.",
      "Documentos guardados no cofre da habitação.",
      "Pedidos de assistência, com a descrição e as fotos que juntar.",
      "As suas respostas a esta política e à partilha de dados, com a data e a versão da política.",
    ],
  },
  {
    icon: Target,
    title: "Para que os usamos",
    list: [
      "Prestar o serviço que pede: guardar as suas faturas e documentos, mostrar os seus consumos e enviar os seus pedidos aos prestadores da sua zona (execução do contrato).",
      "Comparar os seus consumos com casas semelhantes. Só usamos os consumos de quem aceita partilhá-los, sempre agrupados e sem identificar ninguém (consentimento, que pode retirar a qualquer momento).",
      "Manter a plataforma segura: limitar tentativas de acesso e proteger as contas (interesse legítimo).",
    ],
  },
  {
    icon: Share2,
    title: "Com quem partilhamos",
    list: [
      "Prestadores de serviços: veem os pedidos de assistência da zona onde trabalham.",
      "Cloudflare: guarda os ficheiros (faturas, documentos e fotos).",
      "Google e Microsoft: apenas se escolher entrar com essas contas.",
      "O serviço de envio de email, para confirmar a conta e recuperar a palavra-passe.",
    ],
  },
  {
    icon: Clock,
    title: "Durante quanto tempo",
    body: [
      "Guardamos os dados enquanto a conta existir. Ao apagar a conta, os dados pessoais, as habitações, as faturas, os documentos e os ficheiros são apagados.",
      "Os pedidos de assistência com uma proposta aceite ficam no histórico do prestador, mas sem o seu nome nem a sua descrição.",
    ],
  },
  {
    icon: Scale,
    title: "Os seus direitos",
    list: [
      "Consultar e corrigir os seus dados nas Definições.",
      "Retirar a autorização para partilhar consumos, nas Definições, em Privacidade.",
      "Apagar a conta, nas Definições, em Apagar conta.",
      `Pedir uma cópia dos seus dados ou fazer qualquer outro pedido, através de ${CONTACT_EMAIL}. Respondemos no prazo de um mês.`,
      "Apresentar reclamação à Comissão Nacional de Proteção de Dados (CNPD).",
    ],
    settingsLink: true,
  },
];

export default function Privacy() {
  const { isAuthenticated } = useAuth();

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="font-display text-3xl text-navy">Política de Privacidade</h1>
        <p className="text-sm text-muted">Versão {POLICY_VERSION}</p>
      </header>

      <section
        aria-label="Em resumo"
        className="grid gap-4 rounded-2xl bg-navy p-6 text-white sm:grid-cols-3"
      >
        {HIGHLIGHTS.map(({ icon: Icon, text }) => (
          <div key={text} className="flex items-start gap-3">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10">
              <Icon size={18} aria-hidden="true" />
            </span>
            <p className="text-sm leading-relaxed">{text}</p>
          </div>
        ))}
      </section>

      <div className="grid gap-6 md:grid-cols-2">
        {SECTIONS.map(({ icon: Icon, title, body, list, settingsLink }) => (
          <section
            key={title}
            className="flex flex-col gap-3 rounded-2xl border border-border bg-card p-6"
          >
            <div className="flex items-center gap-3">
              <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-navy/10 text-navy">
                <Icon size={18} aria-hidden="true" />
              </span>
              <h2 className="text-lg font-semibold text-navy">{title}</h2>
            </div>

            {body?.map((paragraph) => (
              <p key={paragraph} className="text-sm leading-relaxed text-text">
                {paragraph}
              </p>
            ))}

            {list && (
              <ul className="flex list-disc flex-col gap-2 pl-5 text-sm leading-relaxed text-text">
                {list.map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            )}

            {settingsLink && isAuthenticated && (
              <Link
                to="/settings"
                className="mt-auto self-start rounded-xl bg-navy px-4 py-2.5 text-sm font-medium text-white transition hover:bg-navy-dark"
              >
                Abrir as Definições
              </Link>
            )}
          </section>
        ))}
      </div>
    </div>
  );
}