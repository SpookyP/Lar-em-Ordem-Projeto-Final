import { Home, Wrench, ChevronRight } from "lucide-react";
import { HouseList } from "./HouseList";

function getProfileIcon(name) {
  if (name === "service_provider") return Wrench;
  return Home;
}

export function ProfileCard({ profile, isOpen, onProfileClick, onEnter }) {
  const Icon = getProfileIcon(profile.name);

  return (
    <div className="rounded-2xl bg-card border border-border overflow-hidden">
      <button
        onClick={() => onProfileClick(profile)}
        className="w-full flex items-center gap-4 p-4 text-left transition hover:bg-surface"
      >
        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy text-white">
          <Icon size={22} />
        </div>
        <p className="flex-1 font-medium text-text">{profile.label}</p>
        <ChevronRight size={20} className="text-muted" />
      </button>

      {isOpen && <HouseList onEnter={onEnter} />}
    </div>
  );
}
