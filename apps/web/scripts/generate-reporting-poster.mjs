#!/usr/bin/env node
/**
 * Generates a print-ready A4 poster for one school's reporting entry (#329).
 *
 *   npm run poster -- --identifier 08A-EXEMPLE
 *   npm run poster -- --identifier 08A-EXEMPLE --name "IES Exemple"
 *   npm run poster -- --identifier 08A-EXEMPLE --out ./posters
 *   npm run poster -- --identifier ORG_DEM0000000000000 --url https://conviveaula.com/demo
 *   npm run poster -- --identifier ORG_DEM0000000000000 --format png
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
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import qrcode from 'qrcode-generator';
import jsQR from 'jsqr';
import { chromium } from 'playwright';

/** Brand palette, from docs/brand/README.md. */
const NAVY = '#172B57';
const VOICE_BLUE = '#239DD1';
const SOFT_BACKGROUND = '#F7FAFD';
const GREY = '#53647D';

const APPLICATION_HOST = 'https://app.conviveaula.com';
const WORDMARK_DATA_URI = `data:image/svg+xml;base64,${readFileSync(
  new URL('../src/convive-logo-reversed.svg', import.meta.url),
).toString('base64')}`;
const SCHOOL_IMAGE_DATA_URI = `data:image/png;base64,${readFileSync(
  new URL('../src/assets/public-demo/poster-school-backdrop-v2.png', import.meta.url),
).toString('base64')}`;

/**
 * Tier 1 safety-critical copy under docs/content/plain-language-standard.md.
 * Measured: INFLESZ 81.6, longest sentence 9 words. The floor is 65 and 15.
 */
