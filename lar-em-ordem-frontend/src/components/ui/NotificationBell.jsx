import { useState } from 'react';
import { useNotifications, timeAgo } from '../../hooks/useNotifications';
import { useCondominiumNotices } from '../../hooks/useCondominiumNotices';
import { NotificationPanel } from './NotificationPanel';
import { Bell, Building } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

// condominium notices share the panel with the vault ones: their ids get a prefix so they never clash
const CONDO_PREFIX = 'condo-';
const condoId = (id) =>
    typeof id === 'string' && id.startsWith(CONDO_PREFIX) ? id.slice(CONDO_PREFIX.length) : null;

const PRIORITY_COLORS = {
    high: { accent: '#DC2626', bg: '#FEE2E2' },
    normal: { accent: '#1C3557', bg: '#E8EEF6' },
    low: { accent: '#64748B', bg: '#F1F5F9' },
};

// condominium notice -> the item shape NotificationPanel expects
function toPanelItem(notice) {
    const colors = PRIORITY_COLORS[notice.priority] ?? PRIORITY_COLORS.normal;
    return {
        id: `${CONDO_PREFIX}${notice.id}`,
        title: notice.title,
        body: notice.message,
        time: timeAgo(notice.publishedAt),
        read: notice.read,
        snoozed: false,
        // the API has no "unread" for notices, and snoozing is only for the vault alerts
        canUnread: false,
        canSnooze: false,
        ...colors,
        icon: <Building size={16} />,
        action: { label: 'Ver no condomínio', to: '/condominio' },
    };
}

export function NotificationBell() {
    const [isOpen, setIsOpen] = useState(false);
    const navigate = useNavigate();

    const {
        notifications,
        unreadCount,
        error,
        clearError,
        markAsRead,
        markAsUnread,
        markAllAsRead,
        snoozeNotification,
        deleteNotification
    } = useNotifications();

    const condo = useCondominiumNotices();

    // condominium notices first: they come from the administration and can be urgent
    const items = [...condo.notices.map(toPanelItem), ...notifications];
    const totalUnread = unreadCount + condo.unreadCount;

    return (
        <div className="relative">
            <button
                onClick={() => setIsOpen(!isOpen)}
                aria-label={totalUnread > 0 ? `Notificações, ${totalUnread} por ler` : 'Notificações'}
                className="relative flex items-center justify-center w-10 h-10 rounded-full bg-navy text-white transition hover:bg-navy-light"
            >
                <Bell size={20} />
                {totalUnread > 0 && (
                    <span className="absolute top-0 right-0 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-orange border-2 border-surface rounded-full">
            {totalUnread}
          </span>
                )}
            </button>

            {isOpen && (
                <NotificationPanel
                    notifs={items}
                    error={error || condo.error}
                    onClearError={() => {
                        clearError();
                        condo.clearError();
                    }}
                    onClose={() => setIsOpen(false)}
                    onRead={(id) => (condoId(id) ? condo.markRead(condoId(id)) : markAsRead(id))}
                    onUnread={markAsUnread}
                    onReadAll={() => {
                        markAllAsRead();
                        if (condo.unreadCount > 0) condo.markAllRead();
                    }}
                    onSnooze={snoozeNotification}
                    onDelete={(id) => (condoId(id) ? condo.dismiss(condoId(id)) : deleteNotification(id))}
                    onAction={(item) => {
                        setIsOpen(false);
                        navigate(item?.action?.to ?? '/cofre');
                    }}
                />
            )}
        </div>
    );
}