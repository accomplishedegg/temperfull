import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

export interface User {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    role: 'admin' | 'sales_manager' | 'user';
    is_active: number | boolean;
    password?: string; // For creating/updating
    created_at?: string;
}

export interface UserListResponse {
    data: User[];
    meta: {
        total: number;
        page: number;
        limit: number;
    };
}

@Injectable({
    providedIn: 'root'
})
export class UserService {
    private apiUrl = environment.apiUrl;

    constructor(private http: HttpClient) { }

    getUsers(page: number = 1, limit: number = 10, search: string = ''): Observable<UserListResponse> {
        return this.http.post<UserListResponse>(`${this.apiUrl}/admin/crud?model=User&action=list`, {
            page,
            limit,
            q: search
        }, { withCredentials: true });
    }

    addUser(data: Partial<User>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=User&action=create`, {
            data
        }, { withCredentials: true });
    }

    updateUser(id: number, data: Partial<User>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=User&action=update`, {
            id,
            data
        }, { withCredentials: true });
    }

    deleteUser(id: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=User&action=delete`, {
            id
        }, { withCredentials: true });
    }

    // --- Subscriptions (Plans) ---
    getPlans(): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=Subscription&action=list`, {
            page: 1, limit: 100 // Fetch all plans
        }, { withCredentials: true });
    }

    createPlan(data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=Subscription&action=create`, {
            data
        }, { withCredentials: true });
    }

    updatePlan(id: number, data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=Subscription&action=update`, {
            id,
            data
        }, { withCredentials: true });
    }

    deletePlan(id: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=Subscription&action=delete`, {
            id
        }, { withCredentials: true });
    }

    // --- User Subscriptions ---
    getUserSubscriptions(userId: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserSubscription&action=list`, {
            filters: { user_id: userId },
            page: 1, limit: 20
        }, { withCredentials: true });
    }

    addUserSubscription(data: Partial<UserSubscription>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserSubscription&action=create`, {
            data
        }, { withCredentials: true });
    }

    updateUserSubscription(id: number, data: Partial<UserSubscription>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserSubscription&action=update`, {
            id,
            data
        }, { withCredentials: true });
    }

    deleteUserSubscription(id: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserSubscription&action=delete`, {
            id
        }, { withCredentials: true });
    }

    // New methods for User Side
    getMySubscriptions(): Observable<any> {
        return this.http.post(`${this.apiUrl}/user/manage_subscriptions`, {}, { withCredentials: true });
    }

    getPublicPlans(): Observable<any> {
        return this.http.post(`${this.apiUrl}/public/plans`, {}, { withCredentials: true });
    }

    // --- User Activity ---
    getUserSessions(userId: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserSession&action=list`, {
            filters: { user_id: userId },
            page: 1, limit: 20,
            sort: { id: 'DESC' }
        }, { withCredentials: true });
    }

    getUserOtps(userId: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserOtp&action=list`, {
            filters: { user_id: userId },
            page: 1, limit: 20,
            sort: { id: 'DESC' }
        }, { withCredentials: true });
    }

    getUserLogs(userId: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/crud?model=UserLog&action=list`, {
            filters: { user_id: userId },
            page: 1, limit: 20,
            sort: { id: 'DESC' }
        }, { withCredentials: true });
    }
}

export interface Subscription {
    id: number;
    name: string;
    price: number;
    number_of_days: number;
    is_active: number | boolean;
}

export interface UserSubscription {
    id: number;
    user_id: number;
    subscription_id: number;
    start_date: string;
    end_date: string;
    is_active: number | boolean;
    // Helper property for UI
    plan_name?: string;
}
