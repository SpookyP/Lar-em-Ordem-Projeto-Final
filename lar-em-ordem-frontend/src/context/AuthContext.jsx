import { useState, useEffect } from "react";
import { AuthContext } from "./authContextObject";
import {
  login as loginService,
  logout as logoutService,
  getCurrentUser,
  register as registerService,
  registerSocial as registerSocialService,
} from "../services/api/authService";
import { getPropertyCondominium } from "../services/api/condominiumService";

//Ler um objeto guardado no LocalStorage
function readStored(key) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

//Provider vai distribuir a info da autenticacao
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeProfile, setActiveProfile] = useState(() =>
    readStored("activeProfile"),
  );
  const [activeHouse, setActiveHouse] = useState(() =>
    readStored("activeHouse"),
  );

  // the condominium of the active home: undefined while loading, null when the home has none.
  // The answer is kept with the home it belongs to, so a refresh keeps showing the old one.
  const [condominiumResult, setCondominiumResult] = useState({
    houseId: null,
    data: null,
  });
  const [condominiumVersion, setCondominiumVersion] = useState(0);
  const activeHouseId = activeHouse?.id ?? null;
  const isAuthenticated = Boolean(user);

  useEffect(() => {
    if (!isAuthenticated || !activeHouseId) return;
    let active = true;
    getPropertyCondominium(activeHouseId)
      .then(
        (data) =>
          active && setCondominiumResult({ houseId: activeHouseId, data }),
      )
      // without an answer, hide the condominium instead of breaking the menu
      .catch(
        () =>
          active &&
          setCondominiumResult({ houseId: activeHouseId, data: null }),
      );
    return () => {
      active = false;
    };
  }, [isAuthenticated, activeHouseId, condominiumVersion]);

  const activeCondominium = !activeHouseId
    ? null
    : condominiumResult.houseId === activeHouseId
      ? condominiumResult.data
      : undefined;

  // after joining or leaving: ask the API again (the menu and the pages follow)
  function refreshCondominium() {
    setCondominiumVersion((v) => v + 1);
  }

  useEffect(() => {
    async function checkSession() {
      try {
        const currentUser = await getCurrentUser();
        setUser(currentUser);
      } catch {
        setUser(null);
      } finally {
        setLoading(false);
      }
    }
    checkSession();
  }, []);

  async function login(email, password) {
    const user = await loginService(email, password);
    setUser(user);
    return user;
  }

  async function logout() {
    try {
      await logoutService();
    } finally {
      setUser(null);
      setActiveHouse(null);
      setActiveProfile(null);
      setCondominiumResult({ houseId: null, data: null });
      localStorage.removeItem("activeProfile");
      localStorage.removeItem("activeHouse");
    }
  }

  async function register(data) {
    const response = await registerService(data);
    if (response.status === 202) return { pending: true };
    const user = await getCurrentUser();
    setUser(user);
    return { pending: false, user };
  }

  async function registerSocial(data) {
    const response = await registerSocialService(data);
    if (response.status === 202) return { pending: true };
    const user = await getCurrentUser();
    setUser(user);
    return { pending: false, user };
  }

  function patchUser(fields) {
    setUser((current) => (current ? { ...current, ...fields } : current));
  }

  async function refreshUser() {
    const currentUser = await getCurrentUser();
    setUser(currentUser);
    return currentUser;
  }

  function selectProfile(profile, house) {
    setActiveProfile(profile);
    setActiveHouse(house);
    localStorage.setItem("activeProfile", JSON.stringify(profile));
    localStorage.setItem("activeHouse", JSON.stringify(house));
  }

  function hasRole(roleName) {
    if (!user?.roles) return false;
    return user.roles.includes(roleName);
  }

  const value = {
    user,
    isAuthenticated,
    loading,
    login,
    logout,
    register,
    registerSocial,
    refreshUser,
    patchUser,
    hasRole,
    activeHouse,
    activeProfile,
    selectProfile,
    activeCondominium,
    refreshCondominium,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
