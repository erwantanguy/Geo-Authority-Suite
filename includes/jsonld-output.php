<?php
/**
 * GEO Authority Suite - JSON-LD Output
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    geo_register_all_entities();
}, 20);

add_action('wp_head', function () {
    geo_output_jsonld();
}, 100);

function geo_register_all_entities() {
    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    foreach ($entities as $entity) {
        $entity_data = geo_build_entity_schema($entity);
        if ($entity_data) {
            geo_register_entity($entity_data);
        }
    }
}

function geo_build_entity_schema($entity) {
    $post_id = $entity->ID;

    $types = wp_get_post_terms($post_id, 'entity_type');
    if (!$types || is_wp_error($types)) {
        $type = 'Thing';
    } else {
        $type = $types[0]->name;
    }

    if (in_array(strtolower($type), ['worksfor', 'memberof'])) {
        return null;
    }

    $canonical = get_post_meta($post_id, '_entity_canonical', true);
    $name = !empty($canonical) ? $canonical : get_the_title($entity);
    $description = wp_strip_all_tags($entity->post_content);
    $url = get_post_meta($post_id, '_entity_url', true);

    $id = geo_entity_id($type, sanitize_title($name));

    $schema = [
        '@type' => $type,
        '@id'   => $id,
        'name'  => $name,
    ];

    if (!empty($description)) {
        $schema['description'] = $description;
    }

    if (!empty($url)) {
        $schema['url'] = $url;
    }

    $image = get_post_meta($post_id, '_entity_image', true);
    if (empty($image) && has_post_thumbnail($post_id)) {
        $image = get_the_post_thumbnail_url($post_id, 'full');
    }
    if (!empty($image)) {
        $schema['image'] = $image;
        if ($type === 'Organization' || $type === 'LocalBusiness') {
            $schema['logo'] = $image;
        }
    }

    $synonyms = get_post_meta($post_id, '_entity_synonyms', true);
    if (!empty($synonyms)) {
        $synonyms_array = array_filter(array_map('trim', explode(',', $synonyms)));
        if (!empty($synonyms_array)) {
            $schema['alternateName'] = count($synonyms_array) === 1 ? $synonyms_array[0] : $synonyms_array;
        }
    }

    $same_as = get_post_meta($post_id, '_entity_same_as', true);
    if (!empty($same_as)) {
        $same_as_array = array_filter(array_map('trim', explode("\n", $same_as)));
        if (!empty($same_as_array)) {
            $schema['sameAs'] = $same_as_array;
        }
    }

    switch ($type) {
        case 'Person':
            $schema = geo_add_person_entity_properties($schema, $post_id);
            break;

        case 'Organization':
        case 'LocalBusiness':
            $schema = geo_add_organization_entity_properties($schema, $post_id);
            break;
    }

    return $schema;
}

function geo_add_person_entity_properties($schema, $post_id) {
    $job_title = get_post_meta($post_id, '_entity_job_title', true);
    if (!empty($job_title)) {
        $schema['jobTitle'] = $job_title;
    }

    $email = get_post_meta($post_id, '_entity_email', true);
    if (!empty($email)) {
        $schema['email'] = $email;
    }

    $telephone = get_post_meta($post_id, '_entity_telephone', true);
    if (!empty($telephone)) {
        $schema['telephone'] = $telephone;
    }

    $works_for = get_post_meta($post_id, '_entity_works_for', true);
    if (!empty($works_for)) {
        if ($works_for === 'main_organization') {
            $schema['worksFor'] = [
                '@id' => geo_entity_id('organization'),
            ];
        } else {
            $org_post = get_post($works_for);
            if ($org_post && $org_post->post_type === 'entity') {
                $org_name = get_the_title($org_post);
                $schema['worksFor'] = [
                    '@id' => geo_entity_id('organization', sanitize_title($org_name)),
                ];
            }
        }
    }

    $member_of = get_post_meta($post_id, '_entity_member_of', true);
    if (!empty($member_of)) {
        if ($member_of === 'main_organization') {
            $schema['memberOf'] = [
                '@id' => geo_entity_id('organization'),
            ];
        } else {
            $org_post = get_post($member_of);
            if ($org_post && $org_post->post_type === 'entity') {
                $org_name = get_the_title($org_post);
                $schema['memberOf'] = [
                    '@id' => geo_entity_id('organization', sanitize_title($org_name)),
                ];
            }
        }
    }

    return $schema;
}

function geo_add_organization_entity_properties($schema, $post_id) {
    $email = get_post_meta($post_id, '_entity_email', true);
    if (!empty($email)) {
        $schema['email'] = $email;
    }

    $telephone = get_post_meta($post_id, '_entity_telephone', true);
    if (!empty($telephone)) {
        $schema['telephone'] = $telephone;
    }

    $street = get_post_meta($post_id, '_entity_address_street', true);
    $city = get_post_meta($post_id, '_entity_address_city', true);
    $postal = get_post_meta($post_id, '_entity_address_postal', true);
    $country = get_post_meta($post_id, '_entity_address_country', true);

    if (!empty($street) || !empty($city)) {
        $address = ['@type' => 'PostalAddress'];
        if (!empty($street)) $address['streetAddress'] = $street;
        if (!empty($city)) $address['addressLocality'] = $city;
        if (!empty($postal)) $address['postalCode'] = $postal;
        if (!empty($country)) $address['addressCountry'] = $country;
        $schema['address'] = $address;
    }

    return $schema;
}

function geo_output_jsonld() {
    $entities = geo_get_entities();

    if (empty($entities)) {
        return;
    }

    $graph = [];
    foreach ($entities as $entity) {
        if (isset($entity['@context'])) {
            unset($entity['@context']);
        }
        if (!isset($entity['@type']) || in_array($entity['@type'], ['worksFor', 'memberOf'])) {
            continue;
        }
        $graph[] = $entity;
    }

    if (empty($graph)) {
        return;
    }

    echo "\n" . '<script type="application/ld+json">' . "\n";
    echo wp_json_encode(
        [
            '@context' => 'https://schema.org',
            '@graph'   => array_values($graph),
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    echo "\n" . '</script>' . "\n";
}

/**
 * Shortcode [entity] amélioré
 * 
 * APPROCHE RECOMMANDÉE : Microdata inline + référence JSON-LD
 * Avantages :
 * - Pas de duplication du JSON-LD
 * - Enrichissement sémantique du contenu
 * - Meilleure compréhension par les IA
 * - Tooltip informatif pour l'utilisateur
 */

