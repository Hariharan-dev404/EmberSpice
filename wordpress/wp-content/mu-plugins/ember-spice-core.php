<?php
/**
 * Plugin Name: Ember Spice Core Engine
 * Description: Core functionality, design system, menus, forms, and restaurant architecture for Ember Spice.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

class EmberSpiceCore {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Theme supports
        add_action('after_setup_theme', [$this, 'setup_theme_features']);

        // Site configuration on init
        add_action('init', [$this, 'configure_site_settings']);

        // Force static homepage and blog page options
        add_filter('pre_option_show_on_front', function() { return 'page'; });
        add_filter('pre_option_page_on_front', function() { return 7; });
        add_filter('pre_option_page_for_posts', function() { return 12; });

        // Disable Elementor element caching so content updates render immediately
        add_filter('pre_option_elementor_element_cache_ttl', function() { return 'disable'; });

        // Ensure Elementor Canvas template is always used for our pages
        add_filter('get_post_metadata', function($value, $object_id, $meta_key, $single) {
            if ($meta_key === '_wp_page_template' && in_array(intval($object_id), [7, 8, 9, 10, 11, 13])) {
                return $single ? 'elementor_canvas' : ['elementor_canvas'];
            }
            return $value;
        }, 10, 4);

        // Enqueue assets with high priority so it runs after Elementor
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 99999);

        // Inject critical layout and full-size image overrides at the very end of head
        add_action('wp_head', [$this, 'print_critical_head_styles'], 999999);

        // AJAX handlers
        add_action('wp_ajax_ember_spice_reservation', [$this, 'handle_reservation']);
        add_action('wp_ajax_nopriv_ember_spice_reservation', [$this, 'handle_reservation']);

        add_action('wp_ajax_ember_spice_contact', [$this, 'handle_contact']);
        add_action('wp_ajax_nopriv_ember_spice_contact', [$this, 'handle_contact']);

        // Admin Management Pages
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_pages']);
        }

        // Canvas header & footer integration
        add_action('elementor/page_templates/canvas/before_content', function() {
            echo do_shortcode('[ember_spice_header]');
        });

        add_action('elementor/page_templates/canvas/after_content', function() {
            $post_id = get_the_ID();
            if ($post_id == 9 || is_page(9)) {
                echo '<section style="padding: 2rem 2rem 6rem; max-width: 1280px; margin: 0 auto;">' . do_shortcode('[ember_spice_menu]') . '</section>';
            } elseif ($post_id == 10 || is_page(10)) {
                echo '<section style="padding: 2rem 2rem 6rem; max-width: 1200px; margin: 0 auto;">' . do_shortcode('[ember_spice_reservation_form]') . '</section>';
            } elseif ($post_id == 11 || is_page(11)) {
                echo '<section style="padding: 2rem 2rem 6rem; max-width: 1280px; margin: 0 auto;">' . do_shortcode('[ember_spice_gallery]') . '</section>';
            } elseif ($post_id == 13 || is_page(13)) {
                echo '<section style="padding: 2rem 2rem 6rem; max-width: 1200px; margin: 0 auto;">' . do_shortcode('[ember_spice_contact_form]') . '</section>';
            }
            echo do_shortcode('[ember_spice_footer]');
        });

        // Template router for Blog, Single Posts, and Elementor Canvas
        add_filter('template_include', [$this, 'route_custom_templates'], 9999);

        // Shortcodes
        add_shortcode('ember_spice_header', [$this, 'render_header']);
        add_shortcode('ember_spice_footer', [$this, 'render_footer']);
        add_shortcode('ember_spice_menu', [$this, 'render_menu']);
        add_shortcode('ember_spice_reservation_form', [$this, 'render_reservation_form']);
        add_shortcode('ember_spice_contact_form', [$this, 'render_contact_form']);
        add_shortcode('ember_spice_gallery', [$this, 'render_gallery']);
        add_shortcode('ember_spice_blog', [$this, 'render_blog']);
    }

    public function setup_theme_features() {
        add_theme_support('menus');
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('custom-logo');
        register_nav_menus([
            'ember_spice_primary' => __('Ember Spice Primary Menu', 'ember-spice')
        ]);
    }

    public function configure_site_settings() {
        if (!get_option('ember_spice_configured_v5')) {
            global $wpdb;
            $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = 'elementor_canvas' WHERE meta_key = '_wp_page_template' AND post_id IN (7, 8, 9, 10, 11, 13)");

            // 1. Set static front page and blog page
            update_option('show_on_front', 'page');
            update_option('page_on_front', 7);  // Home page ID
            update_option('page_for_posts', 12); // Blog page ID

            // 2. Ensure pages are published
            $pages_to_publish = [7, 8, 9, 10, 11, 12, 13];
            foreach ($pages_to_publish as $pid) {
                wp_update_post([
                    'ID' => $pid,
                    'post_status' => 'publish'
                ]);
            }

            // 3. Ensure blog posts are published with rich content
            $blog_posts = [
                14 => [
                    'title' => 'The Secrets Behind Authentic Indian Spices',
                    'excerpt' => 'Discover how freshly ground cardamom, toasted cumin, and Kashmiri saffron create the signature depth and aroma of royal Indian cuisine.',
                    'content' => '<p>In Indian gastronomy, spices are not mere seasonings—they are the soul, heritage, and medicine of our culinary culture. At Ember Spice, every morning begins with the slow, dry-roasting of whole seeds in heavy iron karahis.</p><h3>The Holy Trinity of Tempering</h3><p>Known as <em>tadka</em> or <em>chhonk</em>, the technique of blooming whole mustard seeds, cumin, and fragrant curry leaves in bubbling clarified ghee unlocks essential oils that powder alone could never replicate.</p><h3>Saffron from the Valleys of Kashmir</h3><p>We source our pure Mongra saffron directly from Pampore. Steeping gently in warm milk for hours before being layered into our dum biryanis, it lends an unmistakable royal golden hue and delicate earthy perfume.</p>'
                ],
                15 => [
                    'title' => 'A Journey Through India\'s Regional Cuisine',
                    'excerpt' => 'From the rich dum cooking of Awadh to the fiery coastal curries of the Malabar coast, experience the vast culinary tapestry of India.',
                    'content' => '<p>India is a continent of cuisines masquerading as a country. Each state, district, and river valley boasts culinary traditions forged over thousands of years.</p><h3>Awadhi Dum Pukht</h3><p>Originating in the royal courts of Lucknow, Dum Pukht is the art of slow cooking in sealed clay pots (handis) over gentle smoldering charcoal embers. The trapped steam retains every droplet of moisture and delicate aromatic note.</p><h3>Coastal Flavours of Malabar & Goa</h3><p>Moving south, the aromatic profile shifts to roasted coriander, black pepper, tangy kokum, and freshly pressed coconut milk, creating rich seafood curries that dance between heat and acidity.</p>'
                ],
                16 => [
                    'title' => 'The Art of Traditional Tandoor Cooking',
                    'excerpt' => 'Exploring the intense heat, clay ovens, and charcoal alchemy that impart our signature smoky char to succulent kebabs and artisanal naans.',
                    'content' => '<p>The heart of Ember Spice is our authentic, hand-crafted clay tandoor oven, firing at over 480°C (900°F). Fuelled by select hardwood lump charcoal, the intense radiant heat seals juices instantly while imparting our unmistakable smoky signature.</p><h3>Mastering the Skewer</h3><p>Marination is an art of patience. Our meats and paneer undergo a double marination process: first in freshly crushed ginger-garlic paste and mustard oil, followed by thick strained hung yogurt infused with toasted fenugreek and hand-pounded spices.</p><h3>The Perfect Char</h3><p>Suspended in the glowing hearth, the fat melts and drips onto the burning embers below, vaporizing into a fragrant cloud of smoke that bastes the skewers continuously.</p>'
                ]
            ];

            foreach ($blog_posts as $post_id => $post_data) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $post_data['title'],
                    'post_excerpt' => $post_data['excerpt'],
                    'post_content' => $post_data['content'],
                    'post_status' => 'publish'
                ]);
            }

            // 4. Create Navigation Menu
            require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
            $menu_name = 'Ember Spice Main Navigation';
            $menu_exists = wp_get_nav_menu_object($menu_name);
            if (!$menu_exists) {
                $menu_id = wp_create_nav_menu($menu_name);
                $menu_items = [
                    ['title' => 'Home', 'url' => home_url('/')],
                    ['title' => 'About', 'url' => home_url('/about-us/')],
                    ['title' => 'Our Menu', 'url' => home_url('/our-menu/')],
                    ['title' => 'Reservations', 'url' => home_url('/reservations/')],
                    ['title' => 'Gallery', 'url' => home_url('/gallery/')],
                    ['title' => 'Blog', 'url' => home_url('/blog/')],
                    ['title' => 'Contact', 'url' => home_url('/contact/')],
                ];

                $order = 1;
                foreach ($menu_items as $item) {
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => $item['title'],
                        'menu-item-url' => $item['url'],
                        'menu-item-status' => 'publish',
                        'menu-item-position' => $order++
                    ]);
                }

                $locations = get_theme_mod('nav_menu_locations');
                if (!is_array($locations)) {
                    $locations = [];
                }
                $locations['ember_spice_primary'] = $menu_id;
                set_theme_mod('nav_menu_locations', $locations);
            }

            update_option('ember_spice_configured_v5', 1);
        }
    }

    public function enqueue_assets() {
        // Google Fonts
        wp_enqueue_style(
            'ember-spice-google-fonts',
            'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap',
            [],
            null
        );

        // Core CSS - dependent on elementor-frontend and loaded after all plugin styles
        $css_url = content_url('/mu-plugins/assets/css/ember-spice.css');
        $css_path = __DIR__ . '/assets/css/ember-spice.css';
        $css_ver = file_exists($css_path) ? filemtime($css_path) : '1.0.0';
        wp_enqueue_style('ember-spice-style', $css_url, ['ember-spice-google-fonts'], $css_ver);

        // Core JS
        $js_url = content_url('/mu-plugins/assets/js/ember-spice.js');
        $js_path = __DIR__ . '/assets/js/ember-spice.js';
        $js_ver = file_exists($js_path) ? filemtime($js_path) : '1.0.0';
        wp_enqueue_script('ember-spice-script', $js_url, [], $js_ver, true);

        wp_localize_script('ember-spice-script', 'emberSpiceData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ember_spice_action_nonce')
        ]);
    }

    /**
     * Injects absolute priority CSS at the bottom of <head>
     * Guarantees 100% full-size uncropped images and strict vertical content stacking.
     */
    public function print_critical_head_styles() {
        ?>
        <style id="ember-spice-critical-overrides">
        /* ====================================================================
           CRITICAL OVERRIDES: FULL-SIZE IMAGES & STRICT VERTICAL FLOW
           ==================================================================== */

        /* 1. Ensure all featured and section images display full size without cropping */
        html body .elementor-element-6fa6aad4 img,
        html body .elementor-element-63a15db4 img,
        html body .elementor-element-2fa2f402 img,
        html body .elementor-element-46ceb09d img,
        html body .elementor-element-73ce952f img,
        html body .elementor-element-45617a91 img,
        html body .elementor-element-6bae2821 img {
            width: 100% !important;
            height: auto !important;
            max-height: none !important;
            aspect-ratio: auto !important;
            object-fit: contain !important;
            border-radius: 12px !important;
            border: 1px solid rgba(120, 133, 104, 0.3) !important;
            box-shadow: 0 16px 40px rgba(52, 75, 53, 0.12) !important;
            display: block !important;
        }

        /* 2. Global rule: Never crop images inside feature containers */
        html body .e-con:has(.e-image) img,
        html body .e-flexbox-base:has(.e-image) img {
            max-height: none !important;
            aspect-ratio: auto !important;
        }

        /* 3. Strict 2-column layout for split sections (Home intro, Chef, Exp, About Story, Chef, Ambience) */
        html body .elementor-element-34c143f2,
        html body .elementor-element-4a8053e0,
        html body .elementor-element-5458e283,
        html body .elementor-element-1684f3ed,
        html body .elementor-element-3e7fe74,
        html body .elementor-element-7566eaa {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 4.5rem !important;
            align-items: center !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
        }

        /* 4. Strict vertical column flow for text containers - NEVER horizontal strips */
        html body .elementor-element-4a5dc1c5,
        html body .elementor-element-27942172,
        html body .elementor-element-16ef5e00,
        html body .elementor-element-7fdad146,
        html body .elementor-element-2c6ea686,
        html body .elementor-element-3184349 {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            text-align: left !important;
            width: 100% !important;
            gap: 0 !important;
        }

        html body .elementor-element-4a5dc1c5 > *,
        html body .elementor-element-27942172 > *,
        html body .elementor-element-16ef5e00 > *,
        html body .elementor-element-7fdad146 > *,
        html body .elementor-element-2c6ea686 > *,
        html body .elementor-element-3184349 > * {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            display: block !important;
        }

        /* 5. Individual Cards: Strict vertical stacking (Title on top, description below) */
        html body .elementor-element-4572bed3,
        html body .elementor-element-3c8045f1,
        html body .elementor-element-17f3502c,
        html body .elementor-element-440b8b8e,
        html body .elementor-element-3e2e8fd2,
        html body .elementor-element-4e381021,
        html body .elementor-element-715406bc,
        html body .elementor-element-35e87e9b,
        html body .elementor-element-16593cf6,
        html body .elementor-element-70944290,
        html body .elementor-element-188698bc,
        html body .elementor-element-3ac47c0,
        html body .elementor-element-2f8fc644,
        html body .elementor-element-36ec187a,
        html body .elementor-element-58ae0245,
        html body .elementor-element-28d62019,
        html body .elementor-element-33142ffa,
        html body .elementor-element-627ec02f {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            text-align: left !important;
            background: #FFFCF5 !important;
            border: 1px solid rgba(120, 133, 104, 0.25) !important;
            border-radius: 12px !important;
            padding: 2.2rem 2rem !important;
            box-shadow: 0 4px 20px rgba(52, 75, 53, 0.08) !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        html body .elementor-element-4572bed3 .e-heading-base,
        html body .elementor-element-3c8045f1 .e-heading-base,
        html body .elementor-element-17f3502c .e-heading-base,
        html body .elementor-element-440b8b8e .e-heading-base,
        html body .elementor-element-3e2e8fd2 .e-heading-base,
        html body .elementor-element-4e381021 .e-heading-base,
        html body .elementor-element-715406bc .e-heading-base,
        html body .elementor-element-188698bc .e-heading-base,
        html body .elementor-element-3ac47c0 .e-heading-base,
        html body .elementor-element-2f8fc644 .e-heading-base,
        html body .elementor-element-36ec187a .e-heading-base,
        html body .elementor-element-58ae0245 .e-heading-base,
        html body .elementor-element-28d62019 .e-heading-base,
        html body .elementor-element-33142ffa .e-heading-base,
        html body .elementor-element-627ec02f .e-heading-base {
            font-size: 1.35rem !important;
            color: #344B35 !important;
            margin-bottom: 0.85rem !important;
            display: block !important;
            width: 100% !important;
        }

        html body .elementor-element-4572bed3 .e-paragraph-base,
        html body .elementor-element-3c8045f1 .e-paragraph-base,
        html body .elementor-element-17f3502c .e-paragraph-base,
        html body .elementor-element-440b8b8e .e-paragraph-base,
        html body .elementor-element-3e2e8fd2 .e-paragraph-base,
        html body .elementor-element-4e381021 .e-paragraph-base,
        html body .elementor-element-715406bc .e-paragraph-base,
        html body .elementor-element-188698bc .e-paragraph-base,
        html body .elementor-element-3ac47c0 .e-paragraph-base,
        html body .elementor-element-2f8fc644 .e-paragraph-base,
        html body .elementor-element-36ec187a .e-paragraph-base,
        html body .elementor-element-58ae0245 .e-paragraph-base,
        html body .elementor-element-28d62019 .e-paragraph-base,
        html body .elementor-element-33142ffa .e-paragraph-base,
        html body .elementor-element-627ec02f .e-paragraph-base {
            color: #777568 !important;
            font-size: 0.95rem !important;
            line-height: 1.7 !important;
            margin-bottom: 0 !important;
            display: block !important;
            width: 100% !important;
        }

        /* 6. Multi-Column Grids across pages */
        html body .elementor-element-23ac47f2 {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 2rem !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 2.5rem auto !important;
        }

        html body .elementor-element-6198878,
        html body .elementor-element-427555dd {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 2rem !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 2.5rem auto !important;
        }

        html body .elementor-element-31d333d7,
        html body .elementor-element-512823b4 {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 2rem !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 2.5rem auto !important;
        }

        html body .elementor-element-1c9e9dbd {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 2rem !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 2.5rem auto !important;
        }

        /* 7. Dish Cards on Home Page */
        html body .elementor-element-7dc084fb,
        html body .elementor-element-5e2546a8,
        html body .elementor-element-59d4bbff,
        html body .elementor-element-12ede85c,
        html body .elementor-element-7658a4a0,
        html body .elementor-element-56120651 {
            display: flex !important;
            flex-direction: column !important;
            background: #FFFCF5 !important;
            border: 1px solid rgba(120, 133, 104, 0.25) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
        }

        html body .elementor-element-7dc084fb img,
        html body .elementor-element-5e2546a8 img,
        html body .elementor-element-59d4bbff img,
        html body .elementor-element-12ede85c img,
        html body .elementor-element-7658a4a0 img,
        html body .elementor-element-56120651 img {
            width: 100% !important;
            height: 220px !important;
            object-fit: cover !important;
            border: none !important;
            border-radius: 12px 12px 0 0 !important;
            display: block !important;
        }

        html body .elementor-element-7a2a74cc,
        html body .elementor-element-ddccc8b,
        html body .elementor-element-25bfb990,
        html body .elementor-element-32c2a7ed,
        html body .elementor-element-6a871914,
        html body .elementor-element-3c42c24c {
            display: flex !important;
            flex-direction: column !important;
            padding: 1.5rem !important;
            flex-grow: 1 !important;
        }

        /* 8. Hero Sections Across All Pages — Grand Full-Width Photographic Banners (Like Blog Example) */
        html body .elementor-element-38e8dde3,
        html body .elementor-element-ef9390b,
        html body .elementor-element-3ebb97ea,
        html body .elementor-element-64872607,
        html body .elementor-element-4f91ffee,
        html body .elementor-element-2e926034 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            border-bottom: 1px solid rgba(120, 133, 104, 0.25) !important;
            box-sizing: border-box !important;
        }

        /* 8a. Home Hero: Full-screen luxury banquet experience */
        html body .elementor-element-38e8dde3 {
            min-height: 82vh !important;
            padding: 7rem 2rem 6rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.72) 0%, rgba(52, 75, 53, 0.85) 100%),
                        url('https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* 8b. About Us Hero: Whole sacred Indian spices in heritage brass */
        html body .elementor-element-ef9390b {
            min-height: 52vh !important;
            padding: 6rem 2rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.75) 0%, rgba(52, 75, 53, 0.88) 100%),
                        url('https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* 8c. Our Menu Hero: Saffron Awadhi dum biryani & rich handi curries */
        html body .elementor-element-3ebb97ea {
            min-height: 52vh !important;
            padding: 6rem 2rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.75) 0%, rgba(52, 75, 53, 0.88) 100%),
                        url('https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* 8d. Reservations Hero: Intimate candlelit luxury dining sanctuary */
        html body .elementor-element-64872607 {
            min-height: 52vh !important;
            padding: 6rem 2rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.75) 0%, rgba(52, 75, 53, 0.90) 100%),
                        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* 8e. Gallery Hero: Chef culinary artistry in charcoal warmth */
        html body .elementor-element-4f91ffee {
            min-height: 52vh !important;
            padding: 6rem 2rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.75) 0%, rgba(52, 75, 53, 0.88) 100%),
                        url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* 8f. Contact Hero: Welcoming restaurant dining ambience */
        html body .elementor-element-2e926034 {
            min-height: 52vh !important;
            padding: 6rem 2rem !important;
            background: linear-gradient(0deg, rgba(52, 75, 53, 0.75) 0%, rgba(52, 75, 53, 0.90) 100%),
                        url('https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?auto=format&fit=crop&w=1920&q=85') center/cover no-repeat !important;
        }

        /* Hero Typography Hierarchy — Elegant & Readable */
        html body .elementor-element-38e8dde3 .e-heading-base:first-child,
        html body .elementor-element-ef9390b .e-heading-base:first-child,
        html body .elementor-element-3ebb97ea .e-heading-base:first-child,
        html body .elementor-element-64872607 .e-heading-base:first-child,
        html body .elementor-element-4f91ffee .e-heading-base:first-child,
        html body .elementor-element-2e926034 .e-paragraph-base:first-child,
        html body .elementor-element-2e926034 .e-heading-base:first-child {
            color: #B99A65 !important;
            font-size: 0.95rem !important;
            letter-spacing: 4px !important;
            text-transform: uppercase !important;
            margin-bottom: 1rem !important;
            font-weight: 700 !important;
            text-align: center !important;
            display: block !important;
        }

        html body .elementor-element-38e8dde3 h1,
        html body .elementor-element-ef9390b h1,
        html body .elementor-element-3ebb97ea h1,
        html body .elementor-element-64872607 h1,
        html body .elementor-element-4f91ffee h1,
        html body .elementor-element-2e926034 h1,
        html body .elementor-element-38e8dde3 .e-heading-base h1,
        html body .elementor-element-ef9390b .e-heading-base h1,
        html body .elementor-element-3ebb97ea .e-heading-base h1,
        html body .elementor-element-64872607 .e-heading-base h1,
        html body .elementor-element-4f91ffee .e-heading-base h1,
        html body .elementor-element-2e926034 .e-heading-base h1 {
            color: #FFFCF5 !important;
            font-family: 'Playfair Display', Georgia, serif !important;
            font-size: 3.5rem !important;
            margin-bottom: 1.25rem !important;
            line-height: 1.2 !important;
            text-align: center !important;
            max-width: 900px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            display: block !important;
        }

        html body .elementor-element-38e8dde3 > .e-paragraph-base,
        html body .elementor-element-ef9390b > .e-paragraph-base,
        html body .elementor-element-3ebb97ea > .e-paragraph-base,
        html body .elementor-element-64872607 > .e-paragraph-base,
        html body .elementor-element-4f91ffee > .e-paragraph-base,
        html body .elementor-element-2e926034 > .e-paragraph-base {
            color: rgba(255, 252, 245, 0.88) !important;
            font-size: 1.2rem !important;
            max-width: 720px !important;
            line-height: 1.8 !important;
            margin: 0 auto !important;
            text-align: center !important;
            display: block !important;
        }

        html body .elementor-element-6d941c03,
        html body .elementor-element-15ec9579 {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 1.25rem !important;
            width: 100% !important;
            margin-top: 1.75rem !important;
        }

        /* 9. Responsive Mobile Breakpoints */
        @media (max-width: 860px) {
            html body .elementor-element-34c143f2,
            html body .elementor-element-4a8053e0,
            html body .elementor-element-5458e283,
            html body .elementor-element-1684f3ed,
            html body .elementor-element-3e7fe74,
            html body .elementor-element-7566eaa {
                grid-template-columns: 1fr !important;
                gap: 2.5rem !important;
            }

            html body .elementor-element-23ac47f2,
            html body .elementor-element-6198878,
            html body .elementor-element-427555dd,
            html body .elementor-element-31d333d7,
            html body .elementor-element-1c9e9dbd,
            html body .elementor-element-512823b4 {
                grid-template-columns: 1fr !important;
            }

            /* Responsive Hero Banners */
            html body .elementor-element-38e8dde3 {
                min-height: 68vh !important;
                padding: 5rem 1.5rem !important;
            }
            html body .elementor-element-ef9390b,
            html body .elementor-element-3ebb97ea,
            html body .elementor-element-64872607,
            html body .elementor-element-4f91ffee,
            html body .elementor-element-2e926034 {
                min-height: 44vh !important;
                padding: 4.5rem 1.5rem !important;
            }
            html body .elementor-element-38e8dde3 h1,
            html body .elementor-element-ef9390b h1,
            html body .elementor-element-3ebb97ea h1,
            html body .elementor-element-64872607 h1,
            html body .elementor-element-4f91ffee h1,
            html body .elementor-element-2e926034 h1 {
                font-size: 2.35rem !important;
            }
        }
        </style>
        <?php
    }

    /**
     * Builds a luxury responsive HTML email template for Ember Spice notifications.
     */
    private function build_email_template($eyebrow, $heading, $intro_html, $details_rows, $notice_html = '', $cta_html = '') {
        $year = date('Y');

        $rows_html = '';
        foreach ($details_rows as $label => $val) {
            $rows_html .= '
            <tr>
                <td style="padding: 12px 16px; border-bottom: 1px solid rgba(120,133,104,0.2); color: #344B35; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; width: 35%; vertical-align: top;">' . esc_html($label) . '</td>
                <td style="padding: 12px 16px; border-bottom: 1px solid rgba(120,133,104,0.2); color: #2A2C24; font-size: 14px; line-height: 1.6; vertical-align: top;">' . nl2br(esc_html($val)) . '</td>
            </tr>';
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($heading) . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4EEDC; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #2A2C24; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #F4EEDC; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 620px; background-color: #FFFCF5; border: 1px solid rgba(120,133,104,0.3); border-radius: 12px; overflow: hidden; box-shadow: 0 8px 32px rgba(52,75,53,0.1);">
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="padding: 35px 25px 25px; background: linear-gradient(180deg, #344B35 0%, #3d5a3e 100%); border-bottom: 1px solid rgba(120,133,104,0.3);">
                            <div style="font-size: 26px; font-weight: 800; letter-spacing: 4px; color: #FFFCF5; text-transform: uppercase; font-family: Georgia, serif;">
                                EMBER <span style="color: #B99A65;">SPICE</span>
                            </div>
                            <div style="font-size: 10px; font-weight: 700; letter-spacing: 3px; color: #B86F48; text-transform: uppercase; margin-top: 4px;">
                                AUTHENTIC INDIAN DINING
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <div style="color: #B99A65; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px;">
                                ' . esc_html($eyebrow) . '
                            </div>
                            <h1 style="color: #344B35; font-family: Georgia, serif; font-size: 24px; font-weight: 700; margin: 0 0 16px 0; line-height: 1.3;">
                                ' . esc_html($heading) . '
                            </h1>
                            <div style="color: #777568; font-size: 15px; line-height: 1.7; margin-bottom: 25px;">
                                ' . $intro_html . '
                            </div>

                            ' . ($notice_html ? '
                            <div style="margin-bottom: 25px;">
                                ' . $notice_html . '
                            </div>' : '') . '

                            <!-- Details Table -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #F4EEDC; border: 1px solid rgba(120,133,104,0.25); border-radius: 8px; overflow: hidden; margin-bottom: 25px;">
                                ' . $rows_html . '
                            </table>

                            ' . ($cta_html ? '
                            <div style="margin-top: 25px;">
                                ' . $cta_html . '
                            </div>' : '') . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 30px; background-color: #344B35; border-top: 1px solid rgba(120,133,104,0.3); text-align: center; color: rgba(255,252,245,0.7); font-size: 12px; line-height: 1.8;">
                            <div style="color: #B99A65; font-weight: 600; margin-bottom: 6px;">
                                EMBER SPICE RESTAURANT
                            </div>
                            <div>
                                [Restaurant Address Placeholder • Food District] • [Phone: +91 98765 43210]
                            </div>
                            <div>
                                reservations@emberspice.local • http://localhost:8080/food/wordpress/
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.12); color: rgba(255,252,245,0.5); font-size: 11px;">
                                &copy; ' . $year . ' Ember Spice. All rights reserved. Where fire meets flavour.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
        return $html;
    }

    public function handle_reservation() {
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        $guests = intval($_POST['guests'] ?? 2);
        $requests = sanitize_textarea_field($_POST['requests'] ?? '');

        if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time)) {
            wp_send_json_error(['message' => 'Please fill in all required fields (Name, Email, Phone, Date, and Time).']);
        }

        $res_id = uniqid('res_');
        $timestamp = current_time('mysql');

        // Store reservation request safely in WordPress option
        $reservations = get_option('ember_spice_reservations', []);
        $reservations[] = [
            'id' => $res_id,
            'timestamp' => $timestamp,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'date' => $date,
            'time' => $time,
            'guests' => $guests,
            'requests' => $requests,
            'status' => 'Pending Confirmation'
        ];
        update_option('ember_spice_reservations', $reservations);

        $admin_email = get_option('admin_email');

        // 1. Send Admin Notification Email (with Reply-To set to Customer)
        $admin_subject = "[New Reservation Request] {$name} — {$guests} Guests on {$date} at {$time}";
        $admin_rows = [
            'Reference ID' => $res_id,
            'Guest Name' => $name,
            'Email Address' => $email,
            'Phone Number' => $phone,
            'Reservation Date' => $date,
            'Seating Time' => $time,
            'Number of Guests' => $guests . ' Guest(s)',
            'Special Requests' => $requests ?: 'None specified',
            'Submission Time' => $timestamp,
            'Current Status' => 'Pending Host Confirmation'
        ];
        $admin_notice = '<div style="background-color: rgba(185, 154, 101, 0.15); border: 1px solid rgba(185, 154, 101, 0.4); border-radius: 6px; padding: 14px 18px; color: #2A2C24; font-size: 13px; line-height: 1.6;"><strong style="color: #344B35;">⚡ Action Required:</strong> This booking request is currently <strong>Pending Confirmation</strong>. Please verify table allocation in the dining room and contact the guest to confirm.</div>';
        $admin_cta = '<div style="text-align: center;"><a href="mailto:' . esc_attr($email) . '?subject=' . rawurlencode("Regarding your table request at Ember Spice (Ref: {$res_id})") . '" style="display: inline-block; background-color: #B86F48; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 4px; font-weight: 600; font-size: 14px; margin-right: 10px;">Reply to Guest</a> <a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $phone)) . '" style="display: inline-block; background-color: transparent; border: 1px solid #344B35; color: #344B35; text-decoration: none; padding: 12px 24px; border-radius: 4px; font-weight: 600; font-size: 14px;">Call Guest</a></div>';
        
        $admin_html = $this->build_email_template(
            'NEW TABLE RESERVATION REQUEST',
            "Table Request: {$name} ({$guests} Guests)",
            "A visitor has submitted a new table reservation request on the Ember Spice website on <strong>{$timestamp}</strong>. All booking details are provided below.",
            $admin_rows,
            $admin_notice,
            $admin_cta
        );
        $admin_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>'
        ];
        wp_mail($admin_email, $admin_subject, $admin_html, $admin_headers);

        // 2. Send Customer Confirmation Email (Explicitly stating awaiting confirmation)
        $customer_subject = "Reservation Request Received — Ember Spice (Awaiting Host Confirmation)";
        $customer_rows = [
            'Booking Reference' => $res_id,
            'Guest Name' => $name,
            'Reservation Date' => $date,
            'Requested Time' => $time,
            'Party Size' => $guests . ' Guest(s)',
            'Contact Phone' => $phone,
            'Special Requests' => $requests ?: 'None specified',
            'Booking Status' => 'Pending Host Confirmation'
        ];
        $customer_notice = '<div style="background-color: rgba(184, 111, 72, 0.12); border: 1px solid rgba(184, 111, 72, 0.4); border-radius: 8px; padding: 18px 20px; color: #2A2C24; font-size: 14px; line-height: 1.7;"><div style="color: #B86F48; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">⏳ PLEASE NOTE: AWAITING HOST CONFIRMATION</div><div>This email confirms that your reservation request has been received by our booking desk. <strong>Your table is awaiting confirmation</strong> by our dining room hosts. Our team will review availability and contact you at <strong>' . esc_html($phone) . '</strong> or <strong>' . esc_html($email) . '</strong> within a few hours to officially confirm your booking.</div></div>';
        $customer_intro = "Dear {$name},<br><br>Thank you for choosing Ember Spice. We are delighted you wish to join us for an authentic royal dining experience slow-cooked over live charcoal hearths. Below is a summary of your requested table details.";
        $customer_cta = '<div style="color: #777568; font-size: 13px; line-height: 1.7; padding: 15px 20px; background-color: #FFFCF5; border-radius: 6px; border: 1px solid rgba(120, 133, 104, 0.25); text-align: left;"><strong style="color: #344B35;">Dining Policies & Guidelines:</strong><br>&bull; <em>Dress Code:</em> Smart casual to semi-formal attire is welcome.<br>&bull; <em>Grace Period:</em> Tables are held for up to 15 minutes past your reserved seating time.<br>&bull; <em>Questions or Changes:</em> If you need to modify your party size or time, please contact our host desk directly.</div>';

        $customer_html = $this->build_email_template(
            'RESERVATION REQUEST ACKNOWLEDGEMENT',
            "We Have Received Your Table Request, {$name}",
            $customer_intro,
            $customer_rows,
            $customer_notice,
            $customer_cta
        );
        $customer_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: Ember Spice <' . $admin_email . '>'
        ];
        wp_mail($email, $customer_subject, $customer_html, $customer_headers);

        wp_send_json_success([
            'message' => esc_html("Thank you, {$name}! Your table reservation request for {$guests} guest(s) on {$date} at {$time} has been received. An acknowledgement email has been sent to {$email}. Our host team will review table availability and contact you at {$phone} to confirm your booking.")
        ]);
    }

    public function handle_contact() {
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? 'General Inquiry');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(['message' => 'Please provide your Name, Email, and Message.']);
        }

        $msg_id = uniqid('msg_');
        $timestamp = current_time('mysql');

        $contacts = get_option('ember_spice_contacts', []);
        $contacts[] = [
            'id' => $msg_id,
            'timestamp' => $timestamp,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ];
        update_option('ember_spice_contacts', $contacts);

        $admin_email = get_option('admin_email');

        // 1. Send Admin Notification Email (with Reply-To set to Customer)
        $admin_subject = "[New Contact Inquiry] {$subject} — Ember Spice";
        $admin_rows = [
            'Reference ID' => $msg_id,
            'Guest Name' => $name,
            'Email Address' => $email,
            'Subject' => $subject,
            'Submission Time' => $timestamp,
            'Message' => $message
        ];
        $admin_cta = '<div style="text-align: center;"><a href="mailto:' . esc_attr($email) . '?subject=' . rawurlencode('Re: ' . $subject) . '" style="display: inline-block; background-color: #B86F48; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 4px; font-weight: 600; font-size: 14px;">Reply to Guest</a></div>';
        
        $admin_html = $this->build_email_template(
            'NEW WEBSITE INQUIRY',
            "Inquiry from {$name}: {$subject}",
            "A visitor has submitted a new inquiry via the website Contact Us form on <strong>{$timestamp}</strong>. You can reply directly to this email to respond to the guest.",
            $admin_rows,
            '',
            $admin_cta
        );
        $admin_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>'
        ];
        wp_mail($admin_email, $admin_subject, $admin_html, $admin_headers);

        // 2. Send Customer Confirmation Email
        $customer_subject = "Thank you for contacting Ember Spice — We have received your inquiry";
        $customer_rows = [
            'Inquiry Reference' => $msg_id,
            'Your Name' => $name,
            'Subject' => $subject,
            'Your Message' => $message,
            'Received At' => $timestamp
        ];
        $customer_notice = '<div style="background-color: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.35); border-radius: 6px; padding: 14px 18px; color: #86efac; font-size: 13px; line-height: 1.6;"><strong>✓ Message Received:</strong> Our guest concierge team typically reviews and replies to all inquiries within 2 to 4 hours during operating hours.</div>';
        $customer_intro = "Dear {$name},<br><br>Thank you for contacting Ember Spice. We have received your message regarding <strong>" . esc_html($subject) . "</strong> and our concierge team will get back to you promptly.";

        $customer_html = $this->build_email_template(
            'INQUIRY ACKNOWLEDGEMENT',
            "Thank you for contacting Ember Spice, {$name}",
            $customer_intro,
            $customer_rows,
            $customer_notice,
            ''
        );
        $customer_headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: Ember Spice <' . $admin_email . '>'
        ];
        wp_mail($email, $customer_subject, $customer_html, $customer_headers);

        wp_send_json_success([
            'message' => esc_html("Thank you, {$name}! Your message has been received. We have sent a confirmation email to {$email}. The Ember Spice guest concierge team will get back to you shortly.")
        ]);
    }

    public function register_admin_pages() {
        add_menu_page(
            'Ember Spice',
            'Ember Spice',
            'manage_options',
            'ember-spice-dashboard',
            [$this, 'render_admin_dashboard'],
            'dashicons-food',
            6
        );
        add_submenu_page(
            'ember-spice-dashboard',
            'Table Reservations',
            'Reservations',
            'manage_options',
            'ember-spice-dashboard',
            [$this, 'render_admin_dashboard']
        );
        add_submenu_page(
            'ember-spice-dashboard',
            'Contact Inquiries',
            'Inquiries',
            'manage_options',
            'ember-spice-inquiries',
            [$this, 'render_admin_inquiries']
        );
        add_submenu_page(
            'ember-spice-dashboard',
            'Email Delivery Logs',
            'Email Logs',
            'manage_options',
            'fluent-mail#/logs'
        );
    }

    public function render_admin_dashboard() {
        $reservations = array_reverse(get_option('ember_spice_reservations', []));
        ?>
        <div class="wrap" style="max-width: 1200px;">
            <h1 style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #344B35;">EMBER SPICE</span> &mdash; Guest Table Reservations
            </h1>
            <p>Manage and review incoming table reservation requests submitted via the frontend Reservations page.</p>

            <?php if (empty($reservations)): ?>
                <div class="notice notice-info" style="padding: 12px 15px;">
                    <p>No table reservations have been submitted yet. Test the reservation form at <a href="<?php echo esc_url(home_url('/reservations/')); ?>" target="_blank"><?php echo esc_url(home_url('/reservations/')); ?></a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Booking Time</th>
                            <th>Guest Name</th>
                            <th>Contact</th>
                            <th style="width: 80px;">Guests</th>
                            <th style="width: 160px;">Date &amp; Time</th>
                            <th>Special Requests</th>
                            <th style="width: 130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><small><?php echo esc_html($r['timestamp'] ?? 'N/A'); ?></small></td>
                                <td><strong><?php echo esc_html($r['name'] ?? ''); ?></strong></td>
                                <td>
                                    <div><a href="mailto:<?php echo esc_attr($r['email'] ?? ''); ?>"><?php echo esc_html($r['email'] ?? ''); ?></a></div>
                                    <div><small><?php echo esc_html($r['phone'] ?? ''); ?></small></div>
                                </td>
                                <td><span class="badge" style="background: #344B35; color: #FFFCF5; padding: 2px 8px; border-radius: 12px; font-weight: bold;"><?php echo intval($r['guests'] ?? 2); ?></span></td>
                                <td>
                                    <strong><?php echo esc_html($r['date'] ?? ''); ?></strong><br>
                                    <small><?php echo esc_html($r['time'] ?? ''); ?></small>
                                </td>
                                <td><?php echo esc_html($r['requests'] ?: '&mdash;'); ?></td>
                                <td>
                                    <span style="background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                        <?php echo esc_html($r['status'] ?? 'Pending'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_admin_inquiries() {
        $contacts = array_reverse(get_option('ember_spice_contacts', []));
        ?>
        <div class="wrap" style="max-width: 1200px;">
            <h1 style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #344B35;">EMBER SPICE</span> &mdash; Guest Contact Messages
            </h1>
            <p>Direct inquiries and guest feedback received through the website contact form.</p>

            <?php if (empty($contacts)): ?>
                <div class="notice notice-info" style="padding: 12px 15px;">
                    <p>No contact inquiries received yet. Test the contact form at <a href="<?php echo esc_url(home_url('/contact/')); ?>" target="_blank"><?php echo esc_url(home_url('/contact/')); ?></a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Received</th>
                            <th style="width: 160px;">Name</th>
                            <th style="width: 180px;">Email</th>
                            <th style="width: 160px;">Subject</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr>
                                <td><small><?php echo esc_html($c['timestamp'] ?? 'N/A'); ?></small></td>
                                <td><strong><?php echo esc_html($c['name'] ?? ''); ?></strong></td>
                                <td><a href="mailto:<?php echo esc_attr($c['email'] ?? ''); ?>"><?php echo esc_html($c['email'] ?? ''); ?></a></td>
                                <td><em><?php echo esc_html($c['subject'] ?? 'General Inquiry'); ?></em></td>
                                <td style="line-height: 1.5;"><?php echo nl2br(esc_html($c['message'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_header($atts = []) {
        $current_page_id = get_the_ID();
        $pages = [
            ['id' => 7, 'title' => 'Home', 'url' => home_url('/')],
            ['id' => 8, 'title' => 'About', 'url' => home_url('/about-us/')],
            ['id' => 9, 'title' => 'Our Menu', 'url' => home_url('/our-menu/')],
            ['id' => 10, 'title' => 'Reservations', 'url' => home_url('/reservations/')],
            ['id' => 11, 'title' => 'Gallery', 'url' => home_url('/gallery/')],
            ['id' => 12, 'title' => 'Blog', 'url' => home_url('/blog/')],
            ['id' => 13, 'title' => 'Contact', 'url' => home_url('/contact/')],
        ];

        ob_start();
        ?>
        <header class="ember-navbar">
            <div class="ember-nav-container">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ember-brand">
                    <svg class="ember-brand-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 23c-4.97 0-9-4.03-9-9 0-4.13 2.82-7.6 6.64-8.67-.32 1.4.15 2.87 1.25 3.73 1.1.86 2.65.86 3.75 0 1.1-.86 1.57-2.33 1.25-3.73C19.18 6.4 22 9.87 22 14c0 4.97-4.03 9-9 9zm0-19.5c-.6 0-1.15.22-1.58.62-.73.68-.9 1.76-.41 2.62.49.86 1.48 1.34 2.47 1.19.99-.15 1.76-.92 1.91-1.91.15-.99-.33-1.98-1.19-2.47-.36-.2-.77-.3-1.2-.3z"/>
                    </svg>
                    <div class="ember-brand-text">
                        <span class="ember-brand-name">EMBER <span>SPICE</span></span>
                        <span class="ember-brand-sub">AUTHENTIC INDIAN DINING</span>
                    </div>
                </a>

                <ul class="ember-nav-links">
                    <?php foreach ($pages as $p): ?>
                        <li>
                            <a href="<?php echo esc_url($p['url']); ?>" class="<?php echo ($current_page_id === $p['id']) ? 'active' : ''; ?>">
                                <?php echo esc_html($p['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="ember-nav-cta">
                    <a href="<?php echo esc_url(home_url('/reservations/')); ?>" class="ember-btn ember-btn-primary ember-btn-sm">
                        Reserve a Table
                    </a>
                </div>

                <button class="ember-mobile-toggle" aria-label="Toggle Navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        <?php
        return ob_get_clean();
    }

    public function render_footer() {
        ob_start();
        ?>
        <footer class="ember-footer">
            <div class="ember-footer-container">
                <div class="ember-footer-col">
                    <div class="ember-brand" style="margin-bottom: 1.25rem;">
                        <svg class="ember-brand-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 23c-4.97 0-9-4.03-9-9 0-4.13 2.82-7.6 6.64-8.67-.32 1.4.15 2.87 1.25 3.73 1.1.86 2.65.86 3.75 0 1.1-.86 1.57-2.33 1.25-3.73C19.18 6.4 22 9.87 22 14c0 4.97-4.03 9-9 9z"/>
                        </svg>
                        <div class="ember-brand-text">
                            <span class="ember-brand-name">EMBER <span>SPICE</span></span>
                            <span class="ember-brand-sub">WHERE FIRE MEETS FLAVOUR</span>
                        </div>
                    </div>
                    <p style="color: rgba(255,252,245,0.75); font-size: 0.95rem; line-height: 1.7; max-width: 320px;">
                        Celebrating the rich heritage of authentic royal Indian cuisine, slow-cooked over live charcoal embers with freshly stone-ground spices.
                    </p>
                    <div class="ember-social-icons" style="margin-top: 1.5rem;">
                        <a href="#" class="ember-social-btn" title="Instagram">IG</a>
                        <a href="#" class="ember-social-btn" title="Facebook">FB</a>
                        <a href="#" class="ember-social-btn" title="TripAdvisor">TA</a>
                    </div>
                </div>

                <div class="ember-footer-col">
                    <h4>Quick Links</h4>
                    <ul class="ember-footer-links">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                        <li><a href="<?php echo esc_url(home_url('/about-us/')); ?>">About Our Heritage</a></li>
                        <li><a href="<?php echo esc_url(home_url('/our-menu/')); ?>">Explore Menu</a></li>
                        <li><a href="<?php echo esc_url(home_url('/reservations/')); ?>">Table Reservations</a></li>
                        <li><a href="<?php echo esc_url(home_url('/gallery/')); ?>">Photo Gallery</a></li>
                        <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Culinary Stories</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact & Location</a></li>
                    </ul>
                </div>

                <div class="ember-footer-col">
                    <h4>Opening Hours</h4>
                    <ul class="ember-footer-links" style="line-height: 1.8;">
                        <li><strong>Monday – Thursday</strong><br>Lunch: 12:00 PM – 3:30 PM<br>Dinner: 6:30 PM – 11:00 PM</li>
                        <li><strong>Friday – Sunday</strong><br>Lunch: 12:00 PM – 4:00 PM<br>Dinner: 6:00 PM – 11:30 PM</li>
                    </ul>
                </div>

                <div class="ember-footer-col">
                    <h4>Connect & Inquiries</h4>
                    <p style="font-size: 0.9rem; line-height: 1.7; margin-bottom: 1rem;">
                        📍 <em>[Restaurant Address Placeholder — e.g. 108 Heritage Boulevard, Food District]</em><br>
                        📞 <em>[Phone Placeholder — e.g. +91 98765 43210]</em><br>
                        ✉️ <em>[Email Placeholder — reservations@emberspice.local]</em>
                    </p>
                    <h5 style="color: #FFFCF5; margin-bottom: 0.5rem; font-size: 0.95rem;">Join Our Connoisseur Circle</h5>
                    <form id="ember-newsletter-form" style="display: flex; gap: 0.5rem;">
                        <input type="email" placeholder="Your email address" class="ember-input" required style="padding: 0.6rem 0.8rem; font-size: 0.85rem;">
                        <button type="submit" class="ember-btn ember-btn-primary" style="padding: 0.6rem 1rem; font-size: 0.8rem;">Join</button>
                    </form>
                    <div id="newsletter-msg" style="display: none; font-size: 0.8rem;"></div>
                </div>
            </div>

            <div class="ember-footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> <strong>EMBER SPICE</strong>. All Rights Reserved. Crafted with passion.</div>
                <div style="color: rgba(255,252,245,0.6);">Authentic Indian Cuisine &#x2022; Natural Farm-to-Table Dining</div>
            </div>
        </footer>
        <?php
        return ob_get_clean();
    }

    public function render_menu() {
        $categories = [
            'starters' => [
                'title' => 'Starters',
                'desc' => 'Tempting appetizers to begin your culinary journey.',
                'items' => [
                    // Fish tikka: crispy spiced fish fillet
                    ['name' => 'Amritsari Machhi Tikka', 'type' => 'nonveg', 'price' => 545, 'desc' => 'Crispy river sole fillets marinated in crushed ajwain, yellow mustard and spiced gram flour.', 'img' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?auto=format&fit=crop&w=600&q=80'],
                    // Galouti kebab: minced meat patties on plate
                    ['name' => 'Galouti Kebab Sliders', 'type' => 'nonveg', 'price' => 595, 'desc' => 'Melt-in-mouth smoked lamb patties infused with 32 royal Awadhi spices, served on saffron sheermal.', 'img' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=600&q=80'],
                    // Murgh Malai Tikka: creamy white chicken kebab
                    ['name' => 'Murgh Malai Tikka', 'type' => 'nonveg', 'price' => 495, 'desc' => 'Tender chicken cubes bathed in rich mascarpone, cardamom, green chilies and toasted cashews.', 'img' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'veg-starters' => [
                'title' => 'Vegetarian Starters',
                'desc' => 'Handcrafted vegetarian delicacies bursting with regional spices.',
                'items' => [
                    // Dahi Ke Sholay: crispy golden fried rolls
                    ['name' => 'Dahi Ke Sholay', 'type' => 'veg', 'price' => 395, 'desc' => 'Golden fried bread pinwheels stuffed with hung yogurt, fresh pomegranate and roasted bell peppers.', 'img' => 'https://images.unsplash.com/photo-1601050690117-94f5f6fa8bd7?auto=format&fit=crop&w=600&q=80'],
                    // Bharwan Tandoori Aloo: stuffed potato appetizer
                    ['name' => 'Bharwan Tandoori Aloo', 'type' => 'veg', 'price' => 375, 'desc' => 'Crispy potato barrels filled with cottage cheese, golden raisins, and roasted nuts, charred in clay oven.', 'img' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&w=600&q=80'],
                    // Kurkuri Bhindi Chaat: crispy okra
                    ['name' => 'Kurkuri Bhindi Chaat', 'type' => 'veg', 'price' => 345, 'desc' => 'Shredded crispy okra dusted with hand-pounded amchur and chaat masala, laced with tamarind drizzle.', 'img' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'tandoor' => [
                'title' => 'Tandoori Specialties',
                'desc' => 'Roasted to perfection inside our authentic red clay tandoor oven over glowing charcoal.',
                'items' => [
                    // Peshawari Lamb Chops: charred lamb ribs on board
                    ['name' => 'Peshawari Lamb Chops', 'type' => 'nonveg', 'price' => 745, 'desc' => 'Tender ribs marinated overnight in raw papaya, malt vinegar and black stone flower spices.', 'img' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80'],
                    // Tandoori Jheenga: grilled jumbo prawns
                    ['name' => 'Tandoori Jheenga (Jumbo Prawns)', 'type' => 'nonveg', 'price' => 825, 'desc' => 'Succulent Arabian sea prawns glazed with roasted garlic, carom seeds and yellow chili butter.', 'img' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=600&q=80'],
                    // Angaar Paneer Tikka: paneer tikka on skewers
                    ['name' => 'Angaar Paneer Tikka', 'type' => 'veg', 'price' => 465, 'desc' => 'Artisanal cow milk cottage cheese cubes rubbed with degi mirch, mustard oil and kasoori methi.', 'img' => 'https://images.unsplash.com/photo-1567188040759-fb8a883dc6d8?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'veg-main' => [
                'title' => 'Vegetarian Main Course',
                'desc' => 'Rich curries cooked in cast iron and brass handis.',
                'items' => [
                    // Paneer Lababdar: rich tomato-based paneer curry
                    ['name' => 'Paneer Lababdar', 'type' => 'veg', 'price' => 485, 'desc' => 'Cottage cheese simmered in a luscious tomato and onion gravy topped with grated paneer and ginger juliennes.', 'img' => 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?auto=format&fit=crop&w=600&q=80'],
                    // Dal Makhani: black dal with butter
                    ['name' => 'Dal Ember (Signature Dal Makhani)', 'type' => 'veg', 'price' => 445, 'desc' => 'Whole black lentils slow-simmered for 24 hours on tandoor embers with churned country butter and cream.', 'img' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=600&q=80'],
                    // Subz Nizami Handi: mixed vegetable curry in handi
                    ['name' => 'Subz Nizami Handi', 'type' => 'veg', 'price' => 425, 'desc' => 'Garden-fresh vegetables and baby corn tossed in a rich cashew-spinach and aromatic spice sauce.', 'img' => 'https://images.unsplash.com/photo-1610192244261-3f33de3f55e4?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'nonveg-main' => [
                'title' => 'Non-Vegetarian Main Course',
                'desc' => 'Heritage meat and poultry delicacies cooked with imperial precision.',
                'items' => [
                    // Butter Chicken: iconic satin tomato gravy
                    ['name' => 'Butter Chicken 1947', 'type' => 'nonveg', 'price' => 565, 'desc' => 'Charcoal-grilled tandoori chicken pulled and simmered in velvet satin tomato gravy, honey and fenugreek.', 'img' => 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?auto=format&fit=crop&w=600&q=80'],
                    // Nalli Rogan Josh: deep red Kashmiri mutton curry
                    ['name' => 'Nalli Rogan Josh', 'type' => 'nonveg', 'price' => 675, 'desc' => 'Slow-braised mutton shanks infused with Kashmiri ratan jot, fennel seed powder and dried ginger.', 'img' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=80'],
                    // Goan Prawn Curry: coconut-based prawn curry
                    ['name' => 'Goan Prawn Curry', 'type' => 'nonveg', 'price' => 695, 'desc' => 'Plump prawns in a velvety coconut gravy flavored with kokum, red chilies, and crushed coriander.', 'img' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'biryani' => [
                'title' => 'Biryani and Rice',
                'desc' => 'Fragrant aged Daawat basmati rice layered with herbs and steam-cooked in clay handis.',
                'items' => [
                    // Dum Gosht Biryani: mutton dum biryani
                    ['name' => 'Dum Gosht Biryani', 'type' => 'nonveg', 'price' => 645, 'desc' => 'Succulent cuts of lamb layered with aged basmati, saffron, mint and fried onions, served with burani raita.', 'img' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=80'],
                    // Murgh Dum Biryani: chicken biryani
                    ['name' => 'Awadhi Murgh Dum Biryani', 'type' => 'nonveg', 'price' => 565, 'desc' => 'Tender chicken marinated in green herbs, steam-sealed under dough crust with kewra and rose water.', 'img' => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=600&q=80'],
                    // Subz Tarkari Biryani: vegetable biryani
                    ['name' => 'Subz Tarkari Biryani', 'type' => 'veg', 'price' => 475, 'desc' => 'Seasonal organic vegetables, green peas, and paneer gems cooked with saffron-kissed long grains.', 'img' => 'https://images.unsplash.com/photo-1633945274405-b6c8069047b0?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'breads' => [
                'title' => 'Indian Breads',
                'desc' => 'Slapped by hand and baked fresh against the fiery clay walls of our tandoor.',
                'items' => [
                    // Truffle & Cheese Naan: artisan stuffed naan
                    ['name' => 'Truffle & Cheese Naan', 'type' => 'veg', 'price' => 195, 'desc' => 'Artisanal leavened bread stuffed with aged cheddar and brushed with black truffle butter.', 'img' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=600&q=80'],
                    // Garlic & Coriander Naan: tandoori garlic naan
                    ['name' => 'Garlic & Coriander Naan', 'type' => 'veg', 'price' => 145, 'desc' => 'Topped with minced burnt garlic, fresh coriander leaves and salted Amul butter.', 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=80'],
                    // Laccha Paratha: flaky layered flatbread
                    ['name' => 'Laccha Paratha', 'type' => 'veg', 'price' => 135, 'desc' => 'Multi-layered flaky whole wheat bread baked crisp with pure desi ghee.', 'img' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'desserts' => [
                'title' => 'Desserts',
                'desc' => 'Decadent sweet creations honoring timeless Indian festive traditions.',
                'items' => [
                    // Gulab Jamun Tart: authentic gulab jamun
                    ['name' => 'Baked Gulab Jamun Tart', 'type' => 'veg', 'price' => 345, 'desc' => 'Warm khoya dumplings nestled in buttery pastry shell with saffron rabri and pistachio brittle.', 'img' => 'https://images.unsplash.com/photo-1666190092159-3171cf0fbb12?auto=format&fit=crop&w=600&q=80'],
                    // Kesar Pista Kulfi: saffron pistachio kulfi
                    ['name' => 'Kesar Pista Kulfi', 'type' => 'veg', 'price' => 295, 'desc' => 'Traditional dense pot-frozen milk dessert infused with cardamom, saffron threads and rose petals.', 'img' => 'https://images.unsplash.com/photo-1587314168485-3236d6710814?auto=format&fit=crop&w=600&q=80'],
                    // Shahi Tukda: royal bread pudding with rabri
                    ['name' => 'Shahi Tukda Brioche', 'type' => 'veg', 'price' => 325, 'desc' => 'Ghee-crisped brioche soaked in cardamom syrup, topped with silver leaf and thick saffron condensed milk.', 'img' => 'https://images.unsplash.com/photo-1541832676-9b763b0239ab?auto=format&fit=crop&w=600&q=80']
                ]
            ],
            'beverages' => [
                'title' => 'Beverages & Coolers',
                'desc' => 'Refreshing botanical infusions, artisanal lassis and spiced coolers.',
                'items' => [
                    // Kesar Mango Lassi: thick mango yogurt drink
                    ['name' => 'Kesar Mango Lassi', 'type' => 'veg', 'price' => 265, 'desc' => 'Thick creamy churned yogurt blended with Alphonso mango pulp, green cardamom and crushed pistachios.', 'img' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=600&q=80'],
                    // Masala Shikanji Soda: spiced fizzy lemonade
                    ['name' => 'Masala Shikanji Soda', 'type' => 'veg', 'price' => 195, 'desc' => 'Refreshing fizzy lemonade infused with roasted cumin, rock salt, mint leaves and fresh ginger juice.', 'img' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=600&q=80'],
                    // Royal Masala Chai: brewed Indian spiced tea
                    ['name' => 'Royal Masala Chai', 'type' => 'veg', 'price' => 175, 'desc' => 'Single-estate Assam black tea brewed with crushed green cardamom, cinnamon, cloves and buffalo milk.', 'img' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=600&q=80']
                ]
            ]
        ];

        ob_start();
        ?>
        <div class="ember-menu-tabs">
            <button class="ember-menu-tab-btn is-active" data-category="all">All Specialties</button>
            <?php foreach ($categories as $cat_key => $cat): ?>
                <button class="ember-menu-tab-btn" data-category="<?php echo esc_attr($cat_key); ?>">
                    <?php echo esc_html($cat['title']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="ember-menu-content-area">
            <?php foreach ($categories as $cat_key => $cat): ?>
                <div class="ember-menu-category" data-category="<?php echo esc_attr($cat_key); ?>">
                    <h3 class="ember-menu-category-title">
                        <span><?php echo esc_html($cat['title']); ?></span>
                    </h3>
                    <p style="color: var(--muted-text); margin-bottom: 2rem; font-style: italic;">
                        <?php echo esc_html($cat['desc']); ?>
                    </p>
                    <div class="ember-menu-grid">
                        <?php foreach ($cat['items'] as $item): ?>
                            <div class="ember-card">
                                <div class="ember-card-img-wrapper">
                                    <img src="<?php echo esc_url($item['img']); ?>" alt="<?php echo esc_attr($item['name']); ?>" class="ember-card-img" loading="lazy">
                                    <span class="ember-badge-<?php echo esc_attr($item['type']); ?>">
                                        <?php echo ($item['type'] === 'veg') ? 'Pure Veg' : 'Non-Veg'; ?>
                                    </span>
                                    <span class="ember-price-tag">₹<?php echo esc_html($item['price']); ?></span>
                                </div>
                                <div class="ember-card-body">
                                    <h4 class="ember-card-title"><?php echo esc_html($item['name']); ?></h4>
                                    <p class="ember-card-desc"><?php echo esc_html($item['desc']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_reservation_form() {
        ob_start();
        ?>
        <div class="ember-form-card">
            <div class="ember-section-header" style="margin-bottom: 2rem;">
                <span class="ember-eyebrow">RESERVE YOUR EXPERIENCE</span>
                <h3 class="ember-heading" style="font-size: 2.2rem; margin-bottom: 0.5rem;">Book a Table at Ember Spice</h3>
                <p class="ember-subheading" style="font-size: 0.95rem;">
                    Reserve your dining experience. Table requests are reviewed by our host team to ensure personal attention.
                </p>
            </div>

            <form id="ember-reservation-form">
                <div class="ember-form-grid">
                    <div class="ember-form-group">
                        <label class="ember-label" for="res-name">Full Name *</label>
                        <input type="text" id="res-name" name="name" class="ember-input" placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="res-email">Email Address *</label>
                        <input type="email" id="res-email" name="email" class="ember-input" placeholder="rahul@example.com" required>
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="res-phone">Phone Number *</label>
                        <input type="tel" id="res-phone" name="phone" class="ember-input" placeholder="+91 98765 43210" required>
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="res-guests">Number of Guests *</label>
                        <select id="res-guests" name="guests" class="ember-select" required>
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests (Table for Two)</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests (Family Table)</option>
                            <option value="5">5 Guests</option>
                            <option value="6">6 Guests</option>
                            <option value="8">8 Guests (Large Group)</option>
                            <option value="10">10+ Guests (Private Dining)</option>
                        </select>
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="res-date">Reservation Date *</label>
                        <input type="date" id="res-date" name="date" class="ember-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="res-time">Seating Time *</label>
                        <select id="res-time" name="time" class="ember-select" required>
                            <option value="12:00 PM">12:00 PM (Lunch)</option>
                            <option value="1:00 PM">1:00 PM (Lunch)</option>
                            <option value="2:00 PM">2:00 PM (Lunch)</option>
                            <option value="6:30 PM">6:30 PM (Dinner)</option>
                            <option value="7:30 PM" selected>7:30 PM (Dinner)</option>
                            <option value="8:30 PM">8:30 PM (Dinner)</option>
                            <option value="9:30 PM">9:30 PM (Dinner)</option>
                        </select>
                    </div>

                    <div class="ember-form-group full-width">
                        <label class="ember-label" for="res-requests">Special Requests / Dietary Preferences</label>
                        <textarea id="res-requests" name="requests" class="ember-textarea" placeholder="E.g. Anniversary celebration, quiet booth preference, mild spice, nut allergy..."></textarea>
                    </div>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="ember-btn ember-btn-primary ember-btn-lg" style="width: 100%;">
                        Submit Reservation Request
                    </button>
                    <p style="font-size: 0.8rem; color: var(--muted-text); margin-top: 0.8rem;">
                        <em>Note: Submission places a reservation request. You will receive an immediate confirmation once availability is verified.</em>
                    </p>
                </div>

                <div id="reservation-alert" class="ember-form-alert"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_contact_form() {
        ob_start();
        ?>
        <div class="ember-form-card">
            <div class="ember-section-header" style="margin-bottom: 2rem;">
                <span class="ember-eyebrow">GET IN TOUCH</span>
                <h3 class="ember-heading" style="font-size: 2.2rem; margin-bottom: 0.5rem;">Send Us a Message</h3>
                <p class="ember-subheading" style="font-size: 0.95rem;">
                    For private catering, banquet bookings, press inquiries or guest feedback.
                </p>
            </div>

            <form id="ember-contact-form">
                <div class="ember-form-grid">
                    <div class="ember-form-group">
                        <label class="ember-label" for="contact-name">Your Name *</label>
                        <input type="text" id="contact-name" name="name" class="ember-input" placeholder="Full Name" required>
                    </div>

                    <div class="ember-form-group">
                        <label class="ember-label" for="contact-email">Email Address *</label>
                        <input type="email" id="contact-email" name="email" class="ember-input" placeholder="name@domain.com" required>
                    </div>

                    <div class="ember-form-group full-width">
                        <label class="ember-label" for="contact-subject">Subject</label>
                        <input type="text" id="contact-subject" name="subject" class="ember-input" placeholder="E.g. Banquet booking / Catering query">
                    </div>

                    <div class="ember-form-group full-width">
                        <label class="ember-label" for="contact-message">Your Message *</label>
                        <textarea id="contact-message" name="message" class="ember-textarea" placeholder="How may we assist you?" required></textarea>
                    </div>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="ember-btn ember-btn-primary ember-btn-lg" style="width: 100%;">
                        Send Inquiry
                    </button>
                </div>

                <div id="contact-alert" class="ember-form-alert"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_gallery() {
        $gallery_items = [
            ['title' => 'Tandoori Sizzle Platter', 'cat' => 'Food Craft', 'img' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Warm Royal Dining Hall', 'cat' => 'Ambience', 'img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Live Clay Tandoor Hearth', 'cat' => 'Kitchen', 'img' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Royal Awadhi Dum Biryani', 'cat' => 'Food Craft', 'img' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Intimate Golden Booths', 'cat' => 'Ambience', 'img' => 'https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Chef Plating Dal Ember', 'cat' => 'Kitchen', 'img' => 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Artisanal Kebab Presentation', 'cat' => 'Food Craft', 'img' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Bar & Botanical Mocktails', 'cat' => 'Ambience', 'img' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=700&q=80'],
            ['title' => 'Fresh Tandoori Roti Baking', 'cat' => 'Kitchen', 'img' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=700&q=80']
        ];

        ob_start();
        ?>
        <div class="ember-gallery-grid">
            <?php foreach ($gallery_items as $item): ?>
                <div class="ember-gallery-item">
                    <img src="<?php echo esc_url($item['img']); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy">
                    <div class="ember-gallery-overlay">
                        <span class="ember-gallery-cat"><?php echo esc_html($item['cat']); ?></span>
                        <h4 class="ember-gallery-title"><?php echo esc_html($item['title']); ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_blog() {
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => 6,
            'post_status' => 'publish'
        ]);

        $featured_images = [
            14 => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=700&q=80',
            15 => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=700&q=80',
            16 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=700&q=80'
        ];

        ob_start();
        ?>
        <div class="ember-blog-grid">
            <?php foreach ($posts as $post): ?>
                <?php
                $img = $featured_images[$post->ID] ?? 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=700&q=80';
                $post_url = get_permalink($post->ID);
                ?>
                <article class="ember-blog-card">
                    <div class="ember-blog-img-wrap">
                        <a href="<?php echo esc_url($post_url); ?>">
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy">
                        </a>
                    </div>
                    <div class="ember-blog-body">
                        <div class="ember-blog-meta">
                            <span><?php echo get_the_date('M d, Y', $post); ?></span> <span style="margin: 0 0.4em; opacity:0.5;">&#x2022;</span> <span>CULINARY HERITAGE</span>
                        </div>
                        <h3 class="ember-blog-title">
                            <a href="<?php echo esc_url($post_url); ?>"><?php echo esc_html($post->post_title); ?></a>
                        </h3>
                        <p class="ember-blog-excerpt">
                            <?php echo esc_html(wp_trim_words($post->post_excerpt ?: $post->post_content, 22)); ?>
                        </p>
                        <a href="<?php echo esc_url($post_url); ?>" class="ember-blog-link">
                            Read Full Story <span>&rarr;</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function route_custom_templates($template) {
        if (is_home()) {
            $this->render_blog_archive_page();
            exit;
        } elseif (is_single()) {
            $this->render_single_post_page();
            exit;
        } elseif (is_singular() || is_front_page()) {
            $post_id = get_the_ID() ?: get_option('page_on_front');
            $page_template = get_post_meta($post_id, '_wp_page_template', true);
            if ($page_template === 'elementor_canvas') {
                $canvas_file = WP_PLUGIN_DIR . '/elementor/modules/page-templates/templates/canvas.php';
                if (file_exists($canvas_file)) {
                    return $canvas_file;
                }
            }
        }
        return $template;
    }

    private function render_blog_archive_page() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>The Ember Journal • Culinary Stories & Heritage • EMBER SPICE</title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('ember-spice-blog-archive'); ?>>
            <?php
            echo do_shortcode('[ember_spice_header]');
            ?>
            <section style="min-height: 48vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 5rem 2rem; background: linear-gradient(rgba(52, 75, 53, 0.78), rgba(52, 75, 53, 0.90)), url('https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; border-bottom: 1px solid rgba(120, 133, 104, 0.25);">
                <span style="color: #B99A65; font-size: 1rem; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 1rem; font-weight: 600;">STORIES OF FIRE &amp; HERITAGE</span>
                <h1 style="color: #FFFCF5; font-family: 'Playfair Display', Georgia, serif; font-size: 3.5rem; margin-bottom: 1.25rem;">The Ember Journal</h1>
                <p style="color: rgba(255, 252, 245, 0.85); font-size: 1.25rem; max-width: 680px; line-height: 1.8; margin: 0 auto;">Explore the deep-rooted secrets, regional gastronomic histories, and ancient cooking techniques that shape our daily culinary repertoire.</p>
            </section>

            <section style="padding: 4rem 2rem 1.5rem; background-color: var(--natural-cream); text-align: center;">
                <span style="color: #B86F48; font-size: 0.85rem; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 700;">FEATURED STORIES</span>
                <h2 style="color: #344B35; font-family: 'Playfair Display', Georgia, serif; font-size: 2.75rem; margin-bottom: 1rem;">Essays from Our Kitchen Hearth</h2>
                <p style="color: #777568; font-size: 1.15rem; max-width: 720px; margin: 0 auto; line-height: 1.8;">Delve into the soul of Indian gastronomy with insights, recipes, and heritage chronicles written by our master chefs and spice purveyors.</p>
            </section>

            <section style="padding: 2rem 2rem 6rem; max-width: 1280px; margin: 0 auto;">
                <?php echo do_shortcode('[ember_spice_blog]'); ?>
            </section>

            <?php
            echo do_shortcode('[ember_spice_footer]');
            wp_footer();
            ?>
        </body>
        </html>
        <?php
    }

    private function render_single_post_page() {
        $post = get_post();
        $featured_images = [
            14 => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=1200&q=80',
            15 => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=1200&q=80',
            16 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80'
        ];
        $hero_img = $featured_images[$post->ID] ?? 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=1200&q=80';
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($post->post_title); ?> • EMBER SPICE</title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('ember-spice-single-article'); ?>>
            <?php
            echo do_shortcode('[ember_spice_header]');
            ?>
            <article style="background-color: var(--natural-cream); min-height: 80vh;">
                <div style="padding: 5rem 2rem 3rem; background: linear-gradient(rgba(52, 75, 53, 0.80), rgba(52, 75, 53, 0.92)), url('<?php echo esc_url($hero_img); ?>') center/cover no-repeat; text-align: center; border-bottom: 1px solid rgba(120, 133, 104, 0.25);">
                    <div style="color: #B86F48; font-size: 0.9rem; letter-spacing: 3px; text-transform: uppercase; font-weight: 700; margin-bottom: 1rem;">
                        CULINARY HERITAGE • <?php echo get_the_date('F d, Y', $post); ?>
                    </div>
                    <h1 style="color: #FFFCF5; font-family: 'Playfair Display', Georgia, serif; font-size: 3.25rem; max-width: 900px; margin: 0 auto 1.5rem; line-height: 1.2;">
                        <?php echo esc_html($post->post_title); ?>
                    </h1>
                    <div style="color: #B99A65; font-size: 1rem; font-style: italic;">
                        Words by Executive Chef Vikramaditya Rathore
                    </div>
                </div>

                <div style="max-width: 860px; margin: 0 auto; padding: 4rem 2rem;">
                    <div style="border-radius: 8px; overflow: hidden; margin-bottom: 3rem; box-shadow: 0 8px 32px rgba(52,75,53,0.14); border: 1px solid rgba(120, 133, 104, 0.3);">
                        <img src="<?php echo esc_url($hero_img); ?>" alt="<?php echo esc_attr($post->post_title); ?>" style="width: 100%; height: auto; display: block;">
                    </div>

                    <div class="ember-single-content" style="color: #2A2C24; font-size: 1.15rem; line-height: 1.9;">
                        <?php echo wp_kses_post($post->post_content); ?>
                    </div>

                    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid rgba(120, 133, 104, 0.3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="ember-btn ember-btn-outline">
                            &larr; Back to All Stories
                        </a>
                        <a href="<?php echo esc_url(home_url('/reservations/')); ?>" class="ember-btn ember-btn-primary">
                            Reserve a Table at Ember Spice
                        </a>
                    </div>
                </div>
            </article>

            <?php
            echo do_shortcode('[ember_spice_footer]');
            wp_footer();
            ?>
        </body>
        </html>
        <?php
    }
}

EmberSpiceCore::get_instance();
