/**
 * Emplacement d'un élément dans la page (spec 4.6), déduit de ses ancêtres.
 * Les règles sont testées dans l'ordre : la première qui correspond l'emporte.
 */
const REGLES = [
	[ '.wp-block-post-content, .entry-content', 'Contenu de la page' ],
	[ 'header.wp-block-template-part', 'En-tête' ],
	[ 'footer.wp-block-template-part', 'Pied de page' ],
	[ '.products, .wc-block-product-template', 'Liste de produits' ],
	[ '.woocommerce-product-gallery', 'Images du produit' ],
	[ '.wc-block-mini-cart', 'Mini-panier' ],
];

export const GABARIT = 'Gabarit du thème';

/**
 * @param {Element} element
 * @returns {'Contenu de la page'|'En-tête'|'Pied de page'|'Liste de produits'|'Images du produit'|'Mini-panier'|'Gabarit du thème'}
 */
export function emplacement( element ) {
	if ( ! element || ! element.closest ) {
		return GABARIT;
	}
	for ( const [ selecteur, nom ] of REGLES ) {
		if ( element.closest( selecteur ) ) {
			return nom;
		}
	}
	return GABARIT;
}
