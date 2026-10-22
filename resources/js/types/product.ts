export interface ProductServiceRecommendation {
    service_id: number;
    service_name: string;
    recommendation_text: string;
    recommendation_enabled: boolean;
    discount_enabled: boolean;
    discount_percent: number;
}

export interface ProductDraft {
    id: number;
    name: string;
    description: string;
    price: number;
    stock: number;
    is_active: boolean;
    image_url: string | null;
    image_file?: File | null;
    remove_image?: boolean;
    recommendations: ProductServiceRecommendation[];
}

export interface ProductServiceOption {
    id: number;
    name: string;
    is_active: boolean;
}

export type CatalogProduct = Omit<ProductDraft, "recommendations" | "image_file" | "remove_image">;

export interface ProductPage<T = ProductDraft> {
    data: T[];
    meta: { total: number; last_page: number; current_page: number };
    salon?: { id: number; name: string };
}

export interface ProductOrder {
    id: number;
    salon_id: number;
    status: "registered";
    payment_status: "unpaid";
    total_amount: number;
    subtotal_amount: number;
    discount_amount: number;
    discount_code: string | null;
    customer_name: string;
    customer_mobile: string;
    notes: string | null;
    created_at: string;
    items: {
        id: number; product_id: number; product_name: string; unit_price: number; quantity: number; total_amount: number; discount_amount: number; discount_percent: number;
        delivery_status: "pending" | "delivered"; delivered_at: string | null;
        payment_status: "unpaid" | "partial" | "paid"; payment_method: "cash" | "card" | "transfer" | "other" | null;
        amount_received: number; receipt_url: string | null;
    }[];
}

export interface OrderPage {
    data: ProductOrder[];
    meta: { total: number; last_page: number; current_page: number };
}

export interface AftercareRecommendation {
    id: number;
    salon_id: number;
    salon_name: string;
    appointment_id: number | null;
    care_instructions: { service_name: string; text: string }[];
    product: CatalogProduct;
    discount_code: string | null;
    discount_percent: number;
    discount_available: boolean;
    discounted_price: number;
    expires_at: string | null;
    redeemed_at: string | null;
    created_at: string;
}
export interface OrderQuote {
    subtotal_amount: number; discount_amount: number; total_amount: number;
    discount_code: string | null; discount_percent: number; product_id: number | null;
}
