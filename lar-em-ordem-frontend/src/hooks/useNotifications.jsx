import { useState, useEffect, useCallback } from 'react';
import { FileText } from 'lucide-react';
import axiosClient from '../services/api/axiosClient.js';
import { useAuth } from './useAuth';
import { echo } from '../services/echo.js';

const SNOOZE_STORAGE_KEY = 'snoozed_notifs';

function getSnoozedMap() {
    try {
        return JSON.parse(localStorage.getItem(SNOOZE_STORAGE_KEY) || '{}');
    } catch {
        return {};
    }
}

function saveSnoozedMap(data) {
    localStorage.setItem(SNOOZE_STORAGE_KEY, JSON.stringify(data));
}

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

    const clearError = useCallback(() => setError(null), []);

    const unreadCount = notifications.filter((n) => !n.read && !n.snoozed).length;

    const formatSingleNotification = useCallback((n, snoozedData, now) => {
        const isExpired = n.type === 'expired' || n.title?.toLowerCase().includes('expirad');
        const accent = isExpired ? '#DC2626' : '#EA580C';
        const bg = isExpired ? '#FEE2E2' : '#FFF7ED';

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
                icon: <FileText size={16} />,
                action: { label: 'Ver no Cofre Digital', to: '/vault' },
            },
            expiredSnooze,
        };
    }, []);

    useEffect(() => {
        let isMounted = true;

        const loadNotifications = async () => {
            if (!propertyId) {
                if (isMounted) setNotifications([]);
                return;
            }
            try {
                if (isMounted) setError(null);
                const listRes = await axiosClient.get(`/api/v1/properties/${propertyId}/notifications`);
                const snoozedData = getSnoozedMap();
                const now = Date.now();
                let updatedSnoozes = false;

                const formattedNotifs = (listRes.data.data || []).map((n) => {
                    const { formatted, expiredSnooze } = formatSingleNotification(n, snoozedData, now);
                    if (expiredSnooze) updatedSnoozes = true;
                    return formatted;
                });

                if (updatedSnoozes) saveSnoozedMap(snoozedData);
                if (isMounted) setNotifications(formattedNotifs);
            } catch {
                if (isMounted) setError('Não foi possível carregar as notificações.');
            }
        };

        loadNotifications();

        let channel = null;

        if (echo && propertyId) {
            channel = echo.private(`properties.${propertyId}`);

            channel.listen('.vault.notification', (payload) => {
                if (!isMounted) return;
                const snoozedData = getSnoozedMap();
                const { formatted } = formatSingleNotification(payload, snoozedData, Date.now());

                setNotifications((prev) => {
                    const exists = prev.some((item) => item.id === formatted.id);
                    if (exists) {
                        return prev.map((item) => (item.id === formatted.id ? formatted : item));
                    }
                    return [formatted, ...prev];
                });
            });
        }

        const interval = setInterval(() => {
            const snoozedData = getSnoozedMap();
            const now = Date.now();
            let updated = false;

            setNotifications((prev) => {
                let changed = false;
                const newNotifs = prev.map((n) => {
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

            if (updated) saveSnoozedMap(snoozedData);
        }, 15000);

        return () => {
            isMounted = false;
            clearInterval(interval);
            if (channel) {
                channel.stopListening('.vault.notification');
            }
        };
    }, [propertyId, formatSingleNotification]);

    // Função genérica para evitar repetição nas chamadas otimistas à API
    const runOptimistic = async (updater, apiCall, errorMessage) => {
        setError(null);
        const previousNotifs = notifications;
        setNotifications(updater);
        try {
            await apiCall();
        } catch {
            setNotifications(previousNotifs);
            setError(errorMessage);
        }
    };

    const markAsRead = (id) =>
        runOptimistic(
            (prev) => prev.map((n) => (n.id === id ? { ...n, read: true } : n)),
            () => axiosClient.patch(`/api/v1/notifications/${id}/read`),
            'Erro ao marcar notificação como lida.'
        );

    const markAsUnread = (id) =>
        runOptimistic(
            (prev) => prev.map((n) => (n.id === id ? { ...n, read: false } : n)),
            () => axiosClient.patch(`/api/v1/notifications/${id}/unread`),
            'Erro ao reverter estado da notificação.'
        );

    const markAllAsRead = () => {
        if (!propertyId) return;
        return runOptimistic(
            (prev) => prev.map((n) => ({ ...n, read: true })),
            () => axiosClient.patch(`/api/v1/properties/${propertyId}/notifications/read-all`),
            'Erro ao marcar todas as notificações como lidas.'
        );
    };

    const deleteNotification = (id) =>
        runOptimistic(
            (prev) => prev.filter((n) => n.id !== id),
            () => axiosClient.delete(`/api/v1/notifications/${id}`),
            'Erro ao eliminar a notificação.'
        );

    const snoozeNotification = (id, minutes) => {
        const snoozeUntil = Date.now() + minutes * 60 * 1000;
        const snoozedData = getSnoozedMap();
        snoozedData[id] = snoozeUntil;
        saveSnoozedMap(snoozedData);
        setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, snoozed: true } : n)));
    };

    return {
        notifications,
        unreadCount,
        error,
        clearError,
        markAsRead,
        markAsUnread,
        markAllAsRead,
        snoozeNotification,
        deleteNotification,
    };
}