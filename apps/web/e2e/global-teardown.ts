import { glob, rm } from 'node:fs/promises';

export default async function removeSensitiveErrorContexts(): Promise<void> {
  for await (const errorContext of glob('test-results/**/error-context.md')) {
    await rm(errorContext, { force: true });
  }
}
