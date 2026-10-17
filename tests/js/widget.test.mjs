import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';
import { analyser } from '../../assets/js/widget/moteur.js';
import { monterWidget, relancerSiDemande, CLE_RELANCE } from '../../assets/js/widget/interface.js';
import { emplacement } from '../../assets/js/widget/emplacement.js';
import { chargerFixture } from './charger.mjs';

const catalogue = JSON.parse(
  readFileSync(new URL('../../assets/data/regles.json', import.meta.url), 'utf8')
);

/** Données factices au format de Audit_Accessibilite_Widget::donnees(). */
function donneesFactices(objet = { id: 12, type: 'page', lienModifier: 'https://exemple.test/wp-admin/post.php?post=12&action=edit' }) {
  return {
    regles: catalogue.regles,
    limites: catalogue.limites,
    avertissement: catalogue.avertissement,
    objet,
    lienEditeurSite: { url: 'https://exemple.test/wp-admin/site-editor.php', texte: 'Ouvrir l\'éditeur du site' },
    lienMediatheque: 'https://exemple.test/wp-admin/upload.php',
    css: 'https://exemple.test/widget.css',
  };
}

const PRIORITE = { high: 'important', medium: 'à revoir', low: 'à revoir' };
const minusculeInitiale = (t) => t.charAt(0).toLowerCase() + t.slice(1);
const pastilles = (w) => Array.from(w.racine.querySelectorAll('.aa-pastille'));

test('après lancer(), une pastille par problème d\'élément', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const problemes = widget.lancer();
  const avecElement = problemes.filter((p) => p.element);
  assert.ok(avecElement.length > 0);
  assert.equal(pastilles(widget).length, avecElement.length);
  widget.detruire();
});

test('chaque pastille est un bouton nommé « Problème {n}, {priorité} : {titre} »', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const avecElement = widget.lancer().filter((p) => p.element);
  pastilles(widget).forEach((b, i) => {
    const regle = catalogue.regles[avecElement[i].regle];
    assert.equal(b.localName, 'button');
    assert.equal(b.getAttribute('type'), 'button');
    assert.equal(
      b.getAttribute('aria-label'),
      `Problème ${i + 1}, ${PRIORITE[regle.priorite]} : ${minusculeInitiale(regle.titre)}`
    );
    assert.equal(b.textContent.trim(), String(i + 1));
  });
  widget.detruire();
});

test('synthèse et nom du bouton après l\'analyse', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const problemes = widget.lancer();
  const importants = problemes.filter((p) => catalogue.regles[p.regle].priorite === 'high').length;
  const aRevoir = problemes.length - importants;
  const synthese = widget.racine.querySelector('.aa-synthese').textContent;
  assert.equal(
    synthese,
    `${problemes.length} problèmes : ${importants} importants, ${aRevoir} à revoir`
  );
  const bouton = widget.racine.querySelector('.aa-bouton');
  assert.equal(bouton.textContent.replace(/\s+/g, ' ').trim(), `Accessibilité${problemes.length}, ${problemes.length} problèmes`);
  widget.detruire();
});

test('document sans problème : message sans « conforme » et liste des limites', () => {
  const { window } = new JSDOM(
    '<!DOCTYPE html><html lang="fr"><head><title>Page propre</title></head><body><h1>Bonjour</h1><p>Texte</p></body></html>'
  );
  const widget = monterWidget(window.document, donneesFactices());
  assert.deepEqual(widget.lancer(), []);
  const texte = widget.racine.textContent;
  assert.ok(texte.includes(
    'Aucune erreur détectée parmi les 11 contrôles automatiques. D\'autres points restent à vérifier à la main.'
  ));
  assert.ok(!/conforme/i.test(texte));
  assert.ok(!/\baccessible\b/i.test(texte));
  const resultats = widget.racine.querySelector('.aa-resultats');
  for (const limite of catalogue.limites) {
    assert.ok(resultats.textContent.includes(limite.titre), limite.titre);
  }
  assert.equal(pastilles(widget).length, 0);
  widget.detruire();
});

test('l\'avertissement est présent avant toute analyse', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const texte = widget.racine.textContent;
  assert.ok(texte.includes(catalogue.avertissement));
  const boutons = Array.from(widget.racine.querySelectorAll('button')).map((b) => b.textContent.trim());
  assert.ok(boutons.includes('Analyser cette page'));
  assert.ok(boutons.includes('Ce que l\'analyse ne vérifie pas'));
  assert.equal(pastilles(widget).length, 0);
  widget.detruire();
});

