import {
  Clock,
  FileText,
  Handshake,
  House,
  Info,
  KeyRound,
  Scale,
  ShieldCheck,
  Trash2,
  User,
  Users,
  Wrench,
} from "lucide-react";

export const SITE_PAGES = {
  about: {
    title: "Sobre o Lar em Ordem",
    intro: "Uma plataforma para ter a casa organizada num só sítio.",
    sections: [
      {
        icon: Info,
        title: "O que é",
        body: [
          "O Lar em Ordem junta a gestão da casa numa única aplicação: faturas e consumos, documentos, pedidos de assistência, ofertas de parceiros e a ligação ao condomínio.",
        ],
      },
      {
        icon: Users,
        title: "Para quem",
        list: [
          "Moradores, que gerem as suas casas e os seus consumos.",
          "Prestadores de serviços, que respondem a pedidos de assistência.",
          "Parceiros, que publicam ofertas.",
          "Administradores de condomínio, que publicam avisos e documentos.",
        ],
      },
      {
        icon: House,
        title: "O projeto",
        body: [
          "O Lar em Ordem é um projeto final de curso, desenvolvido por uma equipa de cinco formandos.",
        ],
      },
    ],
  },
  help: {
    title: "Ajuda",
    intro: "Respostas rápidas às dúvidas mais comuns.",
    sections: [
      {
        icon: House,
        title: "Como adiciono uma casa?",
        body: [
          "Em Definições de conta, escolha Adicionar habitação e preencha a morada e o tipo de casa.",
        ],
      },
      {
        icon: FileText,
        title: "Como carrego uma fatura?",
        body: [
          "Em Faturas, carregue o PDF da fatura. Os consumos são lidos do ficheiro e ficam disponíveis em Consumos.",
        ],
      },
      {
        icon: Wrench,
        title: "Como peço assistência?",
        body: [
          "Em Assistência, descreva o problema e junte até duas fotos. Os prestadores da sua zona enviam propostas e escolhe a que preferir.",
        ],
      },
      {
        icon: Handshake,
        title: "Como entro no meu condomínio?",
        body: [
          "Peça o código de convite ao administrador e introduza-o em Definições de conta, na opção Condomínio.",
        ],
      },
      {
        icon: KeyRound,
        title: "Esqueci-me da password",
        body: [
          "No ecrã de login, escolha Esqueceu-se da password? e siga o link que enviamos para o seu email.",
        ],
      },
      {
        icon: Clock,
        title: "A sessão terminou sozinha",
        body: [
          "Por segurança, a sessão termina depois de 30 minutos sem atividade. Basta iniciar sessão outra vez.",
        ],
      },
    ],
  },
  terms: {
    title: "Termos de Utilização",
    intro: "As regras para usar o Lar em Ordem.",
    sections: [
      {
        icon: User,
        title: "A sua conta",
        list: [
          "Os dados que indica devem ser verdadeiros e estar atualizados.",
          "É responsável por manter a sua password em segredo.",
          "Cada conta é pessoal e não deve ser partilhada.",
        ],
      },
      {
        icon: ShieldCheck,
        title: "Utilização da plataforma",
        list: [
          "Só pode carregar faturas, documentos e fotos que lhe digam respeito.",
          "Não é permitido usar a plataforma para fins ilegais ou para prejudicar outros utilizadores.",
          "Podemos suspender contas que não cumpram estas regras.",
        ],
      },
      {
        icon: Wrench,
        title: "Prestadores e parceiros",
        body: [
          "O Lar em Ordem põe moradores em contacto com prestadores de serviços e parceiros. O serviço prestado e as ofertas publicadas são da responsabilidade de quem os presta ou publica.",
        ],
      },
      {
        icon: Scale,
        title: "Os seus dados",
        body: [
          "O tratamento dos seus dados pessoais está descrito na Política de Privacidade.",
        ],
        link: { to: "/privacy", label: "Ler a Política de Privacidade" },
      },
      {
        icon: Trash2,
        title: "Terminar a utilização",
        body: [
          "Pode apagar a sua conta quando quiser, em Definições de conta. Ao fazê-lo, os seus dados são apagados como descrito na Política de Privacidade.",
        ],
      },
    ],
  },
};