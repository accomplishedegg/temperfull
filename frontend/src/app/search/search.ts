import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgbPaginationModule } from '@ng-bootstrap/ng-bootstrap';
import { SearchService } from '../services/search.service';
import { NavbarComponent } from '../navbar/navbar.component';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';

@Component({
    selector: 'app-search',
    standalone: true,
    imports: [CommonModule, FormsModule, NgbPaginationModule, NavbarComponent],
    templateUrl: './search.html',
    styleUrls: ['./search.css'] // Added for dropdown styles
})
export class SearchComponent {
    private searchService = inject(SearchService);

    query = '';
    results = signal<any[]>([]);
    selectedTemper = signal<any>(null);
    showDropdown = false;

    private searchSubject = new Subject<string>();

    constructor() {
        this.searchSubject.pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap((query) => {
                if (!query.trim()) {
                    this.showDropdown = false;
                    return [];
                }
                return this.searchService.search(query);
            })
        ).subscribe({
            next: (res: any) => {
                if (res.body) {
                    this.results.set(res.body);
                } else if (Array.isArray(res)) {
                    this.results.set(res);
                } else {
                    this.results.set([]);
                }
                this.showDropdown = this.results().length > 0;
            },
            error: (err) => {
                console.error('Search failed', err);
                this.results.set([]);
                this.showDropdown = false;
            }
        });
    }

    onSearchInput() {
        this.searchSubject.next(this.query);
    }

    selectTemper(item: any) {
        this.query = item.name; // OR item.id? User likely wants to see name.
        this.showDropdown = false;

        this.searchService.getTemperDetails(item.id).subscribe({
            next: (res: any) => {
                this.selectedTemper.set(res.body || res);
            },
            error: (err) => console.error('Failed to load details', err)
        });
    }

    // Optional: Hide dropdown on blur (needs timeout to allow click)
    onBlur() {
        setTimeout(() => this.showDropdown = false, 200);
    }
}
