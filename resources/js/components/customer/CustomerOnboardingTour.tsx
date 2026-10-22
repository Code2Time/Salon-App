import { TourGuideClient } from "@sjmc11/tourguidejs";
import { CircleHelp, RotateCcw, ToggleLeft, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import { completeOnboarding, updateOnboardingTestState } from "../../services/api/auth";
import { readableApiError } from "../../services/api/client";

export default function CustomerOnboardingTour() {
    const { user, setAuthenticatedUser } = useAuth();
    const tourRef = useRef<TourGuideClient | null>(null);
    const completionStarted = useRef(false);
    const disposed = useRef(false);
    const [active, setActive] = useState(false);
    const [changingTestState, setChangingTestState] = useState(false);
    const isCustomer = Boolean(user && !user.abilities.access_panel);

    useEffect(() => {
        disposed.current = false;
        if (!user?.is_first_login || !isCustomer) return;

        const mobile = window.matchMedia("(max-width: 760px)").matches;
        const tour = new TourGuideClient({
            steps: [
                { title: "به سالن خوش آمدید ✨", content: "اینجا فضای شخصی شماست؛ خدمات را می‌بینید، متخصص دلخواهتان را انتخاب می‌کنید و نوبت می‌گیرید.", target: document.body },
                { title: "هویت سالن", content: "با لمس لوگوی سالن از هر صفحه‌ای به خانه اپ برمی‌گردید.", target: ".customer-header .brand" },
                { title: "مسیرهای اصلی", content: "خدمات، نوبت‌های شما و پروفایل همیشه از این نوار در دسترس‌اند.", target: mobile ? ".customer-bottom-nav" : ".customer-desktop-nav" },
                { title: "فضای اصلی شما", content: "محتوای هر بخش اینجا نمایش داده می‌شود. برای رزرو، از صفحه خدمات شروع کنید.", target: mobile ? document.body : "#main-content" },
                { title: "حساب و اعلان‌ها", content: "از این قسمت اعلان‌ها، اطلاعات حساب و خروج را مدیریت کنید. حالا همه‌چیز برای شروع آماده است.", target: ".header-account" },
            ],
            nextLabel: "مرحله بعد",
            prevLabel: "قبلی",
            finishLabel: "شروع استفاده",
            closeButton: true,
            showStepDots: true,
            stepDotsPlacement: "footer",
            showStepProgress: true,
            progressBar: "#c83f75",
            keyboardControls: true,
            exitOnEscape: true,
            exitOnClickOutside: false,
            completeOnFinish: false,
            backdropColor: "rgba(48, 20, 34, .72)",
            targetPadding: 10,
            dialogMaxWidth: 390,
            dialogZ: 10002,
            dialogClass: "ara-customer-tour",
        });
        tourRef.current = tour;

        async function persistCompletion() {
            if (completionStarted.current || disposed.current) return;
            completionStarted.current = true;
            setActive(false);
            try {
                setAuthenticatedUser(await completeOnboarding());
            } catch (caught) {
                completionStarted.current = false;
                toast.error(readableApiError(caught));
            }
        }

        tour.onAfterExit(() => { void persistCompletion(); });
        const timer = window.setTimeout(() => {
            if (disposed.current) return;
            setActive(true);
            void tour.start();
        }, 650);

        return () => {
            disposed.current = true;
            window.clearTimeout(timer);
            setActive(false);
            if (tour.isVisible) void tour.exit();
            tourRef.current = null;
        };
    }, [isCustomer, setAuthenticatedUser, user?.id, user?.is_first_login]);

    async function toggleTestState() {
        if (!user || changingTestState) return;
        setChangingTestState(true);
        try {
            const next = await updateOnboardingTestState(!user.is_first_login);
            setAuthenticatedUser(next);
            toast.success(next.is_first_login ? "حالت اولین ورود روشن شد؛ تور دوباره اجرا می‌شود." : "حالت اولین ورود برای تست خاموش شد.");
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setChangingTestState(false);
        }
    }

    return <>
        {active && <button type="button" className="customer-tour-skip" onClick={() => void tourRef.current?.exit()}><X size={16} /><span>رد کردن راهنما</span><CircleHelp size={17} /></button>}
        {user?.can_test_onboarding && isCustomer && <button type="button" className={`customer-tour-test-toggle${user.is_first_login ? " enabled" : ""}`} disabled={changingTestState} onClick={() => void toggleTestState()}>{user.is_first_login ? <ToggleLeft size={18} /> : <RotateCcw size={18} />}<span>{changingTestState ? "در حال تغییر…" : user.is_first_login ? "خاموش‌کردن اولین ورود" : "تست دوباره تور"}</span></button>}
    </>;
}
