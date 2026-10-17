/**
 * Moteur d'analyse RGAA du widget pédagogique.
 *
 * Port en JavaScript des contrôles PHP (class-audit-accessibilite-rgaa.php) :
 * mêmes règles, mêmes listes, mêmes expressions, mêmes exclusions.
 * Module sans dépendance : utilisable dans le navigateur et dans Node (jsdom).
 */

/** Intitulés qui ne disent rien de la destination hors de leur contexte. */
export const INTITULES_GENERIQUES = [
  'ici', 'cliquez', 'cliquez ici', 'en savoir plus', 'lire la suite', 'lire plus',
  'voir plus', 'voir', 'plus', 'suite', 'lien', 'click', 'click here', 'here',
  'read more', 'more',
];

const EXTENSION_IMAGE = /\.(jpe?g|png|gif|webp|avif|svg)$/i;
const CODE_LANGUE = /^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/;
const ESPACES_PHP = ' \\t\\n\\r\\0\\x0B';

/* ---------- Utilitaires alignés sur PHP ---------- */

/** trim() de PHP : espace, \t, \n, \r, \0, \x0B (pas l'espace insécable). */
function trimPhp(texte, extra = '') {
  const jeu = `[${ESPACES_PHP}${extra}]`;
  return String(texte).replace(new RegExp(`^${jeu}+|${jeu}+$`, 'g'), '');
}

/** preg_replace('/\s+/u', ' ') : avec /u, \s couvre aussi les espaces Unicode (U+00A0, U+202F…), comme \s en JavaScript. */
function replierEspaces(texte) {
  return String(texte).replace(/\s+/g, ' ');
}

function texte(el) {
  return el.textContent || '';
}

function octets(chaine) {
  return new TextEncoder().encode(chaine).length;
}

