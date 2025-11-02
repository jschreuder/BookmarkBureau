import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LinkFormComponent } from './link-form.component';
import { MatCardModule } from '@angular/material/card';
import { CommonModule } from '@angular/common';

describe('LinkFormComponent', () => {
  let component: LinkFormComponent;
  let fixture: ComponentFixture<LinkFormComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LinkFormComponent, MatCardModule, CommonModule]
    }).compileComponents();

    fixture = TestBed.createComponent(LinkFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should render h1 heading', () => {
    const h1 = fixture.nativeElement.querySelector('h1');
    expect(h1).toBeTruthy();
  });

  it('should display Link Form heading', () => {
    const h1 = fixture.nativeElement.querySelector('h1');
    expect(h1.textContent).toContain('Link Form');
  });

  it('should render Material card', () => {
    const card = fixture.nativeElement.querySelector('mat-card');
    expect(card).toBeTruthy();
  });

  it('should render card content', () => {
    const cardContent = fixture.nativeElement.querySelector('mat-card-content');
    expect(cardContent).toBeTruthy();
  });

  it('should display placeholder message in card', () => {
    const cardContent = fixture.nativeElement.querySelector('mat-card-content');
    expect(cardContent.textContent).toContain('Link form will be implemented here for creating/editing links');
  });

  it('should have card with content wrapper', () => {
    const card = fixture.nativeElement.querySelector('mat-card');
    const content = card.querySelector('mat-card-content');
    expect(content).toBeTruthy();
  });
});
