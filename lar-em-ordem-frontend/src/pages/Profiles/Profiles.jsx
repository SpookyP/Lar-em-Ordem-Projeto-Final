import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { ProfileCard } from "../../components/ui/ProfileCard";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";
import { getProperties } from "../../services/api/propertyService";

export default function Profiles() {
  const navigate = useNavigate();
  const { user, selectProfile } = useAuth();
  const [openProfile, setOpenProfile] = useState(null);
  const [deciding, setDeciding] = useState(true);

  //construir os perfis de acordo com as roles do utilizador autenticado
  const profiles = (user?.roles ?? []).map((roleName) => ({
    name: roleName,
    label: ROLE_INFO[roleName]?.label ?? roleName,
    hasHouses: ROLE_INFO[roleName]?.hasHouses ?? false,
  }));

  function handleProfileClick(profile) {
    if (!profile.hasHouses) {
      enter(profile, null);
      return;
    }
    setOpenProfile(profile.name);
  }

  function enter(profile, house) {
    selectProfile(profile, house);
    navigate("/dashboard");
  }

  //Decidir se há perfis ou casas para escolher, se não houver, não passa pelo /perfis
  useEffect(() => {
    if (!user) return;

    async function decideEntry() {
      //vários perfis
      if (profiles.length !== 1) {
        setDeciding(false);
        return;
      }

      const unique = profiles[0];

      //Perfil que n tem casas (prestador/partner)
      if (!unique.hasHouses) {
        enter(unique, null);
        return;
      }

      //meta.total
      const { houses, total } = await getProperties(1);

      //1 casa
      if (total === 1) {
        enter(unique, houses[0]);
        return;
      }

      //0 casas
      //TODO: encaminhar p pag de inserir habitaçao
      if (total === 0) {
        setDeciding(false);
        return;
      }

      //Varias casas
      setOpenProfile(unique.name);
      setDeciding(false);
    }

    decideEntry();
  }, [user]);

  if (deciding) {
    return (
      <div className="min-h-screen bg-surface flex items-center justify-center">
        <p className="text-muted">A carregar...</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-surface flex flex-col items-center justify-center p-6">
      <div className="w-full max-w-md">
        <h1 className="font-display text-3xl text-navy text-center">
          Olá de novo
        </h1>
        <p className="text-muted text-center mb-8">
          Com que perfil quer entrar?
        </p>

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
      </div>
    </div>
  );
}
