import { useSalon } from "../../salon/SalonContext";
import { useCallback, useEffect, useRef, useState } from "react";
import { Alert, Button, CircularProgress, Paper, Typography } from "@mui/material";
import { Copy, Download, ExternalLink, QrCode, RefreshCw, ScanLine, Wifi } from "lucide-react";
import { QRCodeSVG } from "qrcode.react";
import { toast } from "react-toastify";
import { fetchAccessLink } from "../../services/api/accessLink";
import type { AccessLink } from "../../services/api/accessLink";
import { readableApiError } from "../../services/api/client";
import "../../../css/access-qr-code.css";

export default function AccessQrCode() {
    const { salon } = useSalon();
    const [link, setLink] = useState<AccessLink | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const qr = useRef<SVGSVGElement>(null);
    const address = useRef<HTMLInputElement>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try { setLink(await fetchAccessLink()); }
        catch (caught) { setError(readableApiError(caught)); }
        finally { setLoading(false); }
    }, []);
    useEffect(() => { void load(); }, [load]);

    async function copyLink() {
        if (!link?.url) return;
        try {
            if (window.isSecureContext && navigator.clipboard) {
                await navigator.clipboard.writeText(link.url);
            } else {
                // LAN development uses HTTP, where the Clipboard API is unavailable.
                address.current?.focus();
                address.current?.select();
                if (!document.execCommand("copy")) {
                    toast.info("آدرس انتخاب شد؛ آن را کپی کنید.");
                    return;
                }
            }
            toast.success("آدرس برنامه کپی شد.");
        } catch {
            address.current?.focus();
            address.current?.select();
            toast.info("آدرس را از کادر انتخاب‌شده کپی کنید.");
        }
    }

    function downloadQr() {
        if (!qr.current) return;
        const blob = new Blob([new XMLSerializer().serializeToString(qr.current)], { type: "image/svg+xml;charset=utf-8" });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement("a");
        anchor.href = url;
        anchor.download = "salon-customer-qr.svg";
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    return <section className="access-qr-page">
        <header className="access-qr-heading"><span className="eyebrow">همراه مشتریان سالن</span><Typography component="h1" variant="h2">ورود سریع با QR کد</Typography><Typography color="text.secondary">کد را به مشتری نشان دهید تا برنامه سالن را روی گوشی باز کند.</Typography></header>
        {loading ? <Paper variant="outlined" className="access-qr-loading"><CircularProgress size={30} /><span>در حال آماده‌سازی کد ورود…</span></Paper> : error ? <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert> : link && <div className="access-qr-layout">
            <Paper variant="outlined" className="access-qr-card">
                <span className="access-qr-caption">{salon?.logo_url ? <img src={salon.logo_url} alt="" style={{ width: 32, height: 32, objectFit: "contain", borderRadius: 8 }} /> : <QrCode size={18} />}{salon?.name ?? link.salon_name}</span>
                <Typography component="h2" variant="h3">اسکن کنید، وارد شوید</Typography>
                {link.url ? <><div className="access-qr-frame"><QRCodeSVG ref={qr} value={link.url} size={264} level="M" marginSize={4} bgColor="#ffffff" fgColor="#161016" title={`ورود به برنامه ${link.salon_name}`} /></div><p>دوربین گوشی را روی کد بگیرید</p><Button variant="outlined" onClick={downloadQr} startIcon={<Download size={17} />}>دانلود QR کد</Button></> : <div className="access-qr-unavailable"><Wifi size={38} /><strong>آدرس شبکه در دسترس نیست</strong><p>اتصال کامپیوتر به Wi-Fi را بررسی کنید و سپس آدرس و کد را به‌روز کنید.</p></div>}
            </Paper>
            <div className="access-qr-information">
                <Paper variant="outlined" className="access-qr-link-card"><span className="access-qr-section-icon"><ScanLine size={22} /></span><Typography component="h2" variant="h3">لینک برنامه مشتری</Typography><Typography color="text.secondary">این کد مستقیماً صفحه اصلی برنامه مشتری را باز می‌کند.</Typography>{link.url && <><label htmlFor="customer-access-url">آدرس فعلی برنامه</label><input id="customer-access-url" ref={address} value={link.url} readOnly dir="ltr" onFocus={(event) => event.currentTarget.select()} /><div className="access-qr-actions"><Button variant="contained" onClick={() => void copyLink()} startIcon={<Copy size={16} />}>کپی لینک</Button><Button component="a" href={link.url} target="_blank" rel="noopener noreferrer" variant="outlined" startIcon={<ExternalLink size={16} />}>باز کردن برنامه</Button></div></>}</Paper>
                <Paper variant="outlined" className="access-qr-guide"><span className="access-qr-section-icon"><Wifi size={22} /></span><Typography component="h2" variant="h3">اتصال در سالن</Typography><ol><li>کامپیوتر و گوشی مشتری به یک Wi-Fi وصل باشند.</li><li>برنامه روی کامپیوتر روشن بماند.</li><li>بعد از تغییر شبکه یا آدرس، کد را به‌روز کنید.</li></ol><Button color="inherit" disabled={loading} onClick={() => void load()} startIcon={<RefreshCw size={16} />}>به‌روزرسانی آدرس و کد</Button></Paper>
            </div>
        </div>}
    </section>;
}
