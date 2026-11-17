import { useSyncExternalStore } from "react";
import { Download } from "lucide-react";

let installPrompt = null;
const listeners = new Set();

function notify() {
  listeners.forEach((listener) => listener());
}

function subscribe(listener) {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

window.addEventListener("beforeinstallprompt", (event) => {
  event.preventDefault();
  installPrompt = event;
  notify();
});

window.addEventListener("appinstalled", () => {
  installPrompt = null;
  notify();
});

const IS_IOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
const IS_INSTALLED =
  window.matchMedia("(display-mode: standalone)").matches || navigator.standalone === true;

export function InstallButton() {
  const prompt = useSyncExternalStore(subscribe, () => installPrompt);

  async function install() {
    await prompt.prompt();
    installPrompt = null;
    notify();
  }

  if (IS_INSTALLED) return null;

  if (prompt) {
    return (
      <button
        type="button"
        onClick={install}
        className="flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-5 py-3 text-sm font-medium text-navy transition hover:bg-surface"
      >
        <Download size={17} aria-hidden="true" />
        Instalar a aplicação
      </button>
    );
  }

  if (IS_IOS) {
    return (
      <p className="text-center text-xs text-muted">
        Para instalar no iPhone: no Safari, toque em Partilhar e depois em "Adicionar ao ecrã
        principal".
      </p>
    );
  }

  return null;
}