import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { Observable } from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class LeadService {
    private http = inject(HttpClient);
    private apiUrl = environment.apiUrl;

    getLeads(page: number = 1, limit: number = 10, q: string = ''): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/leads?action=list`, { page, limit, q }, { withCredentials: true });
    }

    createLead(data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/leads?action=create`, { data }, { withCredentials: true });
    }

    processLead(id: number, status: 'approved' | 'rejected'): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/leads?action=process`, { id, status }, { withCredentials: true });
    }

    deleteLead(id: number): Observable<any> {
        return this.http.post(`${this.apiUrl}/admin/leads?action=delete`, { id }, { withCredentials: true });
    }
}
