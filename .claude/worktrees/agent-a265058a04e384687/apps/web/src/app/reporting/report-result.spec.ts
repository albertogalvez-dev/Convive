import { ComponentFixture, TestBed } from '@angular/core/testing';
import { vi } from 'vitest';

import reportEvidenceEs from '../../i18n/report-evidence/es.json';
import reportResultEs from '../../i18n/report-result/es.json';
import { i18nTestingModule } from '../i18n/testing/provide-i18n-testing';
import { ReportResult } from './report-result';
import { ReportSubmissionResponse } from './reporting.service';

const submission: ReportSubmissionResponse = {
  publicReference: 'ORG_TEST0000000000001',
  accessSecret: 'fictional-secret-abcdef123456',
  status: 'received',
  createdAt: '2026-08-17T09:00:00.000Z',
};

describe('ReportResult', () => {
  let fixture: ComponentFixture<ReportResult>;
  let page: HTMLElement;

  const render = (): void => {
    fixture = TestBed.createComponent(ReportResult);
    fixture.componentRef.setInput('submitted', submission);
    fixture.detectChanges();
    page = fixture.nativeElement as HTMLElement;
  };

  const clickButton = (label: string): void => {
    const button = Array.from(page.querySelectorAll('button')).find((candidate) =>
      candidate.textContent?.trim().startsWith(label),
    );
    button?.click();
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        ReportResult,
        i18nTestingModule({ 'report-evidence': reportEvidenceEs, 'report-result': reportResultEs }),
      ],
    }).compileComponents();
  });

  describe('copyAccessSecret', () => {
    const writeText = vi.fn<(value: string) => Promise<void>>();

    beforeEach(() => {
      writeText.mockReset();
      Object.defineProperty(navigator, 'clipboard', {
        configurable: true,
        value: { writeText },
      });
    });

    it('confirms the secret was copied when the clipboard write succeeds', async () => {
      writeText.mockResolvedValue();
      render();

      clickButton('Copiar secreto');
      await fixture.whenStable();
      fixture.detectChanges();

      expect(writeText).toHaveBeenCalledWith(submission.accessSecret);
      expect(page.querySelector('.credential-status')?.textContent).toContain('Secreto copiado');
    });

    it('tells the reporter to copy it manually when the clipboard write fails', async () => {
      writeText.mockRejectedValue(new Error('denied'));
      render();

      clickButton('Copiar secreto');
      await fixture.whenStable();
      fixture.detectChanges();

      expect(page.querySelector('.credential-status')?.textContent).toContain(
        'No se ha podido copiar',
      );
    });
  });

  describe('printAccessReceipt', () => {
    it('opens the browser print dialog', () => {
      const print = vi.spyOn(window, 'print').mockImplementation(() => undefined);
      render();

      clickButton('Imprimir o guardar en PDF');

      expect(print).toHaveBeenCalled();
    });
  });

  describe('saveInBrowser', () => {
    class FakePasswordCredential {
      constructor(readonly data: { id: string; password: string; name?: string }) {}
    }

    // PasswordCredential is Chromium-only and typically absent in the test
    // environment; canSaveInBrowser() is read once at construction, so the
    // mock has to exist before the component is created.
    const withPasswordCredentialSupport = (): ReturnType<
      typeof vi.fn<(credential: unknown) => Promise<void>>
    > => {
      const store = vi.fn<(credential: unknown) => Promise<void>>();
      Object.defineProperty(window, 'PasswordCredential', {
        configurable: true,
        value: FakePasswordCredential,
      });
      Object.defineProperty(navigator, 'credentials', {
        configurable: true,
        value: { store },
      });

      return store;
    };

    afterEach(() => {
      delete (window as unknown as { PasswordCredential?: unknown }).PasswordCredential;
    });

    it('asks the browser to store the credential pair and confirms once accepted', async () => {
      const store = withPasswordCredentialSupport();
      store.mockResolvedValue();
      render();

      clickButton('Guardar en el navegador');
      await fixture.whenStable();
      fixture.detectChanges();

      expect(store).toHaveBeenCalledOnce();
      expect((store.mock.calls[0][0] as FakePasswordCredential).data).toMatchObject({
        id: submission.publicReference,
        password: submission.accessSecret,
      });
      expect(page.querySelector('.credential-status')?.textContent).toContain(
        'Se lo hemos pedido a tu navegador',
      );
    });

    it('falls back to manual copy guidance when the browser refuses to store it', async () => {
      const store = withPasswordCredentialSupport();
      store.mockRejectedValue(new Error('refused'));
      render();

      clickButton('Guardar en el navegador');
      await fixture.whenStable();
      fixture.detectChanges();

      expect(page.querySelector('.credential-status')?.textContent).toContain(
        'No se ha podido guardar',
      );
    });

    it('hides the save-in-browser action entirely when the browser cannot store credentials', () => {
      // No PasswordCredential mock: canSaveInBrowser() is false, so the
      // "primary" single-button layout renders instead of the save button.
      // This is the real fallback an unsupported browser takes; the null-
      // constructor branch inside saveInBrowser() itself only guards a race
      // (support detected at load, gone by click time) that the UI can never
      // reach on its own.
      render();

      expect(page.querySelector('.save-button')).toBeNull();
      expect(page.querySelector('.copy-button.primary')).not.toBeNull();
    });
  });
});
