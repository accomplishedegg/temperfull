import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UserService, User } from '../services/user.service';
import { NgbModule, NgbModal } from '@ng-bootstrap/ng-bootstrap';

import { NavbarComponent } from '../navbar/navbar.component';
@Component({
  selector: 'app-manage-users',
  standalone: true,
  imports: [CommonModule, FormsModule, NgbModule, NavbarComponent],
  templateUrl: './manage-users.html',
  styleUrl: './manage-users.css'
})
export class ManageUsersComponent {
  users = signal<User[]>([]);
  total = signal(0);
  page = signal(1);
  pageSize = signal(10);
  searchQuery = signal('');

  loading = signal(false);
  errorMessage = signal('');

  // Modal State
  selectedUser: Partial<User> = {};
  isEditMode = false;
  modalTitle = 'Add User';

  roles = ['admin', 'sales_manager', 'user'];

  constructor(private userService: UserService, private modalService: NgbModal) {
    this.loadUsers();
  }

  loadUsers() {
    this.loading.set(true);
    this.errorMessage.set('');
    this.userService.getUsers(this.page(), this.pageSize(), this.searchQuery()).subscribe({
      next: (res) => {
        // Backend returns flattened { data: [], meta: {} } check from TemperService experience
        // But wait, UserService uses /admin/crud which returns { code: 200, body: { data: ..., meta: ... } } according to admin.php!
        // Temper endpoint was custom. Admin CRUD is generic.
        // Let's re-read admin.php to be SURE about response structure.
        // admin.php: return ['code' => 200, 'body' => ['data' => ..., 'meta' => ...]]
        // AND index.php unwraps response['body'].
        // So client receives: { data: ..., meta: ... }
        // YES. index.php line 101: echo json_encode($response['body']);
        // So it IS flattened.

        if (res.data) {
          this.users.set(res.data);
          this.total.set(res.meta.total);
        } else {
          this.errorMessage.set('Failed to load users: Invalid response format');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('GetUsers Error:', err);
        this.errorMessage.set('Error loading users');
        this.loading.set(false);
      }
    });
  }

  search() {
    this.page.set(1);
    this.loadUsers();
  }

  onPageChange(page: number) {
    this.page.set(page);
    this.loadUsers();
  }

  openModal(content: any, user?: User) {
    this.errorMessage.set('');
    if (user) {
      this.isEditMode = true;
      this.modalTitle = 'Edit User';
      this.selectedUser = { ...user, password: '' }; // Don't carry over password hash if any
    } else {
      this.isEditMode = false;
      this.modalTitle = 'Add User';
      this.selectedUser = {
        name: '',
        email: '',
        phone_number: '',
        role: 'user',
        is_active: true,
        password: ''
      };
    }
    this.modalService.open(content, { centered: true, size: 'xl' });
  }

  saveUser(modal: any) {
    this.loading.set(true);

    // Logic:
    // If Edit, update.
    // If Add, create.

    if (this.isEditMode && this.selectedUser.id) {
      // If password empty, remove it? 
      // Admin.php handles empty password logic or frontend should send only if changed?
      // UserService sends Partial<User>.
      // Let's send what we have. API should handle.

      this.userService.updateUser(this.selectedUser.id, this.selectedUser).subscribe({
        next: (res) => {
          this.loadUsers();
          modal.close();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to update user');
        }
      });
    } else {
      this.userService.addUser(this.selectedUser).subscribe({
        next: (res) => {
          this.loadUsers();
          modal.close();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to create user');
        }
      });
    }
  }

  deleteUser(id: number) {
    if (confirm('Are you sure you want to delete this user?')) {
      this.loading.set(true);
      this.userService.deleteUser(id).subscribe({
        next: (res) => {
          this.loadUsers();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to delete user');
        }
      });
    }
  }

  // --- Subscription Management ---
  activeTab = 'details'; // 'details' | 'subscriptions' | 'sessions' | 'otps' | 'logs'
  userSubscriptions = signal<any[]>([]);
  plans = signal<any[]>([]);

  userSessions = signal<any[]>([]);
  userOtps = signal<any[]>([]);
  userLogs = signal<any[]>([]);

  // Inline Add/Edit Subscription State
  editingSubscription: any = {}; // id, subscription_id, start_date, end_date, is_active
  isSubsEditMode = false;

  setActiveTab(tab: string) {
    this.activeTab = tab;
    if (!this.selectedUser.id) return;

    if (tab === 'subscriptions') {
      this.loadPlans();
      this.loadUserSubscriptions(this.selectedUser.id);
      this.resetSubscriptionForm();
    } else if (tab === 'sessions') {
      this.loadUserSessions(this.selectedUser.id);
    } else if (tab === 'otps') {
      this.loadUserOtps(this.selectedUser.id);
    } else if (tab === 'logs') {
      this.loadUserLogs(this.selectedUser.id);
    }
  }

  loadUserSessions(userId: number) {
    this.userService.getUserSessions(userId).subscribe({
      next: (res) => this.userSessions.set(res.data || [])
    });
  }

  loadUserOtps(userId: number) {
    this.userService.getUserOtps(userId).subscribe({
      next: (res) => this.userOtps.set(res.data || [])
    });
  }

  loadUserLogs(userId: number) {
    this.userService.getUserLogs(userId).subscribe({
      next: (res) => this.userLogs.set(res.data || [])
    });
  }

  loadPlans() {
    // Cache plans?
    if (this.plans().length > 0) return;
    this.userService.getPlans().subscribe({
      next: (res) => {
        if (res.data) {
          this.plans.set(res.data);
        }
      }
    });
  }

  loadUserSubscriptions(userId: number) {
    this.userService.getUserSubscriptions(userId).subscribe({
      next: (res) => {
        if (res.data) {
          // Map plan names
          const subs = res.data.map((sub: any) => {
            const plan = this.plans().find(p => p.id == sub.subscription_id);
            return { ...sub, plan_name: plan ? plan.name : 'Unknown Plan' };
          });
          this.userSubscriptions.set(subs);
        }
      }
    });
  }

  resetSubscriptionForm() {
    this.isSubsEditMode = false;
    this.editingSubscription = {
      user_id: this.selectedUser.id,
      subscription_id: '',
      start_date: new Date().toISOString().split('T')[0],
      end_date: '',
      is_active: 1
    };
  }

  editSubscription(sub: any) {
    this.isSubsEditMode = true;
    this.editingSubscription = { ...sub };
  }

  onSubscriptionPlanChange() {
    if (!this.editingSubscription.subscription_id || !this.editingSubscription.start_date) return;

    const plan = this.plans().find(p => p.id == this.editingSubscription.subscription_id);
    if (plan && plan.number_of_days) {
      const startDate = new Date(this.editingSubscription.start_date);
      const endDate = new Date(startDate);
      endDate.setDate(startDate.getDate() + parseInt(plan.number_of_days));
      this.editingSubscription.end_date = endDate.toISOString().split('T')[0];
    }
  }

  saveSubscription() {
    if (!this.editingSubscription.subscription_id || !this.editingSubscription.start_date || !this.editingSubscription.end_date) {
      alert('Please fill all fields');
      return;
    }

    const payload = { ...this.editingSubscription, user_id: this.selectedUser.id };

    if (this.isSubsEditMode) {
      this.userService.updateUserSubscription(payload.id, payload).subscribe({
        next: () => {
          this.loadUserSubscriptions(this.selectedUser.id!);
          this.resetSubscriptionForm();
        },
        error: (err) => alert('Failed to update subscription')
      });
    } else {
      this.userService.addUserSubscription(payload).subscribe({
        next: () => {
          this.loadUserSubscriptions(this.selectedUser.id!);
          this.resetSubscriptionForm();
        },
        error: (err) => alert('Failed to add subscription')
      });
    }
  }

  deleteSubscription(id: number) {
    if (confirm('Delete this subscription?')) {
      this.userService.deleteUserSubscription(id).subscribe({
        next: () => this.loadUserSubscriptions(this.selectedUser.id!),
        error: (err) => alert('Failed to delete')
      });
    }
  }
}
