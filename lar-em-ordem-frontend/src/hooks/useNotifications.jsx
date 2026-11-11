import { useState, useEffect, useCallback } from 'react';
import axiosClient from '../services/api/axiosClient.js';
import { useAuth } from './useAuth';
import { echo } from '../services/echo.js';

export function timeAgo(dateString) {
    if (!dateString) return 'agora mesmo';
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((new Date() - date) / 1000);
    if (diffInSeconds < 60) return 'agora mesmo';
    if (diffInSeconds < 3600) return `há ${Math.floor(diffInSeconds / 60)} m`;
    if (diffInSeconds < 86400) return `há ${Math.floor(diffInSeconds / 3600)} h`;
    return `há ${Math.floor(diffInSeconds / 86400)} d`;
}

export function useNotifications() {
    const { activeHouse } = useAuth();
    const propertyId = activeHouse?.id;

    const [notifications, setNotifications] = useState([]);
    const [error, setError] = useState(null);

    const clearError = () => setError(null);

    const unreadCount = notifications.filter(n => !n.read && !n.snoozed).length;

    // Formata uma notificação vinda da API ou do WebSocket
    const formatSingleNotification = useCallback((n, snoozedData, now) => {
        let accent = '#EA580C';
        let bg = '#FFF7ED';
        if (n.type === 'expired' || (n.title && n.title.toLowerCase().includes('expirad'))) {
            accent = '#DC2626';
            bg = '#FEE2E2';
        }

        let isSnoozed = false;
        let expiredSnooze = false;
        if (snoozedData[n.id]) {
            if (now < snoozedData[n.id]) {
                isSnoozed = true;
            } else {
                delete snoozedData[n.id];
                expiredSnooze = true;
            }
        }

        return {
            formatted: {
                id: n.id,
                title: n.title,
                body: n.message,
                time: timeAgo(n.alert_date || n.created_at),
                read: Boolean(n.read_at || n.is_read),
                snoozed: isSnoozed,
                accent,
                bg,
                icon: (
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                ),
                action: { label: 'Ver no Cofre Digital' }
            },
            expiredSnooze
        };
    }, []);

    useEffect(() => {
        let isMounted = true;

        const loadNotifications = async () => {
            if (!propertyId) return;
            try {
                setError(null);
                const listRes = await axiosClient.get(`/api/v1/properties/${propertyId}/notifications`);
                const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
                const now = new Date().getTime();
                let updatedSnoozes = false;

                const formattedNotifs = (listRes.data.data || []).map((n) => {
                    const { formatted, expiredSnooze } = formatSingleNotification(n, snoozedData, now);
                    if (expiredSnooze) updatedSnoozes = true;
                    return formatted;
                });

                if (updatedSnoozes) localStorage.setItem('snoozed_notifs', JSON.stringify(snoozedData));
                if (isMounted) setNotifications(formattedNotifs);
            } catch (err) {
                console.error("Erro ao carregar notificações:", err);
                if (isMounted) setError("Não foi possível carregar as notificações.");
            }
        };

        loadNotifications().catch(console.error);

        // --- SUBSCRIÇÃO WEBSOCKET (Laravel Reverb / Echo) ---
        let channel = null;

        if (echo && propertyId) {
            channel = echo.private(`properties.${propertyId}`);

            channel.listen('.vault.notification', (payload) => {
                if (!isMounted) return;
                const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
                const { formatted } = formatSingleNotification(payload, snoozedData, new Date().getTime());

                setNotifications((prev) => {
                    // Evita duplicar caso a notificação já esteja na lista; senão adiciona no topo
                    const exists = prev.some((item) => item.id === formatted.id);
                    if (exists) {
                        return prev.map((item) => (item.id === formatted.id ? formatted : item));
                    }
                    return [formatted, ...prev];
                });
            });
        }

        const interval = setInterval(() => {
            const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
            const now = new Date().getTime();
            let updated = false;

            setNotifications(prev => {
                let changed = false;
                const newNotifs = prev.map(n => {
                    if (n.snoozed && snoozedData[n.id] && now >= snoozedData[n.id]) {
                        delete snoozedData[n.id];
                        updated = true;
                        changed = true;
                        return { ...n, snoozed: false };
                    }
                    return n;
                });
                return changed ? newNotifs : prev;
            });

            if (updated) localStorage.setItem('snoozed_notifs', JSON.stringify(snoozedData));
        }, 15000);

        return () => {
            isMounted = false;
            clearInterval(interval);
            if (channel) {
                channel.stopListening('.vault.notification');
            }
        };
    }, [propertyId, formatSingleNotification]);

    const markAsRead = async (id) => {
        setError(null);
        const previousNotifs = [...notifications];
        setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
        try {
            await axiosClient.patch(`/api/v1/notifications/${id}/read`);
        } catch (error) {
            console.error("Erro na API:", error);
            setNotifications(previousNotifs);
            setError("Erro ao marcar notificação como lida.");
        }
    };

    const markAsUnread = async (id) => {
        setError(null);
        const previousNotifs = [...notifications];
        setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: false } : n));
        try {
            await axiosClient.patch(`/api/v1/notifications/${id}/unread`);
        } catch (error) {
            console.error("Erro na API:", error);
            setNotifications(previousNotifs);
            setError("Erro ao reverter estado da notificação.");
        }
    };

    const markAllAsRead = async () => {
        setError(null);
        const previousNotifs = [...notifications];
        setNotifications(prev => prev.map(n => ({ ...n, read: true })));
        try {
            await axiosClient.patch(`/api/v1/properties/${propertyId}/notifications/read-all`);
        } catch (error) {
            console.error("Erro na API:", error);
            setNotifications(previousNotifs);
            setError("Erro ao marcar todas as notificações como lidas.");
        }
    };

    const snoozeNotification = (id, minutes) => {
        const snoozeUntil = new Date().getTime() + (minutes * 60 * 1000);
        const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
        snoozedData[id] = snoozeUntil;
        localStorage.setItem('snoozed_notifs', JSON.stringify(snoozedData));
        setNotifications(prev => prev.map(n => n.id === id ? { ...n, snoozed: true } : n));
    };

    const deleteNotification = async (id) => {
        setError(null);
        const previousNotifs = [...notifications];
        setNotifications(prev => prev.filter(n => n.id !== id));
        try {
            await axiosClient.delete(`/api/v1/notifications/${id}`);
        } catch (error) {
            console.error("Erro na API:", error);
            setNotifications(previousNotifs);
            setError("Erro ao eliminar a notificação.");
        }
    };

    // Exporta o error e clearError para a UI poder consumi-los
    return {
        notifications, unreadCount, error, clearError,
        markAsRead, markAsUnread, markAllAsRead, snoozeNotification, deleteNotification
    };
}