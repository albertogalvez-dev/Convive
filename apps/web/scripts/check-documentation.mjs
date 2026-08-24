import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { access, readdir, readFile } from 'node:fs/promises';
import { constants } from 'node:fs';
import { dirname, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';

const dom = new JSDOM('<!doctype html><html><body></body></html>');
globalThis.window = dom.window;
globalThis.document = dom.window.document;
const { default: mermaid } = await import('mermaid');

const executeFile = promisify(execFile);
const repositoryRoot = resolve(import.meta.dirname, '../../..');

export async function validateDocumentation(
  root = repositoryRoot,
  trackedOnly = root === repositoryRoot,
) {
  const markdownFiles = trackedOnly
    ? await trackedMarkdownFiles(root)
    : await markdownFilesUnder(root);
  const failures = [];

  for (const file of markdownFiles) {
    const content = await readFile(resolve(root, file), 'utf8');
    await validateLinks(root, file, content, failures);
    await validateMermaid(file, content, failures);
  }

  await validateDiagramCatalogue(root, failures);

  if (failures.length > 0) {
    throw new Error(
      `Documentation validation failed:\n${failures.map((failure) => `- ${failure}`).join('\n')}`,
    );
  }

  return { markdownFiles: markdownFiles.length };
}

async function trackedMarkdownFiles(root) {
  const { stdout } = await executeFile('git', ['ls-files', '-z', '--', '*.md'], { cwd: root });

  return stdout.split('\0').filter(Boolean).sort();
}

async function markdownFilesUnder(root, directory = '.') {
  const files = [];
  const entries = await readdir(resolve(root, directory), { withFileTypes: true });

  for (const entry of entries) {
    if (entry.name === '.git' || entry.name === 'node_modules') {
      continue;
    }

    const child = directory === '.' ? entry.name : `${directory}/${entry.name}`;
    if (entry.isDirectory()) {
      files.push(...(await markdownFilesUnder(root, child)));
    } else if (entry.isFile() && entry.name.endsWith('.md')) {
      files.push(child);
    }
  }

  return files.sort();
}

async function validateLinks(root, file, content, failures) {
  const prose = content.replace(/^```[\s\S]*?^```$/gm, '');
  const expression = /!?\[[^\]]*]\(([^)\n]+)\)/g;

  for (const match of prose.matchAll(expression)) {
    const destination = markdownDestination(match[1]);
    if (destination === null || isExternalOrFragment(destination)) {
      continue;
    }

    const linkPath = destination.split('#', 1)[0];
    if (linkPath === '') {
      continue;
    }

    const target = resolve(dirname(resolve(root, file)), decodeURIComponent(linkPath));
    const targetRelative = relative(root, target);
    if (
      targetRelative === '..' ||
      targetRelative.startsWith(`..${sep}`) ||
      !(await exists(target))
    ) {
      failures.push(
        `${file}:${lineOf(content, match.index)} has a missing relative link: ${destination}`,
      );
    }
  }
}

function markdownDestination(value) {
  const trimmed = value.trim();
  if (trimmed.startsWith('<')) {
    const closing = trimmed.indexOf('>');
    return closing === -1 ? trimmed : trimmed.slice(1, closing);
  }

  return trimmed.split(/\s+/, 1)[0] || null;
}

function isExternalOrFragment(destination) {
  return (
    destination.startsWith('#') ||
    destination.startsWith('//') ||
    /^[a-z][a-z0-9+.-]*:/i.test(destination)
  );
}

async function validateMermaid(file, content, failures) {
  for (const block of mermaidBlocks(content)) {
    try {
      const result = await mermaid.parse(block.source, { suppressErrors: true });
      if (result === false) {
        failures.push(`${file}:${block.line} contains invalid Mermaid syntax.`);
      }
    } catch (error) {
      failures.push(`${file}:${block.line} contains invalid Mermaid syntax: ${error.message}`);
    }
  }
}

function mermaidBlocks(content) {
  const blocks = [];
  const expression = /^```mermaid\s*\r?\n([\s\S]*?)^```\s*$/gm;

  for (const match of content.matchAll(expression)) {
    blocks.push({
      source: match[1],
      line: lineOf(content, match.index),
    });
  }

  return blocks;
}

async function validateDiagramCatalogue(root, failures) {
  const diagramDirectory = resolve(root, 'docs/architecture/diagrams');
  const diagrams = (await readdir(diagramDirectory, { withFileTypes: true }))
    .filter((entry) => entry.isFile() && entry.name.endsWith('.md') && entry.name !== 'README.md')
    .map((entry) => entry.name)
    .sort();
  const cataloguePath = resolve(diagramDirectory, 'README.md');
  const catalogue = await readFile(cataloguePath, 'utf8');
  const catalogueRows = catalogue.split(/\r?\n/).filter((line) => line.startsWith('| ['));
  const indexed = new Set();

  for (const row of catalogueRows) {
    const columns = row
      .split('|')
      .slice(1, -1)
      .map((column) => column.trim());
    const link = columns[0]?.match(/^\[[^\]]+]\(([^)]+)\)$/);

    if (columns.length !== 5 || link === null || columns.slice(1).some((column) => column === '')) {
      failures.push(
        `docs/architecture/diagrams/README.md has incomplete source or verification metadata: ${row}`,
      );
      continue;
    }

    indexed.add(link[1]);
  }

  for (const diagram of diagrams) {
    if (!indexed.has(diagram)) {
      failures.push(
        `docs/architecture/diagrams/${diagram} is not indexed with source and verification metadata.`,
      );
    }
  }

  for (const indexedDiagram of indexed) {
    if (!diagrams.includes(indexedDiagram)) {
      failures.push(
        `docs/architecture/diagrams/README.md indexes a missing diagram: ${indexedDiagram}`,
      );
    }
  }
}

async function exists(path) {
  try {
    await access(path, constants.F_OK);
    return true;
  } catch {
    return false;
  }
}

function lineOf(content, offset) {
  return content.slice(0, offset).split(/\r?\n/).length;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  const result = await validateDocumentation();
  console.log(
    `Documentation validation passed for ${result.markdownFiles} tracked Markdown files.`,
  );
}
