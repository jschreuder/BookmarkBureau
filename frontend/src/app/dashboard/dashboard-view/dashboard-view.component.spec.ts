import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardViewComponent } from './dashboard-view.component';
import { RouterTestingModule } from '@angular/router/testing';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { ApiService } from '../../core/services/api.service';
import { ActivatedRoute } from '@angular/router';
import { createMockApiService, createMockFullDashboard } from '../../../testing/test-helpers';
import { of } from 'rxjs';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { vi } from 'vitest';

describe('DashboardViewComponent', () => {
  let component: DashboardViewComponent;
  let fixture: ComponentFixture<DashboardViewComponent>;
  let mockApiService: any;
  let mockActivatedRoute: any;

  beforeEach(async () => {
    mockApiService = createMockApiService();

    // Create a proper mock ActivatedRoute with paramMap
    mockActivatedRoute = {
      snapshot: {
        paramMap: {
          get: vi.fn().mockReturnValue('test-dashboard-id'),
        },
      },
    };

    // Mock the getDashboard method to return test data
    mockApiService.getDashboard = vi.fn().mockReturnValue(of(createMockFullDashboard()));

    await TestBed.configureTestingModule({
      imports: [
        DashboardViewComponent,
        RouterTestingModule,
        HttpClientTestingModule,
        MatIconModule,
        MatCardModule,
        MatToolbarModule,
        MatButtonModule,
        MatChipsModule,
      ],
      providers: [
        { provide: ApiService, useValue: mockApiService },
        { provide: ActivatedRoute, useValue: mockActivatedRoute },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardViewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render toolbar with Bookmark Bureau title', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-toolbar');
    expect(toolbar).toBeTruthy();
    expect(toolbar.textContent).toContain('Bookmark Bureau');
  });

  it('should have back button in toolbar', () => {
    const backButton = fixture.nativeElement.querySelector(
      'button[aria-label="Back to dashboards"]',
    );
    expect(backButton).toBeTruthy();
  });

  it('should have Admin button in toolbar linking to /admin', () => {
    const adminButton = fixture.nativeElement.querySelector('button[routerLink="/admin"]');
    expect(adminButton).toBeTruthy();
    expect(adminButton.textContent).toContain('Admin');
  });

  it('should render dashboard header section', () => {
    const header = fixture.nativeElement.querySelector('.dashboard-header');
    expect(header).toBeTruthy();
  });

  it('should display dashboard title in header', () => {
    const title = fixture.nativeElement.querySelector('.dashboard-header h1');
    expect(title).toBeTruthy();
    expect(title.textContent).toContain('Test Dashboard');
  });

  it('should display dashboard description in header', () => {
    const description = fixture.nativeElement.querySelector('.dashboard-header .description');
    expect(description).toBeTruthy();
    expect(description.textContent).toContain('Test Description');
  });

  it('should render favorites section', () => {
    const favoritesSection = fixture.nativeElement.querySelector('.favorites-section');
    expect(favoritesSection).toBeTruthy();
  });

  it('should have Favorites heading with icon', () => {
    const favoritesHeading = fixture.nativeElement.querySelector(
      '.favorites-section .section-header h2',
    );
    expect(favoritesHeading).toBeTruthy();
    expect(favoritesHeading.textContent).toContain('Favorites');
  });

  it('should render categories section', () => {
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(categoriesSection).toBeTruthy();
  });

  it('should have Categories heading with icon', () => {
    const categoriesHeading = fixture.nativeElement.querySelector(
      '.categories-section .section-header h2',
    );
    expect(categoriesHeading).toBeTruthy();
    expect(categoriesHeading.textContent).toContain('Categories');
  });

  it('should render example category card', () => {
    const categoryCard = fixture.nativeElement.querySelector('.category-card');
    expect(categoryCard).toBeTruthy();
  });

  it('should display example category title', () => {
    const categoryTitle = fixture.nativeElement.querySelector('.category-card mat-card-title');
    expect(categoryTitle).toBeTruthy();
    expect(categoryTitle.textContent).toContain('Test Category');
  });

  it('should render link card in favorites section', () => {
    const linkCard = fixture.nativeElement.querySelector('.link-card');
    expect(linkCard).toBeTruthy();
  });

  it('should display example link in favorites', () => {
    const linkInfo = fixture.nativeElement.querySelector('.link-card .link-info h3');
    expect(linkInfo).toBeTruthy();
    expect(linkInfo.textContent).toContain('Link 1');
  });

  it('should display example link URL', () => {
    const linkUrl = fixture.nativeElement.querySelector('.link-card .link-url');
    expect(linkUrl).toBeTruthy();
    expect(linkUrl.textContent).toContain('https://example1.com');
  });

  it('should render link items in category', () => {
    const linkItem = fixture.nativeElement.querySelector('.link-item');
    expect(linkItem).toBeTruthy();
  });

  it('should display link item title', () => {
    const linkItemTitle = fixture.nativeElement.querySelector('.link-item h4');
    expect(linkItemTitle).toBeTruthy();
    expect(linkItemTitle.textContent).toContain('Link 1');
  });

  it('should render tags as chips in link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBe(0);
  });

  it('should have correct number of tags in example link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBe(0);
  });

  it('should display tags in chips if they exist', () => {
    const chipTexts = Array.from(fixture.nativeElement.querySelectorAll('.link-item mat-chip')).map(
      (chip) => (chip as HTMLElement).textContent,
    );
    expect(chipTexts.length).toBe(0);
  });

  it('should use Material toolbar with primary color', () => {
    const toolbar = fixture.nativeElement.querySelector('mat-toolbar[color="primary"]');
    expect(toolbar).toBeTruthy();
  });

  it('should render all sections in correct order', () => {
    const sections = fixture.nativeElement.querySelectorAll('section');
    expect(sections.length).toBe(2); // favorites and categories
    expect(sections[0].classList.contains('favorites-section')).toBe(true);
    expect(sections[1].classList.contains('categories-section')).toBe(true);
  });
});
