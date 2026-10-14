import { Badge, IconButton, Menu, MenuItem } from "@mui/material";
import { Bell } from "lucide-react";
import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { api, initializeCsrf } from "../../services/api/client";

interface Notice { id: string; title: string; message: string; path: string | null; read_at: string | null; }
export default function NotificationBell() {
    const [items, setItems] = useState<Notice[]>([]);
    const [anchor, setAnchor] = useState<HTMLElement | null>(null);
    const navigate = useNavigate();
    useEffect(() => { void api.get<{ data: Notice[] }>("/panel/notifications").then(({ data }) => setItems(data.data)).catch(() => undefined); }, []);
    async function open(item: Notice) {
        if (!item.read_at) { await initializeCsrf(); await api.post(`/panel/notifications/${item.id}/read`); setItems((current) => current.map((value) => value.id === item.id ? { ...value, read_at: new Date().toISOString() } : value)); }
        setAnchor(null); if (item.path) navigate(item.path);
    }
    return <><IconButton aria-label="اعلان‌ها" onClick={(event) => setAnchor(event.currentTarget)}><Badge color="error" badgeContent={items.filter((item) => !item.read_at).length}><Bell size={19} /></Badge></IconButton><Menu anchorEl={anchor} open={Boolean(anchor)} onClose={() => setAnchor(null)} slotProps={{ paper: { sx: { width: 330, maxWidth: "calc(100vw - 24px)", maxHeight: 420 } } }}>{items.length ? items.map((item) => <MenuItem key={item.id} onClick={() => void open(item)} sx={{ whiteSpace: "normal", display: "grid", gap: .5, bgcolor: item.read_at ? undefined : "rgba(208,59,119,.06)" }}><strong>{item.title}</strong><small>{item.message}</small></MenuItem>) : <MenuItem disabled>اعلان جدیدی ندارید.</MenuItem>}</Menu></>;
}
