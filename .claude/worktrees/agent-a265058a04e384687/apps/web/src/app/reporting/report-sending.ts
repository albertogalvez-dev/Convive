import { Component } from '@angular/core';
import { provideTranslocoScope, TranslocoPipe } from '@jsverse/transloco';

@Component({
  selector: 'app-report-sending',
  standalone: true,
  imports: [TranslocoPipe],
  providers: [provideTranslocoScope('report-sending')],
  templateUrl: './report-sending.html',
  styleUrl: './report-sending.scss',
})
export class ReportSending {}
