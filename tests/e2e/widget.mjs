/**
 * Tests de bout en bout du widget pédagogique (spec 6.6), sur la boutique Local en HTTPS.
 *
 * Prérequis : le site https://accessibilite.test tourne. La boutique de démonstration est
 * remise en mode « avant » (défauts placés) au début du script, par WP-CLI :
 *   tests/bin/wp.sh eval-file <AA_BOUTIQUE> avant
 * AA_BOUTIQUE : script de mise en état de la boutique (par défaut tests/bin/boutique-demo.php, qui suppose
 *   l'option grain_ardoise_medias déjà créée avec les images de la boutique). AA_SANS_REMISE=1 saute cette étape.
 * AA_CAPTURES : dossier des captures (par défaut, le dossier de la tâche dans .superpowers).
 *
 * Lancement : npm run test:e2e (Node 22).
 */
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const RACINE = fileURLToPath( new URL( '../../', import.meta.url ) );
const PLAYWRIGHT_LOCAL = RACINE + 'node_modules/playwright/index.mjs';
const PLAYWRIGHT_PORTFOLIO = '/Users/julienarcioni/Code/portfolio-julien/node_modules/playwright/index.mjs';
const { chromium } = await import( existsSync( PLAYWRIGHT_LOCAL ) ? PLAYWRIGHT_LOCAL : PLAYWRIGHT_PORTFOLIO );

const SITE = 'https://accessibilite.test';
const BOUTIQUE = process.env.AA_BOUTIQUE || RACINE + 'tests/bin/boutique-demo.php';
const CAPTURES = process.env.AA_CAPTURES || RACINE + '.superpowers/sdd/2026-10-17-widget-pedagogique/';

let echecs = 0;
async function etape( nom, fn ) {
	try {
		await fn();
		console.log( `ok - ${ nom }` );
	} catch ( e ) {
		echecs++;
		console.log( `not ok - ${ nom }\n  ${ String( e && e.message ).split( '\n' ).join( '\n  ' ) }` );
	}
}

if ( ! process.env.AA_SANS_REMISE ) {
	assert.ok( existsSync( BOUTIQUE ), `Script de la boutique introuvable : ${ BOUTIQUE }` );
	execFileSync( 'bash', [ RACINE + 'tests/bin/wp.sh', 'eval-file', BOUTIQUE, 'avant' ], { stdio: [ 'ignore', 'ignore', 'inherit' ] } );
	console.log( '# boutique en mode « avant »' );
}

const navigateur = await chromium.launch();

/* ---------- Visiteur ---------- */
await etape( 'visiteur : pas de widget', async () => {
	const contexte = await navigateur.newContext( { ignoreHTTPSErrors: true } );
	const page = await contexte.newPage();
	await page.goto( SITE + '/', { waitUntil: 'load' } );
	assert.equal( await page.locator( '[data-aa-widget]' ).count(), 0 );
	assert.equal( await page.locator( '#wp-script-module-data-audit-accessibilite-widget' ).count(), 0 );
	await contexte.close();
} );

/* ---------- Administrateur ---------- */
const contexte = await navigateur.newContext( { ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 } } );
const page = await contexte.newPage();
const erreurs = [];
page.on( 'pageerror', ( e ) => erreurs.push( e.message ) );
await page.goto( SITE + '/wp-login.php' );
await page.fill( '#user_login', 'julien' );
await page.fill( '#user_pass', 'admin' );
await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );
await page.evaluate( () => {
	try {
		localStorage.removeItem( 'aa-widget-ferme' );
	} catch ( e ) {}
} );
await page.goto( SITE + '/', { waitUntil: 'load' } );

const bouton = page.getByRole( 'button', { name: /^Accessibilité/ } );
const panneau = page.getByRole( 'dialog', { name: 'Accessibilité de la page' } );
const pastilles = page.locator( '[data-aa-widget] .aa-pastille' );

