import { Button } from "@mui/material";
import { Download, RefreshCw } from "lucide-react";
import { Link } from "react-router";
import { usePwa } from "./PwaContext";
import "../../css/pwa.css";

export function InstallHint() {
    const { installed } = usePwa();
    if (installed) return null;
    return <div className="pwa-install-hint"><span><Download size={20} /><span><strong>برنامه سالن روی گوشی شما</strong><small>دسترسی سریع از صفحه اصلی</small></span></span><Button component={Link} to="/app/install" variant="outlined" size="small">نصب و راهنما</Button></div>;
}
export function AppUpdateNotice() {
    const { updateReady, update } = usePwa();
    if (!updateReady) return null;
    return <div className="pwa-update-notice" role="status"><span>نسخه تازه آماده است. پس از اتمام کار، به‌روزرسانی کنید.</span><Button onClick={update} startIcon={<RefreshCw size={16} />}>به‌روزرسانی</Button></div>;
}
