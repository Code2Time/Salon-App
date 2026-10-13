export interface ServiceCategoryOption {
    id: number;
    name: string;
    is_active: boolean;
    image_url?: string | null;
}

export interface ServiceProvider {
    membership_id: number;
    user_id: number;
    name: string;
    image_url?: string | null;
}

export interface SalonService {
    id: number;
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
    is_active: boolean;
    categories: ServiceCategoryOption[];
    providers: ServiceProvider[];
    categories_count: number;
    providers_count: number;
    created_at: string;
}

export interface ServiceInput {
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
    is_active: boolean;
    category_ids: number[];
    provider_membership_ids: number[];
}

export interface ServiceOptions {
    categories: ServiceCategoryOption[];
    providers: ServiceProvider[];
}

export interface ServiceFilters {
    search?: string;
    status?: string;
    category_id?: number;
    provider_membership_id?: number;
    page?: number;
}

export interface PaginatedServices {
    data: SalonService[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
