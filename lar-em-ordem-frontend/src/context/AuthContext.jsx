import { useState, useEffect } from "react";
import { AuthContext } from "./authContextObject";
import {
  login as loginService,
  logout as logoutService,
  getCurrentUser,
  register as registerService,
} from "../services/api/authService";

//Provider vai distribuir a info da autenticacao
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeProfile, setActiveProfile] = useState(null);
  const [activeHouse, setActiveHouse] = useState(null);

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
  }

  async function register(data) {
    await registerService(data);
    const user = await getCurrentUser();
    setUser(user);
    return user;
  }

  function selectProfile(profile, house) {
    setActiveProfile(profile);
    setActiveHouse(house);
  }

  function hasRole(roleName) {
    if (!user?.roles) return false;

    return user.roles.some((role) => role.name === roleName);
  }

  const value = {
    user,
    isAuthenticated: Boolean(user),
    loading,
    login,
    logout,
    register,
    hasRole,
    activeHouse,
    activeProfile,
    selectProfile,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
