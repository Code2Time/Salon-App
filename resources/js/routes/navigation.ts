import {
    CalendarDays,
    ChartNoAxesCombined,
    Grid2X2,
    House,
    LayoutDashboard,
    Package,
    Scissors,
    Settings,
    Shapes,
    Sparkles,
    UserRound,
    UsersRound,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";

export interface NavigationItem {
    path: string;
    label: string;
    description: string;
    icon: LucideIcon;
}

export const panelNavigation: NavigationItem[] = [
    {
        path: "/panel",
        label: "داشبورد",
        description: "نمای کلی فضای مدیریت سالن شما",
        icon: LayoutDashboard,
    },
    {
        path: "/panel/customers",
        label: "مشتریان",
        description: "فضایی برای شناخت بهتر و همراهی با مشتریان",
        icon: UsersRound,
    },
    {
        path: "/panel/employees",
        label: "کارکنان",
        description: "مدیریت اعضای تیم و تخصص‌های آن‌ها",
        icon: UserRound,
    },
    {
        path: "/panel/service-categories",
        label: "دسته‌بندی خدمات",
        description: "نظم بیشتر برای خدمات سالن",
        icon: Shapes,
    },
    {
        path: "/panel/services",
        label: "خدمات",
        description: "معرفی و مدیریت خدمات زیبایی سالن",
        icon: Scissors,
    },
    {
        path: "/panel/appointments",
        label: "نوبت‌ها",
        description: "برنامه‌ریزی روزهای کاری سالن",
        icon: CalendarDays,
    },
    {
        path: "/panel/products",
        label: "محصولات",
        description: "فضای معرفی محصولات مراقبت و زیبایی",
        icon: Package,
    },
    {
        path: "/panel/reports",
        label: "گزارش‌ها",
        description: "نگاهی روشن‌تر به عملکرد سالن",
        icon: ChartNoAxesCombined,
    },
    {
        path: "/panel/settings",
        label: "تنظیمات",
        description: "تنظیم فضای کاری متناسب با سالن شما",
        icon: Settings,
    },
];

export const customerNavigation: NavigationItem[] = [
    {
        path: "/app",
        label: "خانه",
        description: "به فضای زیبایی خودتان خوش آمدید",
        icon: House,
    },
    {
        path: "/app/services",
        label: "خدمات",
        description: "زمانی برای رسیدگی به خودتان",
        icon: Grid2X2,
    },
    {
        path: "/app/booking",
        label: "رزرو",
        description: "اولین قدم برای یک حال خوب",
        icon: Sparkles,
    },
    {
        path: "/app/appointments",
        label: "نوبت‌های من",
        description: "قرارهای زیبایی شما، در یک نگاه",
        icon: CalendarDays,
    },
    {
        path: "/app/profile",
        label: "پروفایل",
        description: "فضای شخصی شما",
        icon: UserRound,
    },
];
