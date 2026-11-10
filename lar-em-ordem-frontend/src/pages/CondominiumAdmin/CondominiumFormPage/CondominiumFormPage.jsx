import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { ChevronLeft } from "lucide-react";
import { CondominiumForm } from "../../../components/condominium/CondominiumForm";
import { getCondominium } from "../../../services/api/condominiumService";

export default function CondominiumFormPage({ mode }) {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = mode === "edit";

  const [condominium, setCondominium] = useState(null);
  const [loading, setLoading] = useState(isEdit);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!isEdit) return;
    let active = true;
    getCondominium(id)
      .then((data) => active && setCondominium(data))
      .catch(() => active && setError("Não foi possível carregar o condomínio."))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [id, isEdit]);

  return (
    <div className="mx-auto flex max-w-2xl flex-col gap-6">
      <div className="flex items-center gap-3">
        <Link
          to="/admin/condominiums"
          aria-label="Voltar"
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-card text-muted shadow-sm transition hover:text-navy"
        >
          <ChevronLeft size={20} />
        </Link>
        <h1 className="font-display text-2xl text-navy">
          {isEdit ? "Editar condomínio" : "Novo condomínio"}
        </h1>
      </div>

      {error && <p className="text-sm text-red">{error}</p>}

      {loading ? (
        <p className="text-sm text-muted">A carregar...</p>
      ) : (
        (!isEdit || condominium) && (
          <div className="rounded-2xl bg-card p-6 shadow-sm">
            <CondominiumForm
              condominium={condominium}
              onSaved={() => navigate("/admin/condominiums")}
            />
          </div>
        )
      )}
    </div>
  );
}