test('bouton et panneau : aria-expanded, dialog nommé, ouverture et fermeture', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const bouton = widget.racine.querySelector('.aa-bouton');
  const panneau = widget.racine.querySelector('[role="dialog"]');
  assert.equal(bouton.getAttribute('aria-expanded'), 'false');
  assert.equal(panneau.hidden, true);
  assert.equal(panneau.getAttribute('aria-modal'), 'false');
  const titre = widget.racine.getElementById(panneau.getAttribute('aria-labelledby'));
  assert.ok(titre && titre.textContent.trim() !== '');
  bouton.click();
  assert.equal(bouton.getAttribute('aria-expanded'), 'true');
  assert.equal(panneau.hidden, false);
  widget.fermer();
  assert.equal(panneau.hidden, true);
  assert.equal(widget.racine.activeElement, bouton);
  widget.detruire();
});

test('fiche : ouverture depuis la liste, contenu pédagogique, retour et Échap', async () => {
  const { document } = await chargerFixture('liens');
  const zones = [];
  const widget = monterWidget(document, donneesFactices(), {
    zoneCorrection: (probleme, conteneur) => zones.push({ probleme, conteneur }),
  });
  widget.ouvrir();
  const problemes = widget.lancer();
  const ligne = widget.racine.querySelector('.aa-ligne');
  ligne.click();
  const fiche = widget.racine.querySelector('.aa-fiche');
  assert.equal(fiche.hidden, false);
  const regle = catalogue.regles[problemes[0].regle];
  assert.ok(fiche.textContent.includes(regle.titre));
  assert.ok(fiche.textContent.includes(regle.qui_gene));
  assert.ok(fiche.textContent.includes(regle.reflexe));
  assert.ok(fiche.textContent.includes(`Critère RGAA ${regle.critere}`));
  assert.ok(!/null|undefined/.test(fiche.textContent), 'aucune valeur vide affichée');
  assert.equal(fiche.querySelectorAll('ol li').length, regle.corriger.length);
  assert.ok(Array.from(fiche.querySelectorAll('button')).some((b) => b.textContent.trim() === 'Retour à la liste'));
  assert.equal(zones.length, 1);
  assert.equal(zones[0].probleme, problemes[0]);

  const Echap = new document.defaultView.KeyboardEvent('keydown', { key: 'Escape', bubbles: true, composed: true });
  fiche.dispatchEvent(Echap);
  assert.equal(fiche.hidden, true);
  assert.equal(widget.racine.activeElement, ligne);
  ligne.dispatchEvent(new document.defaultView.KeyboardEvent('keydown', { key: 'Escape', bubbles: true, composed: true }));
  assert.equal(widget.racine.querySelector('[role="dialog"]').hidden, true);
  assert.equal(widget.racine.activeElement, widget.racine.querySelector('.aa-bouton'));
  widget.detruire();
});

test('fiche : lien de guidage selon l\'emplacement', () => {
  const { window } = new JSDOM(
    '<!DOCTYPE html><html lang="fr"><head><title>Page de test</title></head><body><h1>T</h1>'
    + '<div class="entry-content"><a href="/a">ici</a></div>'
    + '<footer class="wp-block-template-part"><a href="/b">ici</a></footer></body></html>'
  );
  const donnees = donneesFactices();
  const widget = monterWidget(window.document, donnees);
  widget.lancer();
  const lignes = widget.racine.querySelectorAll('.aa-ligne');
  assert.ok(lignes[0].textContent.includes('Contenu de la page'));
  assert.ok(lignes[1].textContent.includes('Pied de page'));
  lignes[0].click();
  let lien = widget.racine.querySelector('.aa-fiche .aa-guidage a');
  assert.equal(lien.textContent.trim(), 'Modifier cette page');
  assert.equal(lien.getAttribute('href'), donnees.objet.lienModifier);
  widget.racine.querySelector('.aa-retour').click();
  lignes[1].click();
  lien = widget.racine.querySelector('.aa-fiche .aa-guidage a');
  assert.equal(lien.textContent.trim(), 'Ouvrir l\'éditeur du site');
  assert.equal(lien.getAttribute('href'), donnees.lienEditeurSite.url);
  widget.detruire();

  const sansObjet = monterWidget(window.document, donneesFactices(null));
  sansObjet.lancer();
  sansObjet.racine.querySelector('.aa-ligne').click();
  assert.equal(sansObjet.racine.querySelector('.aa-fiche .aa-guidage a'), null);
  sansObjet.detruire();

  const produit = monterWidget(window.document, donneesFactices({ id: 3, type: 'product', lienModifier: '/p' }));
  produit.lancer();
  produit.racine.querySelector('.aa-ligne').click();
  assert.equal(produit.racine.querySelector('.aa-fiche .aa-guidage a').textContent.trim(), 'Modifier le produit');
  produit.detruire();
});

