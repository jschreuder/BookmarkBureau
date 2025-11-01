import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';

@Component({
  selector: 'app-dashboard-form',
  standalone: true,
  imports: [CommonModule, MatCardModule],
  template: `
    <h1>Dashboard Form</h1>
    <mat-card>
      <mat-card-content>
        <p>Dashboard form will be implemented here for creating/editing dashboards.</p>
      </mat-card-content>
    </mat-card>
  `
})
export class DashboardFormComponent {}
