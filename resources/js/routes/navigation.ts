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
    UserRoundCog,
    CircleUserRound,
    UsersRound,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";
import type { PanelPermission } from "../types/auth";

export interface NavigationItem {
    path: string;
    label: string;
    description: string;
    icon: LucideIcon;
    roles?: Array<"salon_owner" | "employee">;
    permission?: PanelPermission;
}

export const panelNavigation: NavigationItem[] = [
    {
        path: "/panel",
        label: "داشبورد",
        description: "نمای کلی فضای مدیریت سالن شما",
        icon: LayoutDashboard,
        roles: ["salon_owner", "employee"],
        permission: "dashboard",
    },
    {
        path: "/panel/users",
        label: "کاربران پنل",
        description: "مدیریت مدیران و اعضای دارای دسترسی به پنل",
        icon: UserRoundCog,
        roles: ["salon_owner"],
    },
    {
        path: "/panel/customers",
        label: "مشتریان",
        description: "فضایی برای شناخت بهتر و همراهی با مشتریان",
        icon: UsersRound,
        roles: ["salon_owner"],
    },
    {
        path: "/panel/employees",
        label: "کارکنان",
        description: "مدیریت اعضای تیم و تخصص‌های آن‌ها",
        icon: UserRound,
        roles: ["salon_owner"],
    },
    {
        path: "/panel/service-categories",
        label: "دسته‌بندی خدمات",
        description: "نظم بیشتر برای خدمات سالن",
        icon: Shapes,
        roles: ["salon_owner", "employee"],
        permission: "service_categories",
    },
    {
        path: "/panel/services",
        label: "خدمات",
        description: "معرفی و مدیریت خدمات زیبایی سالن",
        icon: Scissors,
        roles: ["salon_owner", "employee"],
        permission: "services",
    },
    {
        path: "/panel/appointments",
        label: "نوبت‌ها",
        description: "برنامه‌ریزی روزهای کاری سالن",
        icon: CalendarDays,
        roles: ["salon_owner", "employee"],
        permission: "appointments",
    },
    {
        path: "/panel/products",
        label: "محصولات",
        description: "فضای معرفی محصولات مراقبت و زیبایی",
        icon: Package,
        roles: ["salon_owner", "employee"],
        permission: "products",
    },
    {
        path: "/panel/reports",
        label: "گزارش‌ها",
        description: "نگاهی روشن‌تر به عملکرد سالن",
        icon: ChartNoAxesCombined,
        roles: ["salon_owner", "employee"],
        permission: "reports",
    },
    {
        path: "/panel/profile",
        label: "پروفایل من",
        description: "ویرایش اطلاعات و تصویر حساب کاربری",
        icon: CircleUserRound,
        roles: ["salon_owner", "employee"],
    },
    {
        path: "/panel/settings",
        label: "تنظیمات",
        description: "تنظیم فضای کاری متناسب با سالن شما",
        icon: Settings,
        roles: ["salon_owner"],
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