test('fiche : élément du thème sans lien vers l\'éditeur du site, la fiche renvoie vers la personne qui gère le thème', () => {
  const { window } = new JSDOM(
    '<!DOCTYPE html><html lang="fr"><head><title>Page de test</title></head><body><h1>T</h1>'
    + '<footer class="wp-block-template-part"><a href="/b">ici</a></footer></body></html>'
  );
  const widget = monterWidget(window.document, { ...donneesFactices(), lienEditeurSite: null });
  widget.lancer();
  widget.racine.querySelector('.aa-ligne').click();
  assert.equal(widget.racine.querySelector('.aa-fiche .aa-guidage'), null);
  assert.ok(widget.racine.querySelector('.aa-fiche .aa-note-theme').textContent.includes('Demandez à la personne qui gère le thème du site.'));
  widget.detruire();

  const classique = monterWidget(window.document, {
    ...donneesFactices(),
    lienEditeurSite: { url: '/wp-admin/customize.php', texte: 'Ouvrir l\'outil de personnalisation' },
  });
  classique.lancer();
  classique.racine.querySelector('.aa-ligne').click();
  const lien = classique.racine.querySelector('.aa-fiche .aa-guidage a');
  assert.equal(lien.textContent.trim(), 'Ouvrir l\'outil de personnalisation');
  assert.equal(lien.getAttribute('href'), '/wp-admin/customize.php');
  assert.equal(classique.racine.querySelector('.aa-fiche .aa-note-theme'), null);
  classique.detruire();
});

test('fiche : image hors du contenu, étape et lien vers la médiathèque (et le produit pour une image de produit)', () => {
  const { window } = new JSDOM(
    '<!DOCTYPE html><html lang="fr"><head><title>Page de test</title></head><body><h1>T</h1>'
    + '<ul class="products"><li><img src="tasse.jpg"></li></ul>'
    + '<div class="woocommerce-product-gallery"><img src="cafe.jpg"></div>'
    + '<div class="entry-content"><img src="photo.jpg"></div></body></html>'
  );
  const textes = (w, sel) => Array.from(w.racine.querySelectorAll(sel)).map((el) => el.textContent.trim());
  const widget = monterWidget(window.document, donneesFactices({ id: 3, type: 'product', lienModifier: '/p' }));
  widget.lancer();
  const lignes = widget.racine.querySelectorAll('.aa-ligne');
  assert.equal(lignes.length, 3);

  lignes[0].click(); // Liste de produits
  let etapes = textes(widget, '.aa-fiche ol li');
  assert.equal(etapes.length, 1);
  assert.match(etapes[0], /^Ouvrez « Médias », puis « Médiathèque », choisissez l'image et remplissez « Texte alternatif »/);
  assert.deepEqual(textes(widget, '.aa-fiche .aa-guidage a'), ['Ouvrir la médiathèque']);
  assert.equal(widget.racine.querySelector('.aa-fiche .aa-guidage a').getAttribute('href'), donneesFactices().lienMediatheque);
  widget.racine.querySelector('.aa-retour').click();

  lignes[1].click(); // Images du produit
  assert.equal(textes(widget, '.aa-fiche ol li').length, 1);
  assert.deepEqual(textes(widget, '.aa-fiche .aa-guidage a'), ['Ouvrir la médiathèque', 'Modifier le produit']);
  widget.racine.querySelector('.aa-retour').click();

  lignes[2].click(); // Contenu de la page : étapes de la règle
  assert.deepEqual(textes(widget, '.aa-fiche ol li'), catalogue.regles['image-alt-absent'].corriger);
  assert.deepEqual(textes(widget, '.aa-fiche .aa-guidage a'), ['Modifier le produit']);
  widget.detruire();

  const sansDroit = monterWidget(window.document, { ...donneesFactices(null), lienMediatheque: null });
  sansDroit.lancer();
  sansDroit.racine.querySelector('.aa-ligne').click();
  assert.match(textes(sansDroit, '.aa-fiche ol li')[0], /^Demandez à une personne qui gère les médias du site/);
  assert.equal(sansDroit.racine.querySelector('.aa-fiche .aa-guidage'), null);
  sansDroit.detruire();
});

test('auto-audit : le widget ouvert, fiche affichée, ne contient aucun problème', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  widget.ouvrir();
  widget.lancer();
  widget.racine.querySelector('.aa-ligne').click();
  widget.racine.querySelector('.aa-limites-bouton').click();
  assert.equal(widget.racine.querySelector('.aa-fiche').hidden, false);
  assert.deepEqual(analyser(widget.racine, { ignorerMasques: false }).map((p) => p.regle), []);
  widget.detruire();
});

