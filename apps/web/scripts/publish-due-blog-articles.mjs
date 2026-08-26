import { readFile, writeFile } from 'node:fs/promises';

const catalogUrl = new URL('../src/app/blog/blog-catalog.json', import.meta.url);
const today = new Date().toISOString().slice(0, 10);
const catalog = JSON.parse(await readFile(catalogUrl, 'utf8'));

let publishedCount = 0;
const updatedCatalog = catalog.map((article) => {
  if (article.publicationStatus !== 'draft' || article.publishedAt > today) {
    return article;
  }

  publishedCount += 1;
  return { ...article, publicationStatus: 'published' };
});

if (publishedCount > 0) {
  await writeFile(catalogUrl, `${JSON.stringify(updatedCatalog, null, 2)}\n`);
}

console.log(`Published ${publishedCount} due editorial article(s) on ${today}.`);
