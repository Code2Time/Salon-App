export type SalonRole = "salon_owner" | "employee" | "customer";
export type PanelPermission = "dashboard" | "service_categories" | "manage_service_categories" | "services" | "manage_services" | "appointments" | "products" | "reports";

export interface Membership {
    salon_id: number;
    salon_name: string;
    role: SalonRole;
    permissions: PanelPermission[];
}

export interface AuthUser {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    birth_date: string | null;
    email: string | null;
    mobile: string;
    image_url: string | null;
    system_role: "super_admin" | null;
    memberships: Membership[];
    abilities: {
        access_panel: boolean;
        access_customer_app: boolean;
    };
    home_path: "/panel" | "/app";
    profile_completed: boolean;
}

export type AuthMode = "login" | "register";
