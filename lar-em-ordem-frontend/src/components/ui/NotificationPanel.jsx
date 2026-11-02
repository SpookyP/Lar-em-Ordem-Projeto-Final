export function NotificationPanel({
                                      notifs,
                                      onClose,
                                      onRead,
                                      onSnooze,
                                      onReadAll,
                                      onAction,
                                      onDelete,
                                  }) {
    const visible = notifs.filter((n) => !n.snoozed);

    return (
        <>
            <div onClick={onClose} style={{ position: "fixed", inset: 0, zIndex: 190 }} />
            <div
                style={{
                    position: "absolute", top: 46, right: 0, width: 340, maxWidth: "calc(100vw - 32px)",
                    background: "#fff", borderRadius: 14, border: "1px solid #E2E8F0",
                    boxShadow: "0 14px 40px rgba(15,31,53,0.16)", zIndex: 200, overflow: "hidden",
                    animation: "slideDown 0.18s ease",
                }}
            >
                <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "12px 14px", borderBottom: "1px solid #F1F5F9" }}>
                    <p style={{ fontSize: 12.5, fontWeight: 600, color: "#1C3557", margin: 0 }}>
                        Notificações{" "}
                        {visible.filter((n) => !n.read).length > 0 && (
                            <span style={{ color: "#94A3B8", fontWeight: 500 }}>
                · {visible.filter((n) => !n.read).length} novas
              </span>
                        )}
                    </p>
                    <button onClick={onReadAll} style={{ background: "none", border: "none", color: "#0F766E", fontSize: 11, fontWeight: 600, cursor: "pointer", fontFamily: "Inter, sans-serif" }}>
                        Marcar tudo como lido
                    </button>
                </div>

                <div style={{ maxHeight: 340, overflowY: "auto" }}>
                    {visible.length === 0 && (
                        <p style={{ fontSize: 12, color: "#94A3B8", textAlign: "center", padding: "32px 16px", margin: 0 }}>
                            Sem notificações por agora.
                        </p>
                    )}
                    {visible.map((n) => (
                        <div key={n.id} style={{ display: "flex", gap: 11, padding: "12px 14px", borderBottom: "1px solid #F8FAFC", background: n.read ? "#fff" : "#FCFDFE", borderLeft: `3px solid ${n.read ? "transparent" : n.accent}` }}>
                            <div style={{ width: 30, height: 30, borderRadius: 9, background: n.bg, color: n.accent, display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                                {n.icon}
                            </div>
                            <div style={{ flex: 1, minWidth: 0 }}>

                                {/* ZONA DE TOPO DA NOTIFICAÇÃO (Título, Tempo e X) */}
                                <div style={{ display: "flex", alignItems: "flex-start", gap: 8 }}>
                                    <p style={{ fontSize: 12.5, fontWeight: n.read ? 500 : 600, color: n.read ? "#64748B" : "#0F172A", margin: 0, flex: 1 }}>{n.title}</p>
                                    <span style={{ fontSize: 10, color: "#CBD5E1", flexShrink: 0, marginTop: 2 }}>{n.time}</span>

                                    {/* BOTÃO ELIMINAR (X) */}
                                    <button onClick={() => onDelete(n.id)} className="notif-delete-btn" title="Eliminar notificação">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <p style={{ fontSize: 11, color: "#64748B", margin: "2px 0 0", lineHeight: 1.5 }}>{n.body}</p>

                                <div style={{ display: "flex", gap: 12, marginTop: 8, flexWrap: "wrap" }}>
                                    {n.action && (
                                        <button onClick={() => onAction()} style={{ padding: 0, background: "none", border: "none", color: "#1C3557", fontWeight: 600, fontSize: 11, cursor: "pointer", fontFamily: "Inter, sans-serif" }}>
                                            {n.action.label}
                                        </button>
                                    )}
                                    {!n.read && (
                                        <button onClick={() => onRead(n.id)} style={{ padding: 0, background: "none", border: "none", color: "#0F766E", fontWeight: 600, fontSize: 11, cursor: "pointer", fontFamily: "Inter, sans-serif" }}>
                                            Marcar como lido
                                        </button>
                                    )}
                                    {/* SÓ APARECE NAS NÃO LIDAS */}
                                    {!n.read && (
                                        <button onClick={() => onSnooze(n.id)} style={{ padding: 0, background: "none", border: "none", color: "#94A3B8", fontWeight: 500, fontSize: 11, cursor: "pointer", fontFamily: "Inter, sans-serif" }}>
                                            Avisar mais tarde
                                        </button>
                                    )}
                                </div>

                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* CLASSES CSS INJETADAS NO FINAL */}
            <style>{`
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px) } to { opacity:1; transform:translateY(0) } }
        .notif-delete-btn { display: flex; align-items: center; justify-content: center; border: none; background: transparent; color: #CBD5E1; cursor: pointer; padding: 2px; border-radius: 4px; transition: all 0.2s ease; margin-top: 1px; }
        .notif-delete-btn:hover { background: #FEE2E2; color: #DC2626; }
      `}</style>
        </>
    );
}