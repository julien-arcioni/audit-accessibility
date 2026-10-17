import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';
import { analyser, cheminNoeud } from '../../assets/js/widget/moteur.js';
import { chargerFixture } from './charger.mjs';

const fixtures = readdirSync(fileURLToPath(new URL('../fixtures/', import.meta.url)))
  .filter((f) => f.endsWith('.html'))
  .map((f) => f.replace(/\.html$/, ''));

const cle = (r) => `${r.regle}|${r.chemin}`;

for (const nom of fixtures) {
  test(`fixture ${nom} : règles et chemins attendus`, async () => {
    const { document, attendus } = await chargerFixture(nom);
    const obtenus = analyser(document, { ignorerMasques: false }).map((r) => ({
      regle: r.regle,
      chemin: r.chemin,
    }));
    assert.deepEqual(obtenus.map(cle).sort(), attendus.map(cle).sort());
  });
}

test('cheminNoeud : index seulement parmi les frères de même nom', () => {
  const { window } = new JSDOM('<body><p></p><p><a></a></p></body>');
  const a = window.document.querySelector('a');
  assert.equal(cheminNoeud(a), '/html/body/p[2]/a');
});

test('ignorerMasques : image sans alt sous [hidden]', () => {
  const { window } = new JSDOM(
    '<!DOCTYPE html><html lang="fr"><head><title>Titre long</title></head><body><h1>T</h1><div hidden><img src="a.png"></div></body></html>'
  );
  const doc = window.document;
  const avec = analyser(doc, { ignorerMasques: false }).filter((r) => r.regle === 'image-alt-absent');
  const sans = analyser(doc).filter((r) => r.regle === 'image-alt-absent');
  assert.equal(avec.length, 1);
  assert.equal(sans.length, 0);
});

test('ignorerMasques : barre d\'administration (#wpadminbar) ignorée', () => {
  const { window } = new JSDOM(
    '<body><div id="wpadminbar"><a href="/wp-admin/"><span></span></a><img src="avatar.png"></div><img src="b.png"></body>'
  );
  const res = analyser(window.document.body);
  assert.deepEqual(res.map((r) => r.regle), ['image-alt-absent']);
  assert.equal(res[0].details.fichier, 'b.png');
  const tout = analyser(window.document.body, { ignorerMasques: false }).map((r) => r.regle).sort();
  assert.deepEqual(tout, ['image-alt-absent', 'image-alt-absent', 'lien-sans-intitule']);
});

test('ignorerMasques : contenu du widget ignoré, racine Element sans règles de page', () => {
  const { window } = new JSDOM('<body><div data-aa-widget><img src="a.png"></div><img src="b.png"></body>');
  const doc = window.document;
  const res = analyser(doc.body);
  assert.deepEqual(res.map((r) => r.regle), ['image-alt-absent']);
  assert.equal(res[0].details.fichier, 'b.png');
});
