import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { JSDOM } from 'jsdom';
import { cheminNoeud } from '../../assets/js/widget/moteur.js';

const DOSSIER = fileURLToPath(new URL('../fixtures/', import.meta.url));

/**
 * Charge une page de test dans jsdom et en extrait les résultats attendus
 * (attributs data-attendu des éléments, data-attendu-page sur <html>).
 */
export async function chargerFixture(nom) {
  const fichier = nom.endsWith('.html') ? nom : `${nom}.html`;
  const html = await readFile(DOSSIER + fichier, 'utf8');
  const { window } = new JSDOM(html);
  const document = window.document;
  const attendus = [];

  const racine = document.documentElement;
  if (racine && racine.hasAttribute('data-attendu-page')) {
    for (const regle of racine.getAttribute('data-attendu-page').split(/\s+/).filter(Boolean)) {
      attendus.push({ regle, chemin: null });
    }
  }
  for (const el of document.querySelectorAll('[data-attendu]')) {
    for (const regle of el.getAttribute('data-attendu').split(/\s+/).filter(Boolean)) {
      attendus.push({ regle, chemin: cheminNoeud(el) });
    }
  }
  return { document, attendus };
}