test('élément retiré de la page : pastille masquée et fiche qui le signale', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const avecElement = widget.lancer().filter((p) => p.element);
  avecElement[0].element.remove();
  widget.racine.querySelectorAll('.aa-ligne')[0].click();
  const fiche = widget.racine.querySelector('.aa-fiche');
  assert.ok(fiche.textContent.includes('Cet élément n\'est plus dans la page'));
  assert.equal(pastilles(widget)[0].hidden, true);
  assert.equal(pastilles(widget)[1].hidden, false);
  widget.detruire();
});

test('masquer() retire les pastilles, detruire() retire l\'hôte', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  assert.equal(document.querySelectorAll('[data-aa-widget]').length, 1);
  widget.lancer();
  assert.ok(pastilles(widget).length > 0);
  widget.masquer();
  assert.equal(pastilles(widget).length, 0);
  widget.detruire();
  assert.equal(document.querySelectorAll('[data-aa-widget]').length, 0);
});

test('relancerSiDemande : relance l\'analyse et supprime la clé', async () => {
  const { document } = await chargerFixture('liens');
  const widget = monterWidget(document, donneesFactices());
  const valeurs = new Map([[CLE_RELANCE, '1']]);
  const stockage = {
    getItem: (k) => (valeurs.has(k) ? valeurs.get(k) : null),
    removeItem: (k) => valeurs.delete(k),
  };
  assert.equal(relancerSiDemande(widget, stockage), true);
  assert.equal(valeurs.has(CLE_RELANCE), false);
  assert.ok(pastilles(widget).length > 0);
  assert.equal(widget.racine.querySelector('[role="dialog"]').hidden, false);
  assert.equal(relancerSiDemande(widget, stockage), false);
  assert.equal(relancerSiDemande(widget, { getItem() { throw new Error('bloqué'); } }), false);
  widget.detruire();
});

test('emplacement() : règles de la spec 4.6, dans l\'ordre', () => {
  const { window } = new JSDOM(`<body>
    <header class="wp-block-template-part"><a id="entete">a</a><div class="wc-block-mini-cart"><a id="mini">m</a></div></header>
    <main><div class="wp-block-post-content"><ul class="products"><li><a id="contenu-produits">p</a></li></ul></div>
    <div class="entry-content"><a id="entry">e</a></div>
    <ul class="wc-block-product-template"><li><a id="liste">l</a></li></ul>
    <div class="woocommerce-product-gallery"><img id="galerie" alt=""></div></main>
    <footer class="wp-block-template-part"><a id="pied">f</a></footer>
    <footer><a id="autre-pied">x</a></footer>
    <div class="wp-block-group"><a id="gabarit">g</a></div></body>`);
  const d = window.document;
  const e = (id) => emplacement(d.getElementById(id));
  assert.equal(e('contenu-produits'), 'Contenu de la page');
  assert.equal(e('entry'), 'Contenu de la page');
  assert.equal(e('entete'), 'En-tête');
  assert.equal(e('mini'), 'En-tête');
  assert.equal(e('pied'), 'Pied de page');
  assert.equal(e('liste'), 'Liste de produits');
  assert.equal(e('galerie'), 'Images du produit');
  assert.equal(e('autre-pied'), 'Gabarit du thème');
  assert.equal(e('gabarit'), 'Gabarit du thème');
});

test('emplacement() : « Mini-panier » hors en-tête', () => {
  const { window } = new JSDOM('<body><div class="wc-block-mini-cart"><a id="m">m</a></div></body>');
  assert.equal(emplacement(window.document.getElementById('m')), 'Mini-panier');
});

test('emplacement() : « Pied de page » sous footer.wp-block-template-part', () => {
  const { window } = new JSDOM('<body><footer class="wp-block-template-part"><div><a id="x" href="/x">ici</a></div></footer></body>');
  assert.equal(emplacement(window.document.getElementById('x')), 'Pied de page');
});