// VERSION 1 : MICRODATA INLINE (RECOMMANDÉ)
add_shortcode('entity', function ($atts) {
    $atts = shortcode_atts([
        'id'      => 0,
        'display' => 'inline',    // inline, card, tooltip
        'show'    => 'name',       // name, name+title, full
        'link'    => 'yes',        // yes, no
        'image'   => 'no',         // yes, no
    ], $atts);
    
    $entity_id = intval($atts['id']);
    if (!$entity_id) return '';

    $entity = get_post($entity_id);
    if (!$entity || $entity->post_type !== 'entity') return '';

    // Récupération des métadonnées
    $canonical = get_post_meta($entity_id, '_entity_canonical', true);
    $name = !empty($canonical) ? $canonical : get_the_title($entity);
    $url = get_post_meta($entity_id, '_entity_url', true);
    $description = wp_strip_all_tags($entity->post_content);
    $image = get_post_meta($entity_id, '_entity_image', true);
    if (empty($image) && has_post_thumbnail($entity_id)) {
        $image = get_the_post_thumbnail_url($entity_id, 'thumbnail');
    }
    $job_title = get_post_meta($entity_id, '_entity_job_title', true);
    
    // Type d'entité
    $types = wp_get_post_terms($entity_id, 'entity_type');
    $type = $types && !is_wp_error($types) ? $types[0]->name : 'Thing';
    
    // ID sémantique (référence au JSON-LD du head)
    $semantic_id = geo_entity_id($type, sanitize_title($name));

    // RENDU SELON LE MODE
    switch ($atts['display']) {
        
        case 'card':
            // MODE CARTE : Affichage enrichi avec photo, nom, fonction, description
            return sprintf(
                '<div class="entity-card" itemscope itemtype="https://schema.org/%s" itemid="%s">
                    %s
                    <div class="entity-card-content">
                        <h4 class="entity-card-name" itemprop="name">%s</h4>
                        %s
                        %s
                        %s
                    </div>
                </div>',
                esc_attr($type),
                esc_attr($semantic_id),
                $image ? sprintf('<img itemprop="image" src="%s" alt="%s" class="entity-card-image">', esc_url($image), esc_attr($name)) : '',
                esc_html($name),
                $job_title ? sprintf('<p class="entity-job-title" itemprop="jobTitle">%s</p>', esc_html($job_title)) : '',
                $description ? sprintf('<p class="entity-description" itemprop="description">%s</p>', esc_html(wp_trim_words($description, 20))) : '',
                $url ? sprintf('<a href="%s" itemprop="url" class="entity-card-link" target="_blank">En savoir plus →</a>', esc_url($url)) : ''
            );
            
        case 'tooltip':
            // MODE TOOLTIP : Lien avec info-bulle au survol
            $tooltip_content = '';
            if ($job_title) $tooltip_content .= esc_html($job_title);
            if ($description) $tooltip_content .= ($tooltip_content ? ' – ' : '') . esc_html(wp_trim_words($description, 15));
            
            $link_content = sprintf('<span itemprop="name">%s</span>', esc_html($name));
            
            return sprintf(
                '<span class="entity-mention entity-tooltip" itemscope itemtype="https://schema.org/%s" itemid="%s">
                    <a href="%s" itemprop="url" class="entity-link" data-entity-tooltip="%s">%s</a>
                </span>',
                esc_attr($type),
                esc_attr($semantic_id),
                esc_url($url ?: '#'),
                esc_attr($tooltip_content),
                $link_content
            );
            
        case 'inline':
        default:
            // MODE INLINE : Mention dans le texte
            
            // Construction du contenu selon le paramètre 'show'
            $content = '';
            
            switch ($atts['show']) {
                case 'name+title':
                    $content = sprintf(
                        '<span itemprop="name">%s</span>%s',
                        esc_html($name),
                        $job_title ? sprintf(' <em class="entity-title">(%s)</em>', esc_html($job_title)) : ''
                    );
                    break;
                    
                case 'full':
                    $parts = [];
                    $parts[] = sprintf('<span itemprop="name">%s</span>', esc_html($name));
                    if ($job_title) $parts[] = sprintf('<em itemprop="jobTitle">%s</em>', esc_html($job_title));
                    if ($description) $parts[] = sprintf('<span itemprop="description">%s</span>', esc_html(wp_trim_words($description, 15)));
                    $content = implode(' – ', $parts);
                    break;
                    
                case 'name':
                default:
                    $content = sprintf('<span itemprop="name">%s</span>', esc_html($name));
                    break;
            }
            
            // Image optionnelle
            if ($atts['image'] === 'yes' && $image) {
                $content = sprintf('<img itemprop="image" src="%s" alt="%s" class="entity-inline-image"> ', esc_url($image), esc_attr($name)) . $content;
            }
            
            // Lien optionnel
            if ($atts['link'] === 'yes' && $url) {
                $content = sprintf('<a href="%s" itemprop="url" class="entity-link">%s</a>', esc_url($url), $content);
            } elseif ($atts['link'] === 'no') {
                // Pas de lien, juste le contenu
            }
            
            // Wrapper avec microdata
            return sprintf(
                '<span class="entity-mention entity-inline" itemscope itemtype="https://schema.org/%s" itemid="%s">%s</span>',
                esc_attr($type),
                esc_attr($semantic_id),
                $content
            );
    }
});


