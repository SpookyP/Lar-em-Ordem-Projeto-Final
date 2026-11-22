import { useState } from "react";
import { X } from "lucide-react";

const SNOOZE_OPTIONS = [
    { label: "15 minutos", minutes: 15 },
    { label: "1 dia", minutes: 1440 },
    { label: "7 dias", minutes: 10080 },
];

export function NotificationPanel({
                                      notifs,
                                      error,
                                      onClearError,
                                      onClose,
                                      onRead,
                                      onUnread,
                                      onSnooze,
                                      onReadAll,
                                      onAction,
                                      onDelete,
                                  }) {
    const visible = notifs.filter((n) => !n.snoozed);
    const unreadVisibleCount = visible.filter((n) => !n.read).length;
    const [openSnoozeId, setOpenSnoozeId] = useState(null);

    return (
        <>
            <div
                onClick={onClose}
                style={{ position: "fixed", inset: 0, zIndex: 190 }}
            />
            <div
                style={{
                    position: "absolute",
                    top: 46,
                    right: 0,
                    width: 340,
                    maxWidth: "calc(100vw - 32px)",
                    background: "#fff",
                    borderRadius: 14,
                    border: "1px solid #E2E8F0",
                    boxShadow: "0 14px 40px rgba(15,31,53,0.16)",
                    zIndex: 200,
                    overflow: "hidden",
                    animation: "slideDown 0.18s ease",
                }}
            >
                <div
                    style={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "space-between",
                        padding: "12px 14px",
                        borderBottom: "1px solid #F1F5F9",
                    }}
                >
                    <p
                        style={{
                            fontSize: 12.5,
                            fontWeight: 600,
                            color: "#1C3557",
                            margin: 0,
                        }}
                    >
                        Notificações{" "}
                        {unreadVisibleCount > 0 && (
                            <span style={{ color: "#94A3B8", fontWeight: 500 }}>
                · {unreadVisibleCount} novas
              </span>
                        )}
                    </p>
                    <button
                        onClick={onReadAll}
                        className="notif-inline-action"
                        style={{ color: "#0F766E", fontWeight: 600 }}
                    >
                        Marcar tudo como lido
                    </button>
                </div>

                {error && (
                    <div
                        style={{
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "space-between",
                            background: "#FEF2F2",
                            color: "#DC2626",
                            padding: "8px 14px",
                            fontSize: 11.5,
                            borderBottom: "1px solid #FECACA",
                        }}
                    >
                        <span>{error}</span>
                        {onClearError && (
                            <button
                                onClick={onClearError}
                                aria-label="Fechar erro"
                                style={{
                                    background: "none",
                                    border: "none",
                                    color: "#DC2626",
                                    cursor: "pointer",
                                    marginLeft: 8,
                                    display: "flex",
                                }}
                            >
                                <X size={14} strokeWidth={2.5} />
                            </button>
                        )}
                    </div>
                )}

                <div style={{ maxHeight: 340, overflowY: "auto" }}>
                    {visible.length === 0 && (
                        <p
                            style={{
                                fontSize: 12,
                                color: "#94A3B8",
                                textAlign: "center",
                                padding: "32px 16px",
                                margin: 0,
                            }}
                        >
                            Sem notificações por agora.
                        </p>
                    )}

                    {visible.map((n) => (
                        <div
                            key={n.id}
                            style={{
                                display: "flex",
                                gap: 11,
                                padding: "12px 14px",
                                borderBottom: "1px solid #F8FAFC",
                                background: n.read ? "#fff" : "#FCFDFE",
                                borderLeft: `3px solid ${n.read ? "transparent" : n.accent}`,
                            }}
                        >
                            <div
                                style={{
                                    width: 30,
                                    height: 30,
                                    borderRadius: 9,
                                    background: n.bg,
                                    color: n.accent,
                                    display: "flex",
                                    alignItems: "center",
                                    justifyContent: "center",
                                    flexShrink: 0,
                                }}
                            >
                                {n.icon}
                            </div>

                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div
                                    style={{ display: "flex", alignItems: "flex-start", gap: 8 }}
                                >
                                    <p
                                        style={{
                                            fontSize: 12.5,
                                            fontWeight: n.read ? 500 : 600,
                                            color: n.read ? "#64748B" : "#0F172A",
                                            margin: 0,
                                            flex: 1,
                                        }}
                                    >
                                        {n.title}
                                    </p>
                                    <span
                                        style={{
                                            fontSize: 10,
                                            color: "#CBD5E1",
                                            flexShrink: 0,
                                            marginTop: 2,
                                        }}
                                    >
                    {n.time}
                  </span>

                                    <button
                                        onClick={() => onDelete(n.id)}
                                        className="notif-delete-btn"
                                        title="Eliminar notificação"
                                    >
                                        <X size={14} strokeWidth={2.5} />
                                    </button>
                                </div>

                                <p
                                    style={{
                                        fontSize: 11,
                                        color: "#64748B",
                                        margin: "2px 0 0",
                                        lineHeight: 1.5,
                                    }}
                                >
                                    {n.body}
                                </p>

                                <div
                                    style={{
                                        display: "flex",
                                        gap: 12,
                                        marginTop: 8,
                                        flexWrap: "wrap",
                                    }}
                                >
                                    {n.action && (
                                        <button
                                            onClick={() => onAction(n)}
                                            className="notif-inline-action"
                                            style={{ color: "#1C3557", fontWeight: 600 }}
                                        >
                                            {n.action.label}
                                        </button>
                                    )}

                                    {n.read ? (
                                        n.canUnread !== false && (
                                            <button
                                                onClick={() => onUnread(n.id)}
                                                className="notif-inline-action"
                                                style={{ color: "#94A3B8", fontWeight: 500 }}
                                            >
                                                Marcar como não lido
                                            </button>
                                        )
                                    ) : (
                                        <button
                                            onClick={() => onRead(n.id)}
                                            className="notif-inline-action"
                                            style={{ color: "#0F766E", fontWeight: 600 }}
                                        >
                                            Marcar como lido
                                        </button>
                                    )}

                                    {!n.read && n.canSnooze !== false && (
                                        <div style={{ position: "relative" }}>
                                            <button
                                                onClick={() =>
                                                    setOpenSnoozeId(openSnoozeId === n.id ? null : n.id)
                                                }
                                                className="notif-inline-action"
                                                style={{ color: "#94A3B8", fontWeight: 500 }}
                                            >
                                                Avisar mais tarde
                                            </button>

                                            {openSnoozeId === n.id && (
                                                <div className="notif-snooze-menu">
                                                    {SNOOZE_OPTIONS.map((opt) => (
                                                        <button
                                                            key={opt.minutes}
                                                            onClick={() => {
                                                                onSnooze(n.id, opt.minutes);
                                                                setOpenSnoozeId(null);
                                                            }}
                                                            className="notif-snooze-item"
                                                        >
                                                            {opt.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <style>{`
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px) } to { opacity:1; transform:translateY(0) } }
        .notif-inline-action { padding: 0; background: none; border: none; font-size: 11px; cursor: pointer; font-family: Inter, sans-serif; }
        .notif-delete-btn { display: flex; align-items: center; justify-content: center; border: none; background: transparent; color: #CBD5E1; cursor: pointer; padding: 2px; border-radius: 4px; transition: all 0.2s ease; margin-top: 1px; }
        .notif-delete-btn:hover { background: #FEE2E2; color: #DC2626; }
        .notif-snooze-menu { position: absolute; top: 100%; left: 0; margin-top: 4px; background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; z-index: 210; overflow: hidden; min-width: 100px; }
        .notif-snooze-item { padding: 6px 12px; font-size: 11px; text-align: left; border: none; background: none; cursor: pointer; border-bottom: 1px solid #F1F5F9; color: #1C3557; }
        .notif-snooze-item:last-child { border-bottom: none; }
        .notif-snooze-item:hover { background: #F8FAFC; }
      `}</style>
        </>
    );
}