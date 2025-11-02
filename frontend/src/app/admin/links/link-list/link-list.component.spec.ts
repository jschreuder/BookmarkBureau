import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LinkListComponent } from './link-list.component';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common';

describe('LinkListComponent', () => {
  let component: LinkListComponent;
  let fixture: ComponentFixture<LinkListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LinkListComponent, MatCardModule, MatButtonModule, MatIconModule, CommonModule]
    }).compileComponents();

    fixture = TestBed.createComponent(LinkListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render page header', () => {
    const header = fixture.nativeElement.querySelector('.page-header');
    expect(header).toBeTruthy();
  });

  it('should display Links heading', () => {
    const h1 = fixture.nativeElement.querySelector('h1');
    expect(h1).toBeTruthy();
    expect(h1.textContent).toContain('Links');
  });

  it('should render New Link button', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    expect(button).toBeTruthy();
  });

  it('should have primary color on New Link button', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    expect(button).toBeTruthy();
    expect(button.getAttribute('color')).toBe('primary');
  });

  it('should display New Link text in button', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    expect(button.textContent).toContain('New Link');
  });

  it('should have add icon in New Link button', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    const icon = button.querySelector('mat-icon');
    expect(icon).toBeTruthy();
    expect(icon.textContent).toContain('add');
  });

  it('should render Material card', () => {
    const card = fixture.nativeElement.querySelector('mat-card');
    expect(card).toBeTruthy();
  });

  it('should render card content', () => {
    const cardContent = fixture.nativeElement.querySelector('mat-card-content');
    expect(cardContent).toBeTruthy();
  });

  it('should display main description in card', () => {
    const cardContent = fixture.nativeElement.querySelector('mat-card-content');
    expect(cardContent.textContent).toContain('Link list will be implemented here');
  });

  it('should display additional description text', () => {
    const cardContent = fixture.nativeElement.querySelector('mat-card-content');
    expect(cardContent.textContent).toContain('This will show all links with options to create, edit, and delete them');
  });

  it('should render button with correct icon', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    const matIcon = button.querySelector('mat-icon');
    expect(matIcon).toBeTruthy();
  });

  it('should have Material button raised variant', () => {
    const button = fixture.nativeElement.querySelector('[data-testid="create-btn"]');
    expect(button).toBeTruthy();
    expect(button.hasAttribute('mat-raised-button')).toBe(true);
  });
});
