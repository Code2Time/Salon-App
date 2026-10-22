import { api, initializeCsrf } from "./client";
import type { CatalogProduct, OrderPage, ProductDraft, ProductOrder, ProductPage, ProductServiceOption } from "../../types/product";

export async function fetchProducts(params: { page?: number; search?: string; status?: string; stock?: string } = {}): Promise<ProductPage> {
    const { data } = await api.get<ProductPage>("/panel/products", { params });
    return data;
}

export async function fetchProductServices(): Promise<ProductServiceOption[]> {
    const { data } = await api.get<{ data: ProductServiceOption[] }>("/panel/products/options");
    return data.data;
}

export async function saveProduct(product: ProductDraft): Promise<ProductDraft> {
    const form = new FormData();
    form.append("name", product.name);
    form.append("description", product.description);
    form.append("price", String(product.price));
    form.append("stock", String(product.stock));
    form.append("is_active", product.is_active ? "1" : "0");
    form.append("remove_image", product.remove_image ? "1" : "0");
    // JSON avoids losing empty arrays and preserves booleans in nested recommendations.
    form.append("recommendations_json", JSON.stringify(product.recommendations.map(({ service_name: _, ...link }) => link)));
    if (product.image_file) form.append("image", product.image_file);
    await initializeCsrf();
    const { data } = await api.post<{ data: ProductDraft }>(product.id ? `/panel/products/${product.id}` : "/panel/products", form);
    return data.data;
}

export async function deleteProduct(id: number): Promise<void> {
    await initializeCsrf();
    await api.delete(`/panel/products/${id}`);
}

export async function fetchCatalogProducts(params: { page?: number; search?: string } = {}): Promise<ProductPage<CatalogProduct>> {
    const { data } = await api.get<ProductPage<CatalogProduct>>("/catalog/products", { params });
    return data;
}

export interface PlaceOrderInput {
    request_id: string;
    discount_code?: string;
    salon_id: number;
    notes: string;
    items: { product_id: number; quantity: number; expected_unit_price: number }[];
}

export async function placeOrder(input: PlaceOrderInput): Promise<ProductOrder> {
    await initializeCsrf();
    const { data } = await api.post<{ data: ProductOrder }>("/orders", input);
    return data.data;
}

export async function fetchProductOrders(panel: boolean, page = 1, search = ""): Promise<OrderPage> {
    const { data } = await api.get<OrderPage>(panel ? "/panel/orders" : "/orders", { params: { page, search } });
    return data;
}

export async function fetchProductOrder(panel: boolean, id: string): Promise<ProductOrder> {
    const { data } = await api.get<{ data: ProductOrder }>(`${panel ? "/panel" : ""}/orders/${id}`);
    return data.data;
}

export async function saveOrderItemFulfillment(itemId: number, values: {
    delivery_status: "pending" | "delivered";
    payment_status: "unpaid" | "partial" | "paid";
    payment_method: "cash" | "card" | "transfer" | "other" | "";
    amount_received: number;
    receipt?: File | null;
    remove_receipt?: boolean;
}): Promise<ProductOrder> {
    const form = new FormData();
    form.append("delivery_status", values.delivery_status);
    form.append("payment_status", values.payment_status);
    form.append("payment_method", values.payment_method);
    form.append("amount_received", String(values.amount_received));
    form.append("remove_receipt", values.remove_receipt ? "1" : "0");
    if (values.receipt) form.append("receipt", values.receipt);
    await initializeCsrf();
    const { data } = await api.post<{ data: ProductOrder }>(`/panel/orders/items/${itemId}/fulfillment`, form);
    return data.data;
}

export async function fetchRecommendations(page = 1): Promise<{ data: import("../../types/product").AftercareRecommendation[]; meta: { last_page: number; total: number } }> {
    const { data } = await api.get("/recommendations", { params: { page } });
    return data;
}
export async function quoteOrder(input: PlaceOrderInput): Promise<import("../../types/product").OrderQuote> {
    await initializeCsrf();
    const { data } = await api.post("/orders/quote", input);
    return data.data;
}