/** basename(parse_url($src, PHP_URL_PATH)) */
function nomFichier(src) {
  let chemin = String(src);
  const fin = chemin.search(/[?#]/);
  if (fin !== -1) chemin = chemin.slice(0, fin);
  chemin = chemin.replace(/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\/[^/]*/, '').replace(/^\/\/[^/]*/, '');
  chemin = chemin.replace(/\/+$/, '');
  return chemin.slice(chemin.lastIndexOf('/') + 1);
}

function aAttributOuLiaison(el, attribut) {
  return el.hasAttribute(attribut) || el.hasAttribute('data-wp-bind--' + attribut);
}

function dansTemplate(el) {
  return !!(el.closest && el.closest('template'));
}

/* ---------- Chemin ---------- */

/** Équivalent de DOMNode::getNodePath() pour un élément. */
export function cheminNoeud(noeud) {
  const segments = [];
  let courant = noeud;
  while (courant && courant.nodeType === 1) {
    const nom = courant.localName.toLowerCase();
    const parent = courant.parentNode;
    let segment = nom;
    if (parent) {
      let meme = 0;
      let rang = 0;
      for (const frere of parent.children) {
        if (frere.localName.toLowerCase() === nom) {
          meme++;
          if (frere === courant) rang = meme;
        }
      }
      if (meme > 1) segment += `[${rang}]`;
    }
    segments.unshift(segment);
    courant = parent && parent.nodeType === 1 ? parent : null;
  }
  return '/' + segments.join('/');
}

/* ---------- Noms accessibles ---------- */

function racineDe(el) {
  const r = el.getRootNode ? el.getRootNode() : el.ownerDocument;
  return r && r.querySelectorAll ? r : el.ownerDocument;
}

function premierParId(el, id) {
  for (const c of racineDe(el).querySelectorAll('[id]')) {
    if (c.getAttribute('id') === id) return c;
  }
  return null;
}

/** Équivalent de get_accessible_name. */
export function nomAccessible(el) {
  if (el.hasAttribute('aria-labelledby')) {
    const textes = [];
    for (const id of trimPhp(el.getAttribute('aria-labelledby')).split(/[ \t\n\r\f\v]+/)) {
      const cible = premierParId(el, id);
      if (cible) textes.push(trimPhp(texte(cible)));
    }
    const nom = trimPhp(textes.join(' '));
    if (nom !== '') return nom;
  }

  if (trimPhp(el.getAttribute('aria-label') || '') !== '') {
    return trimPhp(el.getAttribute('aria-label'));
  }

  let t = trimPhp(replierEspaces(texte(el)));
  for (const image of el.querySelectorAll('img[alt]')) {
    t += ' ' + trimPhp(image.getAttribute('alt'));
  }
  return trimPhp(t);
}

/** Équivalent de get_field_label. */
export function etiquetteChamp(champ) {
  if (champ.hasAttribute('aria-labelledby') || champ.hasAttribute('aria-label')) {
    const nom = nomAccessible(champ);
    if (nom !== '' && nom !== trimPhp(replierEspaces(texte(champ)))) return nom;
  }

  const id = champ.getAttribute('id') || '';
  if (id !== '') {
    let label = null;
    for (const l of racineDe(champ).querySelectorAll('label[for]')) {
      if (l.getAttribute('for') === id) { label = l; break; }
    }
    if (label && trimPhp(texte(label)) !== '') return trimPhp(texte(label));
  }

  let parent = champ.parentNode;
  while (parent && parent.nodeType === 1) {
    if (parent.localName === 'label' && trimPhp(texte(parent)) !== '') {
      return trimPhp(texte(parent));
    }
    parent = parent.parentNode;
  }

  return trimPhp(champ.getAttribute('title') || '');
}

/* ---------- Éléments masqués ---------- */

let cacheMasque = new WeakMap(); // remis à zéro à chaque analyse (le DOM vivant change)

function estMasque(el) {
  if (cacheMasque.has(el)) return cacheMasque.get(el);
  const m = calculerMasque(el);
  cacheMasque.set(el, m);
  return m;
}

function calculerMasque(el) {
  const vue = el.ownerDocument && el.ownerDocument.defaultView;
  let courant = el;
  let premier = true;
  while (courant && courant.nodeType === 1) {
    // Le widget et la barre d'administration (jamais vue des visiteurs) sont ignorés comme des éléments masqués.
    if (courant.hasAttribute('hidden') || courant.hasAttribute('data-aa-widget') || courant.id === 'wpadminbar') return true;
    if (vue && vue.getComputedStyle) {
      const style = vue.getComputedStyle(courant);
      if (style.display === 'none') return true;
      if (premier && style.visibility === 'hidden') return true;
    }
    premier = false;
    const parent = courant.parentNode;
    courant = parent && parent.nodeType === 11 ? parent.host : parent;
  }
  return false;
}

/* ---------- Analyse ---------- */

/**
 * Analyse un document ou un sous-arbre.
 * @param {Document|Element|ShadowRoot} racine
 * @param {{ignorerMasques?: boolean}} [options]
 */
export function analyser(racine, options = {}) {
  cacheMasque = new WeakMap();
  const ignorerMasques = options.ignorerMasques !== false;
  const estDocument = racine.nodeType === 9;
  const portee = racine;

  const garder = (el) => !dansTemplate(el) && !(ignorerMasques && estMasque(el));
  const tous = (selecteur) => Array.from(portee.querySelectorAll(selecteur)).filter(garder);

  const pages = [];
  const elements = [];
  const ajouterPage = (regle, details = {}) => pages.push({ regle, element: null, details, chemin: null });
  const ajouter = (regle, element, details) =>
    elements.push({ regle, element, details, chemin: cheminNoeud(element) });

  /* Images (1.1, 1.3) */
  for (const image of tous('img')) {
    const alt = trimPhp(image.getAttribute('alt') || '');
    const src = image.getAttribute('src') || '';
    const fichier = nomFichier(src);
    const decorative = image.getAttribute('aria-hidden') === 'true'
      || ['presentation', 'none'].includes(image.getAttribute('role'));

    if (!decorative && !aAttributOuLiaison(image, 'alt') && !aAttributOuLiaison(image, 'aria-label')
      && !image.hasAttribute('aria-labelledby')) {
      ajouter('image-alt-absent', image, { fichier });
    } else if (!decorative && alt !== ''
      && (alt.toLowerCase() === fichier.toLowerCase() || EXTENSION_IMAGE.test(alt))) {
      ajouter('image-alt-fichier', image, { fichier });
    }
  }

  /* Liens (6.1, 6.2) */
  for (const lien of tous('a[href]')) {
    if (lien.getAttribute('aria-hidden') === 'true') continue;
    const href = lien.getAttribute('href') || '';
    const t = nomAccessible(lien);
    const normalise = trimPhp(t, '.…›»→').toLowerCase();
    if (t === '' && !lien.hasAttribute('data-wp-bind--aria-label')) {
      ajouter('lien-sans-intitule', lien, { href, texte: t });
    } else if (INTITULES_GENERIQUES.includes(normalise)) {
      ajouter('lien-generique', lien, { href, texte: t });
    }
  }

  /* Titre du document (8.5) */
  if (estDocument) {
    const titre = portee.querySelector('title');
    if (!titre) {
      ajouterPage('titre-document-absent');
    } else {
      const t = trimPhp(texte(titre));
      if (t === '' || t === '0' || octets(t) < 5) ajouterPage('titre-document-court');
    }

    /* Langue (8.3, 8.4) */
    const html = portee.documentElement;
    if (!html) {
      ajouterPage('html-absent');
    } else if (!html.hasAttribute('lang')) {
      ajouterPage('langue-absente');
    } else {
      const lang = html.getAttribute('lang');
      if (lang === '' || lang === '0') {
        ajouterPage('langue-vide', { lang });
      } else if (!CODE_LANGUE.test(lang)) {
        ajouterPage('langue-invalide', { lang });
      }
    }
  }

  /* Titres (9.1) */
  const titres = tous('h1,h2,h3,h4,h5,h6');
  if (estDocument) {
    if (titres.length === 0) {
      ajouterPage('titres-absents');
    } else if (!titres.some((h) => h.localName === 'h1')) {
      ajouterPage('h1-absent');
    }
  }
  let precedent = 0;
  for (const h of titres) {
    const niveau = Number(h.localName.slice(1));
    if (precedent > 0 && niveau > precedent + 1) {
      ajouter('titres-saut', h, { de: precedent, a: niveau });
    }
    precedent = niveau;
  }

  /* Formulaires (11.1) */
  const TYPES_EXCLUS = ['hidden', 'submit', 'button', 'reset', 'image'];
  const champs = tous('input,select,textarea').filter((c) =>
    c.localName !== 'input' || !TYPES_EXCLUS.includes(c.getAttribute('type')));
  for (const champ of champs) {
    if (champ.getAttribute('aria-hidden') === 'true' || champ.getAttribute('tabindex') === '-1') continue;
    const nom = champ.getAttribute('name') || champ.getAttribute('id') || champ.localName;
    if (etiquetteChamp(champ) === '' && !champ.hasAttribute('data-wp-bind--aria-label')) {
      ajouter('champ-sans-etiquette', champ, { nom, placeholder: champ.getAttribute('placeholder') || '' });
    }
  }

  /* Tableaux (5.6) */
  const tableaux = tous('table').filter((t) => !['presentation', 'none'].includes(t.getAttribute('role')));
  for (const tableau of tableaux) {
    const enTetes = Array.from(tableau.querySelectorAll('th,[role]')).filter((e) =>
      e.localName === 'th' || ['columnheader', 'rowheader'].includes(e.getAttribute('role')));
    if (enTetes.length === 0) {
      const legende = tableau.querySelector('caption');
      ajouter('tableau-sans-en-tetes', tableau, { legende: legende ? trimPhp(texte(legende)) : null });
    }
  }

  elements.sort((a, b) => {
    const p = a.element.compareDocumentPosition(b.element);
    if (p & 4) return -1; // b suit a
    if (p & 2) return 1;
    return 0;
  });
  return [...pages, ...elements];
}
