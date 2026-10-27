import { X } from "lucide-react";
import { AccountActions } from "./AccountActions";

export function AccountDrawer({ open, onClose }) {
  if (!open) return null;

  return (
    <div className="md:hidden fixed inset-0 z-50">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />

      <div className="absolute bottom-0 left-0 right-0 rounded-t-2xl bg-navy p-4 pb-6 text-white">
        <div className="mb-4 flex items-center justify-between">
          <p className="font-display text-lg">Conta</p>
          <button
            onClick={onClose}
            aria-label="Fechar"
            className="text-white/70 hover:text-white"
          >
            <X size={20} />
          </button>
        </div>

        <AccountActions onAction={onClose} />
      </div>
    </div>
  );
}
