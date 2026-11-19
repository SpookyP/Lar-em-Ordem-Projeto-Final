import { useState, useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { ProfileCard } from "../../components/ui/ProfileCard";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";
import { getPropertiesPage } from "../../services/api/propertyService";
import { HouseList } from "../../components/ui/HouseList";
import { AuthSplit } from "../../components/layout/AuthSplit";
import { Footer } from "../../components/layout/Footer";
import { useGoBack } from "../../hooks/useGoBack";

function Shell({ onLogout, children }) {
  return (
    <div className="flex min-h-dvh flex-col bg-card lg:h-dvh">
      <header className="flex items-center justify-between gap-4 border-b border-border bg-card px-6 py-3 lg:px-10">
        <div className="flex items-center gap-2.5">
          <img src="/logo.svg" alt="" className="size-9" />
          <span className="font-display text-xl text-navy">Lar em Ordem</span>
        </div>
        <button
          type="button"
          onClick={onLogout}
          className="rounded-full bg-surface px-4 py-2 text-sm font-medium text-navy transition hover:bg-border"
        >
          Terminar sessão
        </button>
      </header>
      <AuthSplit>{children}</AuthSplit>
      <Footer />
    </div>
  );
}

export default function Profiles() {
  const navigate = useNavigate();
  const { user, selectProfile, logout } = useAuth();
  const [openProfile, setOpenProfile] = useState(null);
  const [deciding, setDeciding] = useState(true);

  //construir os perfis de acordo com as roles do utilizador autenticado
  const profiles = (user?.roles ?? []).map((roleName) => ({
    name: roleName,
    label: ROLE_INFO[roleName]?.label ?? roleName,
    hasHouses: ROLE_INFO[roleName]?.hasHouses ?? false,
  }));

  const [searchParams] = useSearchParams();
  const preselectedName = searchParams.get("profile");
  const preselectedProfile = profiles.find((p) => p.name === preselectedName);

  const goBack = useGoBack("/profiles");

  function handleProfileClick(profile) {
    if (!profile.hasHouses) {
      enter(profile, null);
      return;
    }
    setOpenProfile(profile.name);
  }

  function enter(profile, house) {
    selectProfile(profile, house);
    navigate(ROLE_INFO[profile.name]?.home ?? "/dashboard");
  }

  async function handleLogout() {
    await logout();
    navigate("/login");
  }

  //Decidir, uma vez por utilizador, se há perfis ou casas para escolher, se não houver, não passa pelo /profiles
  /* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
  useEffect(() => {
    if (!user) return;

    if (preselectedProfile) {
      setDeciding(false);
      return;
    }

    async function decideEntry() {
      try {
        //vários perfis
        if (profiles.length !== 1) {
          setDeciding(false);
          return;
        }

        const unique = profiles[0];

        if (!unique.hasHouses) {
          enter(unique, null);
          return;
        }

        const { items: houses, total } = await getPropertiesPage({ page: 1 });

        if (total === 1) {
          enter(unique, houses[0]);
          return;
        }

        if (total === 0) {
          navigate("/welcome");
          return;
        }

        setOpenProfile(unique.name);
        setDeciding(false);
      } catch (error) {
        console.error(error);
        setDeciding(false);
      }
    }

    decideEntry();
  }, [user]);
  /* eslint-enable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */

  if (deciding) {
    return (
      <Shell onLogout={handleLogout}>
        <p className="text-muted">A carregar...</p>
      </Shell>
    );
  }

  return (
    <Shell onLogout={handleLogout}>
      <div className="w-full">
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-teal">
          {preselectedProfile ? "Gestor de casas" : "Olá de novo"}
        </p>
        <h1 className="mt-2 font-display text-4xl text-navy">
          {preselectedProfile ? "As minhas casas" : "Escolha o perfil"}
        </h1>
        <p className="mt-2 mb-7 text-sm text-muted">
          {preselectedProfile
            ? "Em que casa quer entrar?"
            : "Com que perfil quer entrar?"}
        </p>

        {preselectedProfile ? (
          <div className="overflow-hidden rounded-2xl border border-border bg-card">
            <HouseList onEnter={(house) => enter(preselectedProfile, house)} />
          </div>
        ) : (
          <div className="flex flex-col gap-3">
            {profiles.map((profile) => (
              <ProfileCard
                key={profile.name}
                profile={profile}
                isOpen={openProfile === profile.name}
                onProfileClick={handleProfileClick}
                onEnter={(house) => enter(profile, house)}
              />
            ))}
          </div>
        )}

        {preselectedProfile && (
          <button
            onClick={() => goBack()}
            className="mt-7 text-sm font-medium text-navy hover:underline"
          >
            ← Voltar
          </button>
        )}
      </div>
    </Shell>
  );
}
