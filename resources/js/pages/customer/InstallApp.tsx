import { useState } from "react";
import { Alert, Button, Paper, Tab, Tabs, Typography } from "@mui/material";
import { CheckCircle2, Download, Share2, Smartphone, Wifi } from "lucide-react";
import { Link } from "react-router";
import { usePwa } from "../../pwa/PwaContext";
import { useSalon } from "../../salon/SalonContext";
import "../../../css/pwa.css";

export default function InstallApp() {
    const { salon } = useSalon();
    const pwa = usePwa();
    const [device, setDevice] = useState(pwa.ios ? "ios" : "android");
    return <section className="pwa-install-page"><header><span className="eyebrow">سالن همیشه همراه شما</span><Typography component="h1" variant="h2">نصب برنامه روی گوشی</Typography><Typography color="text.secondary">بدون مراجعه به فروشگاه برنامه، دسترسی سریع به خدمات و نوبت‌ها.</Typography></header>
        <Paper variant="outlined" className="pwa-install-card"><div className="pwa-install-icon">{salon?.logo_url ? <img src={salon.logo_url} alt="" /> : <Smartphone size={42} />}</div><Typography component="h2" variant="h3">{salon?.name ?? "برنامه سالن"}</Typography><p>یک آیکن روی صفحه اصلی گوشی؛ خدمات، پیشنهادها و نوبت‌های شما در دسترس.</p>
            {pwa.installed ? <Alert severity="success" icon={<CheckCircle2 size={23} />}>برنامه نصب شده است. از همین پنجره می‌توانید استفاده کنید.</Alert> : !pwa.secure ? <Alert severity="info">این آدرس برای نصب مستقیم آماده نیست. نسخه برنامه با آدرس امن HTTPS را باز کنید؛ استفاده از نسخه مرورگری همچنان ممکن است.</Alert> : pwa.canInstall ? <Button variant="contained" size="large" disabled={pwa.installing} onClick={() => void pwa.install()} startIcon={<Download size={19} />}>{pwa.installing ? "در حال بازکردن پنجره نصب…" : "نصب برنامه"}</Button> : <Alert severity="info">برای افزودن برنامه به گوشی، راهنمای مرورگر خود را در پایین دنبال کنید.</Alert>}
            {pwa.error && <Alert severity="warning">{pwa.error}</Alert>}<Button component={Link} to="/app" color="inherit">ادامه در برنامه</Button>
        </Paper>
        {!pwa.installed && <Paper variant="outlined" className="pwa-install-guide"><Typography component="h2" variant="h3">راهنمای نصب</Typography><Tabs value={device} onChange={(_, value: string) => setDevice(value)} variant="fullWidth" aria-label="نوع گوشی"><Tab value="android" label="اندروید" id="install-android-tab" aria-controls="install-guide-android" /><Tab value="ios" label="آیفون و آیپد" id="install-ios-tab" aria-controls="install-guide-ios" /></Tabs><div role="tabpanel" id={`install-guide-${device}`} aria-labelledby={`install-${device}-tab`}>
            {device === "ios" ? <ol><li>برنامه را در مرورگر Safari باز کنید.</li><li>گزینه اشتراک‌گذاری <Share2 size={16} /> (Share) را انتخاب کنید.</li><li>«افزودن به صفحه اصلی» (Add to Home Screen) را بزنید.</li><li>در صورت نمایش، «بازکردن به‌صورت برنامه» را روشن و «افزودن» (Add) را انتخاب کنید.</li></ol> : <ol><li>برنامه را در مرورگر Chrome باز کنید.</li><li>منوی سه‌نقطه مرورگر را باز کنید.</li><li>«نصب برنامه» (Install app) یا «افزودن به صفحه اصلی» (Add to Home screen) را انتخاب کنید.</li><li>درخواست نصب را تأیید کنید؛ آیکن برنامه به گوشی اضافه می‌شود.</li></ol>}
        </div></Paper>}
        <div className="pwa-connectivity-note"><Wifi size={19} /><p>برای مشاهده نوبت‌های به‌روز، ثبت رزرو و سفارش، اتصال لازم است. نصب برنامه، کامپیوتر یا سرور سالن را جایگزین نمی‌کند.</p></div>
    </section>;
}
