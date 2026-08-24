#!/usr/bin/env node
/**
 * Records that a locale has been re-confirmed against the current Spanish
 * source, for the drift check in `translation-sync.spec.ts` (#325).
 *
 *   npm run i18n:confirm -- gl              confirm one locale
 *   npm run i18n:confirm -- gl --dry-run    show what would change
 *
 * This deliberately requires a locale argument and prints every key it is
 * about to re-confirm. The whole point of the check is that a human looked at
 * the reworded string in that language; a command that silently blessed every
 * locale at once would hand back exactly the property the check exists to
 * defend.
 */
import { readFileSync, writeFileSync, readdirSync, existsSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const I18N = join(dirname(fileURLToPath(import.meta.url)), '..', 'src', 'i18n');
const SYNC = join(I18N, 'translation-sync');

function digest(text) {
  let hash = 0x811c9dc5;
  for (let i = 0; i < text.length; i += 1) {
    hash ^= text.charCodeAt(i);
    hash = (hash + ((hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24))) >>> 0;
  }
  return hash.toString(16).padStart(8, '0');
}

function flatten(tree, prefix = '') {
  if (typeof tree === 'string') {
    return { [prefix]: tree };
  }
  const entries = Array.isArray(tree)
    ? tree.map((value, index) => [`${prefix}[${index}]`, value])
    : tree !== null && typeof tree === 'object'
      ? Object.entries(tree).map(([key, value]) => [
          prefix === '' ? key : `${prefix}.${key}`,
          value,
        ])
      : [];
  return entries.reduce((all, [path, value]) => Object.assign(all, flatten(value, path)), {});
}

function scopes() {
  return readdirSync(I18N, { withFileTypes: true })
    .filter((entry) => entry.isDirectory() && entry.name !== 'translation-sync')
    .map((entry) => entry.name)
    .sort();
}

/**
 * Digests of the Spanish source for every resource present in the locale.
 * Published locales cover every current source scope; handling an absent file
 * here keeps the command useful while a new locale is being assembled.
 */
function sourceDigests(locale) {
  const out = {};
  const skipped = [];
  for (const scope of scopes()) {
    const source = join(I18N, scope, 'es.json');
    if (!existsSync(source)) continue;
    if (!existsSync(join(I18N, scope, `${locale}.json`))) {
      skipped.push(scope);
      continue;
    }
    for (const [key, text] of Object.entries(flatten(JSON.parse(readFileSync(source, 'utf8'))))) {
      out[`${scope}.${key}`] = digest(text);
    }
  }
  return { digests: out, skipped };
}

const [locale, ...flags] = process.argv.slice(2);
const dryRun = flags.includes('--dry-run');

if (!locale || locale === 'es') {
  console.error('Usage: npm run i18n:confirm -- <locale> [--dry-run]');
  console.error('The Spanish source is what everything else is confirmed against.');
  process.exit(1);
}

const { digests: current, skipped } = sourceDigests(locale);

if (Object.keys(current).length === 0) {
  console.error(`Locale "${locale}" translates no scope. Nothing to confirm.`);
  process.exit(1);
}

if (skipped.length > 0) {
  console.log(`No resource file for "${locale}": ${skipped.join(', ')}\n`);
}
const recordFile = join(SYNC, `${locale}.json`);
const previous = existsSync(recordFile) ? JSON.parse(readFileSync(recordFile, 'utf8')) : {};

const changed = Object.keys(current).filter((key) => previous[key] !== current[key]);
const removed = Object.keys(previous).filter((key) => !(key in current));

if (changed.length === 0 && removed.length === 0) {
  console.log(`"${locale}" is already confirmed against the current Spanish source.`);
  process.exit(0);
}

console.log(`Re-confirming "${locale}" against the current Spanish source.\n`);
for (const key of changed) {
  console.log(`  ${previous[key] === undefined ? 'new    ' : 'changed'}  ${key}`);
}
for (const key of removed) {
  console.log(`  removed  ${key}`);
}
console.log(
  `\n${changed.length} to confirm, ${removed.length} to drop.` +
    '\nConfirming asserts you have read each of these in ' +
    locale +
    ' and it still says what the Spanish says.',
);

if (dryRun) {
  console.log('\n--dry-run: nothing written.');
  process.exit(0);
}

mkdirSync(SYNC, { recursive: true });
const sorted = Object.fromEntries(Object.entries(current).sort(([a], [b]) => a.localeCompare(b)));
writeFileSync(recordFile, `${JSON.stringify(sorted, null, 2)}\n`, 'utf8');
console.log(`\nWrote ${recordFile}`);
