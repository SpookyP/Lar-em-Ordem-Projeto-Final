import { useEffect, useState } from "react";
import { useAuth } from "./useAuth";
import { echo } from "../services/echo";
import {
  dismissNotice,
  getBellNotices,
  markAllNoticesRead,
  markNoticeRead,
  toNotice,
} from "../services/api/condominiumService";
import { isNoticeCurrent } from "../data/condominiumInfo";

/**
 * Condominium notices for the bell of the active home, kept up to date in real time
 * through Reverb (private channel properties.{id}, event .condominium.notice).
 * Homes without a condominium get an empty list and no subscription.
 */
export function useCondominiumNotices() {
  const { activeHouse, activeCondominium } = useAuth();
  const propertyId = activeHouse?.id ?? null;
  const enabled = Boolean(propertyId && activeCondominium);

  // the list is kept with the home it belongs to (same idea as usePagedList)
  const [result, setResult] = useState({ key: null, items: [] });
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!enabled) return;
    let active = true;
    const channelName = `properties.${propertyId}`;

    getBellNotices(propertyId)
      .then((items) => active && setResult({ key: propertyId, items }))
      .catch(() => active && setError("Não foi possível carregar os avisos do condomínio."));

    // a new notice goes to the top; an edited one moves to the top and is unread again
    const channel = echo.private(channelName);
    channel.listen(".condominium.notice", (payload) => {
      if (!active) return;
      const notice = toNotice({ ...payload, read: false });
      setResult((current) => ({
        key: propertyId,
        items: [notice, ...(current.key === propertyId ? current.items : []).filter((n) => n.id !== notice.id)],
      }));
    });

    return () => {
      active = false;
      channel.stopListening(".condominium.notice");
      echo.leave(channelName);
    };
  }, [enabled, propertyId]);

  // expired notices drop out without a reload
  const notices =
    enabled && result.key === propertyId ? result.items.filter((n) => isNoticeCurrent(n)) : [];

  function update(change) {
    setResult((current) => ({ ...current, items: change(current.items) }));
  }

  // optimistic: change the list first, put it back if the API refuses
  async function run(change, request, message) {
    const previous = result.items;
    setError(null);
    update(change);
    try {
      await request();
    } catch {
      update(() => previous);
      setError(message);
    }
  }

  return {
    notices,
    unreadCount: notices.filter((n) => !n.read).length,
    error,
    clearError: () => setError(null),
    markRead: (id) =>
      run(
        (items) => items.map((n) => (n.id === id ? { ...n, read: true } : n)),
        () => markNoticeRead(id),
        "Erro ao marcar o aviso como lido.",
      ),
    markAllRead: () =>
      run(
        (items) => items.map((n) => ({ ...n, read: true })),
        () => markAllNoticesRead(propertyId),
        "Erro ao marcar os avisos como lidos.",
      ),
    dismiss: (id) =>
      run(
        (items) => items.filter((n) => n.id !== id),
        () => dismissNotice(id),
        "Erro ao remover o aviso das notificações.",
      ),
  };
}