import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgbModal, NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { UserService } from '../services/user.service';
import { NavbarComponent } from '../navbar/navbar.component';
import { AuthService } from '../services/auth';

@Component({
    selector: 'app-manage-subscription-types',
    standalone: true,
    imports: [CommonModule, FormsModule, NgbModule, NavbarComponent],
    templateUrl: './manage-subscription-types.html',
})
export class ManageSubscriptionTypesComponent implements OnInit {
    private userService = inject(UserService);
    private modalService = inject(NgbModal);
    public auth = inject(AuthService);

    plans = signal<any[]>([]);

    // Plan Form
    editingPlan: any = { name: '', price: 0, number_of_days: 30, is_active: 1 };
    isEditMode = false;

    ngOnInit() {
        this.loadPlans();
    }

    loadPlans() {
        this.userService.getPlans().subscribe({
            next: (res) => {
                if (res.data) this.plans.set(res.data);
            },
            error: (err) => console.error('Failed to load plans', err)
        });
    }

    openAddModal(content: any) {
        this.isEditMode = false;
        this.editingPlan = { name: '', price: 0, number_of_days: 30, is_active: 1 };
        this.modalService.open(content, { centered: true });
    }

    openEditModal(content: any, plan: any) {
        this.isEditMode = true;
        this.editingPlan = { ...plan }; // Deep copy if needed, but shallow is ok for simple props
        this.modalService.open(content, { centered: true });
    }

    savePlan(modal: any) {
        if (this.isEditMode) {
            this.userService.updatePlan(this.editingPlan.id, this.editingPlan).subscribe({
                next: () => {
                    this.loadPlans();
                    modal.close();
                },
                error: (err) => alert(err.error?.message || 'Failed to update plan')
            });
        } else {
            this.userService.createPlan(this.editingPlan).subscribe({
                next: () => {
                    this.loadPlans();
                    modal.close();
                },
                error: (err) => alert(err.error?.message || 'Failed to create plan')
            });
        }
    }

}