const COPY_BY_LOCALE = {
  es: {
    heading: '¿Alguien te está haciendo daño?',
    lead: 'Puedes contarlo aquí. No hace falta decir tu nombre.',
    scan: 'Apunta la cámara del móvil al código',
    manualIntro: 'O escribe esta dirección en el móvil:',
    who: 'Lo leerá una persona del centro.',
    witness: 'También puedes contarlo si le pasa a otra persona.',
    boundary: 'Esto es una prueba con datos inventados. No llega a ningún centro real.',
    centreCode: 'Código del centro:',
  },
  ca: {
    heading: 'Algú et fa mal?',
    lead: 'Ho pots explicar aquí. No cal que diguis el teu nom.',
    scan: 'Apunta la càmera del mòbil al codi',
    manualIntro: 'O escriu aquesta adreça al mòbil:',
    who: 'Ho llegirà una persona del centre.',
    witness: 'També ho pots explicar si li passa a una altra persona.',
    boundary: 'Aquesta és una prova amb dades inventades. No arriba a cap centre real.',
    centreCode: 'Codi del centre:',
  },
  'ca-valencia': {
    heading: 'Algú et fa mal?',
    lead: 'Ho pots contar ací. No cal que digues el teu nom.',
    scan: 'Apunta la càmera del mòbil al codi',
    manualIntro: 'O escriu esta adreça al mòbil:',
    who: 'Ho llegirà una persona del centre.',
    witness: 'També ho pots contar si li passa a una altra persona.',
    boundary: 'Esta és una prova amb dades inventades. No arriba a cap centre real.',
    centreCode: 'Codi del centre:',
  },
  eu: {
    heading: 'Norbaitek min egiten dizu?',
    lead: 'Hemen kontatu dezakezu. Ez duzu zure izena esan behar.',
    scan: 'Jarri mugikorreko kamera kodeari begira',
    manualIntro: 'Edo idatzi helbide hau mugikorrean:',
    who: 'Ikastetxeko pertsona batek irakurriko du.',
    witness: 'Beste pertsona bati gertatzen bazaio ere kontatu dezakezu.',
    boundary: 'Asmatutako datuekin egindako proba da. Ez da benetako ikastetxe batera iristen.',
    centreCode: 'Ikastetxearen kodea:',
  },
  gl: {
    heading: 'Alguén che está facendo dano?',
    lead: 'Podes contalo aquí. Non tes que dicir o teu nome.',
    scan: 'Apunta a cámara do móbil ao código',
    manualIntro: 'Ou escribe este enderezo no móbil:',
    who: 'Lerao unha persoa do centro.',
    witness: 'Tamén podes contalo se lle pasa a outra persoa.',
    boundary: 'Esta é unha proba con datos inventados. Non chega a ningún centro real.',
    centreCode: 'Código do centro:',
  },
  ar: {
    heading: 'هل يؤذيك أحد؟',
    lead: 'يمكنك أن تحكي ذلك هنا. لا تحتاج إلى ذكر اسمك.',
    scan: 'وجّه كاميرا الهاتف نحو الرمز',
    manualIntro: 'أو اكتب هذا العنوان في الهاتف:',
    who: 'سيقرأه شخص من المركز.',
    witness: 'يمكنك أيضًا أن تحكيه إذا حدث لشخص آخر.',
    boundary: 'هذا مثال ببيانات مخترعة. لا يصل إلى أي مركز حقيقي.',
    centreCode: 'رمز المركز:',
  },
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

function buildPoster({ copy, identifier, name, url, matrix }) {
  const width = 210;
  const height = 297;
  const qrBox = 64;
  const qrOrigin = (width - qrBox) / 2;
  const moduleSize = qrBox / matrix.length;
  const displayUrl = url.replace(/^https:\/\//, '');
  const margin = 18;
  const textWidth = width - margin * 2;

  const heading = textBlock(copy.heading, {
    x: margin,
    y: 112,
    fontSize: 12.5,
    fill: '#FFFFFF',
    weight: 700,
    maxWidth: textWidth,
  });
  const qrTop = heading.next + 11;
  const scan = textBlock(copy.scan, {
    x: width / 2,
    y: qrTop + qrBox + 11,
    fontSize: 7.5,
    fill: '#FFFFFF',
    anchor: 'middle',
    maxWidth: textWidth,
  });
  const address = textBlock(displayUrl, {
    x: width / 2,
    y: scan.next + 3,
    fontSize: 8.5,
    fill: '#FFFFFF',
    anchor: 'middle',
    weight: 700,
    maxWidth: textWidth,
  });
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}mm" height="${height}mm" viewBox="0 0 ${width} ${height}" role="img" aria-label="${escapeXml(copy.heading)} ${escapeXml(displayUrl)}">
  <defs>
    <linearGradient id="poster-overlay" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#0B234C" stop-opacity="0.22"/>
      <stop offset="0.46" stop-color="#0B234C" stop-opacity="0.54"/>
      <stop offset="1" stop-color="#0B234C" stop-opacity="0.72"/>
    </linearGradient>
  </defs>
  <image href="${SCHOOL_IMAGE_DATA_URI}" x="0" y="0" width="${width}" height="${height}" preserveAspectRatio="xMidYMid slice"/>
  <rect width="${width}" height="${height}" fill="url(#poster-overlay)"/>
  <image href="${WORDMARK_DATA_URI}" x="${margin}" y="17" width="62" height="20" preserveAspectRatio="xMidYMid meet"/>
  <text x="${margin}" y="65" font-family="Aptos, Segoe UI, Arial, sans-serif" font-size="7" font-weight="700" letter-spacing="1" fill="#D9F3FC">CENTRO DEMO CONVIVE</text>

${heading.svg}

  <!-- The code retains a white quiet zone for fast scanning in a real corridor. -->
  <rect x="${(qrOrigin - 5).toFixed(2)}" y="${(qrTop - 5).toFixed(2)}" width="${qrBox + 10}" height="${qrBox + 10}" rx="3" fill="#FFFFFF"/>
  <path d="${matrixToPath(matrix, 0, moduleSize)}" transform="translate(${qrOrigin.toFixed(2)} ${qrTop.toFixed(2)})" fill="${NAVY}"/>

${scan.svg}
${address.svg}
</svg>
`;
}

const args = parseArguments(process.argv.slice(2));
const identifier = args.identifier;
const locale = args.locale ?? 'es';
const copy = COPY_BY_LOCALE[locale];
const format = args.format ?? 'svg';

if (!identifier) {
  console.error(
    'Usage: npm run poster -- --identifier <publicReportingIdentifier> [--name "IES …"] [--url https://…] [--out dir]',
  );
  process.exit(1);
}

if (!/^[A-Za-z0-9_-]+$/.test(identifier)) {
  console.error(`Refusing to build a poster for "${identifier}".`);
  console.error(
    'An identifier that needs URL-escaping would print differently from what it encodes.',
  );
  process.exit(1);
}

if (!copy) {
  console.error(`Unsupported poster locale "${locale}".`);
  console.error(`Use one of: ${Object.keys(COPY_BY_LOCALE).join(', ')}.`);
  process.exit(1);
}

if (!['svg', 'png'].includes(format)) {
  console.error(`Unsupported poster format "${format}". Use svg or png.`);
  process.exit(1);
}

const url = args.url ?? reportingUrl(identifier);

try {
  const parsedUrl = new URL(url);
  if (parsedUrl.protocol !== 'https:' || parsedUrl.username || parsedUrl.password) {
    throw new Error('must be an https URL without credentials');
  }
} catch (error) {
  console.error(`Refusing poster URL "${url}": ${error.message}`);
  process.exit(1);
}
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
const file = join(outputDirectory, `convive-poster-${identifier}-${locale}.${format}`);
const posterSvg = buildPoster({ copy, identifier, name: args.name, url, matrix });

if (format === 'svg') {
  writeFileSync(file, posterSvg, 'utf8');
} else {
  const browser = await chromium.launch({ headless: true });

  try {
    const page = await browser.newPage({ viewport: { width: 1050, height: 1485 } });
    const embeddedPoster = Buffer.from(posterSvg).toString('base64');
    await page.setContent(
      `<style>html,body{margin:0;width:1050px;height:1485px;overflow:hidden}img{display:block;width:1050px;height:1485px}</style><img src="data:image/svg+xml;base64,${embeddedPoster}" alt="">`,
    );
    await page.locator('img').waitFor();
    await page.screenshot({ path: file, type: 'png' });
  } finally {
    await browser.close();
  }
}

console.log(`Wrote ${file}`);
console.log(`QR verified by decoding: ${decoded}`);
