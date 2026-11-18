import { Navigate, Outlet } from "react-router-dom";
import { useHouses } from "../hooks/useHouses";
import { useAuth } from "../hooks/useAuth";

export function RequireHouse() {
  const { count, loading } = useHouses();
  const { activeHouse } = useAuth();

  if (loading) return null;
  if (count === 0) return <Navigate to="/welcome" replace />;
  if (!activeHouse) return <Navigate to="/profiles" replace />;

  return <Outlet />;
}
