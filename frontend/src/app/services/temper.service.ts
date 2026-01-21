import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

export interface Temper {
    id: number;
    name: string;
    is_active: number | boolean;
    supportedPhones?: string[];
    created_at?: string;
}

export interface TemperListResponse {
    data: Temper[];
    meta: {
        total: number;
        page: number;
        limit: number;
    };
}

@Injectable({
    providedIn: 'root'
})
export class TemperService {
    private apiUrl = environment.apiUrl;

    constructor(private http: HttpClient) { }

    getTempers(page: number = 1, limit: number = 10, search: string = ''): Observable<TemperListResponse> {
        // Backend expects action, page, limit, q
        // Using simple URL construction or params
        // Note: ensure backend router handles this path structure or we use /temper?action=list...
        // The requirement was "based on backend/views/temper.php".
        // Usually mapped like /temper/list or query params.
        // Assuming /temper endpoint handles dispatch like auth.
        // Let's assume URL structure is `${this.apiUrl}/temper/list` based on previous patterns?
        // Actually backend dispatch in index.php usually routes /temper/list to action=list if configured.
        // Let's check typical router config.
        // Assuming backend router is set up. If not, I'll need to use query params on generic endpoint?
        // Previous auth setup: `${this.apiUrl}/auth/login_by_password`.
        // So likely `${this.apiUrl}/temper/list`.

        return this.http.post<TemperListResponse>(`${this.apiUrl}/admin/temper?action=list`, {
            page,
            limit,
            q: search
        }, { withCredentials: true });
    }

    addTemper(data: Partial<Temper>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/temper?action=create`, {
            data
        }, { withCredentials: true });
    }

    updateTemper(id: number, data: Partial<Temper>): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/temper?action=update`, {
            id,
            data
        }, { withCredentials: true });
    }

    deleteTemper(id: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/temper?action=delete`, {
            id
        }, { withCredentials: true });
    }
}
