import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';

import {
  AccountAdministrationOrganisation,
  ProfessionalAccount,
  ProfessionalAccountRole,
  ProfessionalAccountsService,
  OrganisationMembership,
} from './professional-accounts.service';

@Component({
  selector: 'app-professional-accounts',
  standalone: true,
  imports: [DatePipe, FormsModule],
  templateUrl: './professional-accounts.html',
  styleUrl: './professional-accounts.scss',
})
export class ProfessionalAccounts {
  private readonly accountsApi = inject(ProfessionalAccountsService);
  protected readonly loading = signal(true);
  protected readonly organisations = signal<AccountAdministrationOrganisation[]>([]);
  protected readonly accounts = signal<ProfessionalAccount[]>([]);
  protected readonly memberships = signal<OrganisationMembership[]>([]);
  protected readonly error = signal<string | null>(null);
  protected readonly message = signal<string | null>(null);
  protected readonly credential = signal<{ secret: string; expiresAt: string } | null>(null);
  protected selectedOrganisationId = '';
  protected invitation = { name: '', email: '', role: 'triage' as ProfessionalAccountRole };

  constructor() {
    this.loadAdministrations();
  }

  protected selectOrganisation(): void {
    this.credential.set(null);
    this.message.set(null);
    this.loadAccounts();
  }

  protected invite(): void {
    if (!this.selectedOrganisationId) return;
    this.error.set(null);
    this.accountsApi.invite(this.selectedOrganisationId, this.invitation).subscribe({
      next: (result) => {
        this.credential.set(result.credential);
        this.message.set(
          'La cuenta ficticia se ha creado. Entrega el código una sola vez por un canal autorizado.',
        );
        this.invitation = { name: '', email: '', role: 'triage' };
        this.loadAccounts();
      },
      error: () => this.error.set('No se puede crear esta cuenta en el centro seleccionado.'),
    });
  }

  protected resetPassword(account: ProfessionalAccount): void {
    if (!this.selectedOrganisationId) return;
    this.error.set(null);
    this.accountsApi.resetPassword(this.selectedOrganisationId, account.id).subscribe({
      next: (result) => {
        this.credential.set(result.credential);
        this.message.set('Se ha emitido un código de restablecimiento de un solo uso.');
      },
      error: () => this.error.set('No se puede emitir un código para esta cuenta.'),
    });
  }

  protected changeStatus(
    account: ProfessionalAccount,
    action: 'suspend' | 'reactivate' | 'deactivate',
  ): void {
    if (!this.selectedOrganisationId) return;
    this.error.set(null);
    this.accountsApi.changeStatus(this.selectedOrganisationId, account.id, action).subscribe({
      next: () => {
        this.message.set(
          'El estado de la cuenta se ha actualizado. Las sesiones anteriores dejan de ser válidas.',
        );
        this.loadAccounts();
      },
      error: () => this.error.set('No se puede cambiar el estado de esta cuenta.'),
    });
  }

  protected changeMembership(
    membership: OrganisationMembership,
    action: 'suspend' | 'resume' | 'remove',
  ): void {
    if (!this.selectedOrganisationId) return;
    this.accountsApi
      .changeMembership(this.selectedOrganisationId, membership.id, { action })
      .subscribe({
        next: () => {
          this.message.set('La membresía ha cambiado. No concede acceso automático a ningún caso.');
          this.loadMemberships();
        },
        error: () => this.error.set('No se puede cambiar esta membresía.'),
      });
  }

  protected statusLabel(status: ProfessionalAccount['status']): string {
    return {
      invited: 'Pendiente de activación',
      active: 'Activa',
      suspended: 'Suspendida',
      deactivated: 'Desactivada',
    }[status];
  }

  private loadAdministrations(): void {
    this.accountsApi.administrations().subscribe({
      next: ({ items }) => {
        this.organisations.set(items);
        this.selectedOrganisationId = items[0]?.id ?? '';
        this.loading.set(false);
        this.loadAccounts();
      },
      error: () => {
        this.error.set('No se puede cargar la administración de cuentas.');
        this.loading.set(false);
      },
    });
  }

  private loadAccounts(): void {
    if (!this.selectedOrganisationId) {
      this.accounts.set([]);
      return;
    }
    this.accountsApi.accounts(this.selectedOrganisationId).subscribe({
      next: ({ items }) => this.accounts.set(items),
      error: () => this.error.set('No se puede cargar las cuentas de este centro.'),
    });
    this.loadMemberships();
  }

  private loadMemberships(): void {
    if (!this.selectedOrganisationId) return;
    this.accountsApi.memberships(this.selectedOrganisationId).subscribe({
      next: ({ items }) => this.memberships.set(items),
      error: () => this.error.set('No se pueden cargar las membresías.'),
    });
  }
}