await etape( 'administrateur : le widget est monté, panneau fermé', async () => {
	await bouton.waitFor( { state: 'visible', timeout: 10000 } );
	assert.equal( await bouton.getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( await panneau.count(), 0 );
} );

/** Règle de chaque pastille, retrouvée en rejouant le moteur dans la page. */
async function reglesDesPastilles() {
	return page.evaluate( async () => {
		const url = document.querySelector( 'script[type="module"][src*="widget/index.js"]' ).src.replace( 'index.js', 'moteur.js' );
		const { analyser } = await import( url );
		return analyser( document ).filter( ( p ) => p.element ).map( ( p ) => p.regle );
	} );
}

/**
 * Chaque pastille visible : son coin bas gauche à moins de 4 px du coin haut gauche de son élément
 * (ou, faute de place au-dessus, son coin haut gauche dans le coin de l'élément). Une pastille
 * décalée pour ne pas en chevaucher une autre est à 32 px (ou un multiple) vers la droite.
 */
async function verifierPositions( contexte ) {
	const ecarts = await page.evaluate( async () => {
		const url = document.querySelector( 'script[type="module"][src*="widget/index.js"]' ).src.replace( 'index.js', 'moteur.js' );
		const { analyser } = await import( url );
		const elements = analyser( document ).filter( ( p ) => p.element ).map( ( p ) => p.element );
		const boutons = document.querySelector( '[data-aa-widget]' ).shadowRoot.querySelectorAll( '.aa-pastille' );
		return elements.map( ( el, i ) => {
			let cible = el;
			while ( cible.parentElement && ( cible.getBoundingClientRect().width <= 1 || cible.getBoundingClientRect().height <= 1 ) ) {
				cible = cible.parentElement;
			}
			const a = cible.getBoundingClientRect();
			const b = boutons[ i ].getBoundingClientRect();
			const coin = boutons[ i ].hasAttribute( 'data-dans-le-coin' );
			return {
				dx: b.left - Math.max( 0, a.left ),
				dy: coin ? b.top - a.top : b.bottom - a.top,
				coin,
				decalee: boutons[ i ].hasAttribute( 'data-decalee' ),
			};
		} );
	} );
	for ( const [ i, e ] of ecarts.entries() ) {
		const dx = e.decalee ? e.dx % 32 : e.dx;
		assert.ok( Math.abs( dx ) < 4 && Math.abs( e.dy ) < 4, `${ contexte }, pastille ${ i + 1 } : écart ${ JSON.stringify( e ) }` );
	}
	console.log( `# ${ contexte } : ${ ecarts.length } pastilles, ${ ecarts.filter( ( e ) => e.decalee ).length } décalée(s), ${ ecarts.filter( ( e ) => e.coin ).length } dans le coin` );
}

/** Le bouton « Accessibilité » ne recouvre pas le bandeau de démonstration WooCommerce. */
async function verifierBandeau( contexte ) {
	const mesure = await page.evaluate( () => {
		const bandeau = Array.from( document.querySelectorAll( '.woocommerce-store-notice, p.demo_store' ) ).find( ( b ) => {
			const s = getComputedStyle( b );
			const r = b.getBoundingClientRect();
			return s.display !== 'none' && s.visibility !== 'hidden' && r.height > 0 && r.top < innerHeight && r.bottom > 0;
		} );
		const bouton = document.querySelector( '[data-aa-widget]' ).shadowRoot.querySelector( '.aa-bouton' ).getBoundingClientRect();
		return bandeau ? { haut: bandeau.getBoundingClientRect().top, bas: bouton.bottom } : null;
	} );
	if ( mesure ) {
		assert.ok( mesure.bas <= mesure.haut + 0.5, `${ contexte } : bas du bouton ${ mesure.bas }, haut du bandeau ${ mesure.haut }` );
	}
	console.log( `# ${ contexte } : bandeau ${ mesure ? `visible (haut ${ Math.round( mesure.haut ) }, bas du bouton ${ Math.round( mesure.bas ) })` : 'absent de l\'écran' }` );
}

/**
 * Tab sur chaque pastille, panneau ouvert : la pastille qui a le focus n'est jamais sous le panneau (RGAA 10.7).
 * Départ : le bouton « Masquer les indicateurs », dernier bouton du panneau, juste avant les pastilles.
 */
async function verifierFocusPastilles( contexte ) {
	const n = await pastilles.count();
	await page.getByRole( 'button', { name: 'Masquer les indicateurs' } ).focus();
	const recouvertes = [];
	let deplacements = 0;
	for ( let i = 0; i < n; i++ ) {
		await page.keyboard.press( 'Tab' );
		await page.waitForTimeout( 150 );
		const m = await pastilles.nth( i ).evaluate( ( b ) => {
			const racine = b.getRootNode();
			const a = b.getBoundingClientRect();
			const p = racine.querySelector( '.aa-panneau' ).getBoundingClientRect();
			return {
				focus: racine.activeElement === b,
				chevauche: a.left < p.right && a.right > p.left && a.top < p.bottom && a.bottom > p.top,
				droite: racine.querySelector( '.aa-widget' ).classList.contains( 'aa-droite' ),
				dansEcran: a.top >= 0 && a.bottom <= innerHeight,
			};
		} );
		assert.ok( m.focus, `${ contexte } : la pastille ${ i + 1 } n'a pas reçu le focus` );
		if ( m.chevauche || ! m.dansEcran ) {
			recouvertes.push( { pastille: i + 1, ...m } );
		}
		deplacements += m.droite ? 1 : 0;
	}
	assert.deepEqual( recouvertes, [], `${ contexte } : pastilles cachées au focus` );
	console.log( `# ${ contexte } : ${ n } pastilles atteintes au clavier, aucune sous le panneau (panneau à droite pour ${ deplacements })` );
}

await etape( 'bandeau de démonstration : le bouton ne le recouvre pas (haut de page, puis après défilement)', async () => {
	await page.waitForTimeout( 100 );
	await verifierBandeau( 'haut de page' );
	await page.mouse.wheel( 0, 1500 );
	await page.waitForTimeout( 400 );
	await verifierBandeau( 'après défilement' );
	await page.evaluate( () => scrollTo( 0, 0 ) );
	await page.waitForTimeout( 200 );
} );

await etape( 'accueil à 1440 px : 6 pastilles (3 lien-generique, 1 titres-saut, 1 lien-sans-intitule, 1 champ-sans-etiquette)', async () => {
	await bouton.click();
	assert.equal( await bouton.getAttribute( 'aria-expanded' ), 'true' );
	await panneau.waitFor( { state: 'visible' } );
	await page.getByRole( 'button', { name: 'Analyser cette page' } ).click();
	const n = await pastilles.count();
	const regles = await reglesDesPastilles();
	const compte = regles.reduce( ( acc, r ) => ( { ...acc, [ r ]: ( acc[ r ] || 0 ) + 1 } ), {} );
	assert.equal( n, 6, `pastilles : ${ n } ${ JSON.stringify( compte ) }` );
	assert.deepEqual( compte, { 'lien-generique': 3, 'titres-saut': 1, 'lien-sans-intitule': 1, 'champ-sans-etiquette': 1 } );
	const nom = await bouton.evaluate( ( b ) => b.textContent.replace( /\s+/g, ' ' ) );
	assert.match( nom, /, \d+ problèmes?$/ );
	const synthese = await page.locator( '[data-aa-widget] .aa-synthese' ).textContent();
	console.log( `# synthèse : ${ synthese } ; bouton : ${ nom }` );
	for ( let i = 0; i < n; i++ ) {
		assert.match( await pastilles.nth( i ).getAttribute( 'aria-label' ), new RegExp( `^Problème ${ i + 1 }, (important|à revoir) : ` ) );
	}
	await page.waitForTimeout( 200 );
	await verifierPositions( '1440 px, haut de page' );
} );

await etape( 'la mention « Elle ne remplace pas un audit complet » est visible', async () => {
	await page.getByText( 'Elle ne remplace pas un audit complet' ).waitFor( { state: 'visible' } );
	assert.equal( await page.getByRole( 'button', { name: 'Ce que l\'analyse ne vérifie pas' } ).isVisible(), true );
} );

await etape( 'aucun texte du widget ne contient « conforme »', async () => {
	const texte = await page.locator( '[data-aa-widget]' ).evaluate( ( h ) => h.shadowRoot.textContent );
	assert.ok( ! /conforme/i.test( texte ) );
} );

await etape( 'capture : panneau ouvert avec les pastilles (1440 × 900)', async () => {
	await page.waitForTimeout( 300 );
	await page.screenshot( { path: CAPTURES + 'capture-widget-accueil.png' } );
} );

await etape( 'clavier, 1440 px : chaque pastille atteinte par Tab reste visible, hors du panneau', async () => {
	await verifierFocusPastilles( '1440 px' );
	await page.evaluate( () => scrollTo( 0, 0 ) );
	await page.waitForTimeout( 200 );
} );

await etape( 'clavier : Tab jusqu\'à la première ligne, Entrée, Échap, Échap', async () => {
	await page.getByRole( 'button', { name: 'Analyser cette page' } ).focus();
	const premiere = page.locator( '[data-aa-widget] .aa-ligne' ).first();
	let atteinte = false;
	for ( let i = 0; i < 10 && ! atteinte; i++ ) {
		await page.keyboard.press( 'Tab' );
		atteinte = await premiere.evaluate( ( el ) => el.getRootNode().activeElement === el );
	}
	assert.ok( atteinte, 'première ligne non atteinte au clavier' );
	await page.keyboard.press( 'Enter' );
	const titre = page.locator( '[data-aa-widget] .aa-fiche-titre' );
	await titre.waitFor( { state: 'visible' } );
	assert.ok( await titre.evaluate( ( el ) => el.getRootNode().activeElement === el ), 'focus sur le titre de la fiche' );
	await page.waitForTimeout( 700 );
	await page.screenshot( { path: CAPTURES + 'capture-widget-fiche.png' } );
	await page.keyboard.press( 'Escape' );
	assert.equal( await titre.count(), 0 );
	assert.ok( await premiere.evaluate( ( el ) => el.getRootNode().activeElement === el ), 'focus rendu à la ligne' );
	await page.keyboard.press( 'Escape' );
	assert.equal( await panneau.count(), 0 );
	assert.equal( await bouton.getAttribute( 'aria-expanded' ), 'false' );
	assert.ok( await bouton.evaluate( ( el ) => el.getRootNode().activeElement === el ), 'focus sur le bouton « Accessibilité »' );
	assert.equal( await pastilles.count(), 6, 'les pastilles restent après la fermeture' );
} );

await etape( 'un clic sur une pastille ouvre sa fiche', async () => {
	await pastilles.first().click();
	await page.locator( '[data-aa-widget] .aa-fiche-titre' ).waitFor( { state: 'visible' } );
	await page.keyboard.press( 'Escape' );
	await page.keyboard.press( 'Escape' );
} );

await etape( 'après défilement (fiche du dernier problème) : pastilles toujours à leur place', async () => {
	await pastilles.last().click();
	await page.waitForTimeout( 900 );
	await verifierPositions( '1440 px, après défilement' );
	await verifierBandeau( 'après défilement vers le pied de page' );
	await page.keyboard.press( 'Escape' );
	await page.keyboard.press( 'Escape' );
	await page.evaluate( () => scrollTo( 0, 0 ) );
} );

await etape( '390 px : pastilles au coin de leur élément, panneau sur toute la largeur', async () => {
	await page.setViewportSize( { width: 390, height: 844 } );
	await page.waitForTimeout( 400 );
	await verifierPositions( '390 px' );
	await bouton.click();
	await panneau.waitFor( { state: 'visible' } );
	const largeur = await panneau.evaluate( ( p ) => p.getBoundingClientRect().width );
	const fenetre = await page.evaluate( () => document.documentElement.clientWidth );
	assert.ok( Math.abs( largeur - fenetre ) <= 1, `panneau ${ largeur } px, fenêtre ${ fenetre } px` );
	await page.screenshot( { path: CAPTURES + 'capture-widget-390.png' } );
	// Panneau ouvert sous 600 px : le bouton flottant est masqué ; à la fermeture, il revient avec le focus.
	assert.ok( ! await bouton.isVisible(), 'bouton flottant masqué tant que le panneau est ouvert' );
	await page.keyboard.press( 'Escape' );
	assert.equal( await panneau.count(), 0 );
	assert.ok( await bouton.isVisible(), 'bouton flottant réaffiché à la fermeture' );
	assert.ok( await bouton.evaluate( ( el ) => el.getRootNode().activeElement === el ), 'focus rendu au bouton flottant' );
	await bouton.click();
	await panneau.waitFor( { state: 'visible' } );
} );

await etape( 'clavier, 390 px : chaque pastille atteinte par Tab reste visible, au-dessus du panneau', async () => {
	await verifierFocusPastilles( '390 px' );
	await page.evaluate( () => scrollTo( 0, 0 ) );
	await page.waitForTimeout( 200 );
} );

await etape( 'auto-audit dans le navigateur : widget ouvert, fiche affichée, aucun problème', async () => {
	await page.locator( '[data-aa-widget] .aa-ligne' ).first().click();
	const resultat = await page.evaluate( async () => {
		const url = document.querySelector( 'script[type="module"][src*="widget/index.js"]' ).src.replace( 'index.js', 'moteur.js' );
		const { analyser } = await import( url );
		return analyser( document.querySelector( '[data-aa-widget]' ).shadowRoot, { ignorerMasques: false } ).map( ( p ) => p.regle );
	} );
	assert.deepEqual( resultat, [] );
} );

await etape( '390 px, fiche de l\'image du produit : la pastille reste sous la barre d\'administration', async () => {
	await page.goto( SITE + '/produit/kenya-nyeri/', { waitUntil: 'networkidle' } );
	// Le panneau reste ouvert d'une page à l'autre (et le bouton est alors masqué sous 600 px).
	await page.locator( '[data-aa-widget] .aa-bouton' ).waitFor( { state: 'attached' } );
	if ( ! await panneau.count() ) {
		await bouton.click();
	}
	await panneau.waitFor( { state: 'visible' } );
	await page.getByRole( 'button', { name: 'Analyser cette page' } ).click();
	await page.locator( '[data-aa-widget] .aa-ligne' ).first().waitFor( { state: 'visible' } );
	const choix = await page.evaluate( () => {
		const r = document.querySelector( '[data-aa-widget]' ).shadowRoot;
		const lignes = [ ...r.querySelectorAll( '.aa-ligne' ) ];
		return lignes.map( ( l ) => l.textContent.trim().slice( 0, 60 ) );
	} );
	const ligne = page.locator( '[data-aa-widget] .aa-ligne' ).filter( { hasText: /image/i } ).first();
	assert.ok( await ligne.count(), `aucune ligne d'image : ${ JSON.stringify( choix ) }` );
	await ligne.click();
	await page.locator( '[data-aa-widget] .aa-fiche-titre' ).waitFor( { state: 'visible' } );
	await page.waitForTimeout( 1200 );
	const m = await page.evaluate( () => {
		const r = document.querySelector( '[data-aa-widget]' ).shadowRoot;
		const choisie = r.querySelector( '.aa-pastille.aa-choisie' );
		const barre = document.getElementById( 'wpadminbar' );
		return { haut: choisie.getBoundingClientRect().top, barre: barre ? barre.getBoundingClientRect().bottom : 0 };
	} );
	assert.ok( m.haut >= m.barre, `pastille à ${ m.haut } px, bas de la barre d'administration à ${ m.barre } px` );
	await page.screenshot( { path: CAPTURES + 'capture-widget-produit-390.png' } );
} );

await etape( 'aucune erreur JavaScript', async () => {
	assert.deepEqual( erreurs, [] );
} );

await navigateur.close();
console.log( echecs ? `# ${ echecs } échec(s)` : '# tout passe' );
process.exit( echecs ? 1 : 0 );
