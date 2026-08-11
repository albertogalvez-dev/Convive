import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { BlogIndex } from './blog-index';

describe('BlogIndex', () => {
  it('publishes reviewed content with a canonical index page', async () => {
    await TestBed.configureTestingModule({
      imports: [BlogIndex],
      providers: [provideRouter([])],
    }).compileComponents();

    const fixture = TestBed.createComponent(BlogIndex);
    fixture.detectChanges();

    const page = fixture.nativeElement as HTMLElement;
    expect(page.querySelector('h1')?.textContent).toContain('Ideas para escuchar');
    expect(page.querySelector('a[href="/blog/escuchar-y-ordenar-comunicaciones"]')).toBeTruthy();
    expect(document.title).toBe('Blog | Convive');
    expect(document.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe(
      'https://conviveaula.com/blog/',
    );
  });
});
