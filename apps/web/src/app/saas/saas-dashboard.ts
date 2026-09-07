import {
  Component,
  DestroyRef,
  ElementRef,
  computed,
  inject,
  signal,
  viewChild,
} from '@angular/core';

import { SaasShell } from './saas-shell';
import {
  deadlineChip,
  deadlineState,
  WORK_ITEMS,
  type DeadlineChip,
  type DeadlineState,
  type WorkItem,
} from './work-items';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

interface Row {
  item: WorkItem;
  chip: DeadlineChip;
}

interface Column {
  state: DeadlineState;
  label: string;
  rows: Row[];
}

const COLUMNS: readonly { state: DeadlineState; label: string }[] = [
  { state: 'overdue', label: 'Fuera de plazo' },
  { state: 'today', label: 'Hoy' },
  { state: 'upcoming', label: 'Esta semana' },
];

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
  host: { '(document:keydown.escape)': 'close()' },
})
export class SaasDashboard {
  private readonly now = signal(new Date());
  protected readonly openId = signal<string | null>(null);
  private readonly dialog = viewChild<ElementRef<HTMLElement>>('dialog');

  constructor() {
    const ticker = setInterval(() => this.now.set(new Date()), 1000);
    inject(DestroyRef).onDestroy(() => clearInterval(ticker));
  }

  protected readonly columns = computed<Column[]>(() => {
    const now = this.now();
    return COLUMNS.map((column) => ({
      ...column,
      rows: WORK_ITEMS.filter((item) => deadlineState(item.due, now) === column.state)
        .slice()
        .sort((a, b) => a.due.getTime() - b.due.getTime())
        .map<Row>((item) => ({ item, chip: deadlineChip(item.due, now) })),
    }));
  });

  protected readonly openRow = computed<Row | null>(() => {
    const id = this.openId();
    if (!id) return null;
    for (const column of this.columns()) {
      const row = column.rows.find((candidate) => candidate.item.id === id);
      if (row) return row;
    }
    return null;
  });

  protected open(id: string): void {
    this.openId.set(id);
    // Move focus into the dialog once it has rendered (no CDK in this project).
    setTimeout(() => this.dialog()?.nativeElement.focus());
  }

  protected close(): void {
    this.openId.set(null);
  }
}
