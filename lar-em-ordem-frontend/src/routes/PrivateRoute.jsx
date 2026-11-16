import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";

export function PrivateRoute({ allowedRoles }) {
  const { isAuthenticated, loading, activeProfile, hasRole } = useAuth();

  if (loading) {
    return null;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  const isRestricted = allowedRoles && allowedRoles.length > 0;

  if (isRestricted) {
    if (!activeProfile || !hasRole(activeProfile.name)) return <Navigate to="/profiles" replace />;

    if (!allowedRoles.includes(activeProfile.name)) {
      return <Navigate to="/unauthorized" replace />;
    }
  }

  return <Outlet />;
}
