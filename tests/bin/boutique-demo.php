<?php
// Boutique « Grain d'Ardoise » : styles, en-tête, pied de page, menu, pages et articles.
// wp eval-file boutique-2-site.php avant   -> place les défauts que l'audit doit trouver
// wp eval-file boutique-2-site.php apres   -> version corrigée
$mode = isset($args[0]) ? $args[0] : 'apres';
if (!in_array($mode, array('avant', 'apres'), true)) {
    WP_CLI::error('Mode attendu : avant ou apres');
}
$avant = $mode === 'avant';
$m = get_option('grain_ardoise_medias');
$url = function ($nom, $taille = 'large') use ($m) {
    return wp_get_attachment_image_url($m[$nom], $taille);
};
$alt = function ($nom) use ($m) {
    return esc_attr(get_post_meta($m[$nom], '_wp_attachment_image_alt', true));
};
$home = home_url('/');
$lien_cat = function ($slug) {
    return get_term_link($slug, 'product_cat');
};

// ------------------------------------------------------------------
// Pages : création ou mise à jour par slug
// ------------------------------------------------------------------
function ga_page($slug, $titre, $contenu, $type = 'page', $extra = array()) {
    $ids = get_posts(array('post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids'));
    $donnees = array_merge(array('post_type' => $type, 'post_name' => $slug, 'post_title' => $titre, 'post_status' => 'publish', 'post_content' => $contenu), $extra);
    if ($ids) {
        $donnees['ID'] = $ids[0];
    }
    $id = wp_insert_post(wp_slash($donnees), true);
    if (is_wp_error($id)) {
        WP_CLI::error($slug . ' : ' . $id->get_error_message());
    }
    return $id;
}

