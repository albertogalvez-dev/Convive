import { Component, computed, signal } from '@angular/core';

import { SaasShell } from './saas-shell';
import {
  describeDeadline,
  PROTOTYPE_NOW,
  WORK_ITEMS,
  type DeadlineView,
  type WorkItem,
} from './work-items';

/** Convive SaaS 2.0 — inicio profesional. Fictional sample data. */

type Bucket = 'now' | 'week';

interface QueueRow {
  item: WorkItem;
  deadline: DeadlineView;
  bucket: Bucket;
}

interface QueueGroup {
  bucket: Bucket;
  label: string;
  rows: QueueRow[];
}

const BUCKET_LABELS: Record<Bucket, string> = {
  now: 'Para hoy',
  week: 'Esta semana',
};

@Component({
  selector: 'app-saas-dashboard',
  standalone: true,
  imports: [SaasShell],
  templateUrl: './saas-dashboard.html',
  styleUrl: './saas-dashboard.scss',
})
export class SaasDashboard {
  private readonly now = PROTOTYPE_NOW;

  private readonly rows: readonly QueueRow[] = WORK_ITEMS.map((item) => {
    const deadline = describeDeadline(item.due, this.now);
    const bucket: Bucket =
      deadline.state === 'overdue' || deadline.state === 'today' ? 'now' : 'week';
    return { item, deadline, bucket };
  });

  protected readonly overdueCount = this.rows.filter((row) => row.deadline.state === 'overdue')
    .length;
  protected readonly todayCount = this.rows.filter((row) => row.bucket === 'now').length;

  protected readonly groups: readonly QueueGroup[] = (['now', 'week'] as Bucket[])
    .map((bucket) => ({
      bucket,
      label: BUCKET_LABELS[bucket],
      rows: this.rows.filter((row) => row.bucket === bucket),
    }))
    .filter((group) => group.rows.length > 0);

  protected readonly selectedId = signal<string>(this.rows[0].item.id);
  protected readonly selected = computed<QueueRow>(
    () => this.rows.find((row) => row.item.id === this.selectedId()) ?? this.rows[0],
  );

  protected select(id: string): void {
    this.selectedId.set(id);
  }
}
