import type { AuthMode, AuthUser } from "../../types/auth";
import { api, initializeCsrf } from "./client";

interface UserResponse {
    data: AuthUser;
}

interface AuthResponse {
    message: string;
    user: AuthUser;
}

export async function requestOtp(mobile: string): Promise<void> {
    await initializeCsrf();
    await api.post("/auth/otp/request", { mobile });
}

export async function verifyOtp(input: {
    mobile: string;
    code: string;
    mode: AuthMode;
    first_name?: string;
    last_name?: string;
}): Promise<AuthUser> {
    const { data } = await api.post<AuthResponse>("/auth/otp/verify", input);
    return data.user;
}

export async function fetchCurrentUser(): Promise<AuthUser> {
    const { data } = await api.get<UserResponse>("/auth/me");
    return data.data;
}

export async function completeOnboarding(): Promise<AuthUser> {
    await initializeCsrf();
    const { data } = await api.post<UserResponse>("/auth/onboarding/complete");
    return data.data;
}

export async function updateOnboardingTestState(isFirstLogin: boolean): Promise<AuthUser> {
    await initializeCsrf();
    const { data } = await api.put<UserResponse>("/auth/onboarding/test-state", { is_first_login: isFirstLogin });
    return data.data;
}

export async function logout(): Promise<void> {
    await api.post("/auth/logout");
}
