import type { PanelPermission, SalonRole } from "./auth";

export interface PanelUser {
    id: number;
    first_name: string | null;
    last_name: string | null;
    name: string;
    mobile: string;
    email: string | null;
    image_url: string | null;
    role: SalonRole;
    permissions: PanelPermission[];
    is_active: boolean;
    created_at: string;
}

export interface PanelUserInput {
    first_name: string;
    last_name: string;
    mobile?: string;
    email: string | null;
    role: SalonRole;
    permissions: PanelPermission[];
    is_active: boolean;
    image?: File;
}

export interface PaginatedPanelUsers {
    data: PanelUser[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
