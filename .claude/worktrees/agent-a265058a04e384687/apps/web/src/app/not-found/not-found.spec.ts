import { TestBed } from '@angular/core/testing';

import { NotFound } from './not-found';

describe('NotFound', () => {
  it('renders a clear, non-navigating unavailable page', async () => {
    await TestBed.configureTestingModule({
      imports: [NotFound],
    }).compileComponents();

    const fixture = TestBed.createComponent(NotFound);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Página no disponible');
    expect(page.querySelectorAll('a')).toHaveLength(0);
  });
});
