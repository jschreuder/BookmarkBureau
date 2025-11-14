import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatDividerModule } from '@angular/material/divider';
import { ApiService } from '../../core/services/api.service';
import { Dashboard } from '../../core/models';

interface MenuItem {
  path?: string;
  icon: string;
  label: string;
  isDivider?: boolean;
}

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
    MatToolbarModule,
    MatSidenavModule,
    MatListModule,
    MatIconModule,
    MatButtonModule,
    MatDividerModule,
  ],
  templateUrl: './admin-layout.component.html',
  styleUrls: ['./admin-layout.component.scss'],
})
export class AdminLayoutComponent implements OnInit {
  private apiService = inject(ApiService);

  menuItems: MenuItem[] = [
    { path: '/admin/dashboards', icon: 'dashboard', label: 'Dashboards' },
    { icon: 'label', label: 'Tags', path: '/admin/tags' },
  ];

  topDashboards: Dashboard[] = [];

  ngOnInit(): void {
    this.loadTopDashboards();
  }

  loadTopDashboards(): void {
    this.apiService.listDashboards().subscribe((dashboards) => {
      // Sort by updatedAt descending and take top 10
      this.topDashboards = dashboards
        .sort((a, b) => {
          const dateA = new Date(a.updated_at).getTime();
          const dateB = new Date(b.updated_at).getTime();
          return dateB - dateA;
        })
        .slice(0, 10);
    });
  }
}
