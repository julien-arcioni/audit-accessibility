/**
 * Interface du widget pédagogique : bouton flottant, panneau, fiches et pastilles.
 *
 * Tout vit dans le Shadow DOM d'un hôte <div data-aa-widget> ajouté à la fin de <body> :
 * les styles du thème ne touchent pas le widget, et le moteur l'ignore quand il analyse la page.
 */
import { analyser } from './moteur.js';
import { emplacement, GABARIT } from './emplacement.js';

/** Clé de sessionStorage posée après une correction : l'analyse se relance au chargement suivant. */
export const CLE_RELANCE = 'aa-widget-relancer';
/** Clé de localStorage : « 1 » si l'utilisateur a fermé le panneau, « 0 » s'il l'a laissé ouvert. */
export const CLE_FERME = 'aa-widget-ferme';

const NB_CONTROLES = 11;
const TAILLE_PASTILLE = 28;
const DECALAGE_PASTILLE = 32;
const SEUIL_MOBILE = 600;

const PRIORITES = {
	high: { mot: 'Important', minuscule: 'important', classe: 'aa-important' },
	medium: { mot: 'À revoir', minuscule: 'à revoir', classe: 'aa-a-revoir' },
	low: { mot: 'À revoir', minuscule: 'à revoir', classe: 'aa-a-revoir' },
};

const SVG = 'http://www.w3.org/2000/svg';

/** Règles d'images : hors du contenu de la page, la description se règle dans la médiathèque. */
const REGLES_IMAGE = [ 'image-alt-absent', 'image-alt-fichier' ];
const ETAPE_MEDIATHEQUE = 'Ouvrez « Médias », puis « Médiathèque », choisissez l\'image et remplissez « Texte alternatif » : la description s\'appliquera partout où le thème et la boutique affichent cette image.';
const ETAPE_MEDIATHEQUE_SANS_DROIT = 'Demandez à une personne qui gère les médias du site d\'ouvrir « Médias », puis « Médiathèque », de choisir l\'image et de remplir « Texte alternatif » : la description s\'appliquera partout où le thème et la boutique affichent cette image.';
const NOTE_THEME = 'Cet élément vient du thème. Demandez à la personne qui gère le thème du site.';

const ICONES = {
	fermer: 'M6.4 5 12 10.6 17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4Z',
	retour: 'M10.8 5.3 12.2 6.7 7.9 11H20v2H7.9l4.3 4.3-1.4 1.4L4.1 12Z',
};

function priorite( regle ) {
	return PRIORITES[ regle && regle.priorite ] || PRIORITES.medium;
}

function minusculeInitiale( texte ) {
	return texte ? texte.charAt( 0 ).toLocaleLowerCase( 'fr' ) + texte.slice( 1 ) : '';
}

function lireStockage( stockage, cle ) {
	try {
		return stockage ? stockage.getItem( cle ) : null;
	} catch ( e ) {
		return null;
	}
}

function ecrireStockage( stockage, cle, valeur ) {
	try {
		if ( stockage ) {
			stockage.setItem( cle, valeur );
		}
	} catch ( e ) {
		// Stockage bloqué (navigation privée, réglages) : la préférence n'est simplement pas gardée.
	}
}

function stockageDe( vue, nom ) {
	try {
		return vue ? vue[ nom ] : null;
	} catch ( e ) {
		return null;
	}
}

/**
 * Relance l'analyse si une correction l'a demandé (sessionStorage), puis supprime la demande.
 * @returns {boolean} vrai si l'analyse a été relancée.
 */
export function relancerSiDemande( widget, stockage ) {
	let demande = false;
	try {
		demande = !! stockage && stockage.getItem( CLE_RELANCE ) === '1';
		if ( demande ) {
			stockage.removeItem( CLE_RELANCE );
		}
	} catch ( e ) {
		return false;
	}
	if ( ! demande ) {
		return false;
	}
	widget.ouvrir();
	widget.lancer();
	return true;
}

/** Synthèse : « 6 problèmes : 3 importants, 3 à revoir ». */
export function synthese( problemes, regles ) {
	const total = problemes.length;
	const importants = problemes.filter( ( p ) => priorite( regles[ p.regle ] ).classe === 'aa-important' ).length;
	const aRevoir = total - importants;
	const parties = [];
	if ( importants ) {
		parties.push( `${ importants } ${ importants > 1 ? 'importants' : 'important' }` );
	}
	if ( aRevoir ) {
		parties.push( `${ aRevoir } à revoir` );
	}
	return `${ total } ${ total > 1 ? 'problèmes' : 'problème' } : ${ parties.join( ', ' ) }`;
}

