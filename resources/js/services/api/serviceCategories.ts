import type {
    CategoryDeletionImpact,
    PaginatedServiceCategories,
    ServiceCategory,
    ServiceCategoryInput,
} from "../../types/serviceCategory";
import { api, initializeCsrf } from "./client";

export interface ServiceCategoryFilters {
    search?: string;
    status?: string;
    featured?: boolean;
    page?: number;
}

export async function fetchServiceCategories(filters: ServiceCategoryFilters): Promise<PaginatedServiceCategories> {
    const params = {
        ...filters,
        ...(filters.featured === undefined ? {} : { featured: filters.featured ? 1 : 0 }),
    };
    const { data } = await api.get<PaginatedServiceCategories>("/panel/service-categories", { params });
    return data;
}

export async function fetchServiceCategory(id: number): Promise<ServiceCategory> {
    const { data } = await api.get<{ data: ServiceCategory }>(`/panel/service-categories/${id}`);
    return data.data;
}

export async function createServiceCategory(input: ServiceCategoryInput): Promise<ServiceCategory> {
    await initializeCsrf();
    const { data } = await api.post<{ data: ServiceCategory }>("/panel/service-categories", toFormData(input));
    return data.data;
}

export async function updateServiceCategory(id: number, input: ServiceCategoryInput): Promise<ServiceCategory> {
    await initializeCsrf();
    const { data } = await api.post<{ data: ServiceCategory }>(`/panel/service-categories/${id}`, toFormData(input));
    return data.data;
}

export async function fetchCategoryDeletionImpact(id: number): Promise<CategoryDeletionImpact> {
    const { data } = await api.get<{ data: CategoryDeletionImpact }>(`/panel/service-categories/${id}/deletion-impact`);
    return data.data;
}

export async function deleteServiceCategory(id: number): Promise<void> {
    await initializeCsrf();
    await api.delete(`/panel/service-categories/${id}`);
}

function toFormData(input: ServiceCategoryInput): FormData {
    const data = new FormData();
    data.append("name", input.name);
    if (input.description) data.append("description", input.description);
    data.append("is_featured", input.is_featured ? "1" : "0");
    data.append("is_active", input.is_active ? "1" : "0");
    data.append("sort_order", String(input.sort_order));
    if (input.image) data.append("image", input.image);
    return data;
}
