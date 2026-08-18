#!/usr/bin/env node
/**
 * Generates a print-ready A4 poster for one school's reporting entry (#329).
 *
 *   npm run poster -- --identifier 08A-EXEMPLE
 *   npm run poster -- --identifier 08A-EXEMPLE --name "IES Exemple"
 *   npm run poster -- --identifier 08A-EXEMPLE --out ./posters
 *
 * The QR and the printed URL are derived from the SAME value, so they cannot
 * disagree with each other — a poster whose code and text point at different
 * schools would be worse than no poster.
 *
 * Per ADR-0009 the readable URL is not decoration: it is the manual-entry
 * fallback for a reader whose camera does not work, whose phone is old, who
 * has no phone, or whose poster has been scratched, covered or photocopied
 * badly. In a corridor all of those happen.
 *
 * Per ADR-0028 the generated QR is decoded and checked before the file is
 * written. A wrong code is not a build error; it is a poster that does not
 * scan, discovered by a student standing in front of it because something was
 * happening to them.
 */
import { writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import qrcode from 'qrcode-generator';
import jsQR from 'jsqr';

/** Brand palette, from docs/brand/README.md. */
const NAVY = '#172B57';
const VOICE_BLUE = '#239DD1';
const SOFT_BACKGROUND = '#F7FAFD';
const GREY = '#53647D';

const APPLICATION_HOST = 'https://app.conviveaula.com';

/**
 * Tier 1 safety-critical copy under docs/content/plain-language-standard.md.
 * Measured: INFLESZ 81.6, longest sentence 9 words. The floor is 65 and 15.
 */
const COPY = {
  heading: '¿Alguien te está haciendo daño?',
  lead: 'Puedes contarlo aquí. No hace falta decir tu nombre.',
  scan: 'Apunta la cámara del móvil al código.',
  manualIntro: 'O escribe esta dirección en el móvil:',
  who: 'Lo leerá una persona del centro.',
  witness: 'También puedes contarlo si le pasa a otra persona.',
  boundary: 'Esto es una prueba con datos inventados. No llega a ningún centro real.',
};

function parseArguments(argv) {
  const args = {};
  for (let i = 0; i < argv.length; i += 2) {
    if (!argv[i]?.startsWith('--')) continue;
    args[argv[i].slice(2)] = argv[i + 1];
  }
  return args;
}

function reportingUrl(identifier) {
  return `${APPLICATION_HOST}/r/${identifier}`;
}

/**
 * Builds the QR and returns its module matrix.
 *
 * Error-correction level Q (25%) rather than the usual M: a poster on a wall
 * gets scratched, taped over at one corner and photocopied. The extra
 * redundancy costs a slightly denser code and buys tolerance for exactly the
 * damage a corridor inflicts.
 */
function buildQrMatrix(text) {
  const qr = qrcode(0, 'Q');
  qr.addData(text);
  qr.make();

  const count = qr.getModuleCount();
  const matrix = [];
  for (let row = 0; row < count; row += 1) {
    matrix.push(Array.from({ length: count }, (_, column) => qr.isDark(row, column)));
  }
  return matrix;
}

/**
 * Decodes the matrix by rendering it to pixels in memory and reading it back
 * with an independent decoder.
 *
 * Deliberately not a re-encode-and-compare: that would only prove this script
 * is self-consistent. Feeding pixels to a different library proves the code a
 * phone camera sees resolves to the URL intended.
 */
function decodeMatrix(matrix) {
  const scale = 4;
  const quiet = 4 * scale;
  const size = matrix.length * scale + quiet * 2;
  const pixels = new Uint8ClampedArray(size * size * 4).fill(255);

  for (let row = 0; row < matrix.length; row += 1) {
    for (let column = 0; column < matrix.length; column += 1) {
      if (!matrix[row][column]) continue;
      for (let y = 0; y < scale; y += 1) {
        for (let x = 0; x < scale; x += 1) {
          const px = quiet + column * scale + x;
          const py = quiet + row * scale + y;
          const offset = (py * size + px) * 4;
          pixels[offset] = 0;
          pixels[offset + 1] = 0;
          pixels[offset + 2] = 0;
        }
      }
    }
  }

  return jsQR(pixels, size, size)?.data ?? null;
}

function matrixToPath(matrix, origin, moduleSize) {
  let path = '';
  for (let row = 0; row < matrix.length; row += 1) {
    for (let column = 0; column < matrix.length; column += 1) {
      if (!matrix[row][column]) continue;
      const x = origin + column * moduleSize;
      const y = origin + row * moduleSize;
      path += `M${x.toFixed(3)} ${y.toFixed(3)}h${moduleSize.toFixed(3)}v${moduleSize.toFixed(3)}h-${moduleSize.toFixed(3)}z`;
    }
  }
  return path;
}

function escapeXml(text) {
  return text.replace(
    /[&<>"']/g,
    (character) =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' })[character],
  );
}

/**
 * Wraps text to a millimetre budget.
 *
 * SVG does not wrap, so a string longer than the page runs off the edge and
 * still renders "successfully". Advance width is estimated at 0.5em per
 * character, which is close enough for a humanist sans and deliberately
 * pessimistic: a line that wraps one word early costs nothing, a line that
 * overflows loses the sentence.
 */
function wrap(text, fontSize, maxWidth) {
  const perCharacter = fontSize * 0.5;
  const budget = Math.max(Math.floor(maxWidth / perCharacter), 8);
  const lines = [];
  let line = '';

  for (const word of text.split(' ')) {
    const candidate = line === '' ? word : `${line} ${word}`;
    if (candidate.length > budget && line !== '') {
      lines.push(line);
      line = word;
    } else {
      line = candidate;
    }
  }
  if (line !== '') lines.push(line);

  return lines;
}

/** Emits one <text> per wrapped line, returning the y after the block. */
function textBlock(text, { x, y, fontSize, fill, anchor = 'start', weight = 400, maxWidth }) {
  const lines = wrap(text, fontSize, maxWidth);
  const lineHeight = fontSize * 1.25;
  const svg = lines
    .map(
      (line, index) =>
        `  <text x="${x}" y="${(y + index * lineHeight).toFixed(2)}" text-anchor="${anchor}" font-family="Aptos, Segoe UI, Arial, sans-serif" font-size="${fontSize}" font-weight="${weight}" fill="${fill}">${escapeXml(line)}</text>`,
    )
    .join('\n');

  return { svg, next: y + lines.length * lineHeight };
}

function buildPoster({ identifier, name, url, matrix }) {
  // A4 portrait in millimetres, so the file prints at true size.
  const width = 210;
  const height = 297;
  const qrBox = 68;
  const qrOrigin = (width - qrBox) / 2;
  const moduleSize = qrBox / matrix.length;
  const displayUrl = url.replace(/^https:\/\//, '');

  const margin = 18;
  const textWidth = width - margin * 2;

  const heading = textBlock(COPY.heading, {
    x: margin,
    y: 56,
    fontSize: 11,
    fill: NAVY,
    weight: 700,
    maxWidth: textWidth,
  });
  const lead = textBlock(COPY.lead, {
    x: margin,
    y: heading.next + 3,
    fontSize: 8,
    fill: NAVY,
    maxWidth: textWidth,
  });
  const witness = textBlock(COPY.witness, {
    x: margin,
    y: lead.next + 1,
    fontSize: 8,
    fill: NAVY,
    maxWidth: textWidth,
  });

  const qrTop = witness.next + 6;
  const scan = textBlock(COPY.scan, {
    x: width / 2,
    y: qrTop + qrBox + 14,
    fontSize: 8,
    fill: NAVY,
    anchor: 'middle',
    maxWidth: textWidth,
  });
  const manual = textBlock(COPY.manualIntro, {
    x: width / 2,
    y: scan.next + 3,
    fontSize: 8,
    fill: GREY,
    anchor: 'middle',
    maxWidth: textWidth,
  });
  const address = textBlock(displayUrl, {
    x: width / 2,
    y: manual.next + 2,
    fontSize: 10,
    fill: NAVY,
    anchor: 'middle',
    weight: 700,
    maxWidth: textWidth,
  });
  const who = textBlock(COPY.who, {
    x: width / 2,
    y: address.next + 4,
    fontSize: 8,
    fill: NAVY,
    anchor: 'middle',
    maxWidth: textWidth,
  });
  const centre = name
    ? textBlock(name, {
        x: width / 2,
        y: who.next + 2,
        fontSize: 8.5,
        fill: GREY,
        anchor: 'middle',
        maxWidth: textWidth,
      })
    : { svg: '', next: who.next };
  const boundary = textBlock(COPY.boundary, {
    x: width / 2,
    y: centre.next + 7,
    fontSize: 7,
    fill: GREY,
    anchor: 'middle',
    maxWidth: textWidth,
  });

  return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}mm" height="${height}mm" viewBox="0 0 ${width} ${height}" role="img" aria-label="${escapeXml(COPY.heading)} ${escapeXml(displayUrl)}">
  <rect width="${width}" height="${height}" fill="${SOFT_BACKGROUND}"/>

  <!-- Wordmark, scaled to 26mm wide. Clear space of at least a quarter of its
       height is kept below, per docs/brand/README.md. -->
  <g transform="translate(${margin} 24) scale(0.0236)" aria-hidden="true">
    <text x="0" y="235" font-family="Trebuchet MS, Arial, sans-serif" font-size="210" font-weight="700" fill="${NAVY}">con<tspan fill="${VOICE_BLUE}">v</tspan>i<tspan fill="${VOICE_BLUE}">v</tspan>e</text>
  </g>

${heading.svg}
${lead.svg}
${witness.svg}

  <!-- The QR sits on white rather than the page tint: scanners cope badly with
       a tinted quiet zone, and a poster that scans slowly is one someone walks
       away from. -->
  <rect x="${(qrOrigin - 5).toFixed(2)}" y="${(qrTop - 5).toFixed(2)}" width="${qrBox + 10}" height="${qrBox + 10}" rx="3" fill="#FFFFFF"/>
  <path d="${matrixToPath(matrix, 0, moduleSize)}" transform="translate(${qrOrigin.toFixed(2)} ${qrTop.toFixed(2)})" fill="${NAVY}"/>

${scan.svg}
${manual.svg}
${address.svg}
${who.svg}
${centre.svg}
  <!-- Boundary notice. Every public surface says this; a wall is no exception. -->
${boundary.svg}

  <!-- Lets staff confirm from the wall that a poster matches the current
       identifier. ADR-0009 requires reprinting when an identifier is rotated,
       and without this there is no way to tell by looking. -->
  <text x="${width / 2}" y="${height - 8}" text-anchor="middle" font-family="Aptos, Segoe UI, Arial, sans-serif" font-size="6.5" fill="${GREY}">Código del centro: ${escapeXml(identifier)}</text>
</svg>
`;
}

const args = parseArguments(process.argv.slice(2));
const identifier = args.identifier;

if (!identifier) {
  console.error(
    'Usage: npm run poster -- --identifier <publicReportingIdentifier> [--name "IES …"] [--out dir]',
  );
  process.exit(1);
}

if (!/^[A-Za-z0-9-]+$/.test(identifier)) {
  console.error(`Refusing to build a poster for "${identifier}".`);
  console.error(
    'An identifier that needs URL-escaping would print differently from what it encodes.',
  );
  process.exit(1);
}

const url = reportingUrl(identifier);
const matrix = buildQrMatrix(url);
const decoded = decodeMatrix(matrix);

if (decoded !== url) {
  console.error('The generated QR does not decode to the intended address. Nothing written.');
  console.error(`  expected: ${url}`);
  console.error(`  decoded : ${decoded ?? '(unreadable)'}`);
  process.exit(1);
}

const outputDirectory = args.out ?? 'posters';
mkdirSync(outputDirectory, { recursive: true });
const file = join(outputDirectory, `convive-poster-${identifier}.svg`);
writeFileSync(file, buildPoster({ identifier, name: args.name, url, matrix }), 'utf8');

console.log(`Wrote ${file}`);
console.log(`QR verified by decoding: ${decoded}`);
