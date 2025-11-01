import { Routes } from '@angular/router';

export const DASHBOARD_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./dashboard-list/dashboard-list.component').then(m => m.DashboardListComponent)
  },
  {
    path: ':id',
    loadComponent: () => import('./dashboard-view/dashboard-view.component').then(m => m.DashboardViewComponent)
  }
];