function ga_gabarit($type, $slug, $titre, $contenu, $zone = '') {
    $ids = get_posts(array('post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
        'tax_query' => array(array('taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => 'twentytwentyfive'))));
    $donnees = array('post_type' => $type, 'post_name' => $slug, 'post_title' => $titre, 'post_status' => 'publish', 'post_content' => $contenu);
    if ($ids) {
        $donnees['ID'] = $ids[0];
    }
    $id = wp_insert_post(wp_slash($donnees));
    wp_set_object_terms($id, 'twentytwentyfive', 'wp_theme');
    if ($zone) {
        wp_set_object_terms($id, $zone, 'wp_template_part_area');
    }
    return $id;
}

// ------------------------------------------------------------------
// Styles globaux : palette, typographie, boutons, CSS du formulaire
// ------------------------------------------------------------------
$theme_dir = get_template_directory();
$typo = json_decode(file_get_contents($theme_dir . '/styles/typography/typography-preset-7.json'), true);
$styles = array(
    'version' => 3,
    'isGlobalStylesUserThemeJSON' => true,
    'settings' => array(
        'color' => array('palette' => array('theme' => array(
            array('slug' => 'base', 'name' => 'Crème', 'color' => '#F5F0E8'),
            array('slug' => 'contrast', 'name' => 'Encre', 'color' => '#231E1A'),
            array('slug' => 'accent-1', 'name' => 'Ardoise', 'color' => '#3B4650'),
            array('slug' => 'accent-2', 'name' => 'Sable', 'color' => '#E7DCCB'),
            array('slug' => 'accent-3', 'name' => 'Torréfié', 'color' => '#8A4B20'),
            array('slug' => 'accent-4', 'name' => 'Gris chaud', 'color' => '#5F5A55'),
            array('slug' => 'accent-5', 'name' => 'Lin', 'color' => '#EDE5D8'),
            array('slug' => 'accent-6', 'name' => 'Filet', 'color' => 'color-mix(in srgb, currentColor 20%, transparent)'),
        ))),
        'typography' => array('fontFamilies' => array('theme' => $typo['settings']['typography']['fontFamilies'])),
    ),
    'styles' => array(
        'typography' => array('fontFamily' => 'var:preset|font-family|fira-sans', 'letterSpacing' => '0'),
        'elements' => array(
            'heading' => array('typography' => array('fontFamily' => 'var:preset|font-family|literata', 'fontWeight' => '500', 'letterSpacing' => '-0.01em')),
            'button' => array(
                'color' => array('background' => 'var:preset|color|accent-1', 'text' => 'var:preset|color|base'),
                'border' => array('radius' => '999px'),
                ':hover' => array('color' => array('background' => 'var:preset|color|contrast', 'text' => 'var:preset|color|base')),
            ),
            'link' => array('color' => array('text' => 'var:preset|color|contrast'), ':hover' => array('color' => array('text' => 'var:preset|color|accent-3'))),
        ),
        'blocks' => array(
            'core/site-title' => array('typography' => array('fontFamily' => 'var:preset|font-family|literata', 'fontWeight' => '600', 'fontSize' => 'var:preset|font-size|large')),
        ),
        'css' => 'body .woocommerce-store-notice, body p.demo_store { background-color: #231E1A; font-size: 0.9rem; }
.woocommerce-store-notice a { color: inherit; }
.ga-surtitre { text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.8rem; font-weight: 600; }
.ga-lettre { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: end; }
.ga-lettre label { flex-basis: 100%; }
.ga-lettre input { flex: 1 1 14rem; padding: 0.8rem 1rem; border: 1px solid #D9C9B0; border-radius: 999px; background: #F5F0E8; color: #231E1A; font: inherit; }
.ga-lettre button { padding: 0.8rem 1.4rem; border: 0; border-radius: 999px; background: #F5F0E8; color: #3B4650; font: inherit; font-weight: 600; cursor: pointer; }
.ga-lettre button:hover { background: #E7DCCB; }
.ga-icone { display: inline-flex; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; border: 1px solid #D9C9B0; border-radius: 50%; color: inherit; }
.ga-carte img { aspect-ratio: 4 / 3; object-fit: cover; }
.wp-block-table th, .wp-block-table td { padding: 0.6rem 0.8rem; }
.ga-produits ul.products li.product .button { white-space: nowrap; }
.ga-produits ul.products li.product img { aspect-ratio: 1; object-fit: cover; }
.wp-block-latest-posts__featured-image img { aspect-ratio: 16 / 10; object-fit: cover; width: 100%; }
.wp-block-latest-posts__post-title { font-family: var(--wp--preset--font-family--literata); font-size: var(--wp--preset--font-size--large); text-decoration: none; }',
    ),
);
$gs = get_posts(array('post_type' => 'wp_global_styles', 'name' => 'wp-global-styles-twentytwentyfive', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids'));
wp_update_post(wp_slash(array('ID' => $gs[0], 'post_content' => wp_json_encode($styles))));

// ------------------------------------------------------------------
// Menu principal
// ------------------------------------------------------------------
$lien = function ($texte, $href) {
    return '<!-- wp:navigation-link {"label":"' . $texte . '","url":"' . esc_url($href) . '","kind":"custom"} /-->';
};
$menu = $lien('Boutique', wc_get_page_permalink('shop'))
    . $lien('Cafés', $lien_cat('cafes'))
    . $lien('Accessoires', $lien_cat('accessoires'))
    . $lien("L'atelier", home_url('/l-atelier/'))
    . $lien('Journal', home_url('/journal/'))
    . $lien('Contact', home_url('/contact/'));
wp_update_post(wp_slash(array('ID' => 10, 'post_title' => 'Menu principal', 'post_content' => $menu)));

// ------------------------------------------------------------------
// En-tête
// ------------------------------------------------------------------
$entete = <<<HTML
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"var:preset|color|accent-2","width":"1px"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-background-color has-background" style="border-bottom-color:var(--wp--preset--color--accent-2);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:site-title {"level":0} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:navigation {"ref":10,"overlayMenu":"mobile","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","justifyContent":"right"}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
HTML;
ga_gabarit('wp_template_part', 'header', 'En-tête', $entete, 'header');

// ------------------------------------------------------------------
// Pied de page
// ------------------------------------------------------------------
$icone_mail = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>';
$lien_mail = $avant
    ? '<a class="ga-icone" href="mailto:bonjour@graindardoise.fr">' . $icone_mail . '</a>'
    : '<a class="ga-icone" href="mailto:bonjour@graindardoise.fr" aria-label="Nous écrire : bonjour@graindardoise.fr">' . $icone_mail . '</a>';
$lettre = $avant
    ? '<form class="ga-lettre" action="#" method="post"><input type="email" name="email" placeholder="Votre adresse e-mail"><button type="submit">S\'inscrire</button></form>'
    : '<form class="ga-lettre" action="#" method="post"><label for="ga-lettre-email">Votre adresse e-mail</label><input id="ga-lettre-email" type="email" name="email" autocomplete="email"><button type="submit">S\'inscrire</button></form>';
$liens_pied = function ($liens) {
    $html = '';
    foreach ($liens as $texte => $href) {
        $html .= '<li><a href="' . esc_url($href) . '">' . $texte . '</a></li>';
    }
    return '<!-- wp:list {"className":"is-style-default","style":{"spacing":{"padding":{"left":"0"}},"typography":{"lineHeight":"2"}}} --><ul style="padding-left:0;line-height:2;list-style:none" class="wp-block-list is-style-default">' . $html . '</ul><!-- /wp:list -->';
};
$colonne_boutique = $liens_pied(array('Tous nos cafés' => $lien_cat('cafes'), 'Accessoires' => $lien_cat('accessoires'), 'Coffrets' => $lien_cat('coffrets'), 'Mon compte' => wc_get_page_permalink('myaccount')));
$colonne_maison = $liens_pied(array('L\'atelier' => home_url('/l-atelier/'), 'Journal' => home_url('/journal/'), 'Livraison et retours' => home_url('/livraison-et-retours/'), 'Mentions légales' => home_url('/mentions-legales/')));
$annee = date('Y');
$pied = <<<HTML
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"backgroundColor":"accent-1","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-color has-accent-1-background-color has-text-color has-background has-link-color" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%"><!-- wp:site-title {"level":0} /-->

<!-- wp:paragraph -->
<p>Torréfaction artisanale de cafés de spécialité, en petites quantités, chaque semaine.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<p>{$lien_mail}</p>
<!-- /wp:html --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"18%"} -->
<div class="wp-block-column" style="flex-basis:18%"><!-- wp:heading {"level":2,"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size">La boutique</h2>
<!-- /wp:heading -->

{$colonne_boutique}</div>
<!-- /wp:column -->

<!-- wp:column {"width":"18%"} -->
<div class="wp-block-column" style="flex-basis:18%"><!-- wp:heading {"level":2,"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size">La maison</h2>
<!-- /wp:heading -->

{$colonne_maison}</div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"><!-- wp:heading {"level":2,"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size">La lettre du torréfacteur</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Un e-mail par mois : les nouveaux lots, avant tout le monde.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
{$lettre}
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:separator {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|30"}}},"backgroundColor":"accent-6"} -->
<hr class="wp-block-separator alignwide has-text-color has-accent-6-color has-alpha-channel-opacity has-accent-6-background-color has-background" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--30)"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"align":"wide","fontSize":"small"} -->
<p class="alignwide has-small-font-size">© {$annee} Grain d'Ardoise. Boutique fictive, réalisée pour une démonstration : aucune commande n'est traitée. Photos : Unsplash.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML;
ga_gabarit('wp_template_part', 'footer', 'Pied de page', $pied, 'footer');

// ------------------------------------------------------------------
// Accueil
// ------------------------------------------------------------------
$niveau_atouts = $avant ? 4 : 3;
$carte = function ($image, $titre, $texte, $href, $intitule) use ($url, $alt, $avant) {
    $libelle = $avant ? 'En savoir plus' : $intitule;
    return '<!-- wp:column {"className":"ga-carte"} -->
<div class="wp-block-column ga-carte"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="' . $url($image) . '" alt="' . $alt($image) . '"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">' . $titre . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . $texte . '</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="' . esc_url($href) . '">' . $libelle . ' →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->';
};
$atout = function ($titre, $texte) use ($niveau_atouts) {
    return '<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":' . $niveau_atouts . ',"fontSize":"large"} -->
<h' . $niveau_atouts . ' class="wp-block-heading has-large-font-size">' . $titre . '</h' . $niveau_atouts . '>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . $texte . '</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->';
};
$cartes = $carte('origine-recolte', 'Pures origines', 'Un pays, une région, souvent un seul domaine : des cafés qui racontent leur terroir.', $lien_cat('pures-origines'), 'Voir les pures origines')
    . "\n\n" . $carte('assemblage-ardoise', 'Assemblages', 'Plusieurs origines réunies pour un espresso rond, qui supporte aussi le lait.', $lien_cat('assemblages'), 'Voir les assemblages')
    . "\n\n" . $carte('cafetiere-filtre', 'Accessoires', 'Moulin, cafetière, filtres : le matériel pour réussir son café à la maison.', $lien_cat('accessoires'), 'Voir les accessoires');
$atouts = $atout('Torréfié chaque semaine', 'Nous torréfions en petites quantités, le mardi, et expédions dans la foulée. Votre café arrive au meilleur de sa fraîcheur.')
    . "\n\n" . $atout('Des origines tracées', 'Chaque café indique son pays, sa région, son altitude et son procédé. Nous achetons auprès d\'importateurs qui connaissent les producteurs.')
    . "\n\n" . $atout('La mouture qui vous va', 'En grains ou moulu pour votre méthode : filtre, espresso ou piston. Nous moulons à la commande.');
$hero = $url('accueil-torrefacteur', 'full');
$cafes_url = esc_url($lien_cat('cafes'));
$atelier_url = esc_url(home_url('/l-atelier/'));
$accueil = <<<HTML
<!-- wp:cover {"url":"{$hero}","dimRatio":60,"overlayColor":"contrast","minHeight":620,"contentPosition":"center left","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-center-left has-base-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50);min-height:620px"><img class="wp-block-cover__image-background" alt="" src="{$hero}" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-60 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"640px","justifyContent":"left"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"className":"ga-surtitre"} -->
<p class="ga-surtitre">Torréfaction artisanale</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"xx-large"} -->
<h1 class="wp-block-heading has-xx-large-font-size">Le café, torréfié chaque semaine.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Des cafés de spécialité choisis pour leur terroir, torréfiés en petites quantités et expédiés en 48 heures.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"base","textColor":"accent-1"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-accent-1-color has-base-background-color has-text-color has-background wp-element-button" href="{$cafes_url}">Découvrir nos cafés</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{$atelier_url}">Notre atelier</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading -->
<h2 class="wp-block-heading">Nos cafés du moment</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="{$cafes_url}">Voir tous nos cafés →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"ga-produits","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide ga-produits" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:shortcode -->
[products limit="4" columns="4" visibility="featured" orderby="menu_order"]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"accent-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-accent-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Pourquoi Grain d'Ardoise</h2>
<!-- /wp:heading -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--50)">{$atouts}</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Trouver son café</h2>
<!-- /wp:heading -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--50)">{$cartes}</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"accent-2","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-accent-2-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide">Le journal</h2>
<!-- /wp:heading -->

<!-- wp:latest-posts {"postsToShow":2,"displayPostContent":true,"excerptLength":24,"displayFeaturedImage":true,"featuredImageSizeSlug":"medium_large","postLayout":"grid","columns":2,"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} /--></div>
<!-- /wp:group -->
HTML;
$id_accueil = ga_page('accueil', 'Accueil', $accueil, 'page', array('ID' => 7, 'page_template' => 'page-no-title'));
update_option('show_on_front', 'page');
update_option('page_on_front', $id_accueil);

// ------------------------------------------------------------------
// L'atelier
// ------------------------------------------------------------------
$photo_atelier = $avant
    ? '<!-- wp:html -->
<figure class="wp-block-image size-large"><img src="' . $url('atelier') . '" width="1024" height="683"></figure>
<!-- /wp:html -->'
    : '<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="' . $url('atelier') . '" alt="' . $alt('atelier') . '"/></figure>
<!-- /wp:image -->';
$recolte = $url('origine-recolte');
$alt_recolte = $alt('origine-recolte');
$atelier = <<<HTML
<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Grain d'Ardoise est né d'une idée simple : un café frais se goûte. Dans notre atelier, un torréfacteur à tambour de quinze kilos tourne deux jours par semaine.</p>
<!-- /wp:paragraph -->

{$photo_atelier}

<!-- wp:heading -->
<h2 class="wp-block-heading">Notre façon de torréfier</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Chaque café a son profil de torréfaction : plus court et plus clair pour les cafés floraux d'Éthiopie ou du Kenya, plus long pour les cafés destinés à l'espresso. Nous goûtons chaque lot avant de le mettre en vente.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Après la torréfaction, les grains reposent quarante-huit heures avant d'être ensachés : c'est le temps nécessaire pour que le café libère son gaz carbonique et révèle ses arômes.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Des origines que l'on connaît</h2>
<!-- /wp:heading -->

<!-- wp:media-text {"mediaPosition":"right","mediaType":"image","verticalAlignment":"center"} -->
<div class="wp-block-media-text has-media-on-the-right is-stacked-on-mobile is-vertically-aligned-center"><div class="wp-block-media-text__content"><!-- wp:paragraph -->
<p>Nous achetons nos cafés verts auprès d'importateurs spécialisés, qui travaillent directement avec les coopératives et les domaines. Sur chaque fiche, vous trouverez le pays, la région, l'altitude, la variété et le procédé.</p>
<!-- /wp:paragraph --></div><figure class="wp-block-media-text__media"><img src="{$recolte}" alt="{$alt_recolte}"/></figure></div>
<!-- /wp:media-text -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Venir nous voir</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>L'atelier ouvre ses portes le samedi matin, sur rendez-vous, pour une dégustation et une visite de la torréfaction.</p>
<!-- /wp:paragraph -->
HTML;
ga_page('l-atelier', 'L\'atelier', $atelier);

// ------------------------------------------------------------------
// Livraison et retours
// ------------------------------------------------------------------
$livraison = <<<HTML
<!-- wp:paragraph -->
<p>Les commandes passées avant mardi midi partent avec la torréfaction de la semaine.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Tarifs de livraison</h2>
<!-- /wp:heading -->

<!-- wp:table {"hasFixedLayout":true} -->
<figure class="wp-block-table"><table class="has-fixed-layout"><caption>Tarifs de livraison en France métropolitaine</caption><thead><tr><th scope="col">Mode de livraison</th><th scope="col">Délai</th><th scope="col">Tarif</th></tr></thead><tbody><tr><td>Point relais</td><td>2 à 3 jours ouvrés</td><td>4,90 €</td></tr><tr><td>À domicile</td><td>2 à 3 jours ouvrés</td><td>6,90 €</td></tr><tr><td>Point relais ou domicile</td><td>Dès 45 € d'achat</td><td>Offerte</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Retours</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Les accessoires peuvent être retournés sous 14 jours, dans leur emballage d'origine. Le café, denrée périssable, ne peut pas être repris une fois le sachet ouvert ; s'il ne vous plaît pas, écrivez-nous, nous trouverons une solution.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Fraîcheur</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Chaque sachet indique sa date de torréfaction. Le café est à son meilleur entre une et six semaines après cette date.</p>
<!-- /wp:paragraph -->
HTML;
ga_page('livraison-et-retours', 'Livraison et retours', $livraison);

// ------------------------------------------------------------------
// Contact
// ------------------------------------------------------------------
$lien_commande = $avant
    ? 'Une question sur une commande ? Cliquez <a href="' . esc_url(home_url('/livraison-et-retours/')) . '">ici</a>.'
    : 'Une question sur une commande ? Consultez d\'abord notre page <a href="' . esc_url(home_url('/livraison-et-retours/')) . '">Livraison et retours</a>.';
$contact = <<<HTML
<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Une question sur un café, une méthode ou un abonnement pour votre entreprise ? Écrivez-nous, nous répondons sous deux jours ouvrés.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Nous écrire</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Par e-mail : <a href="mailto:bonjour@graindardoise.fr">bonjour@graindardoise.fr</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>{$lien_commande}</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Visiter l'atelier</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Le samedi matin, sur rendez-vous : dégustation commentée et visite de la torréfaction, environ une heure.</p>
<!-- /wp:paragraph -->
HTML;
ga_page('contact', 'Contact', $contact, 'page', array('ID' => 38));

// ------------------------------------------------------------------
// Mentions légales (boutique fictive, crédits des photos)
// ------------------------------------------------------------------
$credits = '';
foreach (get_option('grain_ardoise_medias') as $nom => $id) {
    $source = get_post_field('post_content', $id);
    if (preg_match('/^Photo : (.+) sur Unsplash \((.+)\)$/', $source, $c)) {
        $credits .= '<li>' . esc_html(get_the_title($id)) . ' : ' . esc_html($c[1]) . ', <a href="' . esc_url($c[2]) . '">photo sur Unsplash</a></li>';
    }
}
$mentions = <<<HTML
<!-- wp:paragraph -->
<p><strong>Grain d'Ardoise est une boutique fictive.</strong> Ce site a été réalisé pour une démonstration : les produits, les prix et les textes sont inventés, et aucune commande n'est traitée.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Crédits photographiques</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Les photos proviennent d'Unsplash et sont utilisées selon la licence Unsplash.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list">{$credits}</ul>
<!-- /wp:list -->
HTML;
ga_page('mentions-legales', 'Mentions légales', $mentions);

// ------------------------------------------------------------------
// Journal et articles
// ------------------------------------------------------------------
ga_page('journal', 'Journal', '', 'page', array('ID' => 31));
update_option('page_for_posts', 31);

$tableau = $avant
    ? '<table class="has-fixed-layout"><tbody><tr><td>Méthode</td><td>Mouture</td><td>Temps d\'extraction</td></tr><tr><td>Espresso</td><td>Très fine</td><td>25 à 30 secondes</td></tr><tr><td>Cafetière filtre</td><td>Moyenne</td><td>3 à 4 minutes</td></tr><tr><td>Cafetière à piston</td><td>Grossière</td><td>4 minutes</td></tr></tbody></table>'
    : '<table class="has-fixed-layout"><caption>Mouture conseillée selon la méthode</caption><thead><tr><th scope="col">Méthode</th><th scope="col">Mouture</th><th scope="col">Temps d\'extraction</th></tr></thead><tbody><tr><td>Espresso</td><td>Très fine</td><td>25 à 30 secondes</td></tr><tr><td>Cafetière filtre</td><td>Moyenne</td><td>3 à 4 minutes</td></tr><tr><td>Cafetière à piston</td><td>Grossière</td><td>4 minutes</td></tr></tbody></table>';
$article_mouture = <<<HTML
<!-- wp:paragraph -->
<p>La mouture règle la vitesse à laquelle l'eau traverse le café. Trop fine, l'extraction s'éternise et le café devient amer ; trop grossière, il reste plat et acide. Voici nos repères pour chaque méthode.</p>
<!-- /wp:paragraph -->

<!-- wp:table {"hasFixedLayout":true} -->
<figure class="wp-block-table">{$tableau}</figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Ajuster au goût</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ces repères sont un point de départ. Si votre café est amer, moulez un peu plus gros ; s'il manque de corps, un peu plus fin. Changez un seul réglage à la fois.</p>
<!-- /wp:paragraph -->
HTML;
$id_a1 = ga_page('quelle-mouture-pour-quelle-methode', 'Quelle mouture pour quelle méthode ?', $article_mouture, 'post',
    array('post_excerpt' => 'Espresso, filtre ou piston : la mouture change tout. Nos repères pour régler la vôtre.', 'post_date' => '2026-10-06 09:00:00'));
set_post_thumbnail($id_a1, $m['article-carnet']);

$article_conserver = <<<HTML
<!-- wp:paragraph -->
<p>Un café torréfié perd ses arômes au contact de l'air, de la lumière, de la chaleur et de l'humidité. Quelques gestes suffisent pour le garder au meilleur.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Les bons réflexes</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Gardez le café dans son sachet à valve, bien refermé, ou dans une boîte opaque et hermétique.</li><li>Rangez-le au placard, loin du four et de la lumière, jamais au réfrigérateur.</li><li>Achetez-le en grains et moulez juste avant la préparation.</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Un sachet de 250 g se déguste idéalement dans le mois qui suit son ouverture.</p>
<!-- /wp:paragraph -->
HTML;
$id_a2 = ga_page('bien-conserver-son-cafe', 'Bien conserver son café', $article_conserver, 'post',
    array('post_excerpt' => 'Air, lumière, chaleur, humidité : les quatre ennemis du café, et comment s\'en protéger.', 'post_date' => '2026-09-22 09:00:00'));
set_post_thumbnail($id_a2, $m['coffret-decouverte']);
foreach (array($id_a1, $id_a2) as $id) {
    wp_set_post_categories($id, array(get_option('default_category')));
}
wp_update_term(get_option('default_category'), 'category', array('name' => 'Conseils', 'slug' => 'conseils'));

// ------------------------------------------------------------------
// Alternative d'une photo produit : nom du fichier avant, description après
// ------------------------------------------------------------------
update_post_meta($m['kenya-nyeri'], '_wp_attachment_image_alt', $avant ? 'kenya-nyeri.jpg' : 'Grains de café à la torréfaction claire sur fond blanc');

// Pages WooCommerce de démonstration hors menu, brouillons et anciens contenus hors audit
foreach (array(52, 67) as $id) {
    if (get_post_status($id) === 'publish') {
        wp_update_post(array('ID' => $id, 'post_status' => 'draft'));
    }
}
wp_cache_flush();
WP_CLI::success("Site en place, mode $mode.");
