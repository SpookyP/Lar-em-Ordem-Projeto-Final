import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { exampleProfiles } from "../../data/exampleData";
import { ProfileCard } from "../../components/ui/ProfileCard";

export default function Profiles() {
  const navigate = useNavigate();

  const [openProfile, setOpenProfile] = useState(null);

  function handleProfileClick(profile) {
    if (!profile.hasHouses) {
      enter(profile, null);
      return;
    }
    setOpenProfile(profile.name);
  }

  function enter(profile, house) {
    console.log("Entrar como", profile.label, "| casa:", house?.label ?? "-");
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
          {exampleProfiles.map((profile) => (
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