/** Description courte de l'élément repéré (balise, texte, image), pour la fiche. */
function decrire( probleme ) {
	const d = probleme.details || {};
	switch ( probleme.regle ) {
		case 'image-alt-absent':
		case 'image-alt-fichier':
			return d.fichier ? `Image repérée : « ${ d.fichier } ».` : 'Image repérée, sans nom de fichier.';
		case 'lien-sans-intitule':
			return d.href ? `Lien repéré, vers « ${ d.href } ».` : 'Lien repéré.';
		case 'lien-generique':
			return `Lien repéré : « ${ d.texte } »${ d.href ? `, vers « ${ d.href } »` : '' }.`;
		case 'titres-saut':
			return `Titre de niveau ${ d.a } placé juste après un titre de niveau ${ d.de } : le niveau ${ d.de + 1 } manque.`;
		case 'champ-sans-etiquette':
			return d.placeholder
				? `Champ repéré, avec seulement le texte indicatif « ${ d.placeholder } ».`
				: 'Champ de saisie repéré, sans texte qui le décrive.';
		case 'tableau-sans-en-tetes':
			return d.legende ? `Tableau repéré : « ${ d.legende } ».` : 'Tableau repéré.';
		case 'langue-vide':
		case 'langue-invalide':
			return `Langue déclarée : « ${ d.lang } ».`;
		default:
			return '';
	}
}

/**
 * Monte le widget dans le document.
 * @param {Document} doc
 * @param {object} donnees Données de Audit_Accessibilite_Widget::donnees().
 * @param {{zoneCorrection?: (probleme: object, conteneur: HTMLElement) => void}} [options]
 *        zoneCorrection : point d'extension (tâche 7), appelé à chaque fiche affichée avec
 *        un conteneur vide placé entre le critère RGAA et le lien de guidage.
 */
