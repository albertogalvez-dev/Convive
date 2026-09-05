import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';
import { PROTOTYPE_NOW, WORK_ITEMS, sameDay, type WorkKind } from './work-items';

/**
 * Convive SaaS 2.0 — mi agenda. A per-person calendar: it shows this
 * professional's dated work (the same items as Inicio) alongside personal
 * entries they add themselves. Fictional sample data; the personal entries
 * live only in this component.
 */

type EntryKind = WorkKind | 'personal';

interface CalendarEntry {
  date: Date;
  time: string;
  title: string;
  reference?: string;
  kind: EntryKind;
}

interface DayCell {
  date: Date;
  inMonth: boolean;
  isToday: boolean;
  entries: CalendarEntry[];
}

const WEEKDAYS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
const MONTH_FMT = new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' });
const TIME_FMT = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' });
const DAY_FMT = new Intl.DateTimeFormat('es-ES', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
});

function capitalise(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

@Component({
  selector: 'app-saas-calendar',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-calendar.html',
  styleUrl: './saas-calendar.scss',
})
export class SaasCalendar {
  protected readonly weekdays = WEEKDAYS;

  private readonly workEntries: readonly CalendarEntry[] = WORK_ITEMS.map((item) => ({
    date: item.due,
    time: TIME_FMT.format(item.due),
    title: item.title,
    reference: item.reference,
    kind: item.kind,
  }));

  protected readonly personalEntries = signal<CalendarEntry[]>([
    {
      date: new Date('2026-09-08T16:00:00'),
      time: '16:00',
      title: 'Preparar la reunión de coordinación',
      kind: 'personal',
    },
    {
      date: new Date('2026-09-10T11:00:00'),
      time: '11:00',
      title: 'Llamar a la orientadora del CEIP',
      kind: 'personal',
    },
  ]);

  /** The month on screen. Starts on the prototype's month. */
  protected readonly monthAnchor = signal(
    new Date(PROTOTYPE_NOW.getFullYear(), PROTOTYPE_NOW.getMonth(), 1),
  );
  protected readonly monthLabel = computed(() => capitalise(MONTH_FMT.format(this.monthAnchor())));

  protected readonly selectedDate = signal(new Date(PROTOTYPE_NOW));
  protected readonly selectedLabel = computed(() =>
    capitalise(DAY_FMT.format(this.selectedDate())),
  );

  private readonly allEntries = computed<CalendarEntry[]>(() => [
    ...this.workEntries,
    ...this.personalEntries(),
  ]);

  protected readonly weeks = computed<DayCell[][]>(() => {
    const anchor = this.monthAnchor();
    const firstOfMonth = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
    const offset = (firstOfMonth.getDay() + 6) % 7; // Monday-first
    const gridStart = new Date(firstOfMonth);
    gridStart.setDate(1 - offset);

    const entries = this.allEntries();
    const weeks: DayCell[][] = [];

    for (let week = 0; week < 6; week++) {
      const row: DayCell[] = [];
      for (let day = 0; day < 7; day++) {
        const date = new Date(gridStart);
        date.setDate(gridStart.getDate() + week * 7 + day);
        row.push({
          date,
          inMonth: date.getMonth() === anchor.getMonth(),
          isToday: sameDay(date, PROTOTYPE_NOW),
          entries: entries
            .filter((entry) => sameDay(entry.date, date))
            .sort((a, b) => a.time.localeCompare(b.time)),
        });
      }
      weeks.push(row);
    }
    return weeks;
  });

  protected readonly selectedEntries = computed<CalendarEntry[]>(() =>
    this.allEntries()
      .filter((entry) => sameDay(entry.date, this.selectedDate()))
      .sort((a, b) => a.time.localeCompare(b.time)),
  );

  // New-entry form -------------------------------------------------------

  protected readonly draftDate = signal(this.toInputDate(PROTOTYPE_NOW));
  protected readonly draftTime = signal('09:00');
  protected readonly draftTitle = signal('');
  protected readonly canAdd = computed(() => this.draftTitle().trim().length > 0);

  protected shiftMonth(delta: number): void {
    this.monthAnchor.update((current) => {
      const next = new Date(current);
      next.setMonth(current.getMonth() + delta);
      return next;
    });
  }

  protected selectDay(date: Date): void {
    this.selectedDate.set(date);
    this.draftDate.set(this.toInputDate(date));
  }

  protected addEntry(): void {
    if (!this.canAdd()) return;
    const [year, month, day] = this.draftDate().split('-').map(Number);
    const [hour, minute] = this.draftTime().split(':').map(Number);
    const date = new Date(year, month - 1, day, hour, minute);

    this.personalEntries.update((current) => [
      ...current,
      { date, time: this.draftTime(), title: this.draftTitle().trim(), kind: 'personal' },
    ]);
    this.draftTitle.set('');
    this.selectDay(date);
  }

  protected onInput(target: EventTarget | null, setter: (value: string) => void): void {
    setter((target as HTMLInputElement).value);
  }

  private toInputDate(date: Date): string {
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${date.getFullYear()}-${month}-${day}`;
  }
}
