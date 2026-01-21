import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;

  // Holds the current user details
  currentUser: any = {};

  constructor(private http: HttpClient) {
    this.getUserInfo();
  }

  loginByPassword(email: string, password: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/login_by_password`, {
      email,
      password
    }, { withCredentials: true }).pipe(
      tap((res: any) => {
        if (res && res.user) {
          this.setSession(res.user);
        }
      })
    );
  }

  loginByOtp(email: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/login_by_otp`, {
      email
    }, { withCredentials: true });
  }

  verifyOtp(email: string, otp: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/verify_otp`, {
      email,
      otp
    }, { withCredentials: true }).pipe(
      tap((res: any) => {
        if (res && res.user) {
          this.setSession(res.user);
        }
      })
    );
  }

  checkSessions(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/check_sessions`, {}, { withCredentials: true });
  }

  getUserInfo(): any {
    this.http.post(`${this.apiUrl}/user/info`, {}, { withCredentials: true }).subscribe({
      next: (res: any) => {
        if (res) {
          this.setSession(res);
        }
      }
    })
  }

  deleteSession(sessionId: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/delete_session`, {
      session_id: sessionId
    }, { withCredentials: true });
  }

  logout() {
    const user = this.currentUser;
    if (user && user.current_session_id) {
      this.deleteSession(user.current_session_id).subscribe({
        next: () => console.log('Session deleted from server'),
        error: (err) => console.error('Failed to notify server of logout', err)
      });
    }

    this.currentUser = {};
    sessionStorage.removeItem('currentUser');

  }

  private setSession(user: any) {

    this.currentUser = user;
    sessionStorage.setItem('currentUser', JSON.stringify(user));
  }
}
