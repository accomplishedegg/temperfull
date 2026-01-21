import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '../services/user.service';
import { NavbarComponent } from '../navbar/navbar.component';

@Component({
    selector: 'app-user-subscriptions',
    standalone: true,
    imports: [CommonModule, NavbarComponent],
    templateUrl: './user-subscriptions.component.html',
})
export class UserSubscriptionsComponent implements OnInit {
    private userService = inject(UserService);

    mySubscriptions = signal<any[]>([]);
    publicPlans = signal<any[]>([]);

    ngOnInit() {
        this.loadMySubscriptions();
        this.loadPublicPlans();
    }

    loadMySubscriptions() {
        this.userService.getMySubscriptions().subscribe({
            next: (res: any) => {
                if (res.body) {
                    this.mySubscriptions.set(res.body);
                } else if (Array.isArray(res)) {
                    this.mySubscriptions.set(res);
                }
            },
            error: (err) => console.error('Failed to load my subscriptions', err)
        });
    }

    loadPublicPlans() {
        this.userService.getPublicPlans().subscribe({
            next: (res: any) => {
                if (res.body) {
                    this.publicPlans.set(res.body);
                } else if (Array.isArray(res)) {
                    this.publicPlans.set(res);
                }
            },
            error: (err) => console.error('Failed to load public plans', err)
        });
    }
}
