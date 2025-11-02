import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardListComponent } from './dashboard-list.component';
import { RouterTestingModule } from '@angular/router/testing';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';

describe('DashboardListComponent', () => {
  let component: DashboardListComponent;
  let fixture: ComponentFixture<DashboardListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        DashboardListComponent,
        RouterTestingModule,
        MatIconModule,
        MatCardModule,
        MatToolbarModule,
        MatButtonModule
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render toolbar', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-toolbar');
    expect(toolbar).toBeTruthy();
  });

  it('should have toolbar with primary color', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-toolbar[color="primary"]');
    expect(toolbar).toBeTruthy();
  });

  it('should display Bookmark Bureau title in toolbar', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-toolbar');
    expect(toolbar.textContent).toContain('Bookmark Bureau');
  });

  it('should have bookmark icon in toolbar', () => {
    const icon = fixture.nativeElement.querySelector('mat-toolbar mat-icon');
    expect(icon).toBeTruthy();
    expect(icon.textContent).toContain('bookmark');
  });

  it('should have Admin button in toolbar', () => {
    const adminButton = fixture.nativeElement.querySelector('button[routerLink="/admin"]');
    expect(adminButton).toBeTruthy();
  });

  it('should display Admin text in button', () => {
    const adminButton = fixture.nativeElement.querySelector('button[routerLink="/admin"]');
    expect(adminButton.textContent).toContain('Admin');
  });

  it('should have settings icon in Admin button', () => {
    const settingsIcon = fixture.nativeElement.querySelector('button[routerLink="/admin"] mat-icon');
    expect(settingsIcon).toBeTruthy();
    expect(settingsIcon.textContent).toContain('settings');
  });

  it('should render main container', () => {
    const container = fixture.nativeElement.querySelector('.container');
    expect(container).toBeTruthy();
  });

  it('should display page header', () => {
    const pageHeader = fixture.nativeElement.querySelector('.page-header');
    expect(pageHeader).toBeTruthy();
  });

  it('should display My Dashboards title', () => {
    const title = fixture.nativeElement.querySelector('.page-header h1');
    expect(title).toBeTruthy();
    expect(title.textContent).toContain('My Dashboards');
  });

  it('should display subtitle under title', () => {
    const subtitle = fixture.nativeElement.querySelector('.page-header .subtitle');
    expect(subtitle).toBeTruthy();
    expect(subtitle.textContent).toContain('Organize your bookmarks into customizable dashboards');
  });

  it('should render dashboard grid', () => {
    const grid = fixture.nativeElement.querySelector('.dashboard-grid');
    expect(grid).toBeTruthy();
  });

  it('should render at least one dashboard card', () => {
    const cards = fixture.nativeElement.querySelectorAll('.dashboard-card');
    expect(cards.length).toBeGreaterThan(0);
  });

  it('should display dashboard card title', () => {
    const cardTitle = fixture.nativeElement.querySelector('.dashboard-card mat-card-title');
    expect(cardTitle).toBeTruthy();
    expect(cardTitle.textContent).toContain('Dashboard list will be displayed here');
  });

  it('should display card content', () => {
    const cardContent = fixture.nativeElement.querySelector('.dashboard-card mat-card-content');
    expect(cardContent).toBeTruthy();
    expect(cardContent.textContent).toContain('Click on a dashboard to view its bookmarks');
  });

  it('should have card actions section', () => {
    const cardActions = fixture.nativeElement.querySelector('.dashboard-card mat-card-actions');
    expect(cardActions).toBeTruthy();
  });

  it('should have Create Dashboard button', () => {
    const button = fixture.nativeElement.querySelector('.dashboard-card button[mat-button]');
    expect(button).toBeTruthy();
    expect(button.textContent).toContain('Create Dashboard');
  });

  it('should have add icon in Create Dashboard button', () => {
    const icon = fixture.nativeElement.querySelector('.dashboard-card button[mat-button] mat-icon');
    expect(icon).toBeTruthy();
    expect(icon.textContent).toContain('add');
  });

  it('should have Create Dashboard button with primary color', () => {
    const button = fixture.nativeElement.querySelector('.dashboard-card button[color="primary"]');
    expect(button).toBeTruthy();
  });

  it('should render Material card component', () => {
    const matCard = fixture.nativeElement.querySelector('mat-card');
    expect(matCard).toBeTruthy();
  });

  it('should render Material card header', () => {
    const cardHeader = fixture.nativeElement.querySelector('mat-card-header');
    expect(cardHeader).toBeTruthy();
  });

  it('should have correct structure with header, content, and actions', () => {
    const card = fixture.nativeElement.querySelector('.dashboard-card');
    const header = card.querySelector('mat-card-header');
    const content = card.querySelector('mat-card-content');
    const actions = card.querySelector('mat-card-actions');

    expect(header).toBeTruthy();
    expect(content).toBeTruthy();
    expect(actions).toBeTruthy();
  });

  it('should display instruction text about using admin panel', () => {
    const cardContent = fixture.nativeElement.querySelector('.dashboard-card mat-card-content');
    expect(cardContent.textContent).toContain('Use the Admin panel to create and manage your dashboards');
  });
});
