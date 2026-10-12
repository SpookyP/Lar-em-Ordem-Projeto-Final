import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { ProfileCard } from "../../components/ui/ProfileCard";
import { useAuth } from "../../hooks/useAuth";
import { ROLE_INFO } from "../../data/roleInfo";

export default function Profiles() {
  const navigate = useNavigate();
  const { user, selectProfile } = useAuth();
  const [openProfile, setOpenProfile] = useState(null);

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
