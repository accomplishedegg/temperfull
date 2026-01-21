import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgbModal, NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { LeadService } from '../services/lead.service';
import { UserService } from '../services/user.service';
import { NavbarComponent } from '../navbar/navbar.component';

@Component({
    selector: 'app-manage-leads',
    standalone: true,
    imports: [CommonModule, FormsModule, NgbModule, NavbarComponent],
    templateUrl: './manage-leads.html',
    styles: [`
    .badge-approved { background-color: #d4edda; color: #155724; }
    .badge-rejected { background-color: #f8d7da; color: #721c24; }
    .badge-pending { background-color: #fff3cd; color: #856404; }
  `]
})
export class ManageLeadsComponent implements OnInit {
    private leadService = inject(LeadService);
    private userService = inject(UserService);
    private modalService = inject(NgbModal);

    leads = signal<any[]>([]);
    plans = signal<any[]>([]);

    // Pagination & Search
    page = 1;
    pageSize = 10;
    total = 0;
    searchQuery = '';

    // New Lead Form
    newLead: any = {
        name: '',
        email: '',
        phone_number: '',
        subscription_id: null,
        start_date: ''
    };

    ngOnInit() {
        this.loadLeads();
        this.loadPlans();
    }

    loadLeads() {
        this.leadService.getLeads(this.page, this.pageSize, this.searchQuery).subscribe({
            next: (res: any) => {
                if (res.data) {
                    this.leads.set(res.data);
                    this.total = res.meta.total;
                }
            },
            error: (err) => console.error('Failed to load leads', err)
        });
    }

    loadPlans() {
        this.userService.getPlans().subscribe({
            next: (res) => {
                if (res.data) this.plans.set(res.data);
            }
        });
    }

    onPageChange(page: number) {
        this.page = page;
        this.loadLeads();
    }

    onSearch() {
        this.page = 1;
        this.loadLeads();
    }

    openAddModal(content: any) {
        const today = new Date().toISOString().split('T')[0];
        this.newLead = { name: '', email: '', phone_number: '', subscription_id: null, start_date: today };
        this.modalService.open(content, { centered: true });
    }

    saveLead(modal: any) {
        this.leadService.createLead(this.newLead).subscribe({
            next: () => {
                this.loadLeads();
                modal.close();
            },
            error: (err) => alert(err.error?.message || 'Failed to create lead')
        });
    }

    processLead(id: number, status: 'approved' | 'rejected') {
        if (!confirm(`Are you sure you want to ${status} this lead?`)) return;

        this.leadService.processLead(id, status).subscribe({
            next: () => {
                this.loadLeads();
            },
            error: (err) => alert(err.error?.message || `Failed to ${status} lead`)
        });
    }

    getPlanName(id: number) {
        const plan = this.plans().find(p => p.id == id);
        return plan ? plan.name : '-';
    }
}
