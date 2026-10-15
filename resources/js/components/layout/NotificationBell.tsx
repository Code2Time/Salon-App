import { Badge, Button, Divider, IconButton, Menu, MenuItem } from "@mui/material";
import { Bell, ChevronLeft } from "lucide-react";
import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { fetchNotifications, markNotificationRead } from "../../services/api/notifications";
import type { Notice } from "../../types/notification";
import { noticeCreatedAt, noticeMessage } from "../../utils/notifications";

export default function NotificationBell() {
    const [items, setItems] = useState<Notice[]>([]);
    const [anchor, setAnchor] = useState<HTMLElement | null>(null);
    const navigate = useNavigate();
    useEffect(() => { void fetchNotifications().then(setItems).catch(() => undefined); }, []);
    function showPreview(target: HTMLElement) {
        setAnchor(target);
        void fetchNotifications().then(setItems).catch(() => undefined);
    }
    async function open(item: Notice) {
        if (!item.read_at) { await markNotificationRead(item.id); setItems((current) => current.map((value) => value.id === item.id ? { ...value, read_at: new Date().toISOString() } : value)); }
        setAnchor(null); if (item.path) navigate(item.path);
    }
    function showMore() {
        setAnchor(null);
        navigate(location.pathname.startsWith("/panel") ? "/panel/notifications" : "/app/notifications");
    }
    return <><IconButton aria-label="اعلان‌ها" onClick={(event) => showPreview(event.currentTarget)}><Badge color="error" badgeContent={items.filter((item) => !item.read_at).length}><Bell size={19} /></Badge></IconButton><Menu anchorEl={anchor} open={Boolean(anchor)} onClose={() => setAnchor(null)} slotProps={{ paper: { sx: { width: 350, maxWidth: "calc(100vw - 24px)", maxHeight: 460 } } }}>{items.length ? items.slice(0, 4).map((item) => <MenuItem key={item.id} onClick={() => void open(item)} sx={{ whiteSpace: "normal", display: "grid", gap: .5, bgcolor: item.read_at ? undefined : "rgba(208,59,119,.06)" }}><strong>{item.title}</strong><small>{noticeMessage(item)}</small><small className="notification-preview-date">{noticeCreatedAt(item.created_at)}</small></MenuItem>) : <MenuItem disabled>اعلان جدیدی ندارید.</MenuItem>}<Divider /><Button fullWidth onClick={showMore} endIcon={<ChevronLeft size={16} />}>نمایش بیشتر</Button></Menu></>;
}