// CSS ET JS POUR LES ENTITÉS
add_action('wp_enqueue_scripts', function () {
    global $post;
    
    // Vérifier si le post contient le shortcode [entity]
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'entity')) {
        
        // Ajouter le CSS inline
        wp_add_inline_style('wp-block-library', '
            /* === ENTITY CARD === */
            .entity-card {
                display: flex;
                gap: 15px;
                padding: 20px;
                margin: 25px 0;
                background: #f8f9fa;
                border-left: 4px solid #0073aa;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .entity-card-image {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 50%;
                flex-shrink: 0;
                border: 3px solid #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .entity-card-content {
                flex: 1;
            }
            .entity-card-name {
                margin: 0 0 5px;
                color: #0073aa;
                font-size: 18px;
                font-weight: 600;
            }
            .entity-job-title {
                margin: 0 0 10px;
                font-size: 14px;
                color: #666;
                font-style: italic;
            }
            .entity-description {
                margin: 0 0 12px;
                font-size: 14px;
                line-height: 1.6;
                color: #333;
            }
            .entity-card-link {
                display: inline-block;
                padding: 6px 12px;
                font-size: 14px;
                color: #fff;
                background: #0073aa;
                text-decoration: none;
                border-radius: 4px;
                transition: background 0.2s;
            }
            .entity-card-link:hover {
                background: #005a87;
            }
            
            /* === ENTITY INLINE === */
            .entity-inline {
                display: inline;
            }
            .entity-inline .entity-link {
                color: #0073aa;
                text-decoration: none;
                border-bottom: 1px solid #0073aa;
                transition: all 0.2s;
            }
            .entity-inline .entity-link:hover {
                color: #005a87;
                border-bottom-color: #005a87;
            }
            .entity-inline .entity-title {
                font-style: italic;
                color: #666;
                font-size: 0.95em;
            }
            .entity-inline-image {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                object-fit: cover;
                vertical-align: middle;
                margin-right: 5px;
            }
            
            /* === ENTITY TOOLTIP === */
            .entity-tooltip {
                position: relative;
                display: inline-block;
            }
            .entity-tooltip .entity-link {
                color: #0073aa;
                text-decoration: none;
                border-bottom: 1px dotted #0073aa;
                cursor: help;
            }
            .entity-tooltip .entity-link:hover {
                border-bottom-style: solid;
            }
            
            /* Tooltip bubble */
            .entity-tooltip .entity-link[data-entity-tooltip]:hover::after {
                content: attr(data-entity-tooltip);
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                margin-bottom: 10px;
                padding: 10px 14px;
                background: #333;
                color: #fff;
                font-size: 13px;
                line-height: 1.5;
                white-space: normal;
                width: max-content;
                max-width: 300px;
                border-radius: 6px;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                font-style: normal;
            }
            
            /* Tooltip arrow */
            .entity-tooltip .entity-link[data-entity-tooltip]:hover::before {
                content: "";
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                margin-bottom: 4px;
                border: 6px solid transparent;
                border-top-color: #333;
                z-index: 1000;
            }
            
            /* Responsive */
            @media (max-width: 600px) {
                .entity-card {
                    flex-direction: column;
                    text-align: center;
                }
                .entity-card-image {
                    margin: 0 auto;
                }
                .entity-tooltip .entity-link[data-entity-tooltip]:hover::after {
                    left: 0;
                    transform: none;
                    max-width: 250px;
                }
            }
        ');
    }
}, 20);


/*
=== EXEMPLES D'UTILISATION ===

1. MENTION INLINE SIMPLE
[entity id=5]
→ Affiche : "Erwan Tanguy" (lien simple)

2. MENTION AVEC FONCTION
[entity id=5 show="name+title"]
→ Affiche : "Erwan Tanguy (CEO, développeur)"

3. MENTION COMPLÈTE
[entity id=5 show="full"]
→ Affiche : "Erwan Tanguy – CEO – Expert en SEO depuis..."

4. SANS LIEN
[entity id=5 show="name+title" link="no"]
→ Affiche : "Erwan Tanguy (CEO)" (pas de lien)

5. AVEC IMAGE MINIATURE
[entity id=5 image="yes" show="name+title"]
→ Affiche : [photo] Erwan Tanguy (CEO)

6. CARTE ENRICHIE
[entity id=5 display="card"]
→ Affiche : Carte complète avec photo, nom, fonction, description, bouton

7. TOOLTIP AU SURVOL
[entity id=5 display="tooltip"]
→ Affiche : Lien avec info-bulle affichant fonction + description

*/