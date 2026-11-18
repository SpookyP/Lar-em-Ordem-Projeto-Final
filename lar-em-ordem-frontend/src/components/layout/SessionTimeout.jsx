import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../hooks/useAuth";
import { ConfirmDialog } from "../ui/ConfirmDialog";

const IDLE_LIMIT_MINUTES = 30;
const IDLE_LIMIT_MS = IDLE_LIMIT_MINUTES * 60 * 1000;
const WARNING_MS = 60 * 1000;
const CHECK_EVERY_MS = 1000;
const SAVE_EVERY_MS = 5000;
const ACTIVITY_KEY = "lastActivity";
const ACTIVITY_EVENTS = ["mousedown", "mousemove", "keydown", "scroll", "touchstart"];

function saveActivity() {
  localStorage.setItem(ACTIVITY_KEY, String(Date.now()));
}

export function SessionTimeout() {
  const { isAuthenticated, loading, logout } = useAuth();
  const navigate = useNavigate();
  const [status, setStatus] = useState("active");
  const logoutRef = useRef(logout);

  useEffect(() => {
    logoutRef.current = logout;
  });

  useEffect(() => {
    if (loading) return;

    if (!isAuthenticated) {
      localStorage.removeItem(ACTIVITY_KEY);
      return;
    }

    if (localStorage.getItem(ACTIVITY_KEY) === null) saveActivity();

    let lastSaved = 0;
    let warned = false;
    let ended = false;

    function end(nextStatus) {
      ended = true;
      setStatus(nextStatus);
      logoutRef.current().catch(() => {});
    }

    function check() {
      if (ended) return;

      const stored = localStorage.getItem(ACTIVITY_KEY);
      if (stored === null) {
        end("active");
        return;
      }

      const idle = Date.now() - Number(stored);
      if (idle >= IDLE_LIMIT_MS) {
        end("expired");
        return;
      }

      warned = idle >= IDLE_LIMIT_MS - WARNING_MS;
      setStatus(warned ? "warning" : "active");
    }

    function onActivity() {
      if (warned || ended) return;
      const now = Date.now();
      if (now - lastSaved < SAVE_EVERY_MS) return;
      lastSaved = now;
      saveActivity();
    }

    function onVisible() {
      if (document.visibilityState === "visible") check();
    }

    const timer = setInterval(check, CHECK_EVERY_MS);
    ACTIVITY_EVENTS.forEach((name) =>
      window.addEventListener(name, onActivity, { passive: true }),
    );
    document.addEventListener("visibilitychange", onVisible);

    return () => {
      clearInterval(timer);
      ACTIVITY_EVENTS.forEach((name) =>
        window.removeEventListener(name, onActivity),
      );
      document.removeEventListener("visibilitychange", onVisible);
    };
  }, [isAuthenticated, loading]);

  function stay() {
    saveActivity();
    setStatus("active");
  }

  function goToLogin() {
    setStatus("active");
    navigate("/login");
  }

  if (status === "expired") {
    return (
      <ConfirmDialog
        title="Sessão expirada"
        message={`Esteve ${IDLE_LIMIT_MINUTES} minutos sem atividade. Por segurança, terminámos a sessão.`}
        confirmLabel="Iniciar sessão"
        cancelLabel={null}
        onConfirm={goToLogin}
        onCancel={goToLogin}
      />
    );
  }

  if (status === "warning" && isAuthenticated) {
    return (
      <ConfirmDialog
        title="Ainda está aí?"
        message="Por segurança, a sessão termina dentro de 1 minuto por inatividade."
        confirmLabel="Continuar sessão"
        cancelLabel={null}
        onConfirm={stay}
        onCancel={stay}
      />
    );
  }

  return null;
}