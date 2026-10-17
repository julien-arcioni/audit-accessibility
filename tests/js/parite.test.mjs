import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { analyser } from '../../assets/js/widget/moteur.js';
import { chargerFixture } from './charger.mjs';

// AA_PHP_JSON permet de pointer vers un autre fichier (utile pour prouver que le test peut échouer).
const FICHIER_PHP = process.env.AA_PHP_JSON
  || fileURLToPath(new URL('../.resultats/php.json', import.meta.url));
const DOSSIER_FIXTURES = fileURLToPath(new URL('../fixtures/', import.meta.url));

const cle = (regle, chemin) => `${regle}@${chemin ?? 'page'}`;

let php = null;
try {
  php = JSON.parse(await readFile(FICHIER_PHP, 'utf8'));
} catch {
  php = null;
}

const fixtures = (await readdir(DOSSIER_FIXTURES))
  .filter((f) => f.endsWith('.html'))
  .map((f) => f.replace(/\.html$/, ''));
const noms = [...new Set([...Object.keys(php ?? {}), ...fixtures])].sort();

for (const nom of noms) {
  test(`parité PHP / JS : ${nom}`, async () => {
    assert.ok(php && Object.hasOwn(php, nom), `Lancer d'abord npm run test:php (aucun résultat PHP pour « ${nom} »)`);
    assert.ok(fixtures.includes(nom), `Page de test « ${nom}.html » introuvable dans tests/fixtures`);

    const { document } = await chargerFixture(nom);
    const js = analyser(document, { ignorerMasques: false }).map((r) => cle(r.regle, r.chemin)).sort();
    const pp = php[nom].map((r) => cle(r.regle, r.chemin)).sort();

    const reste = (a, b) => {
      const copie = [...b];
      return a.filter((x) => {
        const i = copie.indexOf(x);
        if (i === -1) return true;
        copie.splice(i, 1);
        return false;
      });
    };
    const seulementPhp = reste(pp, js);
    const seulementJs = reste(js, pp);
    assert.ok(
      seulementPhp.length === 0 && seulementJs.length === 0,
      `Écart PHP / JS pour « ${nom} »\n  seulement en PHP : ${JSON.stringify(seulementPhp)}\n  seulement en JS : ${JSON.stringify(seulementJs)}`,
    );
  });
}
