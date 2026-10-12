import { api } from "./client";
import type { CustomerServiceCategory, CustomerServiceFilters, CustomerServicePage } from "../../types/customerService";

export async function fetchCustomerServices(filters: CustomerServiceFilters): Promise<CustomerServicePage> {
    const { data } = await api.get<CustomerServicePage>("/catalog/services", { params: filters });
    return data;
}

export async function fetchCustomerServiceCategories(): Promise<CustomerServiceCategory[]> {
    const { data } = await api.get<{ data: CustomerServiceCategory[] }>("/catalog/service-categories");
    return data.data;
}
