import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

interface ScreenGroup {
  heading: string;
  screens: { route: string; title: string; summary: string }[];
}

const GROUPS: readonly ScreenGroup[] = [
  {
    heading: 'Espacio profesional',
    screens: [
      {
        route: 'panel',
        title: 'Inicio',
        summary: 'Lo que necesitas atender primero, y el resumen del centro debajo.',
      },
      {
        route: 'pendientes',
        title: 'Pendientes',
        summary: 'Todo lo que espera tu acción, con filtros y avisos.',
      },
      {
        route: 'caso',
        title: 'Seguimiento del caso',
        summary: 'Tareas del protocolo y la historia completa del caso.',
      },
      {
        route: 'calendario',
        title: 'Mi agenda',
        summary: 'Tu trabajo con plazo y lo que apuntes tú, en un calendario.',
      },
      {
        route: 'avisos',
        title: 'Avisos',
        summary: 'Lo que ha pasado y cómo quieres que te avisemos.',
      },
      {
        route: 'miembros',
        title: 'Miembros',
        summary: 'Quién trabaja en el centro, con qué rol y en qué estado.',
      },
    ],
  },
  {
    heading: 'Cuenta y centro',
    screens: [
      { route: 'registro', title: 'Crear cuenta', summary: 'Alta en un paso.' },
      { route: 'cuenta', title: 'Tu cuenta', summary: 'Cuando aún no perteneces a ningún centro.' },
      { route: 'ajustes', title: 'Ajustes de cuenta', summary: 'Perfil, centros y sesión.' },
      { route: 'centro', title: 'Crear centro', summary: 'Un formulario, sin pasos.' },
      {
        route: 'centro-identidad',
        title: 'Identidad del centro',
        summary: 'Nombre visible, logotipo, idioma y zona horaria.',
      },
    ],
  },
  {
    heading: 'Quien comunica',
    screens: [
      { route: 'entrada', title: 'Comunicación', summary: 'El formulario público, paso a paso.' },
      {
        route: 'entrada-confirmacion',
        title: 'Comunicación enviada',
        summary: 'El código para volver a entrar.',
      },
      {
        route: 'entrada-revocada',
        title: 'Enlace no disponible',
        summary: 'Qué se ve cuando el enlace se ha revocado.',
      },
      { route: 'cartel', title: 'Cartel del centro', summary: 'QR, enlace y materiales.' },
    ],
  },
  {
    heading: 'Documentos',
    screens: [
      {
        route: 'documento',
        title: 'Ficha de caso',
        summary: 'El PDF que el centro descarga, sin marca de Convive.',
      },
    ],
  },
];

@Component({
  selector: 'app-saas-review-index',
  standalone: true,
  imports: [RouterLink],
  template: `
    <div class="wrap">
      <header class="index-heading">
        <img src="/convive-logo.svg" alt="Convive" width="168" height="55" />
        <p class="eyebrow">Convive SaaS 2.0</p>
        <h1>Las pantallas</h1>
        <p class="lead">
          Cada pantalla del producto, con datos ficticios de ejemplo. Escritorio y móvil.
        </p>
      </header>

      @for (group of groups; track group.heading) {
        <section class="group">
          <h2>{{ group.heading }}</h2>
          <ul>
            @for (screen of group.screens; track screen.route) {
              <li>
                <a [routerLink]="screen.route">
                  <span class="copy">
                    <strong>{{ screen.title }}</strong>
                    <span>{{ screen.summary }}</span>
                  </span>
                  <span class="arrow" aria-hidden="true">&rarr;</span>
                </a>
              </li>
            }
          </ul>
        </section>
      }
    </div>
  `,
  styles: `
    :host {
      display: block;
      min-height: 100vh;
      padding: 4rem clamp(1.25rem, 6vw, 4rem) 5rem;
      background:
        radial-gradient(circle at 95% 3%, #edf6fb 0, transparent 28rem),
        radial-gradient(circle at 5% 95%, #eaf8fc 0, transparent 28rem), #f5f8fc;
    }
    .wrap {
      max-width: 46rem;
      margin: 0 auto;
    }
    .index-heading {
      margin-bottom: 2.6rem;
    }
    .index-heading img {
      display: block;
      height: auto;
      margin-bottom: 1.8rem;
    }
    .eyebrow {
      margin: 0 0 0.5rem;
      color: #176f9c;
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }
    h1 {
      margin: 0 0 0.6rem;
      color: #102b60;
      font-size: clamp(2rem, 4vw, 2.7rem);
      letter-spacing: -0.04em;
    }
    .lead {
      margin: 0;
      color: #5d6f8e;
      font-size: 0.95rem;
      line-height: 1.65;
    }
    .group {
      margin-bottom: 2.2rem;
    }
    .group h2 {
      margin: 0 0 0.8rem;
      color: #637493;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    ul {
      list-style: none;
      margin: 0;
      padding: 0;
      overflow: hidden;
      border: 1px solid #d4dfed;
      border-radius: 1rem;
      background: #fff;
      box-shadow: 0 1.2rem 2.6rem rgba(32, 62, 111, 0.07);
    }
    li + li {
      border-top: 1px solid #e6ecf5;
    }
    a {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.3rem;
      text-decoration: none;
    }
    a:hover {
      background: #f7fbfd;
    }
    .copy {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
    }
    .copy strong {
      color: #102b60;
      font-size: 1rem;
      font-weight: 800;
    }
    .copy span {
      color: #60718f;
      font-size: 0.8rem;
    }
    .arrow {
      color: #176f9c;
      font-size: 1.15rem;
    }
  `,
})
export class SaasReviewIndex {
  protected readonly groups = GROUPS;
}
