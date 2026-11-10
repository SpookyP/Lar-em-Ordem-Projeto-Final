import { Link } from "react-router-dom";
import { Checkbox } from "../form";

/**
 * Consents asked at sign-up: the privacy policy (required) and, for residents, sharing their
 * consumption for the comparisons (optional, never ticked by default).
 */
export function ConsentFields({ value, onChange, errors, askDataSharing }) {
  return (
    <div className="flex flex-col gap-3">
      <Checkbox
        required
        checked={value.accept_privacy}
        onChange={(checked) => onChange("accept_privacy", checked)}
        error={errors.accept_privacy}
        label={
          <>
            Li e aceito a{" "}
            {/* new tab, so the form keeps what was typed; stopPropagation so the click doesn't tick the box */}
            <Link
              to="/privacy"
              target="_blank"
              rel="noopener"
              onClick={(e) => e.stopPropagation()}
              className="font-medium text-navy underline"
            >
              Política de Privacidade
            </Link>
            .
          </>
        }
      />
      {askDataSharing && (
        <Checkbox
          checked={value.data_sharing}
          onChange={(checked) => onChange("data_sharing", checked)}
          error={errors.data_sharing}
          label="Aceito partilhar os meus consumos, de forma anónima, para as comparações com casas semelhantes."
          hint="Opcional. Pode mudar isto a qualquer momento nas Definições."
        />
      )}
    </div>
  );
}