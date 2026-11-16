import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";
import { homePath } from "../data/roleInfo";

// Pages for visitors only (login, sign-up, password recovery).
// It does not wait for the session check: the page shows right away and,
// once a session is known, the user is sent to their home.
export function GuestRoute() {
  const { isAuthenticated, activeProfile } = useAuth();

  if (isAuthenticated) {
    return <Navigate to={homePath(activeProfile?.name)} replace />;
  }

  return <Outlet />;
}