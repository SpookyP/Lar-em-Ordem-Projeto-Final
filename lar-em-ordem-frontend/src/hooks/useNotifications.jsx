import { useState, useEffect } from 'react';
import axiosClient from '../services/api/axiosClient.js';
import { useAuth } from './useAuth';

const SNOOZE_MINUTES = 5;

function timeAgo(dateString) {
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
    const [unreadCount, setUnreadCount] = useState(0);

    useEffect(() => {
        let isMounted = true;

        const loadNotifications = async () => {
            if (!propertyId) return;

            try {
                const listRes = await axiosClient.get(`/api/v1/properties/${propertyId}/notifications`);
                const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
                const now = new Date().getTime();
                let updatedSnoozes = false;

                const formattedNotifs = listRes.data.data.map((n) => {
                    let accent = '#EA580C';
                    let bg = '#FFF7ED';
                    if (n.type === 'expired' || n.title.toLowerCase().includes('expirad')) {
                        accent = '#DC2626'; bg = '#FEE2E2';
                    }

                    let isSnoozed = false;
                    if (snoozedData[n.id]) {
                        if (now < snoozedData[n.id]) {
                            isSnoozed = true;
                        } else {
                            delete snoozedData[n.id];
                            updatedSnoozes = true;
                        }
                    }

                    return {
                        id: n.id,
                        title: n.title,
                        body: n.message,
                        time: timeAgo(n.alert_date),
                        read: Boolean(n.read_at || n.is_read),
                        snoozed: isSnoozed,
                        accent, bg,
                        icon: (
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        ),
                        action: { label: 'Ver no Cofre Digital' }
                    };
                });

                if (updatedSnoozes) {
                    localStorage.setItem('snoozed_notifs', JSON.stringify(snoozedData));
                }

                if (isMounted) {
                    setNotifications(formattedNotifs);
                    setUnreadCount(formattedNotifs.filter(n => !n.read && !n.snoozed).length);
                }
            } catch (error) {
                console.error("Erro ao carregar notificações:", error);
            }
        };

        loadNotifications();
        return () => { isMounted = false; };
    }, [propertyId]);

    const markAsRead = async (id) => {
        setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
        setUnreadCount(prev => Math.max(0, prev - 1));
        try {
            await axiosClient.patch(`/api/v1/notifications/${id}/read`);
        } catch (error) {
            console.error("Erro ao marcar como lido na API:", error);
        }
    };

    const markAllAsRead = async () => {
        setNotifications(prev => prev.map(n => ({ ...n, read: true })));
        setUnreadCount(0);
        try {
            await axiosClient.patch(`/api/v1/properties/${propertyId}/notifications/read-all`);
        } catch (error) {
            console.error("Erro ao marcar todas como lidas na API:", error);
        }
    };

    const snoozeNotification = (id) => {
        const snoozeUntil = new Date().getTime() + (SNOOZE_MINUTES * 60 * 1000);
        const snoozedData = JSON.parse(localStorage.getItem('snoozed_notifs') || '{}');
        snoozedData[id] = snoozeUntil;
        localStorage.setItem('snoozed_notifs', JSON.stringify(snoozedData));

        setNotifications(prev => prev.map(n => n.id === id ? { ...n, snoozed: true } : n));
        setUnreadCount(prev => Math.max(0, prev - 1));
    };

    // Eliminar notificação
    const deleteNotification = async (id) => {
        const notif = notifications.find(n => n.id === id);
        if (notif && !notif.read && !notif.snoozed) {
            setUnreadCount(prev => Math.max(0, prev - 1));
        }

        // Remove da interface permanentemente
        setNotifications(prev => prev.filter(n => n.id !== id));

        try {
            if (notif && !notif.read) {
                await axiosClient.patch(`/api/v1/notifications/${id}/read`);
            }
        } catch (error) {
            console.error("Erro ao eliminar notificação:", error);
        }
    };

    return { notifications, unreadCount, markAsRead, markAllAsRead, snoozeNotification, deleteNotification };
}