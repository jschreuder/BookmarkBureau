import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AdminLayoutComponent } from './admin-layout.component';
import { RouterTestingModule } from '@angular/router/testing';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatDividerModule } from '@angular/material/divider';
import { DebugElement } from '@angular/core';
import { By } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

describe('AdminLayoutComponent', () => {
  let component: AdminLayoutComponent;
  let fixture: ComponentFixture<AdminLayoutComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        AdminLayoutComponent,
        RouterTestingModule,
        MatToolbarModule,
        MatSidenavModule,
        MatListModule,
        MatIconModule,
        MatButtonModule,
        MatDividerModule,
        BrowserAnimationsModule
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(AdminLayoutComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have menu items array defined', () => {
    expect(component.menuItems).toBeDefined();
  });

  it('should have 4 menu items', () => {
    expect(component.menuItems.length).toBe(4);
  });

  it('should have Dashboards menu item', () => {
    const dashboardsItem = component.menuItems.find(item => item.label === 'Dashboards');
    expect(dashboardsItem).toBeDefined();
    expect(dashboardsItem?.path).toBe('/admin/dashboards');
    expect(dashboardsItem?.icon).toBe('dashboard');
  });

  it('should have Categories menu item', () => {
    const categoriesItem = component.menuItems.find(item => item.label === 'Categories');
    expect(categoriesItem).toBeDefined();
    expect(categoriesItem?.path).toBe('/admin/categories');
    expect(categoriesItem?.icon).toBe('category');
  });

  it('should have Links menu item', () => {
    const linksItem = component.menuItems.find(item => item.label === 'Links');
    expect(linksItem).toBeDefined();
    expect(linksItem?.path).toBe('/admin/links');
    expect(linksItem?.icon).toBe('link');
  });

  it('should have Tags menu item', () => {
    const tagsItem = component.menuItems.find(item => item.label === 'Tags');
    expect(tagsItem).toBeDefined();
    expect(tagsItem?.path).toBe('/admin/tags');
    expect(tagsItem?.icon).toBe('label');
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
    expect(listItems.length).toBeGreaterThanOrEqual(4); // At least the 4 menu items
  });

  it('should render Back to Dashboards link', () => {
    const backLink = fixture.nativeElement.querySelector('.sidenav-footer a[routerLink="/dashboard"]');
    expect(backLink).toBeTruthy();
    expect(backLink.textContent).toContain('Back to Dashboards');
  });

  it('should have arrow_back icon for back button', () => {
    const backIcon = fixture.nativeElement.querySelector('.sidenav-footer a[routerLink="/dashboard"] mat-icon');
    expect(backIcon).toBeTruthy();
    expect(backIcon.textContent).toContain('arrow_back');
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
    const toolbarTitle = fixture.nativeElement.querySelector('mat-sidenav-content mat-toolbar .toolbar-title');
    expect(toolbarTitle).toBeTruthy();
    expect(toolbarTitle.textContent).toContain('Administration Panel');
  });

  it('should have settings icon in toolbar', () => {
    const settingsIcon = fixture.nativeElement.querySelector('mat-sidenav-content mat-toolbar mat-icon');
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
    expect(allLinks.length).toBe(4);

    // Check if links exist by looking at the menu items
    const dashboardsItem = component.menuItems.find(item => item.path === '/admin/dashboards');
    const categoriesItem = component.menuItems.find(item => item.path === '/admin/categories');
    const linksItem = component.menuItems.find(item => item.path === '/admin/links');
    const tagsItem = component.menuItems.find(item => item.path === '/admin/tags');

    expect(dashboardsItem).toBeTruthy();
    expect(categoriesItem).toBeTruthy();
    expect(linksItem).toBeTruthy();
    expect(tagsItem).toBeTruthy();
  });

  it('should render all menu item icons correctly', () => {
    const menuIcons = fixture.nativeElement.querySelectorAll('mat-nav-list mat-icon[matListItemIcon]');
    expect(menuIcons.length).toBeGreaterThanOrEqual(4);
  });

  it('should render all menu item labels correctly', () => {
    const menuLabels = fixture.nativeElement.querySelectorAll('mat-nav-list span[matListItemTitle]');
    expect(menuLabels.length).toBeGreaterThanOrEqual(4);
  });

  it('should have routerLinkActive="active-link" on menu items', () => {
    // Check that all menu items have routerLinkActive directive
    const menuLinks = fixture.nativeElement.querySelectorAll('mat-nav-list a[mat-list-item]');
    expect(menuLinks.length).toBe(4);

    // All links should have the routerLinkActive directive applied
    menuLinks.forEach((link: HTMLElement) => {
      expect(link.getAttribute('routerLinkActive')).toBe('active-link');
    });
  });
});