export function monterWidget( doc, donnees, options = {} ) {
	const vue = doc.defaultView;
	const regles = donnees.regles || {};
	const limites = Array.isArray( donnees.limites ) ? donnees.limites : [];
	const local = stockageDe( vue, 'localStorage' );
	const nettoyages = [];

	/* ---------- Fabrique d'éléments (jamais d'innerHTML avec des données) ---------- */

	function h( balise, attributs = {}, enfants = [] ) {
		const el = doc.createElement( balise );
		for ( const [ nom, valeur ] of Object.entries( attributs ) ) {
			if ( valeur === null || valeur === undefined || valeur === false ) {
				continue;
			}
			if ( nom === 'texte' ) {
				el.textContent = valeur;
			} else if ( nom.startsWith( 'on' ) ) {
				el.addEventListener( nom.slice( 2 ), valeur );
			} else if ( nom === 'hidden' ) {
				el.hidden = true;
			} else {
				el.setAttribute( nom, valeur === true ? '' : String( valeur ) );
			}
		}
		for ( const enfant of [].concat( enfants ) ) {
			if ( enfant === null || enfant === undefined || enfant === false ) {
				continue;
			}
			el.append( typeof enfant === 'string' ? doc.createTextNode( enfant ) : enfant );
		}
		return el;
	}

	function icone( chemin ) {
		const svg = doc.createElementNS( SVG, 'svg' );
		svg.setAttribute( 'viewBox', '0 0 24 24' );
		svg.setAttribute( 'width', '20' );
		svg.setAttribute( 'height', '20' );
		svg.setAttribute( 'aria-hidden', 'true' );
		svg.setAttribute( 'focusable', 'false' );
		svg.setAttribute( 'class', 'aa-icone' );
		const path = doc.createElementNS( SVG, 'path' );
		path.setAttribute( 'd', chemin );
		svg.append( path );
		return svg;
	}

	function listeLimites() {
		return h( 'ul', { class: 'aa-limites-liste' }, limites.map( ( l ) =>
			h( 'li', {}, [ h( 'strong', { texte: l.titre } ), ' ', h( 'span', { texte: l.geste } ) ] )
		) );
	}

	/* ---------- Structure ---------- */

	const hote = h( 'div', { 'data-aa-widget': true, lang: 'fr' } );
	const racine = hote.attachShadow( { mode: 'open' } );

	if ( donnees.css ) {
		// Masqué jusqu'au chargement de la feuille, qui rétablit l'affichage (même sélecteur, placé après).
		racine.append( h( 'style', { texte: ':host{display:none}' } ) );
		racine.append( h( 'link', { rel: 'stylesheet', href: donnees.css } ) );
	}

	const compte = h( 'span', { class: 'aa-compte', 'aria-hidden': 'true', hidden: true } );
	const compteLu = h( 'span', { class: 'aa-sr' } );
	const bouton = h( 'button', {
		type: 'button',
		class: 'aa-bouton',
		'aria-expanded': 'false',
		'aria-controls': 'aa-panneau',
	}, [ h( 'span', { texte: 'Accessibilité' } ), compte, compteLu ] );

	const titrePanneau = h( 'h2', { id: 'aa-titre', class: 'aa-titre', texte: 'Accessibilité de la page' } );
	const boutonFermer = h( 'button', { type: 'button', class: 'aa-fermer', onclick: () => fermer() },
		[ icone( ICONES.fermer ), h( 'span', { texte: 'Fermer' } ) ] );

	const boutonAnalyser = h( 'button', { type: 'button', class: 'aa-analyser', texte: 'Analyser cette page', onclick: () => lancer() } );
	const limitesBloc = h( 'div', { id: 'aa-limites', class: 'aa-limites', hidden: true }, [ listeLimites() ] );
	const boutonLimites = h( 'button', {
		type: 'button',
		class: 'aa-limites-bouton',
		'aria-expanded': 'false',
		'aria-controls': 'aa-limites',
		texte: 'Ce que l\'analyse ne vérifie pas',
		onclick: () => {
			const ouvert = limitesBloc.hidden;
			limitesBloc.hidden = ! ouvert;
			boutonLimites.setAttribute( 'aria-expanded', String( ouvert ) );
		},
	} );
	const resultats = h( 'div', { class: 'aa-resultats', hidden: true } );
	const vueListe = h( 'div', { class: 'aa-vue-liste' }, [
		h( 'div', { class: 'aa-lancement' }, [
			boutonAnalyser,
			h( 'p', { class: 'aa-avertissement', texte: donnees.avertissement || '' } ),
			boutonLimites,
			limitesBloc,
		] ),
		resultats,
	] );
	const fiche = h( 'div', { class: 'aa-fiche', hidden: true } );
	const boutonMasquer = h( 'button', { type: 'button', class: 'aa-masquer', hidden: true, texte: 'Masquer les indicateurs', onclick: () => basculerPastilles() } );

	const corps = h( 'div', { class: 'aa-corps' }, [ vueListe, fiche ] );
	const pied = h( 'div', { class: 'aa-pied', hidden: true }, [ boutonMasquer ] );
	const panneau = h( 'div', {
		id: 'aa-panneau',
		class: 'aa-panneau',
		role: 'dialog',
		'aria-modal': 'false',
		'aria-labelledby': 'aa-titre',
		hidden: true,
	}, [
		h( 'div', { class: 'aa-entete' }, [ titrePanneau, boutonFermer ] ),
		corps,
		pied,
	] );

	const annonce = h( 'div', { class: 'aa-sr', 'aria-live': 'polite' } );
	const contour = h( 'div', { class: 'aa-contour', 'aria-hidden': 'true', hidden: true } );
	// Espace ajouté sous la fin de la page, sur mobile, pour pouvoir amener ses derniers éléments au-dessus du panneau.
	const espace = h( 'div', { class: 'aa-espace', 'aria-hidden': 'true', hidden: true } );
	const calque = h( 'div', { class: 'aa-calque' }, [ contour, espace ] );
	const conteneur = h( 'div', { class: 'aa-widget' }, [ bouton, panneau, annonce, calque ] );
	racine.append( conteneur );
	doc.body.append( hote );

	bouton.addEventListener( 'click', () => ( panneau.hidden ? ouvrir() : fermer() ) );

	/* ---------- État ---------- */

	let problemes = [];
	let pastilles = []; // { probleme, numero, bouton, cible, deplace }
	let pastillesMasquees = false;
	let ficheCourante = null;
	let origineFiche = null;

	/* ---------- Panneau ---------- */

	function ouvrir( { focus = true } = {} ) {
		panneau.hidden = false;
		conteneur.classList.add( 'aa-ouvert' );
		bouton.setAttribute( 'aria-expanded', 'true' );
		ecrireStockage( local, CLE_FERME, '0' );
		if ( focus ) {
			boutonAnalyser.focus();
		}
	}

	function fermer() {
		if ( ficheCourante ) {
			fermerFiche( { focus: false } );
		}
		panneau.hidden = true;
		espace.hidden = true;
		conteneur.classList.remove( 'aa-ouvert' );
		bouton.setAttribute( 'aria-expanded', 'false' );
		ecrireStockage( local, CLE_FERME, '1' );
		bouton.focus();
	}

	function annoncer( texte ) {
		annonce.textContent = '';
		// Un court délai : le changement est perçu comme une nouvelle annonce.
		const ecrire = () => { annonce.textContent = texte; };
		if ( vue && vue.setTimeout ) {
			vue.setTimeout( ecrire, 50 );
		} else {
			ecrire();
		}
	}

	racine.addEventListener( 'keydown', ( e ) => {
		if ( e.key !== 'Escape' ) {
			return;
		}
		if ( ficheCourante ) {
			e.preventDefault();
			fermerFiche();
		} else if ( ! panneau.hidden ) {
			e.preventDefault();
			fermer();
		}
	} );

	/* ---------- Analyse et liste ---------- */

	function lancer() {
		if ( ficheCourante ) {
			fermerFiche( { focus: false } );
		}
		problemes = analyser( doc );
		rendreResultats();
		pastillesMasquees = false;
		creerPastilles();
		mettreAJourBouton();
		annoncer( problemes.length
			? `Analyse terminée. ${ synthese( problemes, regles ) }.`
			: `Analyse terminée. Aucune erreur détectée parmi les ${ NB_CONTROLES } contrôles automatiques.` );
		return problemes;
	}

	function mettreAJourBouton() {
		const n = problemes.length;
		compte.hidden = false;
		compte.textContent = String( n );
		compte.classList.toggle( 'aa-compte-nul', n === 0 );
		compteLu.textContent = n === 0 ? ', aucune erreur détectée' : `, ${ n } ${ n > 1 ? 'problèmes' : 'problème' }`;
	}

	function numeroDe( probleme ) {
		const p = pastilles.find( ( x ) => x.probleme === probleme );
		return p ? p.numero : null;
	}

	function ligne( probleme, numero ) {
		const regle = regles[ probleme.regle ] || { titre: probleme.regle };
		const prio = priorite( regle );
		const lieu = probleme.element ? emplacement( probleme.element ) : 'Toute la page';
		const btn = h( 'button', { type: 'button', class: `aa-ligne ${ prio.classe }` }, [
			numero
				? h( 'span', { class: 'aa-num', texte: String( numero ) } )
				: h( 'span', { class: 'aa-num aa-num-page', 'aria-hidden': 'true' } ),
			h( 'span', { class: 'aa-ligne-texte' }, [
				h( 'span', { class: 'aa-ligne-titre', texte: regle.titre } ),
				h( 'span', { class: 'aa-ligne-meta' }, [
					h( 'span', { class: 'aa-sr', texte: ', ' } ),
					h( 'span', { class: 'aa-priorite', texte: prio.mot } ),
					h( 'span', { class: 'aa-sr', texte: ', ' } ),
					h( 'span', { class: 'aa-lieu', texte: lieu } ),
				] ),
			] ),
		] );
		btn.addEventListener( 'click', () => ouvrirFiche( probleme, btn ) );
		if ( probleme.element ) {
			const montrer = () => montrerContour( probleme );
			btn.addEventListener( 'mouseenter', montrer );
			btn.addEventListener( 'focus', montrer );
			btn.addEventListener( 'mouseleave', retablirContour );
			btn.addEventListener( 'blur', retablirContour );
		}
		return h( 'li', {}, [ btn ] );
	}

	function rendreResultats() {
		resultats.replaceChildren();
		resultats.hidden = false;
		if ( problemes.length === 0 ) {
			resultats.append(
				h( 'p', {
					class: 'aa-synthese aa-synthese-vide',
					texte: `Aucune erreur détectée parmi les ${ NB_CONTROLES } contrôles automatiques. D'autres points restent à vérifier à la main.`,
				} ),
				h( 'p', { class: 'aa-intro-limites', texte: 'Ce que l\'analyse ne vérifie pas :' } ),
				listeLimites()
			);
			return;
		}
		resultats.append( h( 'p', { class: 'aa-synthese', texte: synthese( problemes, regles ) } ) );
		const pages = problemes.filter( ( p ) => ! p.element );
		const elements = problemes.filter( ( p ) => p.element );
		if ( pages.length ) {
			resultats.append( h( 'h3', { class: 'aa-groupe', texte: 'Pour toute la page' } ),
				h( 'ul', { class: 'aa-liste' }, pages.map( ( p ) => ligne( p, null ) ) ) );
		}
		if ( elements.length ) {
			resultats.append( h( 'h3', { class: 'aa-groupe', texte: 'Dans la page, de haut en bas' } ),
				h( 'ul', { class: 'aa-liste' }, elements.map( ( p, i ) => ligne( p, i + 1 ) ) ) );
		}
	}

	/* ---------- Fiche ---------- */

	/**
	 * Guidage de la fiche selon l'emplacement de l'élément : liens vers l'écran où corriger,
	 * étapes propres à l'emplacement (null : celles de la règle), note quand aucun lien ne convient.
	 * @returns {{liens: {texte: string, url: string}[], etapes: string[]|null, note: string|null}}
	 */
	function guidage( probleme ) {
		const objet = donnees.objet || null;
		const produit = !! objet && objet.type === 'product';
		const lieu = probleme.element ? emplacement( probleme.element ) : null;
		const modifier = objet && objet.lienModifier
			? { texte: produit ? 'Modifier le produit' : 'Modifier cette page', url: objet.lienModifier }
			: null;
		const editeur = donnees.lienEditeurSite && donnees.lienEditeurSite.url ? donnees.lienEditeurSite : null;
		const liens = [];
		let etapes = null;
		let note = null;
		if ( ! lieu || lieu === 'Contenu de la page' ) {
			if ( modifier ) {
				liens.push( modifier );
			}
			return { liens, etapes, note };
		}
		const image = REGLES_IMAGE.includes( probleme.regle );
		if ( image ) {
			etapes = [ donnees.lienMediatheque ? ETAPE_MEDIATHEQUE : ETAPE_MEDIATHEQUE_SANS_DROIT ];
			if ( donnees.lienMediatheque ) {
				liens.push( { texte: 'Ouvrir la médiathèque', url: donnees.lienMediatheque } );
			}
		}
		if ( lieu === 'Images du produit' ) {
			if ( produit && modifier ) {
				liens.push( modifier );
			}
			return { liens, etapes, note };
		}
		if ( image && lieu === 'Liste de produits' ) {
			return { liens, etapes, note };
		}
		// En-tête, pied de page, gabarit, liste de produits et mini-panier viennent des gabarits du thème.
		if ( editeur ) {
			liens.push( { texte: editeur.texte || 'Ouvrir l\'éditeur du site', url: editeur.url } );
		} else if ( ! liens.length ) {
			note = NOTE_THEME;
		}
		return { liens, etapes, note };
	}

	function ouvrirFiche( probleme, origine ) {
		if ( panneau.hidden ) {
			ouvrir( { focus: false } );
		}
		const regle = regles[ probleme.regle ] || { titre: probleme.regle, corriger: [] };
		const prio = priorite( regle );
		const numero = numeroDe( probleme );
		masquerRetirees();
		const pastille = pastilles.find( ( x ) => x.probleme === probleme );
		const lieu = probleme.element ? emplacement( probleme.element ) : 'Toute la page';
		const description = decrire( probleme );
		const guide = guidage( probleme );
		const titre = h( 'h3', { class: 'aa-fiche-titre', tabindex: '-1', texte: regle.titre } );
		const zone = h( 'div', { class: 'aa-correction' } );

		fiche.replaceChildren( ...[
			h( 'button', { type: 'button', class: 'aa-retour', onclick: () => fermerFiche() },
				[ icone( ICONES.retour ), h( 'span', { texte: 'Retour à la liste' } ) ] ),
			h( 'p', { class: 'aa-fiche-meta' }, [
				h( 'span', { class: `aa-etiquette ${ prio.classe }`, texte: numero ? `${ numero } · ${ prio.mot }` : prio.mot } ),
				h( 'span', { class: 'aa-sr', texte: ', ' } ),
				h( 'span', { class: 'aa-lieu', texte: lieu } ),
			] ),
			titre,
			h( 'p', { class: 'aa-explication', texte: regle.explication || '' } ),
			description ? h( 'p', { class: 'aa-repere', texte: description } ) : null,
			probleme.element && ! probleme.element.isConnected
				? h( 'p', { class: 'aa-note aa-note-retire', texte: 'Cet élément n\'est plus dans la page (il a pu être retiré ou rechargé) : relancez l\'analyse pour mettre la liste à jour.' } )
				: null,
			pastille && pastille.deplace && probleme.element && probleme.element.isConnected
				? h( 'p', { class: 'aa-note', texte: 'Cet élément n\'est pas visible à l\'écran : l\'indicateur est posé sur la zone qui le contient.' } )
				: null,
			h( 'h4', { texte: 'Qui ça gêne' } ),
			h( 'p', { texte: regle.qui_gene || '' } ),
			h( 'h4', { texte: 'Comment corriger' } ),
			h( 'ol', { class: 'aa-etapes' }, ( guide.etapes || regle.corriger || [] ).map( ( etape ) => h( 'li', { texte: etape } ) ) ),
			h( 'h4', { texte: 'Le bon réflexe' } ),
			h( 'p', { class: 'aa-reflexe', texte: regle.reflexe || '' } ),
			h( 'p', { class: 'aa-critere', texte: `Critère RGAA ${ regle.critere }` } ),
			zone,
			guide.liens.length
				? h( 'p', { class: 'aa-guidage' }, guide.liens.map( ( l ) => h( 'a', { href: l.url, texte: l.texte } ) ) )
				: null,
			guide.note ? h( 'p', { class: 'aa-note aa-note-theme', texte: guide.note } ) : null,
		].filter( Boolean ) );
		if ( typeof options.zoneCorrection === 'function' ) {
			options.zoneCorrection( probleme, zone );
		}

		vueListe.hidden = true;
		fiche.hidden = false;
		ficheCourante = probleme;
		origineFiche = origine || null;
		corps.scrollTop = 0;
		pastilles.forEach( ( p ) => p.bouton.classList.toggle( 'aa-choisie', p.probleme === probleme ) );
		montrerContour( probleme );
		amenerALaVue( probleme );
		placerPanneau( probleme );
		titre.focus( { preventScroll: true } );
	}

	function fermerFiche( { focus = true } = {} ) {
		fiche.hidden = true;
		fiche.replaceChildren();
		vueListe.hidden = false;
		ficheCourante = null;
		conteneur.classList.remove( 'aa-droite' );
		pastilles.forEach( ( p ) => p.bouton.classList.remove( 'aa-choisie' ) );
		retablirContour();
		const origine = origineFiche;
		origineFiche = null;
		if ( focus ) {
			( origine && origine.isConnected ? origine : boutonAnalyser ).focus();
		}
	}

	/* ---------- Pastilles ---------- */

	function creerPastilles() {
		supprimerPastilles();
		problemes.filter( ( p ) => p.element ).forEach( ( probleme, i ) => {
			const regle = regles[ probleme.regle ] || { titre: probleme.regle };
			const prio = priorite( regle );
			const numero = i + 1;
			const btn = h( 'button', {
				type: 'button',
				class: `aa-pastille ${ prio.classe }`,
				'aria-label': `Problème ${ numero }, ${ prio.minuscule } : ${ minusculeInitiale( regle.titre ) }`,
				texte: String( numero ),
			} );
			btn.addEventListener( 'click', () => ouvrirFiche( probleme, btn ) );
			btn.addEventListener( 'mouseenter', () => montrerContour( probleme ) );
			const entree = { probleme, numero, bouton: btn, cible: probleme.element, deplace: false };
			btn.addEventListener( 'focus', () => {
				montrerContour( probleme );
				garderHorsDuPanneau( entree );
			} );
			btn.addEventListener( 'mouseleave', retablirContour );
			btn.addEventListener( 'blur', retablirContour );
			calque.append( btn );
			pastilles.push( entree );
		} );
		pied.hidden = pastilles.length === 0;
		boutonMasquer.hidden = pastilles.length === 0;
		boutonMasquer.textContent = 'Masquer les indicateurs';
		placer();
		surveiller();
	}

	function supprimerPastilles() {
		pastilles.forEach( ( p ) => p.bouton.remove() );
		pastilles = [];
		contour.hidden = true;
	}

	function masquer() {
		supprimerPastilles();
		pastillesMasquees = true;
		if ( problemes.some( ( p ) => p.element ) ) {
			boutonMasquer.textContent = 'Afficher les indicateurs';
		}
	}

	function basculerPastilles() {
		if ( pastillesMasquees ) {
			creerPastilles();
			pastillesMasquees = false;
			annoncer( 'Indicateurs affichés.' );
		} else {
			masquer();
			annoncer( 'Indicateurs masqués.' );
		}
	}

	function rect( el ) {
		return el && el.getBoundingClientRect ? el.getBoundingClientRect() : null;
	}

	function sansMesure( r ) {
		return ! r || ( r.width === 0 && r.height === 0 && r.top === 0 && r.left === 0 );
	}

	/** L'élément lui-même s'il se voit, sinon son plus proche parent visible. */
	function cibleVisible( el ) {
		let courant = el;
		while ( courant && courant !== doc.body && courant.nodeType === 1 ) {
			const r = courant.getBoundingClientRect();
			const style = vue && vue.getComputedStyle ? vue.getComputedStyle( courant ) : null;
			const invisible = r.width <= 1 || r.height <= 1
				|| ( style && ( style.visibility === 'hidden' || style.opacity === '0' ) );
			if ( ! invisible ) {
				return { cible: courant, deplace: courant !== el };
			}
			courant = courant.parentElement;
		}
		return { cible: courant || el, deplace: true };
	}

	/** Vrai si l'élément ou un de ses ancêtres est en position fixe ou collante (il bouge avec le défilement). */
	function dansFixe( el, cache ) {
		if ( ! vue || ! vue.getComputedStyle ) {
			return false;
		}
		const parcourus = [];
		let courant = el;
		let resultat = false;
		while ( courant && courant.nodeType === 1 && courant !== doc.body ) {
			if ( cache.has( courant ) ) {
				resultat = cache.get( courant );
				break;
			}
			parcourus.push( courant );
			const position = vue.getComputedStyle( courant ).position;
			if ( position === 'fixed' || position === 'sticky' ) {
				resultat = true;
				break;
			}
			courant = courant.parentElement;
		}
		parcourus.forEach( ( el2 ) => cache.set( el2, resultat ) );
		return resultat;
	}

	/** Masque la pastille d'un élément retiré de la page depuis l'analyse. */
	function masquerRetirees() {
		for ( const p of pastilles ) {
			p.retire = ! p.probleme.element || ! p.probleme.element.isConnected;
			p.bouton.hidden = p.retire;
		}
	}

	function placer() {
		masquerRetirees();
		const origine = rect( calque );
		if ( sansMesure( origine ) && sansMesure( rect( doc.documentElement ) ) ) {
			return; // Pas de mise en page (jsdom).
		}
		ajusterDecalages();
		// Bas de la barre d'administration, en coordonnées du calque (haut du document).
		const limiteHaut = Math.max( 0, hauteurBarreAdmin() - ( origine.top + ( vue.scrollY || 0 ) ) );

		// 1. Lectures (toutes les mesures d'abord, pour ne pas forcer une mise en page par pastille).
		const cacheFixe = new Map();
		const mesures = pastilles.map( ( p ) => {
			const el = p.probleme.element;
			if ( ! el || ! el.isConnected ) {
				return { retire: true };
			}
			const { cible, deplace } = cibleVisible( el );
			return { cible, deplace, r: cible.getBoundingClientRect(), fixe: dansFixe( cible, cacheFixe ) };
		} );

		// 2. Calculs, puis écritures.
		const places = [];
		pastilles.forEach( ( p, i ) => {
			const m = mesures[ i ];
			p.retire = !! m.retire;
			p.bouton.hidden = p.retire;
			if ( p.retire ) {
				p.fixe = false;
				return;
			}
			p.cible = m.cible;
			p.deplace = m.deplace;
			p.fixe = m.fixe;
			let x = Math.max( 0, m.r.left - origine.left );
			// Juste au-dessus du coin haut gauche, pour ne rien cacher de l'élément ;
			// dans le coin s'il n'y a pas la place (haut de page, barre d'administration).
			let y = m.r.top - origine.top - TAILLE_PASTILLE - 2;
			const dedans = y < limiteHaut;
			if ( dedans ) {
				y = Math.max( 0, m.r.top - origine.top );
			}
			let decale = false;
			while ( places.some( ( q ) => Math.abs( q.x - x ) < TAILLE_PASTILLE && Math.abs( q.y - y ) < TAILLE_PASTILLE ) ) {
				x += DECALAGE_PASTILLE;
				decale = true;
			}
			places.push( { x, y } );
			p.bouton.style.left = `${ x }px`;
			p.bouton.style.top = `${ y }px`;
			p.bouton.toggleAttribute( 'data-decalee', decale );
			p.bouton.toggleAttribute( 'data-dans-le-coin', dedans );
		} );
		if ( ! contour.hidden ) {
			const p = pastilles[ Number( contour.dataset.cible ) ];
			if ( p ) {
				poserContour( p );
			}
		}
	}

	function poserContour( p ) {
		if ( ! p.probleme.element || ! p.probleme.element.isConnected ) {
			contour.hidden = true;
			return;
		}
		const origine = rect( calque );
		const r = rect( p.cible );
		if ( sansMesure( r ) ) {
			return;
		}
		contour.style.left = `${ r.left - origine.left - 6 }px`;
		contour.style.top = `${ r.top - origine.top - 6 }px`;
		contour.style.width = `${ r.width + 12 }px`;
		contour.style.height = `${ r.height + 12 }px`;
	}

	function montrerContour( probleme ) {
		const index = pastilles.findIndex( ( x ) => x.probleme === probleme );
		if ( index === -1 ) {
			return;
		}
		const p = pastilles[ index ];
		contour.className = `aa-contour ${ p.bouton.classList.contains( 'aa-important' ) ? 'aa-important' : 'aa-a-revoir' }`;
		contour.dataset.cible = String( index );
		contour.hidden = false;
		poserContour( p );
	}

	function retablirContour() {
		if ( ficheCourante && pastilles.some( ( p ) => p.probleme === ficheCourante ) ) {
			montrerContour( ficheCourante );
		} else {
			contour.hidden = true;
		}
	}

	function amenerALaVue( probleme ) {
		const p = pastilles.find( ( x ) => x.probleme === probleme );
		if ( ! p || ! vue || typeof vue.scrollTo !== 'function' ) {
			return;
		}
		const r = rect( p.cible );
		if ( sansMesure( r ) ) {
			return;
		}
		const mobile = vue.innerWidth < SEUIL_MOBILE;
		const haut = hauteurBarreAdmin();
		// Sur mobile, le panneau occupe le bas de l'écran : l'élément va dans la partie haute.
		const basVisible = mobile ? vue.innerHeight * 0.4 : vue.innerHeight - 8;
		// La pastille est posée au-dessus de l'élément : sa hauteur et une marge entrent dans le calcul.
		const hautPastille = haut + TAILLE_PASTILLE + 12;
		if ( r.top >= hautPastille && r.bottom <= basVisible ) {
			return;
		}
		const marge = mobile ? hautPastille : Math.max( hautPastille, ( vue.innerHeight - r.height ) / 2 );
		const reduit = vue.matchMedia && vue.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		vue.scrollTo( { top: Math.max( 0, vue.scrollY + r.top - marge ), behavior: reduit ? 'auto' : 'smooth' } );
	}

	/**
	 * Sur grand écran, le panneau passe à droite si, à gauche, il recouvrirait l'élément choisi
	 * alors que celui-ci tient dans la partie restée libre à gauche.
	 */
	function placerPanneau( probleme ) {
		const p = pastilles.find( ( x ) => x.probleme === probleme );
		const r = p ? rect( p.cible ) : null;
		const largeur = panneau.getBoundingClientRect ? panneau.getBoundingClientRect().width : 0;
		let droite = false;
		if ( vue && ! sansMesure( r ) && largeur && vue.innerWidth >= SEUIL_MOBILE ) {
			droite = r.left < largeur + 8 && r.right <= vue.innerWidth - largeur - 8;
		}
		conteneur.classList.toggle( 'aa-droite', droite );
	}

	function chevauche( a, b ) {
		return a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top;
	}

	/**
	 * Une pastille qui reçoit le focus ne doit jamais rester cachée sous le panneau (RGAA 10.7) :
	 * sur grand écran, le panneau change de côté ; sur mobile, la page défile pour amener la pastille
	 * au-dessus du panneau. Mesuré après le défilement que fait le navigateur au focus.
	 */
	function garderHorsDuPanneau( p ) {
		if ( ! vue ) {
			return;
		}
		const verifier = () => {
			if ( panneau.hidden || ! p.bouton.isConnected || p.bouton.hidden || p.bouton.getRootNode().activeElement !== p.bouton ) {
				return;
			}
			const b = p.bouton.getBoundingClientRect();
			if ( sansMesure( b ) || ! chevauche( b, panneau.getBoundingClientRect() ) ) {
				return;
			}
			if ( vue.innerWidth >= SEUIL_MOBILE ) {
				conteneur.classList.toggle( 'aa-droite' );
				if ( chevauche( b, panneau.getBoundingClientRect() ) ) {
					conteneur.classList.toggle( 'aa-droite' ); // Aucun côté ne la libère : le panneau reste où il était.
				}
				return;
			}
			// Sur mobile, le panneau occupe le bas de l'écran : la pastille va en haut, sous la barre d'administration.
			if ( typeof vue.scrollTo !== 'function' ) {
				return;
			}
			const voulu = Math.max( 0, vue.scrollY + b.top - ( hauteurBarreAdmin() + 12 ) );
			const racineDoc = doc.documentElement;
			if ( voulu > racineDoc.scrollHeight - vue.innerHeight ) {
				// Fin de page : pas assez de place pour défiler, on en ajoute la hauteur du panneau.
				espace.hidden = true;
				const finDePage = racineDoc.scrollHeight - ( rect( calque ).top + vue.scrollY );
				espace.style.top = `${ finDePage }px`;
				espace.style.height = `${ Math.ceil( panneau.getBoundingClientRect().height ) }px`;
				espace.hidden = false;
			}
			vue.scrollTo( { top: voulu, behavior: 'auto' } );
		};
		if ( typeof vue.requestAnimationFrame === 'function' ) {
			vue.requestAnimationFrame( verifier );
		} else {
			verifier();
		}
	}

	/* ---------- Décalages (barre d'administration, bandeau de démonstration WooCommerce) ---------- */

	function hauteurBarreAdmin() {
		const barre = doc.getElementById( 'wpadminbar' );
		if ( ! barre || ! vue || ! vue.getComputedStyle ) {
			return 0;
		}
		const style = vue.getComputedStyle( barre );
		if ( style.display === 'none' || style.position !== 'fixed' ) {
			return 0;
		}
		return Math.max( 0, barre.getBoundingClientRect().bottom );
	}

	function ajusterDecalages() {
		if ( ! vue || ! vue.getComputedStyle ) {
			return;
		}
		let bas = 0;
		const hauteur = vue.innerHeight;
		// Tout bandeau visible qui touche le bas de l'écran, quelle que soit sa position (fixed, absolute…).
		for ( const bandeau of doc.querySelectorAll( '.woocommerce-store-notice, p.demo_store' ) ) {
			const style = vue.getComputedStyle( bandeau );
			if ( style.display === 'none' || style.visibility === 'hidden' ) {
				continue;
			}
			const r = bandeau.getBoundingClientRect();
			if ( r.height > 0 && r.width > 0 && r.top < hauteur && r.bottom > hauteur - 80 ) {
				bas = Math.max( bas, hauteur - r.top );
			}
		}
		hote.style.setProperty( '--aa-bas', `${ bas }px` );
		hote.style.setProperty( '--aa-haut', `${ hauteurBarreAdmin() }px` );
	}

	/* ---------- Recalcul ---------- */

	let enAttente = false;
	function planifier() {
		if ( enAttente ) {
			return;
		}
		enAttente = true;
		const suite = () => {
			enAttente = false;
			placer();
		};
		if ( vue && vue.requestAnimationFrame ) {
			vue.requestAnimationFrame( suite );
		} else if ( vue && vue.setTimeout ) {
			vue.setTimeout( suite, 16 );
		} else {
			suite();
		}
	}

	let surveille = false;
	function surveiller() {
		if ( surveille || ! vue ) {
			return;
		}
		surveille = true;
		vue.addEventListener( 'resize', planifier );
		nettoyages.push( () => vue.removeEventListener( 'resize', planifier ) );
		for ( const image of Array.from( doc.images ) ) {
			image.addEventListener( 'load', planifier );
			nettoyages.push( () => image.removeEventListener( 'load', planifier ) );
		}
		if ( typeof vue.ResizeObserver === 'function' ) {
			const observateur = new vue.ResizeObserver( planifier );
			observateur.observe( doc.body );
			nettoyages.push( () => observateur.disconnect() );
		}
	}

	// Défilement : le bandeau WooCommerce peut s'éloigner du bas, et les éléments fixes ou collants bougent.
	let defilementEnAttente = false;
	function surDefilement() {
		if ( defilementEnAttente ) {
			return;
		}
		defilementEnAttente = true;
		const suite = () => {
			defilementEnAttente = false;
			if ( pastilles.some( ( p ) => p.fixe ) ) {
				placer(); // appelle aussi ajusterDecalages()
			} else {
				ajusterDecalages();
			}
		};
		if ( vue.requestAnimationFrame ) {
			vue.requestAnimationFrame( suite );
		} else {
			vue.setTimeout( suite, 16 );
		}
	}

	if ( vue ) {
		const retirerEspace = () => { espace.hidden = true; };
		vue.addEventListener( 'resize', retirerEspace );
		nettoyages.push( () => vue.removeEventListener( 'resize', retirerEspace ) );
		vue.addEventListener( 'resize', ajusterDecalages );
		vue.addEventListener( 'scroll', surDefilement, { passive: true } );
		nettoyages.push( () => vue.removeEventListener( 'resize', ajusterDecalages ) );
		nettoyages.push( () => vue.removeEventListener( 'scroll', surDefilement, { passive: true } ) );
	}
	ajusterDecalages();
	if ( vue ) {
		// Le bandeau WooCommerce peut s'afficher après le montage (script de la boutique, styles tardifs).
		vue.addEventListener( 'load', ajusterDecalages );
		nettoyages.push( () => vue.removeEventListener( 'load', ajusterDecalages ) );
		const bandeaux = doc.querySelectorAll( '.woocommerce-store-notice, p.demo_store' );
		if ( bandeaux.length && typeof vue.MutationObserver === 'function' ) {
			const observateur = new vue.MutationObserver( () => surDefilement() );
			bandeaux.forEach( ( b ) => observateur.observe( b, { attributes: true, attributeFilter: [ 'style', 'class', 'hidden' ] } ) );
			nettoyages.push( () => observateur.disconnect() );
		}
	}

	// Panneau laissé ouvert lors de la visite précédente : il se rouvre, sans lancer d'analyse.
	if ( lireStockage( local, CLE_FERME ) === '0' ) {
		ouvrir( { focus: false } );
	}

	function detruire() {
		nettoyages.splice( 0 ).forEach( ( f ) => f() );
		hote.remove();
	}

	return {
		ouvrir: () => ouvrir(),
		fermer,
		lancer,
		masquer,
		detruire,
		ouvrirFiche: ( probleme ) => ouvrirFiche( probleme, null ),
		racine,
	};
}
