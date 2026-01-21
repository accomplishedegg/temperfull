import { Component, signal, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TemperService, Temper } from '../services/temper.service';
import { NgbModule, NgbModal } from '@ng-bootstrap/ng-bootstrap';

import { NavbarComponent } from '../navbar/navbar.component';
@Component({
  selector: 'app-manage-temper',
  standalone: true,
  imports: [CommonModule, FormsModule, NgbModule, NavbarComponent],
  templateUrl: './manage-temper.html',
  styleUrl: './manage-temper.css'
})
export class ManageTemperComponent {
  tempers = signal<Temper[]>([]);
  total = signal(0);
  page = signal(1);
  pageSize = signal(10);
  searchQuery = signal('');

  loading = signal(false);
  errorMessage = signal('');

  // Modal State
  selectedTemper: Partial<Temper> = {};
  isEditMode = false;
  modalTitle = 'Add Temper';

  constructor(private temperService: TemperService, private modalService: NgbModal) {
    this.loadTempers();
  }

  loadTempers() {
    this.loading.set(true);
    this.temperService.getTempers(this.page(), this.pageSize(), this.searchQuery()).subscribe({
      next: (res) => {
        // Backend returns body directly: { data: [...], meta: ... }
        if (res.data) {
          this.tempers.set(res.data);
          this.total.set(res.meta.total);
        } else {
          this.errorMessage.set('Failed to load tempers: Invalid response');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('GetTempers Error:', err);
        this.errorMessage.set('Error loading tempers');
        this.loading.set(false);
      }
    });
  }

  search() {
    this.page.set(1);
    this.loadTempers();
  }

  onPageChange(page: number) {
    this.page.set(page);
    this.loadTempers();
  }

  openModal(content: any, temper?: Temper) {
    this.errorMessage.set('');
    if (temper) {
      this.isEditMode = true;
      this.modalTitle = 'Edit Temper';
      // Deep copy or spread to avoid live editing table
      this.selectedTemper = { ...temper, supportedPhones: [...(temper.supportedPhones || [])] };
    } else {
      this.isEditMode = false;
      this.modalTitle = 'Add Temper';
      this.selectedTemper = { name: '', is_active: true, supportedPhones: [] };
    }
    this.modalService.open(content, { centered: true });
  }

  saveTemper(modal: any) {
    this.loading.set(true);

    // Prepare payload
    // Backend expects data object
    // Supported phones needs to be array of strings.
    // If user entered them as / separated string in basic UI?
    // Let's assume for now a simple name entry, and phones logic handles adding name itself.
    // Ideally UI has chips input for phones. For MVP I'll just save name and is_active.
    // If I want to support phones input I need a UI for it.
    // I will add a textarea for supported phones (/ separated) for simplicity.

    // Convert / string to array if we used that in UI, or assume it's already array if using chips.
    // For simplicity:
    // this.selectedTemper.supportedPhones = this.selectedTemper.supportedPhones (as array)

    if (this.isEditMode && this.selectedTemper.id) {
      this.temperService.updateTemper(this.selectedTemper.id, this.selectedTemper).subscribe({
        next: (res) => {
          this.loadTempers();
          modal.close();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to update');
        }
      });
    } else {
      this.temperService.addTemper(this.selectedTemper).subscribe({
        next: (res) => {
          this.loadTempers();
          modal.close();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to create');
        }
      });
    }
  }

  deleteTemper(id: number) {
    if (confirm('Are you sure you want to delete this temper?')) {
      this.loading.set(true);
      this.temperService.deleteTemper(id).subscribe({
        next: (res) => {
          this.loadTempers();
        },
        error: (err) => {
          console.error(err);
          this.loading.set(false);
          alert('Failed to delete');
        }
      });
    }
  }

  // Helper for Supported Phones String (/ separated)
  get phonesString(): string {
    return (this.selectedTemper.supportedPhones || []).filter(phone => !phone.includes(this.selectedTemper.name || '')).join('/');
  }

  set phonesString(val: string) {
    this.selectedTemper.supportedPhones = val.split('/').map(s => s.trim()).filter(s => s.length > 0);
  }
}
