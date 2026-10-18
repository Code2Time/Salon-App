import { Button, Paper, TextField, Typography } from "@mui/material";
import { ArrowLeft, RotateCcw } from "lucide-react";
import { useState } from "react";
import type { FormEvent } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import Brand from "../../components/layout/Brand";
import { readableApiError } from "../../services/api/client";
import { requestOtp, verifyOtp } from "../../services/api/auth";
import type { AuthMode } from "../../types/auth";
import { toast } from "react-toastify";
import { fa } from "../../locales/fa";

export default function AuthPage({ mode }: { mode: AuthMode }) {
    const { user, loading: authLoading, setAuthenticatedUser } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const requestedPath = (location.state as { from?: unknown } | null)?.from;
    const returnTo = typeof requestedPath === "string" && requestedPath.startsWith("/app/")
        ? requestedPath
        : null;
    const [mobile, setMobile] = useState("");
    const [firstName, setFirstName] = useState("");
    const [lastName, setLastName] = useState("");
    const [code, setCode] = useState("");
    const [step, setStep] = useState<"details" | "code">("details");
    const [loading, setLoading] = useState(false);
    const destinationFor = (authenticated: typeof user) =>
        authenticated?.abilities.access_panel ? "/panel" : returnTo ?? authenticated?.home_path ?? "/app";

    if (!authLoading && user) return <Navigate to={destinationFor(user)} replace />;

    async function submit(event: FormEvent) {
        event.preventDefault();
        setLoading(true);
        try {
            if (step === "details") {
                await requestOtp(mobile);
                setStep("code");
                toast.success(fa.auth.otpSent);
            } else {
                const authenticated = await verifyOtp({
                    mobile,
                    code,
                    mode,
                    ...(firstName ? { first_name: firstName } : {}),
                    ...(lastName ? { last_name: lastName } : {}),
                });
                setAuthenticatedUser(authenticated);
                toast.success(mode === "register" ? fa.auth.registrationSucceeded : fa.auth.loginSucceeded);
                navigate(destinationFor(authenticated), { replace: true });
            }
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="auth-page">
            <Paper className="auth-card" variant="outlined">
                <Brand />
                <div>
                    <span className="eyebrow">ورود امن با شماره همراه</span>
                    <Typography component="h1" variant="h2">
                        {mode === "register" ? "ساخت حساب مشتری" : "ورود به آرا"}
                    </Typography>
                    <Typography color="text.secondary" className="auth-intro">
                        {mode === "register"
                            ? "حساب‌های جدید با دسترسی مشتری ساخته می‌شوند."
                            : "پس از ورود، فضای مناسب نقش شما باز می‌شود."}
                    </Typography>
                </div>
                <form onSubmit={submit} className="auth-form">
                    {mode === "register" && step === "details" && (
                        <div className="auth-name-fields">
                            <TextField
                                label="نام"
                                value={firstName}
                                onChange={(event) => setFirstName(event.target.value)}
                                required
                                autoComplete="given-name"
                            />
                            <TextField
                                label="نام خانوادگی"
                                value={lastName}
                                onChange={(event) => setLastName(event.target.value)}
                                required
                                autoComplete="family-name"
                            />
                        </div>
                    )}
                    <TextField
                        label="شماره موبایل"
                        value={mobile}
                        onChange={(event) => setMobile(event.target.value)}
                        placeholder="09123456789"
                        required
                        disabled={step === "code"}
                        slotProps={{ htmlInput: { inputMode: "tel", maxLength: 11, dir: "ltr" } }}
                        autoComplete="tel"
                    />
                    {step === "code" && (
                        <TextField
                            label="کد چهار رقمی"
                            value={code}
                            onChange={(event) => setCode(event.target.value)}
                            required
                            autoFocus
                            slotProps={{ htmlInput: { inputMode: "numeric", maxLength: 4, dir: "ltr" } }}
                            autoComplete="one-time-code"
                        />
                    )}
                    <Button type="submit" variant="contained" size="large" disabled={loading}>
                        {loading ? "لطفاً صبر کنید…" : step === "details" ? "دریافت کد" : "تأیید و ادامه"}
                        {!loading && <ArrowLeft size={18} />}
                    </Button>
                    {step === "code" && (
                        <Button
                            type="button"
                            color="inherit"
                            onClick={() => { setStep("details"); setCode(""); }}
                        >
                            <RotateCcw size={17} /> اصلاح شماره
                        </Button>
                    )}
                </form>
                <p className="auth-switch">
                    {mode === "register" ? "قبلاً حساب ساخته‌اید؟" : "حساب مشتری ندارید؟"}{" "}
                    <Link
                        to={mode === "register" ? "/login" : "/register"}
                        state={returnTo ? { from: returnTo } : undefined}
                    >
                        {mode === "register" ? "وارد شوید" : "ثبت‌نام کنید"}
                    </Link>
                </p>
            </Paper>
        </main>
    );
}
