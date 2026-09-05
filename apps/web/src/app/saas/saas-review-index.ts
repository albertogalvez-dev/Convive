import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

interface ScreenLink {
  route: string;
  issue: string;
  title: string;
  summary: string;
  ready: boolean;
}

const SCREENS: readonly ScreenLink[] = [
  {
    route: 'panel',
    issue: '#508',
    title: 'Panel principal',
    summary: '"Necesitas actuar" primero, panorama después, distinto por rol.',
    ready: true,
  },
  {
    route: 'registro',
    issue: '#511',
    title: 'Registro, cuenta sin centro y ajustes',
    summary: 'Alta en un paso, estado sin centro, ajustes de cuenta.',
    ready: true,
  },
  {
    route: 'centro',
    issue: '#512',
    title: 'Crear centro e identidad del centro',
    summary: 'Formulario de creación y pantalla de identidad con logo.',
    ready: true,
  },
  {
    route: 'miembros',
    issue: '#513',
    title: 'Gestión de miembros',
    summary: 'Lista de miembros con rol, estado y acciones en la propia fila.',
    ready: true,
  },
  {
    route: 'entrada',
    issue: '#516-#518',
    title: 'Recorrido de quien reporta',
    summary: 'Entrada pública, confirmación, enlace revocado, cartel QR.',
    ready: true,
  },
  {
    route: 'pendientes',
    issue: '#526',
    title: 'Pendientes y avisos',
    summary: 'Pantalla completa de pendientes y el sistema de notificaciones.',
    ready: true,
  },
  {
    route: 'caso',
    issue: '#527',
    title: 'Espacio de trabajo del caso',
    summary: 'Cabecera fija, próximos pasos y una historia filtrable.',
    ready: true,
  },
  {
    route: 'export',
    issue: '#543',
    title: 'Exportación PDF',
    summary: 'Plantilla de documento sin marca de Convive.',
    ready: true,
  },
];

@Component({
  selector: 'app-saas-review-index',
  standalone: true,
  imports: [RouterLink],
  template: `
    <div class="wrap">
      <p class="eyebrow">Convive SaaS 2.0 · revisión DR-1</p>
      <h1>Prototipos de pantallas</h1>
      <p class="lead">
        Cada pantalla está construida con el sistema de diseño real de Convive y datos ficticios.
        Sirven para que decidas la dirección visual y de interacción antes de construir cada
        superficie de verdad.
      </p>
      <ul class="screens">
        @for (screen of screens; track screen.route) {
          <li [class.pending]="!screen.ready">
            @if (screen.ready) {
              <a [routerLink]="screen.route">
                <span class="issue">{{ screen.issue }}</span>
                <span class="body">
                  <strong>{{ screen.title }}</strong>
                  <span>{{ screen.summary }}</span>
                </span>
                <span class="go" aria-hidden="true">&rarr;</span>
              </a>
            } @else {
              <span class="row">
                <span class="issue">{{ screen.issue }}</span>
                <span class="body">
                  <strong>{{ screen.title }}</strong>
                  <span>{{ screen.summary }}</span>
                </span>
                <span class="soon">En preparación</span>
              </span>
            }
          </li>
        }
      </ul>
    </div>
  `,
  styles: `
    :host {
      display: block;
      min-height: 100vh;
      padding: 4rem clamp(1.25rem, 6vw, 4rem);
      background:
        radial-gradient(circle at 95% 3%, #edf6fb 0, transparent 28rem),
        radial-gradient(circle at 5% 95%, #eaf8fc 0, transparent 28rem), #f5f8fc;
    }
    .wrap {
      max-width: 46rem;
      margin: 0 auto;
    }
    .eyebrow {
      margin: 0 0 0.5rem;
      color: #0f7ba3;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.13em;
      text-transform: uppercase;
    }
    h1 {
      margin: 0 0 0.6rem;
      color: #102b60;
      font-size: clamp(1.8rem, 4vw, 2.4rem);
      font-weight: 800;
      letter-spacing: -0.03em;
    }
    .lead {
      margin: 0 0 2.4rem;
      color: #5d6f8e;
      font-size: 0.95rem;
      line-height: 1.65;
    }
    .screens {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }
    .screens a,
    .screens .row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.15rem;
      border: 1px solid #e1e7f0;
      border-radius: 0.9rem;
      background: #fff;
      box-shadow: 0 1px 2px rgba(18, 36, 74, 0.05);
      text-decoration: none;
    }
    .screens a:hover {
      border-color: #b8cbe0;
    }
    li.pending .row {
      background: #f8fafd;
    }
    .issue {
      flex: none;
      width: 4.5rem;
      color: #8b97ad;
      font-size: 0.74rem;
      font-weight: 800;
    }
    .body {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }
    .body strong {
      color: #102b60;
      font-size: 0.95rem;
    }
    .body span {
      color: #5d6f8e;
      font-size: 0.8rem;
    }
    .go {
      flex: none;
      color: #0f7ba3;
      font-size: 1.2rem;
    }
    .soon {
      flex: none;
      color: #8b97ad;
      font-size: 0.72rem;
      font-weight: 700;
    }
  `,
})
export class SaasReviewIndex {
  protected readonly screens = SCREENS;
}
