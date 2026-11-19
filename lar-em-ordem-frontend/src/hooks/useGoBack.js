import { useCallback } from "react";
import { useNavigate, useLocation } from "react-router-dom";

//if the page was opened directly (no in-app history), fall back to `fallback`
export function useGoBack(fallback) {
  const navigate = useNavigate();
  const location = useLocation();
  return useCallback(() => {
    if (location.key !== "default") navigate(-1);
    else navigate(fallback);
  }, [navigate, location.key, fallback]);
}
