import assert from 'node:assert/strict';
import { mkdtemp, mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { validateDocumentation } from './check-documentation.mjs';

const root = await mkdtemp(join(tmpdir(), 'convive-documentation-check-'));

try {
  await writeFixture(root);
  await validateDocumentation(root, false);

  await writeFile(join(root, 'docs', 'broken-link.md'), '[Missing](missing.md)\n');
  await assert.rejects(
    () => validateDocumentation(root, false),
    /broken-link\.md:1 has a missing relative link: missing\.md/,
  );
  await rm(join(root, 'docs', 'broken-link.md'));

  await writeFile(
    join(root, 'docs', 'invalid-mermaid.md'),
    '```mermaid\nflowchart TD\n    A -->\n```\n',
  );
  await assert.rejects(
    () => validateDocumentation(root, false),
    /invalid-mermaid\.md:1 contains invalid Mermaid syntax/,
  );
  await rm(join(root, 'docs', 'invalid-mermaid.md'));

  await writeFile(join(root, 'docs', 'architecture', 'diagrams', 'unindexed.md'), '# Unindexed\n');
  await assert.rejects(
    () => validateDocumentation(root, false),
    /unindexed\.md is not indexed with source and verification metadata/,
  );
} finally {
  await rm(root, { recursive: true, force: true });
}

console.log('Documentation validation tests passed.');

async function writeFixture(root) {
  const diagrams = join(root, 'docs', 'architecture', 'diagrams');
  await mkdir(diagrams, { recursive: true });
  await writeFile(join(root, 'docs', 'guide.md'), '[Diagram](architecture/diagrams/example.md)\n');
  await writeFile(
    join(diagrams, 'example.md'),
    '# Example\n\n```mermaid\nflowchart TD\n    A --> B\n```\n',
  );
  await writeFile(
    join(diagrams, 'README.md'),
    [
      '# Catalogue',
      '',
      '| Diagram | Audience | Source of truth | Verification | Status |',
      '| --- | --- | --- | --- | --- |',
      '| [Example](example.md) | Reviewer | Source | Test | Maintained |',
      '',
    ].join('\n'),
  );
}
