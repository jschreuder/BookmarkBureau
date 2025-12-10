import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { of, throwError, Subject } from 'rxjs';
import { vi } from 'vitest';
import { TagListComponent } from './tag-list.component';
import { TagService } from '../../../core/services/tag.service';
import { Tag } from '../../../core/models';

describe('TagListComponent', () => {
  let component: TagListComponent;
  let fixture: ComponentFixture<TagListComponent>;
  let tagService: TagService;
  let dialog: MatDialog;
  let snackBar: MatSnackBar;

  const mockTags: Tag[] = [
    { tag_name: 'work', color: '#2196f3' },
    { tag_name: 'personal', color: '#4caf50' },
  ];

  beforeEach(async () => {
    const tagServiceMock = {
      loadTags: vi.fn().mockReturnValue(of(mockTags)),
      tags$: of(mockTags),
      deleteTag: vi.fn(),
    };

    const dialogRefMock = {
      afterClosed: vi.fn().mockReturnValue(of(false)),
    };

    const afterAllClosedSubject = new Subject();

    const dialogMock = {
      open: vi.fn().mockReturnValue(dialogRefMock),
      openDialogs: [],
      _getAfterAllClosed: vi.fn().mockReturnValue(afterAllClosedSubject),
      afterAllClosed: afterAllClosedSubject.asObservable(),
      _afterAllClosedAtThisLevel: afterAllClosedSubject,
    };

    const snackBarMock = {
      open: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [
        TagListComponent,
        MatCardModule,
        MatButtonModule,
        MatIconModule,
        MatTableModule,
        MatDialogModule,
        MatSnackBarModule,
        NoopAnimationsModule,
      ],
      providers: [
        { provide: TagService, useValue: tagServiceMock },
        { provide: MatDialog, useValue: dialogMock },
        { provide: MatSnackBar, useValue: snackBarMock },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TagListComponent);
    component = fixture.componentInstance;
    tagService = TestBed.inject(TagService);
    dialog = TestBed.inject(MatDialog);
    snackBar = TestBed.inject(MatSnackBar);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should load tags on init', () => {
    expect(tagService.loadTags).toHaveBeenCalled();
  });

  it('should display tags in table', () => {
    const tableRows = fixture.nativeElement.querySelectorAll('tr.mat-mdc-row');
    expect(tableRows.length).toBe(mockTags.length);
  });

  it('should have openCreateDialog method', () => {
    expect(typeof component.openCreateDialog).toBe('function');
  });

  it('should have openEditDialog method', () => {
    expect(typeof component.openEditDialog).toBe('function');
  });

  it('should delete tag on confirmation', () => {
    const dialogRefMock = {
      afterClosed: vi.fn().mockReturnValue(of(true)),
    };
    vi.spyOn(component['dialog'], 'open').mockReturnValue(dialogRefMock as any);
    vi.spyOn(tagService, 'deleteTag').mockReturnValue(of(void 0));

    component.deleteTag(mockTags[0]);

    expect(component['dialog'].open).toHaveBeenCalled();
    expect(tagService.deleteTag).toHaveBeenCalledWith('work');
  });

  it('should not delete tag when user cancels', () => {
    const dialogRefMock = {
      afterClosed: vi.fn().mockReturnValue(of(false)),
    };
    vi.spyOn(component['dialog'], 'open').mockReturnValue(dialogRefMock as any);

    component.deleteTag(mockTags[0]);

    expect(component['dialog'].open).toHaveBeenCalled();
    expect(tagService.deleteTag).not.toHaveBeenCalled();
  });

  it('should call deleteTag service when confirmed', () => {
    const dialogRefMock = {
      afterClosed: vi.fn().mockReturnValue(of(true)),
    };
    vi.spyOn(component['dialog'], 'open').mockReturnValue(dialogRefMock as any);
    tagService.deleteTag = vi.fn().mockReturnValue(of(void 0));

    component.deleteTag(mockTags[0]);

    expect(component['dialog'].open).toHaveBeenCalled();
    expect(tagService.deleteTag).toHaveBeenCalledWith('work');
  });
});
