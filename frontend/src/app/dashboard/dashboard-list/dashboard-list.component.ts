import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { ApiService } from '../../core/services/api.service';
import { Dashboard } from '../../core/models';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-dashboard-list',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatCardModule,
    MatButtonModule,
    MatToolbarModule,
    MatIconModule,
  ],
  templateUrl: './dashboard-list.component.html',
  styleUrl: './dashboard-list.component.scss',
})
export class DashboardListComponent implements OnInit {
  private readonly apiService = inject(ApiService);

  dashboards$!: Observable<Dashboard[]>;

  ngOnInit(): void {
    this.dashboards$ = this.apiService.listDashboards();
  }
}
