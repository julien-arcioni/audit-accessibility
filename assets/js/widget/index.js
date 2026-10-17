import { monterWidget, relancerSiDemande } from './interface.js';

const element = document.getElementById( 'wp-script-module-data-audit-accessibilite-widget' );
let donnees = null;
try {
	donnees = element ? JSON.parse( element.textContent ) : null;
} catch ( e ) {
	donnees = null;
}

if ( donnees ) {
	const widget = monterWidget( document, donnees );
	let session = null;
	try {
		session = window.sessionStorage;
	} catch ( e ) {
		session = null;
	}
	// Après une correction, l'analyse se relance d'elle-même (la clé est supprimée).
	relancerSiDemande( widget, session );
}
