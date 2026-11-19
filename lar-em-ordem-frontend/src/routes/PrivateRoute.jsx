import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../hooks/useAuth";

export function PrivateRoute({ allowedRoles }) {
  const { isAuthenticated, loading, activeProfile, hasRole } = useAuth();
  const location = useLocation();

  if (loading) {
    return null;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  const isRestricted = allowedRoles && allowedRoles.length > 0;

  if (isRestricted) {
    if (!activeProfile || !hasRole(activeProfile.name))
      return <Navigate to="/profiles" replace />;

    if (!allowedRoles.includes(activeProfile.name)) {
      return <Navigate to="/unauthorized" replace />;
    }
  }

  return <Outlet />;
}
