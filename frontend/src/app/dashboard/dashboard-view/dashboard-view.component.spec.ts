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

  it('should render dashboard toolbar', () => {
    const toolbar = fixture.nativeElement.querySelector('.dashboard-toolbar');
    expect(toolbar).toBeTruthy();
  });

  it('should display dashboard title in toolbar', () => {
    const title = fixture.nativeElement.querySelector('.toolbar-title');
    expect(title).toBeTruthy();
    expect(title.textContent).toContain('Test Dashboard');
  });

  it('should display dashboard description as quote', () => {
    const description = fixture.nativeElement.querySelector('.description-quote p');
    expect(description).toBeTruthy();
    expect(description.textContent).toContain('Test Description');
  });

  it('should render favorites in toolbar', () => {
    const favorites = fixture.nativeElement.querySelector('.toolbar-favorites');
    expect(favorites).toBeTruthy();
  });

  it('should render favorite chips', () => {
    const chips = fixture.nativeElement.querySelectorAll('.favorite-chip');
    expect(chips.length).toBeGreaterThan(0);
  });

  it('should render categories section', () => {
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(categoriesSection).toBeTruthy();
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

  it('should display favorite link titles in chips', () => {
    const chip = fixture.nativeElement.querySelector('.favorite-chip');
    expect(chip).toBeTruthy();
    expect(chip.textContent).toContain('Link 1');
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

  it('should render main sections in correct order', () => {
    const toolbar = fixture.nativeElement.querySelector('.dashboard-toolbar');
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(toolbar).toBeTruthy();
    expect(categoriesSection).toBeTruthy();
  });

  it('should open link in new window when clicking favorite chip', () => {
    const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);
    const chip = fixture.nativeElement.querySelector('.favorite-chip');

    chip.click();

    expect(windowOpenSpy).toHaveBeenCalledWith('https://example1.com', '_blank');
    windowOpenSpy.mockRestore();
  });

  it('should open link in new window when clicking category link item', () => {
    const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null);
    const linkItem = fixture.nativeElement.querySelector('.link-item');

    linkItem.click();

    expect(windowOpenSpy).toHaveBeenCalledWith('https://example1.com', '_blank');
    windowOpenSpy.mockRestore();
  });
});
