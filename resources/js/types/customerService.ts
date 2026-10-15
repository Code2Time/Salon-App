export interface CustomerServiceCategory {
    id: number;
    name: string;
    image_url: string | null;
    is_featured?: boolean;
    services_count?: number;
}

export interface CustomerServiceProvider {
    membership_id: number;
    name: string;
    image_url: string | null;
}

export interface CustomerService {
    id: number;
    name: string;
    description: string | null;
    price: number;
    duration_minutes: number;
    categories: CustomerServiceCategory[];
    providers: CustomerServiceProvider[];
}

export interface CustomerServiceFilters {
    search?: string;
    category_id?: number;
    sort?: "newest" | "price_asc" | "price_desc";
    page?: number;
    per_page?: number;
}

export interface CustomerServicePage {
    data: CustomerService[];
    salon: { id: number; name: string };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
