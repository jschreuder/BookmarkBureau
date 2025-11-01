import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';

@Component({
  selector: 'app-link-form',
  standalone: true,
  imports: [CommonModule, MatCardModule],
  template: `
    <h1>Link Form</h1>
    <mat-card>
      <mat-card-content>
        <p>Link form will be implemented here for creating/editing links.</p>
      </mat-card-content>
    </mat-card>
  `
})
export class LinkFormComponent {}
