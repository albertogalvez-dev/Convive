import { Component, DestroyRef, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { SaasCombobox } from './saas-combobox';
import { SaasShell } from './saas-shell';
import { SPANISH_MUNICIPALITIES } from './municipalities';

/**
 * SaaS 2.0 — centre creation and centre identity (issue #512, C-1 / C-3).
 *
 * `create` is an onboarding step, before any centre exists: a paced
 * one-question-per-screen flow (same shape as the anonymous reporting flow),
 * never the professional panel. `identity` is C-3, run by the centre's
 * administrator from inside the panel; those fields are editable only with the
 * centre-administration capability.
 *
 * The applicable-guidance reference is disclosed at centre creation and echoed
 * on the identity view — never repeated on the working case (charter §6.4 P-12,
 * revised 2026-09-06).
 */

export type CentreView = 'create' | 'identity';

interface TerritorialGuide {
  ccaa: string;
  source: string;
  reviewed: string;
  officialUrl: string;
}

const GUIDES: readonly TerritorialGuide[] = [
  {
    ccaa: 'Andalucía',
    source: 'Protocolo de acoso escolar de Andalucía (Orden de 20/06/2011)',
    reviewed: '11/08/2026',
    officialUrl: 'https://www.juntadeandalucia.es/boja/2011/132/1',
  },
  {
    ccaa: 'Aragón',
    source: 'Protocolo de Aragón (Orden ECD/584/2026)',
    reviewed: '17/08/2026',
    officialUrl: 'https://www.boa.aragon.es/cgi-bin/EBOA/BRSCGI?CMD=VEROBJ&MLKOB=1444982450505',
  },
  {
    ccaa: 'Principado de Asturias',
    source: 'Protocolo de Asturias (Circular de 30/01/2024)',
    reviewed: '17/08/2026',
    officialUrl:
      'https://www.educastur.es/-/instrucciones-de-aplicacion-del-protocolo-de-actuacion-ante-situaciones-de-posible-acoso-escolar',
  },
  {
    ccaa: 'Illes Balears',
    source: 'Protocol de les Illes Balears (Convivèxit, revisió 09/2023)',
    reviewed: '17/08/2026',
    officialUrl: 'https://www.caib.es/sites/convivexit/f/439010',
  },
  {
    ccaa: 'Canarias',
    source: 'Marco de actuación de Canarias (DGOIPE, 2015)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.gobiernodecanarias.org/educacion/web/servicios/inspeccion_educativa/normativa_clasificada/orientaciones_programas_protocolos/protocolos/',
  },
  {
    ccaa: 'Cantabria',
    source: 'Protocolo de Cantabria (Educantabria, 09/2016)',
    reviewed: '17/08/2026',
    officialUrl:
      'https://www.educantabria.es/documents/39930/385471/Protocolo+de+actuaci%C3%B3n+ante+una+posible+situaci%C3%B3n+de+acoso+escolar_.pdf/a4613219-c95e-db41-953d-4f3af21dcd44?t=1683636946944',
  },
  {
    ccaa: 'Castilla-La Mancha',
    source: 'Protocolo de Castilla-La Mancha (Resolución de 18/01/2017)',
    reviewed: '17/08/2026',
    officialUrl:
      'http://docm.jccm.es/portaldocm/descargarArchivo.do?ruta=2017/01/20/pdf/2017_632.pdf&tipo=rutaDocm',
  },
  {
    ccaa: 'Castilla y León',
    source: 'Protocolo de Castilla y León (Orden EDU/1071/2017)',
    reviewed: '17/08/2026',
    officialUrl: 'http://bocyl.jcyl.es/boletines/2017/12/14/pdf/BOCYL-D-14122017-3.pdf',
  },
  {
    ccaa: 'Catalunya',
    source: 'Protocol de Catalunya davant la violència (07/2024)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://educacio.gencat.cat/ca/departament/publicacions/protocols/actuacio-davant-violencia-ambit-educatiu/',
  },
  {
    ccaa: 'Comunitat Valenciana',
    source: 'Decret 193/2025 de convivència (Comunitat Valenciana)',
    reviewed: '17/08/2026',
    officialUrl: 'https://dogv.gva.es/datos/2025/12/17/pdf/2025_50344_es.pdf',
  },
  {
    ccaa: 'Extremadura',
    source: 'Protocolo de Extremadura (Educarex, 10/2016)',
    reviewed: '17/08/2026',
    officialUrl: 'https://www.educarex.es/pub/cont/com/0033/documentos/procolo_acoso.pdf',
  },
  {
    ccaa: 'Galicia',
    source: 'Protocolo de Galicia (educonvives.gal)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.edu.xunta.gal/portal/sites/web/files/protocolo_educativo_para_a_prevencion_deteccion_e_tratamento_do_acoso_e_ciberacoso_escolarpdf.pdf',
  },
  {
    ccaa: 'Comunidad de Madrid',
    source: 'Protocolo de la Comunidad de Madrid (act. 30/04/2026)',
    reviewed: '18/08/2026',
    officialUrl: 'https://www.educa2.madrid.org/web/convivencia/acoso-escolar',
  },
  {
    ccaa: 'Región de Murcia',
    source: 'Instrucciones de Murcia (Resolución de 13/11/2017)',
    reviewed: '18/08/2026',
    officialUrl: 'https://www.carm.es/web/pagina?IDCONTENIDO=4105&IDTIPO=100',
  },
  {
    ccaa: 'Comunidad Foral de Navarra',
    source: 'Orden Foral 204/2010 de convivencia (Navarra)',
    reviewed: '18/08/2026',
    officialUrl: 'http://www.lexnavarra.navarra.es/detalle.asp?r=9755',
  },
  {
    ccaa: 'País Vasco / Euskadi',
    source: 'Protocolo de Euskadi (Resolución de 12/03/2024)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.euskadi.eus/contenidos/informacion/hezkuntza_ikuskaritza_dok_lh/es_def/adjuntos/Resolucion-protocolos-de-acoso-y-conducta-suicida.pdf',
  },
  {
    ccaa: 'La Rioja',
    source: 'Protocolo de La Rioja (Resolución 126/2023)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.larioja.org/edu-aten-diversidad/es/protocolos/protocolo-acoso-escolar.ficheros/1493318-Acoso%20escolar%20actualizado.pdf',
  },
  {
    ccaa: 'Ciudad de Ceuta',
    source: 'Protocolo de Ceuta (Resolución MEFPD de 08/08/2024)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.educacionfpydeportes.gob.es/dam/jcr:42b6494e-3822-46e1-9c80-bda5374f8ecb/resolucion-acoso-escolar.pdf',
  },
  {
    ccaa: 'Ciudad de Melilla',
    source: 'Protocolo de Melilla (Resolución MEFPD de 08/08/2024)',
    reviewed: '18/08/2026',
    officialUrl:
      'https://www.educacionfpydeportes.gob.es/dam/jcr:42b6494e-3822-46e1-9c80-bda5374f8ecb/resolucion-acoso-escolar.pdf',
  },
];

const ETAPAS = ['Infantil', 'Primaria', 'Secundaria', 'Bachillerato', 'FP', 'Otra'] as const;

@Component({
  selector: 'app-saas-centre',
  standalone: true,
  imports: [SaasShell, SaasCombobox, RouterLink],
  templateUrl: './saas-centre.html',
  styleUrl: './saas-centre.scss',
  host: { '(document:keydown.escape)': 'dismissPopup()' },
})
export class SaasCentre {
  protected readonly view: CentreView =
    (inject(ActivatedRoute).snapshot.data['view'] as CentreView) ?? 'create';

  protected readonly ccaaNames = GUIDES.map((g) => g.ccaa);
  protected readonly municipios = SPANISH_MUNICIPALITIES;
  protected readonly etapas = ETAPAS;

  /** The centre already has a bound guide on the identity view. */
  protected readonly centreGuide = GUIDES[0];

  // --- The paced creation flow -------------------------------------------
  protected readonly totalSteps = 5;
  protected readonly stepList = Array.from({ length: this.totalSteps }, (_, i) => i + 1);
  protected readonly step = signal(1);
  protected readonly submitted = signal(false);

  /** The segmented progress bar only walks backwards, like the report flow. */
  protected goToStep(target: number): void {
    if (target < this.step()) {
      this.step.set(target);
    }
  }

  protected readonly nombre = signal('');
  protected readonly ccaa = signal('');
  protected readonly selectedEtapas = signal<string[]>([]);
  protected readonly municipio = signal('');

  protected next(): void {
    if (this.step() < this.totalSteps) {
      this.step.update((s) => s + 1);
    } else {
      this.submitted.set(true);
    }
  }

  protected back(): void {
    this.step.update((s) => Math.max(1, s - 1));
  }

  protected toggleEtapa(value: string): void {
    this.selectedEtapas.update((current) =>
      current.includes(value) ? current.filter((e) => e !== value) : [...current, value],
    );
  }

  protected hasEtapa(value: string): boolean {
    return this.selectedEtapas().includes(value);
  }

  // --- Applicable-guidance popup (fires when a CCAA is chosen) -----------
  protected readonly popup = signal<TerritorialGuide | null>(null);
  protected readonly popupSeconds = signal(0);
  private popupInterval?: ReturnType<typeof setInterval>;

  constructor() {
    inject(DestroyRef).onDestroy(() => clearInterval(this.popupInterval));
  }

  protected onCcaaChange(value: string): void {
    this.ccaa.set(value);

    const guide = GUIDES.find((g) => g.ccaa === value) ?? null;
    clearInterval(this.popupInterval);
    this.popup.set(guide);
    if (guide) {
      this.popupSeconds.set(5);
      this.popupInterval = setInterval(() => {
        this.popupSeconds.update((s) => s - 1);
        if (this.popupSeconds() <= 0) this.dismissPopup();
      }, 1000);
    }
  }

  protected dismissPopup(): void {
    clearInterval(this.popupInterval);
    this.popup.set(null);
    this.popupSeconds.set(0);
  }
}
