import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AdminLayoutComponent } from './admin-layout.component';
import { provideRouter } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatDividerModule } from '@angular/material/divider';
import { of } from 'rxjs';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { Router } from '@angular/router';
import { vi } from 'vitest';

describe('AdminLayoutComponent', () => {
  let component: AdminLayoutComponent;
  let fixture: ComponentFixture<AdminLayoutComponent>;
  let mockApiService: any;
  let mockAuthService: any;
  let router: Router;

  beforeEach(async () => {
    // Create mocks
    mockApiService = {
      listDashboards: () => of([]),
    };

    mockAuthService = {
      logout: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [
        AdminLayoutComponent,
        MatToolbarModule,
        MatSidenavModule,
        MatListModule,
        MatIconModule,
        MatButtonModule,
        MatDividerModule,
      ],
      providers: [
        { provide: ApiService, useValue: mockApiService },
        { provide: AuthService, useValue: mockAuthService },
        provideRouter([]),
      ],
    }).compileComponents();

    router = TestBed.inject(Router);

    fixture = TestBed.createComponent(AdminLayoutComponent);
    component = fixture.componentInstance;

    // Verify the mock is being used
    const injectedService = TestBed.inject(ApiService);
    expect(injectedService).toBe(mockApiService);

    fixture.detectChanges();
  });

  afterEach(() => {
    TestBed.resetTestingModule();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have dashboardsExpanded property', () => {
    expect(component.dashboardsExpanded).toBeDefined();
    expect(component.dashboardsExpanded).toBe(true);
  });

  it('should toggle dashboards expansion', () => {
    expect(component.dashboardsExpanded).toBe(true);
    component.toggleDashboards();
    expect(component.dashboardsExpanded).toBe(false);
    component.toggleDashboards();
    expect(component.dashboardsExpanded).toBe(true);
  });

  it('should have topDashboards array', () => {
    expect(component.topDashboards).toBeDefined();
    expect(Array.isArray(component.topDashboards)).toBe(true);
  });

  it('should render sidenav container', () => {
    const sidenavContainer = fixture.nativeElement.querySelector('mat-sidenav-container');
    expect(sidenavContainer).toBeTruthy();
  });

  it('should render sidenav', () => {
    const sidenav = fixture.nativeElement.querySelector('mat-sidenav');
    expect(sidenav).toBeTruthy();
  });

  it('should have sidenav opened by default', () => {
    const sidenav = fixture.nativeElement.querySelector('mat-sidenav[opened]');
    expect(sidenav).toBeTruthy();
  });

  it('should have sidenav with side mode', () => {
    const sidenav = fixture.nativeElement.querySelector('mat-sidenav[mode="side"]');
    expect(sidenav).toBeTruthy();
  });

  it('should render sidenav header', () => {
    const header = fixture.nativeElement.querySelector('.sidenav-header');
    expect(header).toBeTruthy();
  });

  it('should display Bookmark Bureau logo in sidenav', () => {
    const logoIcon = fixture.nativeElement.querySelector('.sidenav-header mat-icon');
    expect(logoIcon).toBeTruthy();
    expect(logoIcon.textContent).toContain('bookmark');
  });

  it('should display app name in sidenav header', () => {
    const appName = fixture.nativeElement.querySelector('.sidenav-header .app-name');
    expect(appName).toBeTruthy();
    expect(appName.textContent).toContain('Bookmark Bureau');
  });

  it('should display admin label in sidenav header', () => {
    const adminLabel = fixture.nativeElement.querySelector('.sidenav-header .admin-label');
    expect(adminLabel).toBeTruthy();
    expect(adminLabel.textContent).toContain('Administration');
  });

  it('should render mat-nav-list', () => {
    const navList = fixture.nativeElement.querySelector('mat-nav-list');
    expect(navList).toBeTruthy();
  });

  it('should render menu items in nav list', () => {
    const listItems = fixture.nativeElement.querySelectorAll('mat-nav-list a[mat-list-item]');
    expect(listItems.length).toBeGreaterThanOrEqual(2); // Dashboards and Tags
  });

  it('should render Dashboards section with expand icon', () => {
    const dashboardsSection = fixture.nativeElement.querySelector('.nav-section');
    expect(dashboardsSection).toBeTruthy();

    const sectionHeader = dashboardsSection.querySelector('.section-header');
    expect(sectionHeader).toBeTruthy();

    const expandIcon = sectionHeader.querySelector('.expand-icon');
    expect(expandIcon).toBeTruthy();
    expect(expandIcon.textContent).toContain('expand_more'); // Should be expanded by default
  });

  it('should render Tags menu item', () => {
    const tagsLink = fixture.nativeElement.querySelector('a[routerLink="/admin/tags"]');
    expect(tagsLink).toBeTruthy();
    expect(tagsLink.textContent).toContain('Tags');
  });

  it('should render Back to Dashboards button', () => {
    const backButton = fixture.nativeElement.querySelector('.sidenav-footer .footer-button');
    const buttons = fixture.nativeElement.querySelectorAll('.sidenav-footer .footer-button');
    expect(buttons.length).toBe(2);

    const backToDbButton = Array.from(buttons).find((btn: any) =>
      btn.textContent.includes('Back to Dashboards'),
    );
    expect(backToDbButton).toBeTruthy();
  });

  it('should have arrow_back icon for back button', () => {
    const buttons = fixture.nativeElement.querySelectorAll('.sidenav-footer .footer-button');
    const backToDbButton = Array.from(buttons).find((btn: any) =>
      btn.textContent.includes('Back to Dashboards'),
    ) as HTMLElement;

    const backIcon = backToDbButton?.querySelector('mat-icon');
    expect(backIcon).toBeTruthy();
    expect(backIcon?.textContent).toContain('arrow_back');
  });

  it('should render sidenav footer with divider', () => {
    const footer = fixture.nativeElement.querySelector('.sidenav-footer');
    expect(footer).toBeTruthy();

    const divider = footer.querySelector('mat-divider');
    expect(divider).toBeTruthy();
  });

  it('should render sidenav content area', () => {
    const content = fixture.nativeElement.querySelector('mat-sidenav-content');
    expect(content).toBeTruthy();
  });

  it('should render toolbar in content area', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-sidenav-content mat-toolbar');
    expect(toolbar).toBeTruthy();
  });

  it('should display Administration Panel title in toolbar', () => {
    const toolbarTitle = fixture.nativeElement.querySelector(
      'mat-sidenav-content mat-toolbar .toolbar-title',
    );
    expect(toolbarTitle).toBeTruthy();
    expect(toolbarTitle.textContent).toContain('Administration Panel');
  });

  it('should have settings icon in toolbar', () => {
    const settingsIcon = fixture.nativeElement.querySelector(
      'mat-sidenav-content mat-toolbar mat-icon',
    );
    expect(settingsIcon).toBeTruthy();
    expect(settingsIcon.textContent).toContain('settings');
  });

  it('should render router outlet for content', () => {
    const routerOutlet = fixture.nativeElement.querySelector('mat-sidenav-content router-outlet');
    expect(routerOutlet).toBeTruthy();
  });

  it('should have admin content container', () => {
    const adminContent = fixture.nativeElement.querySelector('.admin-content');
    expect(adminContent).toBeTruthy();
  });

  it('should render menu items with correct routerLink attributes', () => {
    const allLinks = fixture.nativeElement.querySelectorAll('mat-nav-list a[mat-list-item]');
    expect(allLinks.length).toBeGreaterThanOrEqual(2);

    // Check if Dashboards and Tags links exist
    const dashboardsLink = fixture.nativeElement.querySelector('a[routerLink="/admin/dashboards"]');
    const tagsLink = fixture.nativeElement.querySelector('a[routerLink="/admin/tags"]');

    expect(dashboardsLink).toBeTruthy();
    expect(tagsLink).toBeTruthy();
  });

  it('should render all menu item icons correctly', () => {
    const menuIcons = fixture.nativeElement.querySelectorAll(
      'mat-nav-list mat-icon[matListItemIcon]',
    );
    expect(menuIcons.length).toBeGreaterThanOrEqual(2);
  });

  it('should render all menu item labels correctly', () => {
    const menuLabels = fixture.nativeElement.querySelectorAll(
      'mat-nav-list span[matListItemTitle]',
    );
    expect(menuLabels.length).toBeGreaterThanOrEqual(2);
  });

  it('should have routerLinkActive="active-link" on menu items', () => {
    // Check that all menu items have routerLinkActive directive
    const menuLinks = fixture.nativeElement.querySelectorAll('mat-nav-list a[mat-list-item]');
    expect(menuLinks.length).toBeGreaterThanOrEqual(2);

    // All links should have the routerLinkActive directive applied
    menuLinks.forEach((link: HTMLElement) => {
      expect(link.getAttribute('routerLinkActive')).toBe('active-link');
    });
  });

  it('should render logout button', () => {
    const buttons = fixture.nativeElement.querySelectorAll('.sidenav-footer .footer-button');
    const logoutButton = Array.from(buttons).find((btn: any) => btn.textContent.includes('Logout'));
    expect(logoutButton).toBeTruthy();
  });

  it('should call logout and navigate on logout button click', () => {
    const navigateSpy = vi.spyOn(router, 'navigate');
    const buttons = fixture.nativeElement.querySelectorAll('.sidenav-footer .footer-button');
    const logoutButton = Array.from(buttons).find((btn: any) =>
      btn.textContent.includes('Logout'),
    ) as HTMLElement;

    logoutButton.click();

    expect(mockAuthService.logout).toHaveBeenCalled();
    expect(navigateSpy).toHaveBeenCalledWith(['/login']);
  });
});
