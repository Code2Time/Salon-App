import { Alert, Button, Chip, CircularProgress, Paper, Typography } from "@mui/material";
import { Bell, CheckCheck, ExternalLink } from "lucide-react";
import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from "../services/api/notifications";
import { readableApiError } from "../services/api/client";
import type { Notice } from "../types/notification";
import { noticeCreatedAt, noticeMessage } from "../utils/notifications";

export default function Notifications() {
    const [items, setItems] = useState<Notice[]>([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        void fetchNotifications().then(setItems).catch((error) => toast.error(readableApiError(error))).finally(() => setLoading(false));
    }, []);

    async function read(item: Notice, follow = false) {
        try {
            if (!item.read_at) {
                await markNotificationRead(item.id);
                setItems((current) => current.map((notice) => notice.id === item.id ? { ...notice, read_at: new Date().toISOString() } : notice));
            }
            if (follow && item.path) navigate(item.path);
        } catch (error) {
            toast.error(readableApiError(error));
        }
    }

    async function readAll() {
        try {
            await markAllNotificationsRead();
            const readAt = new Date().toISOString();
            setItems((current) => current.map((item) => ({ ...item, read_at: item.read_at ?? readAt })));
            toast.success("همه اعلان‌ها خوانده شدند.");
        } catch (error) {
            toast.error(readableApiError(error));
        }
    }

    return <section className="notifications-page"><div className="page-heading notification-page-heading"><div><span className="eyebrow">مرکز پیام‌ها</span><Typography variant="h2" component="h1">اعلان‌ها</Typography><Typography color="text.secondary">تغییرات برنامه و رویدادهای مهم حساب شما در این بخش نگهداری می‌شوند.</Typography></div>{items.some((item) => !item.read_at) && <Button variant="outlined" onClick={() => void readAll()} startIcon={<CheckCheck size={17} />}>خواندن همه</Button>}</div>{loading ? <div className="users-loading"><CircularProgress /></div> : items.length === 0 ? <Paper className="users-empty"><Bell /><Typography>هنوز اعلانی ندارید.</Typography></Paper> : <div className="notification-list">{items.map((item) => <Paper key={item.id} variant="outlined" className={`notification-card${item.read_at ? "" : " unread"}`}><div className="notification-card-title"><div><strong>{item.title}</strong><small>{noticeCreatedAt(item.created_at)}</small></div><Chip size="small" color={item.read_at ? "default" : "primary"} label={item.read_at ? "خوانده‌شده" : "جدید"} /></div><Alert severity="info" icon={false}>{noticeMessage(item)}</Alert><div className="notification-card-actions">{!item.read_at && <Button onClick={() => void read(item)} startIcon={<CheckCheck size={16} />}>خواندم</Button>}{item.path && <Button variant="contained" onClick={() => void read(item, true)} endIcon={<ExternalLink size={15} />}>مشاهده مرتبط</Button>}</div></Paper>)}</div>}</section>;
}
