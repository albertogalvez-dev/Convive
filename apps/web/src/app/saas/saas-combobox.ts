import { Component, computed, input, model, signal } from '@angular/core';

/**
 * SaaS 2.0 — a small type-to-filter combobox. You type, the list narrows
 * ("And" -> "Andalucía"); arrows move, Enter / click selects, Escape closes.
 * Fictional-data review screens only.
 */
@Component({
  selector: 'app-saas-combobox',
  standalone: true,
  templateUrl: './saas-combobox.html',
  styleUrl: './saas-combobox.scss',
})
export class SaasCombobox {
  readonly label = input.required<string>();
  readonly options = input.required<readonly string[]>();
  readonly placeholder = input<string>('');
  readonly optional = input<boolean>(false);
  /** When true, only an exact option may remain on blur (used for the CCAA). */
  readonly strict = input<boolean>(false);

  readonly value = model<string>('');

  protected readonly text = signal('');
  protected readonly open = signal(false);
  protected readonly activeIndex = signal(-1);

  private syncedFrom = '';

  protected readonly filtered = computed<readonly string[]>(() => {
    const query = this.text().trim().toLowerCase();
    const all = this.options();
    if (!query) return all.slice(0, 60);

    const starts: string[] = [];
    const contains: string[] = [];
    for (const option of all) {
      const lower = option.toLowerCase();
      if (lower.startsWith(query)) starts.push(option);
      else if (lower.includes(query)) contains.push(option);
    }
    return [...starts, ...contains].slice(0, 60);
  });

  constructor() {
    // Reflect an external value change into the text field.
    // (queueMicrotask keeps it out of the current change-detection pass.)
  }

  protected onFocus(): void {
    if (this.syncedFrom !== this.value()) {
      this.text.set(this.value());
      this.syncedFrom = this.value();
    }
    this.open.set(true);
    this.activeIndex.set(-1);
  }

  protected onInput(raw: string): void {
    this.text.set(raw);
    this.open.set(true);
    this.activeIndex.set(this.filtered().length ? 0 : -1);
  }

  protected onKey(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
      this.open.set(false);
      return;
    }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      this.open.set(true);
      const count = this.filtered().length;
      if (!count) return;
      const step = event.key === 'ArrowDown' ? 1 : -1;
      this.activeIndex.update((i) => (i + step + count) % count);
      return;
    }
    if (event.key === 'Enter') {
      const item = this.filtered()[this.activeIndex()];
      if (this.open() && item) {
        event.preventDefault();
        this.pick(item);
      }
    }
  }

  protected pick(option: string): void {
    this.text.set(option);
    this.value.set(option);
    this.syncedFrom = option;
    this.open.set(false);
    this.activeIndex.set(-1);
  }

  protected onBlur(): void {
    // Let a click on an option run first.
    setTimeout(() => {
      this.open.set(false);
      const typed = this.text().trim();
      const match = this.options().find((o) => o.toLowerCase() === typed.toLowerCase());
      if (match) {
        this.pick(match);
      } else if (this.strict()) {
        this.text.set(this.value());
      } else {
        this.value.set(typed);
        this.syncedFrom = typed;
      }
    });
  }
}
