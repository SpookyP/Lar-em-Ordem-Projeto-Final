import { useState } from 'react';
import { useNotifications } from '../../hooks/useNotifications';
import { NotificationPanel } from './NotificationPanel';
import { Bell } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

export function NotificationBell() {
    const [isOpen, setIsOpen] = useState(false);
    const navigate = useNavigate();

    const {
        notifications,
        unreadCount,
        markAsRead,
        markAllAsRead,
        snoozeNotification,
        deleteNotification
    } = useNotifications();

    return (
        <div className="relative">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative flex items-center justify-center w-10 h-10 rounded-full bg-navy text-white transition hover:bg-navy-light"
            >
                <Bell size={20} />
                {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-orange border-2 border-surface rounded-full">
            {unreadCount}
          </span>
                )}
            </button>

            {isOpen && (
                <NotificationPanel
                    notifs={notifications}
                    onClose={() => setIsOpen(false)}
                    onRead={markAsRead}
                    onReadAll={markAllAsRead}
                    onSnooze={snoozeNotification}
                    onDelete={deleteNotification}
                    onAction={() => {
                        setIsOpen(false);
                        navigate('/cofre');
                    }}
                />
            )}
        </div>
    );
}