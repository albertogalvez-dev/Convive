# Memoria técnica de Convive

**Estado:** memoria verificable de la demostración pública con datos ficticios
**Última revisión:** 1 de septiembre de 2026

## Resumen

Convive es una aplicación web de código abierto para recibir comunicaciones de convivencia escolar y ordenar su valoración interna. Una comunicación no se convierte automáticamente en un caso: un profesional autorizado valora, registra la decisión y, si procede, trabaja el caso con tareas, personas vinculadas, historial y una fuente territorial aplicable.

Esta memoria describe el producto y el código existentes en el repositorio. La demostración usa exclusivamente datos ficticios. No habilita atención real, no es un canal de emergencia y no afirma cumplimiento jurídico, institucional u operativo para datos reales.

## Producto y recorridos

| Entrada | Propósito | Límite |
| --- | --- | --- |
| Sitio público | Explica Convive, recursos de ayuda y la demostración guiada | No sustituye una emergencia ni un centro real |
| Comunicación pública | Recorre un formulario sin cuenta | El acceso posterior usa un secreto de un solo uso y una capacidad limitada a esa comunicación |
| Área profesional | Muestra bandeja, casos, tareas, evidencias y ajustes | La demo ofrece recorridos preparados y no persistentes donde corresponde |

La demostración pública ofrece un recorrido vacío y un ejemplo ficticio ya completado. El ejemplo estático no posee una capacidad, no llama a la API ni aparenta guardar una comunicación. El visitante ve dos entradas profesionales: **Gestión de casos** y **Administración**. Los permisos de responsable, colaborador u observador siguen siendo reglas internas por caso.

Las diecisiete comunidades autónomas, Ceuta y Melilla se modelan con fuentes territoriales versionadas. Convive cita documento, autoridad y versión; no inventa plazos ni automatiza una decisión a partir de una norma.

## Arquitectura

| Componente | Responsabilidad |
| --- | --- |
| Angular 22 | Sitio público, formulario, seguimiento y espacio profesional |
| Symfony 7.4 / PHP 8.5 | Validación, sesiones, capacidades anónimas, autorización y auditoría |
| PostgreSQL 18.4 | Organizaciones, comunicaciones, casos, tareas y auditoría ficticios |
| Redis 8.2 | Límites de abuso y estado transitorio autenticado |
| Almacenamiento privado + ClamAV | Cuarentena y análisis de evidencias |
| Gateway Caddy | Angular compilado y API de mismo origen |
| Caddy de plataforma | Única entrada HTTP(S) pública aprobada del VPS |

La [vista de contexto](../architecture/diagrams/c4-context.md), la [vista de contenedores](../architecture/diagrams/c4-container.md) y el [flujo de seguridad](../architecture/diagrams/security-data-flow.md) son el mapa mantenido. Los motivos de diseño están en los [ADR](../architecture/decisions/README.md).

## Seguridad y privacidad

La interfaz no autoriza: Symfony exige sesión profesional activa, pertenencia a organización y permiso exacto sobre el caso. La posesión de un secreto de comunicante no puede convertirse en sesión profesional.

Las evidencias quedan en almacenamiento privado, pasan por cuarentena y análisis y solo se previsualizan en memoria tras una lectura autorizada de formato seguro. No hay URL pública de almacenamiento ni se fabrican vídeos para la demo. Los secretos operativos son archivos de permisos restrictivos fuera de Git y los registros evitan contenido y credenciales.

El [modelo de amenazas](../security/threat-model.md), el [registro de privacidad](../security/privacy-engineering-register.md) y el [modelo de evidencias](../security/attachment-threat-model.md) documentan controles y límites.

## Datos ficticios y verificación

La demo determinista se crea mediante un comando explícito y protegido, nunca con fixtures de Doctrine durante el arranque. Prepara comunicaciones, casos en etapas distintas, asignaciones, tareas, historial y evidencias de contexto sin datos reales. Es idempotente; el reinicio exige confirmación y afecta solo al espacio reservado. El procedimiento está en [datos ficticios de demostración](../operations/fictional-demo-data.md).

Cada cambio se comprueba con pruebas Symfony, Angular, tipo/formato, OpenAPI, Compose, recuperación cifrada y Chromium aislado. E2E crea una base ficticia efímera y elimina su pila. La [estrategia de pruebas](../testing/strategy.md) y la trazabilidad de entrega definen la evidencia exigida.

## Operación y estado

La release prepara primero una generación saludable con imágenes inmutables por digest, migraciones revisadas y evidencia de recuperación. Solo después se activa la ruta Caddy exacta y se verifica HTTPS, host, API, cabeceras, etiqueta de demo y ausencia de puertos inesperados. Un fallo conserva la candidata no pública o restaura únicamente la ruta y generación de Convive.

El [runbook de release y rollback](../operations/deployment-release-and-rollback.md), su [secuencia](../architecture/diagrams/release-rollback-sequence.md) y [ADR-0029](../architecture/decisions/0029-use-the-platform-caddy-per-project-edge-for-public-ingress.md) son la fuente operativa. La demostración pública está operativa en `https://conviveaula.com` y la aplicación en `https://app.conviveaula.com`; ambas permanecen limitadas a datos ficticios. Esto no supone un centro real ni autorización para datos personales.

## Evolución prevista durante 2026

La evolución se priorizará después de validación y no como una promesa de producto. Las líneas previstas son mejorar la accesibilidad cognitiva y completar la revisión con lector de pantalla; ofrecer actualizaciones más claras a la persona informante mediante su acceso seguro; mantener fuentes territoriales y protocolos; y estudiar ampliaciones lingüísticas solo con una decisión de alcance y revisión cualificada.

Un piloto con datos reales, cualquier automatización de decisiones educativas, analítica no esencial y una aplicación sin conexión quedan fuera de esta memoria. Requerirían decisiones adicionales de responsable, privacidad, seguridad, operación y producto.

Consulte el [README](../../README.md), el [índice de operaciones](../operations/README.md), el [PDF en español](pdf/convive-memoria-tecnica-es.pdf) y la [versión inglesa](technical-report-en.md).
