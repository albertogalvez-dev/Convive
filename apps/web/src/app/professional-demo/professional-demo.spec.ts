import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { ProfessionalDemo } from './professional-demo';

describe('ProfessionalDemo', () => {
  it('renders only fixed fictional professional capabilities', async () => {
    await TestBed.configureTestingModule({
      imports: [ProfessionalDemo],
      providers: [provideRouter([])],
    }).compileComponents();

    const fixture = TestBed.createComponent(ProfessionalDemo);
    fixture.detectChanges();
    const page = fixture.nativeElement as HTMLElement;

    expect(page.textContent).toContain('DEMOSTRACIÓN PROFESIONAL FICTICIA');
    expect(page.textContent).toContain('DATOS FICTICIOS · ENTORNO DE MUESTRA');
    expect(page.querySelector('[href*="/profesionales/"]')).toBeNull();
    expect(Array.from(page.querySelectorAll('button')).some((button) => button.disabled)).toBe(
      false,
    );
  });
});
