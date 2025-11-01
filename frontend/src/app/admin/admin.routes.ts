import { Routes } from '@angular/router';

export const ADMIN_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./admin-layout/admin-layout.component').then(m => m.AdminLayoutComponent),
    children: [
      {
        path: '',
        redirectTo: 'dashboards',
        pathMatch: 'full'
      },
      {
        path: 'dashboards',
        loadComponent: () => import('./dashboards/dashboard-list/dashboard-list.component').then(m => m.AdminDashboardListComponent)
      },
      {
        path: 'dashboards/:id',
        loadComponent: () => import('./dashboards/dashboard-form/dashboard-form.component').then(m => m.DashboardFormComponent)
      },
      {
        path: 'categories',
        loadComponent: () => import('./categories/category-list/category-list.component').then(m => m.CategoryListComponent)
      },
      {
        path: 'links',
        loadComponent: () => import('./links/link-list/link-list.component').then(m => m.LinkListComponent)
      },
      {
        path: 'links/:id',
        loadComponent: () => import('./links/link-form/link-form.component').then(m => m.LinkFormComponent)
      },
      {
        path: 'tags',
        loadComponent: () => import('./tags/tag-list/tag-list.component').then(m => m.TagListComponent)
      }
    ]
  }
];
