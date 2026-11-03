import { useState, useEffect } from "react";
import { AuthContext } from "./authContextObject";
import {
  login as loginService,
  logout as logoutService,
  getCurrentUser,
  register as registerService,
  registerSocial as registerSocialService,
} from "../services/api/authService";

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
    await logoutService();
    setUser(null);
    setActiveHouse(null);
    setActiveProfile(null);
    localStorage.removeItem("activeProfile");
    localStorage.removeItem("activeHouse");
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
    isAuthenticated: Boolean(user),
    loading,
    login,
    logout,
    register,
    registerSocial,
    refreshUser,
    hasRole,
    activeHouse,
    activeProfile,
    selectProfile,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
