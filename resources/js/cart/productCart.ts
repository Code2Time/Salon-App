import type { CatalogProduct } from "../types/product";

export interface CartLine { product: Pick<CatalogProduct, "id" | "name" | "price" | "stock" | "image_url">; quantity: number }
export interface Cart { salon_id: number; lines: CartLine[]; notes: string; request_id: string; discount_code?: string }
export const storageKey = "ara-product-cart-v1";

export function uuid(): string {
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, (c) => (Number(c) ^ (crypto.getRandomValues(new Uint8Array(1))[0] ?? 0) & 15 >> Number(c) / 4).toString(16));
}
export function readCart(): Cart {
    try {
        const value = JSON.parse(sessionStorage.getItem(storageKey) || "null") as Cart | null;
        if (value && (value.discount_code === undefined || (typeof value.discount_code === "string" && value.discount_code.length <= 40)) && Array.isArray(value.lines) && value.lines.length <= 20 && typeof value.notes === "string" && typeof value.request_id === "string" && Number.isInteger(value.salon_id)
            && value.lines.every((line) => line.product && Number.isInteger(line.product.id) && Number.isInteger(line.quantity) && line.quantity > 0 && line.quantity <= 99 && Number.isFinite(line.product.price))) return value;
    } catch { /* A cart can still be used when session storage is unavailable. */ }
    return { salon_id: 0, lines: [], notes: "", request_id: uuid() };
}
