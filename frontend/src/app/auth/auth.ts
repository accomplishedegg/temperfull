import { Component, signal, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../services/auth';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { Router } from '@angular/router';

@Component({
  selector: 'app-auth',
  standalone: true,
  imports: [CommonModule, FormsModule, NgbModule],
  templateUrl: './auth.html',
  styleUrl: './auth.css'
})
export class AuthComponent {
  email = signal('');
  password = signal('');
  otp = signal('');

  isOtpMode = signal(true);
  otpSent = signal(false);

  sessions = signal<any[]>([]);
  isBlocked = signal(false);

  errorMessage = signal('');
  loading = signal(false);

  constructor(private authService: AuthService, private router: Router) {
    // Check if user is already logged in
    if (this.authService.currentUser?.email) {
      this.checkSessions();
    }
  }

  toggleMode() {
    this.isOtpMode.update(v => !v);
    this.otpSent.set(false);
    this.errorMessage.set('');
  }

  handleLogin() {
    this.loading.set(true);
    this.errorMessage.set('');

    if (this.isOtpMode()) {
      if (!this.otpSent()) {
        this.sendOtp();
      } else {
        this.verifyOtp();
      }
    } else {
      this.loginByPassword();
    }
  }

  private loginByPassword() {
    this.authService.loginByPassword(this.email(), this.password()).subscribe({
      next: (res) => {
        this.checkSessions();
      },
      error: (err) => {
        this.errorMessage.set(err.error?.message || 'Login failed');
        this.loading.set(false);
      }
    });
  }

  private sendOtp() {
    this.authService.loginByOtp(this.email()).subscribe({
      next: (res) => {
        this.otpSent.set(true);
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMessage.set(err.error?.message || 'Failed to send OTP');
        this.loading.set(false);
      }
    });
  }

  private verifyOtp() {
    this.authService.verifyOtp(this.email(), this.otp()).subscribe({
      next: (res) => {
        this.checkSessions();
      },
      error: (err) => {
        this.errorMessage.set(err.error?.message || 'Invalid OTP');
        this.loading.set(false);
      }
    });
  }

  checkSessions() {
    this.authService.checkSessions().subscribe({
      next: (res) => {
        this.loading.set(false);
        const activeSessions = res.sessions || [];
        console.log('CheckSessions Response:', activeSessions);

        // Requirement: if more than 2 sessions block user
        if (activeSessions.length > 2) {
          console.log('Blocking user, count:', activeSessions.length);
          this.sessions.set(activeSessions);
          this.isBlocked.set(true);
        } else {
          console.log('Allowing user, count:', activeSessions.length, 'Navigating to Home');
          this.isBlocked.set(false);
          // Proceed to home
          this.router.navigate(['/home']);
        }
      },
      error: (err) => {
        console.error('CheckSessions Error:', err);
        this.errorMessage.set('Could not verify sessions');
        this.loading.set(false);
      }
    });
  }

  deleteSession(sessionId: any) {
    this.authService.deleteSession(sessionId).subscribe({
      next: (res) => {
        // Refresh session list
        this.checkSessions();
      },
      error: (err) => {
        alert('Failed to delete session');
      }
    });
  }
}
