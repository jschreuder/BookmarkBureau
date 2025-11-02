import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DashboardViewComponent } from './dashboard-view.component';
import { RouterTestingModule } from '@angular/router/testing';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';

describe('DashboardViewComponent', () => {
  let component: DashboardViewComponent;
  let fixture: ComponentFixture<DashboardViewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        DashboardViewComponent,
        RouterTestingModule,
        MatIconModule,
        MatCardModule,
        MatToolbarModule,
        MatButtonModule,
        MatChipsModule
      ]
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
    const backButton = fixture.nativeElement.querySelector('button[aria-label="Back to dashboards"]');
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
    expect(title.textContent).toContain('Dashboard Title');
  });

  it('should display dashboard description in header', () => {
    const description = fixture.nativeElement.querySelector('.dashboard-header .description');
    expect(description).toBeTruthy();
    expect(description.textContent).toContain('Dashboard description');
  });

  it('should render favorites section', () => {
    const favoritesSection = fixture.nativeElement.querySelector('.favorites-section');
    expect(favoritesSection).toBeTruthy();
  });

  it('should have Favorites heading with icon', () => {
    const favoritesHeading = fixture.nativeElement.querySelector('.favorites-section .section-header h2');
    expect(favoritesHeading).toBeTruthy();
    expect(favoritesHeading.textContent).toContain('Favorites');
  });

  it('should render categories section', () => {
    const categoriesSection = fixture.nativeElement.querySelector('.categories-section');
    expect(categoriesSection).toBeTruthy();
  });

  it('should have Categories heading with icon', () => {
    const categoriesHeading = fixture.nativeElement.querySelector('.categories-section .section-header h2');
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
    expect(categoryTitle.textContent).toContain('Example Category');
  });

  it('should render link card in favorites section', () => {
    const linkCard = fixture.nativeElement.querySelector('.link-card');
    expect(linkCard).toBeTruthy();
  });

  it('should display example link in favorites', () => {
    const linkInfo = fixture.nativeElement.querySelector('.link-card .link-info h3');
    expect(linkInfo).toBeTruthy();
    expect(linkInfo.textContent).toContain('Example Link');
  });

  it('should display example link URL', () => {
    const linkUrl = fixture.nativeElement.querySelector('.link-card .link-url');
    expect(linkUrl).toBeTruthy();
    expect(linkUrl.textContent).toContain('https://example.com');
  });

  it('should render link items in category', () => {
    const linkItem = fixture.nativeElement.querySelector('.link-item');
    expect(linkItem).toBeTruthy();
  });

  it('should display link item title', () => {
    const linkItemTitle = fixture.nativeElement.querySelector('.link-item h4');
    expect(linkItemTitle).toBeTruthy();
    expect(linkItemTitle.textContent).toContain('Link Title');
  });

  it('should render tags as chips in link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBeGreaterThan(0);
  });

  it('should have correct number of tags in example link item', () => {
    const chips = fixture.nativeElement.querySelectorAll('.link-item mat-chip');
    expect(chips.length).toBe(2);
  });

  it('should display tag1 and tag2 in chips', () => {
    const chipTexts = Array.from(fixture.nativeElement.querySelectorAll('.link-item mat-chip'))
      .map(chip => (chip as HTMLElement).textContent);
    expect(chipTexts).toContain('tag1');
    expect(chipTexts).toContain('tag2');
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
