import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-admin-dashboard-list',
  standalone: true,
  imports: [CommonModule, MatCardModule, MatButtonModule, MatIconModule],
  template: `
    <div class="page-header">
      <h1>Dashboards</h1>
      <button mat-raised-button color="primary">
        <mat-icon>add</mat-icon>
        New Dashboard
      </button>
    </div>
    <mat-card>
      <mat-card-content>
        <p>Dashboard list will be implemented here.</p>
        <p>This will show all dashboards with options to create, edit, and delete them.</p>
      </mat-card-content>
    </mat-card>
  `,
  styles: [`
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    h1 {
      margin: 0;
    }

    button mat-icon {
      margin-right: 8px;
    }
  `]
})
export class AdminDashboardListComponent {}
