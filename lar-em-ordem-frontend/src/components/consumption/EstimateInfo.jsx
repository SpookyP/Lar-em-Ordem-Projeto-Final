import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { Info } from "lucide-react";
import { getServiceTheme } from "./benchmarkShared";

export function EstimateInfo({ entries, general = false }) {
  const [isOpen, setIsOpen] = useState(false);
  const [position, setPosition] = useState(null);

  const buttonRef = useRef(null);
  const popupRef = useRef(null);

  useEffect(() => {
    if (!isOpen) return undefined;

    const updatePosition = () => {
      const anchor = buttonRef.current?.getBoundingClientRect();

      if (!anchor) return;

      const popupWidth = Math.min(340, window.innerWidth - 24);
      const popupHeight = popupRef.current?.offsetHeight ?? 100;

      const left = Math.max(
        12,
        Math.min(anchor.left, window.innerWidth - popupWidth - 12),
      );

      const below = anchor.bottom + 8;

      const top =
        below + popupHeight <= window.innerHeight - 12
          ? below
          : Math.max(12, anchor.top - popupHeight - 8);

      setPosition({
        top,
        left,
        width: popupWidth,
      });
    };

    const frame = window.requestAnimationFrame(updatePosition);

    const handleOutsideClick = (event) => {
      const target = event.target;

      if (
        buttonRef.current?.contains(target) ||
        popupRef.current?.contains(target)
      ) {
        return;
      }

      setIsOpen(false);
    };

    const handleKeyDown = (event) => {
      if (event.key === "Escape") {
        setIsOpen(false);
      }
    };

    document.addEventListener("pointerdown", handleOutsideClick);
    document.addEventListener("keydown", handleKeyDown);

    window.addEventListener("resize", updatePosition);
    window.addEventListener("scroll", updatePosition, true);

    return () => {
      window.cancelAnimationFrame(frame);
      document.removeEventListener("pointerdown", handleOutsideClick);
      document.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("resize", updatePosition);
      window.removeEventListener("scroll", updatePosition, true);
    };
  }, [isOpen]);

  if (!entries?.length) return null;

  const popup =
    isOpen && typeof document !== "undefined"
      ? createPortal(
          <div
            ref={popupRef}
            role="tooltip"
            className="fixed z-1000 max-h-[70vh] overflow-y-auto rounded-lg border border-slate-200 bg-white p-3 text-left shadow-lg"
            style={{
              top: position?.top ?? -1000,
              left: position?.left ?? -1000,
              width: position?.width ?? 340,
              visibility: position ? "visible" : "hidden",
            }}
          >
            <p className="text-base font-semibold text-slate-800">
              {general ? "Serviços com estimativas" : "Estimativas"}
            </p>

            {general ? (
              <div className="mt-2 space-y-2">
                {entries.map((entry, index) => {
                  const theme = getServiceTheme(
                    entry.option ?? { label: entry.label },
                  );
                  const Icon = theme.Icon;

                  return (
                    <div
                      key={`${entry.label}-${index}`}
                      className="flex items-center gap-2 text-base font-medium text-slate-700"
                    >
                      <Icon
                        size={18}
                        className={theme.iconClass}
                        aria-hidden="true"
                      />
                      <span>{entry.label}</span>
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="mt-2 space-y-1.5">
                {entries.flatMap((entry, entryIndex) =>
                  entry.details.map((detail, detailIndex) => (
                    <p
                      key={`${entry.label ?? "estimate"}-${entryIndex}-${detailIndex}`}
                      className="text-base leading-snug text-slate-600"
                    >
                      <strong className="font-semibold text-slate-800">
                        {detail.label}:
                      </strong>{" "}
                      {detail.text}
                    </p>
                  )),
                )}
              </div>
            )}

            <button
              type="button"
              onClick={() => setIsOpen(false)}
              className="mt-3 w-full rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200"
            >
              Fechar
            </button>
          </div>,
          document.body,
        )
      : null;

  return (
    <div className="inline-flex">
      <button
        ref={buttonRef}
        type="button"
        aria-label="Informação sobre estimativas"
        aria-expanded={isOpen}
        onClick={() => setIsOpen((current) => !current)}
        className="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1"
      >
        <Info size={19} strokeWidth={2} aria-hidden="true" />
      </button>

      {popup}
    </div>
  );
}
