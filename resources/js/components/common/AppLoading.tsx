import { CircularProgress } from "@mui/material";
import { logoUrl } from "../../constants/assets";

export default function AppLoading() {
    return (
        <div className="auth-loading" role="status" aria-label="در حال بارگذاری">
            <img src={logoUrl} alt="" className="loading-logo" />
            <CircularProgress size={28} thickness={3.5} />
            <span>در حال آماده‌سازی فضای شما…</span>
        </div>
    );
}
