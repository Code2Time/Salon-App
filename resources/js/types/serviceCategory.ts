export interface ServiceCategory {
    id: number;
    name: string;
    description: string | null;
    image_url: string | null;
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
    services_count: number;
    services?: CategoryServiceSummary[];
    created_at: string;
}

export interface CategoryServiceSummary {
    id: number;
    name: string;
    price: number;
    duration_minutes: number;
    is_active: boolean;
}

export interface ServiceCategoryInput {
    name: string;
    description: string | null;
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
    image?: File;
}

export interface CategoryDeletionImpact {
    category: { id: number; name: string };
    services: Array<{ id: number; name: string; will_be_deleted: boolean }>;
}

export interface PaginatedServiceCategories {
    data: ServiceCategory[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
