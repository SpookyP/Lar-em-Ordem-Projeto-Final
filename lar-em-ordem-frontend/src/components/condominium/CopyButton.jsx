import { useState } from "react";
import { Check, Copy } from "lucide-react";

/** Copies a code to the clipboard and says "Copiado" for two seconds. */
export function CopyButton({ text }) {
  const [copied, setCopied] = useState(false);

  async function copy() {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // clipboard blocked (http or browser setting): the code is still visible to copy by hand
    }
  }

  return (
    <button
      type="button"
      onClick={copy}
      className="flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-sm text-muted transition hover:text-navy"
    >
      {copied ? <Check size={15} /> : <Copy size={15} />}
      {copied ? "Copiado" : "Copiar"}
    </button>
  );
}