import { provideHttpClient } from '@angular/common/http';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, Subject, throwError } from 'rxjs';
import { vi } from 'vitest';

import attachmentPreviewEs from '../../i18n/attachment-preview/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { PrivateAttachmentPreview } from './private-attachment-preview';

describe('PrivateAttachmentPreview', () => {
  let fixture: ComponentFixture<PrivateAttachmentPreview>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        PrivateAttachmentPreview,
        i18nTestingModule({ 'attachment-preview': attachmentPreviewEs }),
      ],
      providers: [provideHttpClient()],
    }).compileComponents();
  });

  afterEach(() => vi.restoreAllMocks());

  it('uses an in-memory image URL and revokes it when the viewer closes', () => {
    const createObjectUrl = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:private-image');
    const revokeObjectUrl = vi.spyOn(URL, 'revokeObjectURL');
    fixture = TestBed.createComponent(PrivateAttachmentPreview);
    fixture.componentRef.setInput('attachment', { id: 'attachment-1', mediaType: 'image/png' });
    fixture.componentRef.setInput('load', () => of(new Blob(['image'], { type: 'image/png' })));
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    page.querySelector<HTMLButtonElement>('.preview-trigger')?.click();
    fixture.detectChanges();
    expect(createObjectUrl).toHaveBeenCalled();
    expect(page.querySelector('img')?.getAttribute('src')).toBe('blob:private-image');

    page.querySelector<HTMLButtonElement>('.close')?.click();
    expect(revokeObjectUrl).toHaveBeenCalledWith('blob:private-image');
  });

  it('does not render content if the returned type differs from the available attachment type', () => {
    fixture = TestBed.createComponent(PrivateAttachmentPreview);
    fixture.componentRef.setInput('attachment', {
      id: 'attachment-1',
      mediaType: 'application/pdf',
    });
    fixture.componentRef.setInput('load', () => of(new Blob(['image'], { type: 'image/png' })));
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    page.querySelector<HTMLButtonElement>('.preview-trigger')?.click();
    fixture.detectChanges();
    expect(page.querySelector('iframe')).toBeNull();
    expect(page.querySelector('[role="alert"]')?.textContent).toContain(
      'No hemos podido abrir este archivo.',
    );
  });

  it('does not retain a view when loading fails', () => {
    fixture = TestBed.createComponent(PrivateAttachmentPreview);
    fixture.componentRef.setInput('attachment', { id: 'attachment-1', mediaType: 'image/jpeg' });
    fixture.componentRef.setInput('load', () => throwError(() => new Error('network')));
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    page.querySelector<HTMLButtonElement>('.preview-trigger')?.click();
    fixture.detectChanges();
    expect(page.querySelector('img')).toBeNull();
  });

  it('discards a late response after the viewer has been closed', () => {
    const content = new Subject<Blob>();
    const createObjectUrl = vi.spyOn(URL, 'createObjectURL');
    fixture = TestBed.createComponent(PrivateAttachmentPreview);
    fixture.componentRef.setInput('attachment', { id: 'attachment-1', mediaType: 'image/png' });
    fixture.componentRef.setInput('load', () => content);
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    page.querySelector<HTMLButtonElement>('.preview-trigger')?.click();
    page.querySelector<HTMLButtonElement>('.close')?.click();
    content.next(new Blob(['image'], { type: 'image/png' }));
    fixture.detectChanges();

    expect(createObjectUrl).not.toHaveBeenCalled();
    expect(page.querySelector('img')).toBeNull();
  });
});
