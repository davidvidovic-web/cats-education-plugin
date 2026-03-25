<?php
/**
 * Plugin Name: Cats Educations
 * Description: Custom Post Types and Block for Educations.
 * Version: 1.3
 * Author: David Vidovic
 * Author URI: https://davidvidovic.com
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Types
 */
function cats_educations_register_cpts() {
    // Register Edukacija Suza
    $args_suza = array(
        'labels' => array(
            'name' => 'Edukacije (Suza)',
            'singular_name' => 'Edukacija (Suza)',
        ),
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-welcome-learn-more',
    );
    register_post_type('edukacija-suza', $args_suza);

    // Register Edukacija Slađana
    $args_sladja = array(
        'labels' => array(
            'name' => 'Edukacije (Slađana)',
            'singular_name' => 'Edukacija (Slađana)',
        ),
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-welcome-learn-more',
    );
    register_post_type('edukacija-sladja', $args_sladja);
}
add_action('init', 'cats_educations_register_cpts');

/**
 * Settings Page
 */
function cats_educations_add_settings_page() {
    add_submenu_page(
        'options-general.php',
        'Cats Educations - Email Postavke',
        'Cats Educations',
        'manage_options',
        'cats-educations-settings',
        'cats_educations_render_settings_page'
    );
}
add_action('admin_menu', 'cats_educations_add_settings_page');

function cats_educations_register_settings() {
    register_setting('cats_educations_settings_group', 'cats_educations_suza_emails');
    register_setting('cats_educations_settings_group', 'cats_educations_sladja_emails');
}
add_action('admin_init', 'cats_educations_register_settings');

/**
 * Admin Notice for Missing Email Configuration
 */
function cats_educations_admin_notice() {
    // Only show to admins
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if on settings page - don't show notice there
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'cats-educations-settings') !== false) {
        return;
    }
    
    $suza_emails = get_option('cats_educations_suza_emails');
    $sladja_emails = get_option('cats_educations_sladja_emails');
    
    // Check if both are empty
    if (empty($suza_emails) && empty($sladja_emails)) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Cats Educations Plugin:</strong> 
                Email adrese za prijave nisu konfigurisane. 
                <a href="<?php echo admin_url('options-general.php?page=cats-educations-settings'); ?>">Kliknite ovdje da ih konfigurišete</a>.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'cats_educations_admin_notice');

/**
 * Handle Test Email Request
 */
function cats_educations_send_test_email() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_admin_referer('cats_test_email_nonce');
    
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $test_email = '';
    
    if ($type === 'suza') {
        $emails_option = get_option('cats_educations_suza_emails');
        $label = 'Suza';
    } elseif ($type === 'sladja') {
        $emails_option = get_option('cats_educations_sladja_emails');
        $label = 'Slađana';
    } else {
        wp_redirect(add_query_arg(array('page' => 'cats-educations-settings', 'test' => 'invalid'), admin_url('options-general.php')));
        exit;
    }
    
    // Parse emails
    $to_emails = [];
    if (!empty($emails_option)) {
        $emails = array_map('trim', explode(',', $emails_option));
        $to_emails = array_filter($emails, 'is_email');
    }
    
    if (empty($to_emails)) {
        wp_redirect(add_query_arg(array('page' => 'cats-educations-settings', 'test' => 'noemails'), admin_url('options-general.php')));
        exit;
    }
    
    // Send test email
    $subject = 'Test Email - Cats Educations Plugin';
    $message = "Ovo je test email iz Cats Educations plugina.\n\n";
    $message .= "Tip: $label edukacije\n";
    $message .= "Email poslan na: " . implode(', ', $to_emails) . "\n";
    $message .= "Datum: " . date('d.m.Y H:i:s') . "\n\n";
    $message .= "Ako vidite ovu poruku, vaša email konfiguracija radi ispravno!";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    $sent = wp_mail($to_emails, $subject, $message, $headers);
    
    if ($sent) {
        wp_redirect(add_query_arg(array('page' => 'cats-educations-settings', 'test' => 'success'), admin_url('options-general.php')));
    } else {
        wp_redirect(add_query_arg(array('page' => 'cats-educations-settings', 'test' => 'failed'), admin_url('options-general.php')));
    }
    exit;
}
add_action('admin_post_cats_test_email', 'cats_educations_send_test_email');

function cats_educations_render_settings_page() {
    // Get current values
    $suza_emails = get_option('cats_educations_suza_emails');
    $sladja_emails = get_option('cats_educations_sladja_emails');
    
    // Show status message after save
    if (isset($_GET['settings-updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Postavke su uspješno sačuvane!</strong></p></div>';
    }
    
    // Show test email results
    if (isset($_GET['test'])) {
        $test_result = sanitize_text_field($_GET['test']);
        if ($test_result === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Test email je uspješno poslan!</strong> Provjerite inbox.</p></div>';
        } elseif ($test_result === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p><strong>✗ Test email NIJE poslan!</strong> Provjerite WordPress debug.log za detalje. Možda trebate konfigurirati SMTP plugin.</p></div>';
        } elseif ($test_result === 'noemails') {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>⚠️ Nema konfiguriranih email adresa!</strong> Prvo sačuvajte validne email adrese.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>📧 Cats Educations - Email Postavke</h1>
        <p class="description">Konfigurirajte email adrese na koje će stizati prijave za edukacije. Možete dodati više email adresa odvojenih zarezom.</p>
        
        <form method="post" action="options.php">
            <?php settings_fields('cats_educations_settings_group'); ?>
            <?php do_settings_sections('cats_educations_settings_group'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="cats_educations_suza_emails">Email-ovi za Suza edukacije</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="cats_educations_suza_emails"
                               name="cats_educations_suza_emails" 
                               value="<?php echo esc_attr($suza_emails); ?>" 
                               class="regular-text" 
                               style="width: 100%; max-width: 600px;" 
                               placeholder="primjer@email.com, drugi@email.com" />
                        <p class="description">
                            Trenutno: <?php 
                            if (!empty($suza_emails)) {
                                $suza_array = array_map('trim', explode(',', $suza_emails));
                                $valid_suza = array_filter($suza_array, 'is_email');
                                echo '<strong>' . count($valid_suza) . ' validnih email(ova)</strong> - ' . esc_html(implode(', ', $valid_suza));
                                $invalid_suza = array_diff($suza_array, $valid_suza);
                                if (!empty($invalid_suza)) {
                                    echo '<br><span style="color: #d63638;">⚠️ Nevalidni: ' . esc_html(implode(', ', $invalid_suza)) . '</span>';
                                }
                                // Test email button
                                $test_url = wp_nonce_url(admin_url('admin-post.php?action=cats_test_email&type=suza'), 'cats_test_email_nonce');
                                echo '<br><br><a href="' . esc_url($test_url) . '" class="button button-secondary">📨 Pošalji Test Email</a>';
                            } else {
                                echo '<span style="color: #d63638;">Nije konfigurisano - koristi se default email</span>';
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">
                        <label for="cats_educations_sladja_emails">Email-ovi za Slađana edukacije</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="cats_educations_sladja_emails"
                               name="cats_educations_sladja_emails" 
                               value="<?php echo esc_attr($sladja_emails); ?>" 
                               class="regular-text" 
                               style="width: 100%; max-width: 600px;" 
                               placeholder="primjer@email.com, drugi@email.com" />
                        <p class="description">
                            Trenutno: <?php 
                            if (!empty($sladja_emails)) {
                                $sladja_array = array_map('trim', explode(',', $sladja_emails));
                                $valid_sladja = array_filter($sladja_array, 'is_email');
                                echo '<strong>' . count($valid_sladja) . ' validnih email(ova)</strong> - ' . esc_html(implode(', ', $valid_sladja));
                                $invalid_sladja = array_diff($sladja_array, $valid_sladja);
                                if (!empty($invalid_sladja)) {
                                    echo '<br><span style="color: #d63638;">⚠️ Nevalidni: ' . esc_html(implode(', ', $invalid_sladja)) . '</span>';
                                }
                                // Test email button
                                $test_url = wp_nonce_url(admin_url('admin-post.php?action=cats_test_email&type=sladja'), 'cats_test_email_nonce');
                                echo '<br><br><a href="' . esc_url($test_url) . '" class="button button-secondary">📨 Pošalji Test Email</a>';
                            } else {
                                echo '<span style="color: #d63638;">Nije konfigurisano - koristi se default email</span>';
                            }
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="description" style="background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa; margin-top: 20px;">
                <strong>💡 Napomena:</strong><br>
                • Odvojite više email adresa sa zarezom (,)<br>
                • Svaka prijava će biti poslana na sve konfigurisane email adrese<br>
                • Ako nisu konfigurisani email-ovi, sistem će koristiti default email: suzana.mladjenovic@icloud.com<br>
                • Koristite "Pošalji Test Email" dugme da provjerite da li email radi
            </p>
            
            <?php submit_button('Sačuvaj Postavke'); ?>
        </form>
        
        <hr style="margin: 40px 0;">
        
        <h2>🔧 Troubleshooting - Email ne stižu?</h2>
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
            <p><strong>Česte Probleme i Rješenja:</strong></p>
            <ol>
                <li>
                    <strong>Local Development (Local WP, XAMPP, MAMP):</strong><br>
                    PHP mail() funkcija često ne radi na local development okruženjima.<br>
                    <strong>Rješenje:</strong> Instalirajte SMTP plugin poput "WP Mail SMTP" ili "Post SMTP"
                </li>
                <li>
                    <strong>Emailovi idu u SPAM:</strong><br>
                    WordPress default email često završava u spam folderu.<br>
                    <strong>Rješenje:</strong> Koristite SMTP sa pravim email providerom (Gmail, SendGrid, Mailgun)
                </li>
                <li>
                    <strong>Provjera Debug Loga:</strong><br>
                    Idite na <code>/wp-content/debug.log</code> da vidite šta se dešava sa emailovima.<br>
                    Tražite linije koje počinju sa "CATS Education Plugin:"
                </li>
                <li>
                    <strong>Preporučeni SMTP Plugins:</strong><br>
                    • <a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a> (besplatan)<br>
                    • <a href="https://wordpress.org/plugins/post-smtp/" target="_blank">Post SMTP</a> (besplatan)<br>
                    • <a href="https://wordpress.org/plugins/easy-wp-smtp/" target="_blank">Easy WP SMTP</a> (besplatan)
                </li>
            </ol>
            <p style="margin-top: 15px;">
                <strong>📝 Debug Info:</strong> 
                WordPress verzija: <?php echo get_bloginfo('version'); ?> | 
                PHP verzija: <?php echo phpversion(); ?> |
                Server: <?php echo isset($_SERVER['SERVER_SOFTWARE']) ? esc_html($_SERVER['SERVER_SOFTWARE']) : 'Unknown'; ?>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Register Block
 */
function cats_educations_register_block() {
    register_block_type(__DIR__ . '/block.json', array(
        'render_callback' => 'cats_educations_render_callback'
    ));
}
add_action('init', 'cats_educations_register_block');

/**
 * Enqueue Assets
 */
function cats_educations_enqueue_assets() {
    wp_enqueue_script(
        'cats-educations-popup',
        plugin_dir_url(__FILE__) . 'assets/js/register-popup.js',
        array('jquery'),
        '1.7',
        true
    );

    wp_localize_script('cats-educations-popup', 'registerPopupAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('register_popup_nonce')
    ));

    wp_enqueue_style(
        'cats-educations-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        array(),
        '1.2'
    );
}
add_action('wp_enqueue_scripts', 'cats_educations_enqueue_assets');

/**
 * Block Render Callback
 */
function cats_educations_render_callback($attributes, $content) {
    if (!function_exists('get_field')) {
        return '<p>Please enable ACF plugin.</p>';
    }

    $za_koga = isset($attributes['za_koga']) ? $attributes['za_koga'] : '';

    // Determine post type logic
    if (empty($za_koga)) {
        $post_types = array('edukacija-suza', 'edukacija-sladja');
    } elseif (strtolower($za_koga) === 'suza') {
        $post_types = 'edukacija-suza';
    } elseif (strtolower($za_koga) === 'sladja') {
        $post_types = 'edukacija-sladja';
    } else {
        return '<p>Nema dostupnih edukacija za odabrani kriterij.</p>';
    }

    $args = array(
        'post_type'      => $post_types,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'aktivna',
                'value'   => '1',
                'compare' => '!='
            ),
            array(
                'key'     => 'aktivna',
                'compare' => 'NOT EXISTS'
            ),
        ),
    );

    $query = new WP_Query($args);
    $output = '<div class="edukacije-wrapper">';

    if ($query->have_posts()) {
        $post_count = 0;
        $posts_per_row = 3;

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Start a new row every 3 posts (Maintain structure)
            if ($post_count % $posts_per_row === 0) {
                if ($post_count > 0) {
                    $output .= '</div>'; // Close previous row
                }
                $output .= '<div class="wp-block-columns has-accent-5-background-color has-background is-layout-flex wp-container-core-columns-is-layout-28f84493 wp-block-columns-is-layout-flex" style="flex-wrap: wrap;">';
            }

            // Individual Card
            $output .= '<div class="wp-block-column has-base-color has-contrast-background-color has-text-color has-background has-link-color is-layout-flow" style="border-radius: 8px; padding: 20px 30px; margin-bottom: 20px; flex-basis: 30%; flex-grow: 1;">';

            // Naziv paketa
            $naziv_paketa = get_field('naziv_paketa', $post_id);
            if ($naziv_paketa) {
                $output .= '<h2 class="wp-block-heading has-text-align-center has-x-large-font-size" style="padding: 0;"><strong>' . esc_html($naziv_paketa) . '</strong></h2>';
            }

            // Naziv kursa
            $naziv_kursa = get_field('naziv_kursa', $post_id);
            if ($naziv_kursa) {
                $output .= '<p class="has-text-align-center has-medium-font-size">' . esc_html($naziv_kursa) . '</p>';
            }

            // Ukljucuje poklon paket
            $ukljucuje_poklon_paket = get_field('ukljucuje_poklon_paket', $post_id);
            if ($ukljucuje_poklon_paket && isset($ukljucuje_poklon_paket['ima_poklon_paket']) && $ukljucuje_poklon_paket['ima_poklon_paket']) {
                $sta_ukljucuje_paket = $ukljucuje_poklon_paket['sta_ukljucuje_paket'];
                if ($sta_ukljucuje_paket) {
                   $poklon_with_styles = preg_replace('/<p([^>]*)>/', '<p$1 class="has-text-align-center" style="font-size: 18px;">🎁 ', $sta_ukljucuje_paket);
                   $output .= $poklon_with_styles;
                }
            }

            $output .= '<div style="height: 20px" aria-hidden="true" class="wp-block-spacer"></div>';

            // Content Sections (Days)
            $content_sections = array();
            for ($i = 1; $i <= 3; $i++) {
                $dan_group = get_field('dan_' . $i, $post_id);
                if ($dan_group) {
                    $naslov_key = 'naslov_za_' . ($i == 1 ? 'prvi' : ($i == 2 ? 'drugi' : 'treci')) . '_dan';
                    $sadrzaj_key = ($i == 1 ? 'prvi' : ($i == 2 ? 'drugi' : 'treci')) . '_dan_sadrzaj';

                    $naslov = isset($dan_group[$naslov_key]) ? $dan_group[$naslov_key] : '';
                    $sadrzaj = isset($dan_group[$sadrzaj_key]) ? $dan_group[$sadrzaj_key] : '';

                    if ($naslov || $sadrzaj) {
                        $section_content = '';
                        if ($naslov) {
                            $section_content .= '<h4 style="font-size: 22px;"><strong>' . esc_html($naslov) . '</strong></h4>';
                        }
                        if ($sadrzaj) {
                             $sadrzaj_with_class = preg_replace('/<p([^>]*)>/', '<p$1 class="has-small-font-size">', $sadrzaj);
                             $section_content .= $sadrzaj_with_class;
                        }
                        $content_sections[] = $section_content;
                    }
                }
            }

            // Dodatno
            $dodatno_ukljucuje = get_field('dodatno_ukljucuje', $post_id);
            if ($dodatno_ukljucuje) {
                 if (strpos($dodatno_ukljucuje, 'class=') !== false) {
                     $dodatno_with_class = preg_replace('/<p([^>]*?)class="([^"]*)"([^>]*)>/', '<p$1class="$2 has-small-font-size"$3>', $dodatno_ukljucuje);
                 } else {
                     $dodatno_with_class = preg_replace('/<p([^>]*)>/', '<p$1 class="has-small-font-size">', $dodatno_ukljucuje);
                 }
                 $content_sections[] = $dodatno_with_class;
            }

            // Render Sections
            for ($i = 0; $i < count($content_sections); $i++) {
                $output .= $content_sections[$i];
                if ($i < count($content_sections) - 1) {
                    $output .= '<hr class="wp-block-separator has-text-color has-accent-5-color has-alpha-channel-opacity has-accent-5-background-color has-background" />';
                }
            }

            $output .= '<div style="height: 30px" aria-hidden="true" class="wp-block-spacer"></div>';

            // Prices
            $output .= '<div class="wp-block-group is-vertical is-content-justification-center" style="padding: 0; text-align: center;">';
            
            // Individual
            $cijena_individualne = get_field('cijena_individualne_edukacije', $post_id);
            if ($cijena_individualne) {
                $output .= '<p>Individualna</p>';
                $output .= '<p class="has-medium-font-size" style="margin-top: -20px;">datum po dogovoru</p>';

                // Discount Logic Individual
                $popust_individualne = get_field('iznos_popusta_individualne', $post_id);
                $formatted_price = number_format((float)$cijena_individualne, 0, '.', '.');

                if ($popust_individualne && is_numeric($popust_individualne) && $popust_individualne > 0) {
                    $nova_cijena = (float)$cijena_individualne - (float)$popust_individualne;
                    $formatted_nova_cijena = number_format($nova_cijena, 0, '.', '.');
                    
                    // Get discount dates
                    $popust_od = get_field('popust_od', $post_id);
                    $popust_do = get_field('popust_do', $post_id);
                    
                    $output .= '<div class="price-container" style="display: flex; flex-direction: column; align-items: center; gap: 5px;">';
                    $output .= '<div class="discount-badge" style="background: #e63946; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">VAUČER ' . number_format($popust_individualne, 0, '.', '.') . ' KM</div>';
                    
                    // Display discount dates if available
                    if ($popust_od && $popust_do) {
                        $output .= '<div class="discount-dates" style="font-size: 0.75em; color: #e63946; font-weight: bold; margin-top: -3px;">Važi: ' . esc_html($popust_od) . ' - ' . esc_html($popust_do) . '</div>';
                    } elseif ($popust_do) {
                        $output .= '<div class="discount-dates" style="font-size: 0.75em; color: #e63946; font-weight: bold; margin-top: -3px;">Važi do: ' . esc_html($popust_do) . '</div>';
                    }
                    
                    $output .= '<h2 class="wp-block-heading has-text-align-center" style="margin-bottom: 0;"><del style="color: #999; font-size: 0.7em; margin-right: 10px;">' . $formatted_price . ' KM</del> <strong>' . $formatted_nova_cijena . ' KM</strong></h2>';
                    $output .= '</div>';
                } else {
                    $output .= '<h2 class="wp-block-heading has-text-align-center"><strong>' . $formatted_price . ' KM</strong></h2>';
                }
                
                $output .= '<div style="height: 0px" aria-hidden="true" class="wp-block-spacer"></div>';
            }

            // Group
            $cijena_grupne = get_field('cijena_grupne_edukacije', $post_id);
            if ($cijena_grupne) {
                $output .= '<p>Grupna ( 2-3 osobe )</p>';

                $datum_od = get_field('datum_edukacije_od', $post_id);
                $datum_do = get_field('datum_edukacije_do', $post_id);
                $datum_od_2 = get_field('datum_edukacije_od_2', $post_id);
                $datum_do_2 = get_field('datum_edukacije_do_2', $post_id);
                
                $has_dates = $datum_do || ($datum_od && $datum_do) || $datum_do_2 || ($datum_od_2 && $datum_do_2);
                
                if ($has_dates) {
                    $output .= '<div style="display: flex; flex-direction: column; align-items: center; margin-top: -10px; margin-bottom: 10px; background-color: white; padding:5px 15px; border-radius: 8px;">';
                    if ($datum_do) {
                        $output .= '<div class="has-text-align-center has-link-color wp-block-post-date has-text-color" style="font-size: 20px; font-weight: bold; color: black;">';
                        if ($datum_od) $output .= esc_html($datum_od) . ' - ';
                        $output .= esc_html($datum_do);
                        $output .= '</div>';
                    }
                    if ($datum_do_2) {
                        $output .= '<div class="has-text-align-center has-link-color wp-block-post-date has-text-color" style="font-size: 20px; font-weight: bold; color: black;">';
                        if ($datum_od_2) $output .= esc_html($datum_od_2) . ' - ';
                        $output .= esc_html($datum_do_2);
                        $output .= '</div>';
                    }
                     $output .= '</div>';
                }

                 // Discount Logic Group
                 $popust_grupne = get_field('iznos_popusta_grupne', $post_id);
                 $formatted_price = number_format((float)$cijena_grupne, 0, '.', '.');

                 if ($popust_grupne && is_numeric($popust_grupne) && $popust_grupne > 0) {
                     $nova_cijena = (float)$cijena_grupne - (float)$popust_grupne;
                     $formatted_nova_cijena = number_format($nova_cijena, 0, '.', '.');
                     
                     // Get discount dates (already fetched above but get again to ensure we have them)
                     $popust_od = get_field('popust_od', $post_id);
                     $popust_do = get_field('popust_do', $post_id);
                     
                     $output .= '<div class="price-container" style="display: flex; flex-direction: column; align-items: center; gap: 5px;">';
                     $output .= '<div class="discount-badge" style="background: #e63946; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">VAUČER ' . number_format($popust_grupne, 0, '.', '.') . ' KM</div>';
                     
                     // Display discount dates if available
                     if ($popust_od && $popust_do) {
                         $output .= '<div class="discount-dates" style="font-size: 0.75em; color: #e63946; font-weight: bold; margin-top: -3px;">Važi: ' . esc_html($popust_od) . ' - ' . esc_html($popust_do) . '</div>';
                     } elseif ($popust_do) {
                         $output .= '<div class="discount-dates" style="font-size: 0.75em; color: #e63946; font-weight: bold; margin-top: -3px;">Važi do: ' . esc_html($popust_do) . '</div>';
                     }
                     
                     $output .= '<h2 class="wp-block-heading"><strong><del style="color: #999; font-size: 0.7em; margin-right: 10px;">' . $formatted_price . ' KM</del> ' . $formatted_nova_cijena . ' KM</strong></h2>';
                     $output .= '</div>';
                 } else {
                     $output .= '<h2 class="wp-block-heading"><strong>' . $formatted_price . ' KM</strong></h2>';
                 }

                 $output .= '<p class="has-medium-font-size">*po osobi</p>';
                 $output .= '<div style="height: 0px" aria-hidden="true" class="wp-block-spacer"></div>';
            }

            $output .= '</div>'; // end price group

            // Dates for Button Data
            $all_dates = array();
            if ($datum_do) {
                $all_dates[] = $datum_od ? ($datum_od . ' - ' . $datum_do) : $datum_do;
            }
            if ($datum_do_2) {
                 $all_dates[] = $datum_od_2 ? ($datum_od_2 . ' - ' . $datum_do_2) : $datum_do_2;
            }
            $combined_dates = implode(', ', $all_dates);

            // Discount Dates
            $popust_od = get_field('popust_od', $post_id);
            $popust_do = get_field('popust_do', $post_id);
            $discount_dates = '';
            if ($popust_od && $popust_do) {
                $discount_dates = $popust_od . ' - ' . $popust_do;
            } elseif ($popust_do) {
                $discount_dates = 'do ' . $popust_do;
            }
            
            // Discount Amounts
            $popust_individualne = get_field('iznos_popusta_individualne', $post_id);
            $popust_grupne = get_field('iznos_popusta_grupne', $post_id);
            $discount_individual = ($popust_individualne && is_numeric($popust_individualne) && $popust_individualne > 0) ? number_format($popust_individualne, 0, '.', '.') : '';
            $discount_group = ($popust_grupne && is_numeric($popust_grupne) && $popust_grupne > 0) ? number_format($popust_grupne, 0, '.', '.') : '';

            // Determine Owner
            $post_type = get_post_type($post_id);
            $owner = ($post_type === 'edukacija-suza') ? 'suza' : 'sladja';

            // Button with Enhanced Data Attributes
            $output .= '<div class="wp-block-buttons is-content-justification-center">';
            $output .= '<div class="wp-block-button is-style-outline">';
            $output .= '<a class="wp-block-button__link wp-element-button open-register-popup cats-edu-apply-btn" href="#" ';
            $output .= 'data-date="' . esc_attr($combined_dates) . '" ';
            $output .= 'data-heading="' . esc_attr($naziv_paketa) . '" ';
            $output .= 'data-desc="' . esc_attr($naziv_kursa) . '" ';
            $output .= 'data-owner="' . esc_attr($owner) . '" ';
            $output .= 'data-discount-dates="' . esc_attr($discount_dates) . '" ';
            $output .= 'data-discount-individual="' . esc_attr($discount_individual) . '" ';
            $output .= 'data-discount-group="' . esc_attr($discount_group) . '" ';
            $output .= '>PRIJAVI SE</a>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div style="height: 0px" aria-hidden="true" class="wp-block-spacer"></div>';

            $output .= '</div>'; // End Column

            $post_count++;
        }
        $output .= '</div>'; // End Row
    } else {
        $output .= '<div class="wp-block-columns has-accent-5-background-color has-background">';
        $output .= '<div class="wp-block-column" style="padding: 20px;"><p class="has-text-align-center">Nema dostupnih edukacija.</p></div>';
        $output .= '</div>';
    }

    $output .= '</div>'; // End Wrapper

    wp_reset_postdata();
    return $output;
}

/**
 * Handle Ajax Submission
 */
add_action('wp_ajax_register_popup_form', 'cats_educations_handle_form');
add_action('wp_ajax_nopriv_register_popup_form', 'cats_educations_handle_form');

function cats_educations_handle_form() {
    check_ajax_referer('register_popup_nonce', 'nonce');

    $fields = array(
        'name', 'instagram', 'email', 'phone', 
        'tip_kursa', 'popup_heading', 'popup_desc', 'education_date', 'discount_dates', 
        'discount_individual', 'discount_group', 'owner'
    );
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = sanitize_text_field($_POST[$field] ?? '');
    }

    // Validate required fields
    if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
        wp_send_json_error('Molimo popunite sva obavezna polja.');
        return;
    }

    // Determine Recipient based on owner
    $owner = strtolower(trim($data['owner']));
    $to_emails = [];
    $email_source = '';

    if ($owner === 'suza') {
        $option_val = get_option('cats_educations_suza_emails');
        $email_source = 'Suza settings';
    } elseif ($owner === 'sladja') {
        $option_val = get_option('cats_educations_sladja_emails');
        $email_source = 'Slađana settings';
    } else {
        $option_val = ''; 
        $email_source = 'Unknown owner';
    }

    // Parse and validate emails from settings
    if (!empty($option_val)) {
        $emails = array_map('trim', explode(',', $option_val));
        $to_emails = array_filter($emails, 'is_email');
    }

    // Fallback if no valid emails found in options
    if (empty($to_emails)) {
        $to_emails = array('suzana.mladjenovic@icloud.com');
        $email_source = 'Default fallback';
    }

    // Build email message
    $to = $to_emails;
    $subject = 'Nova prijava za edukaciju';
    $message = "Kurs: {$data['popup_heading']}\n";
    $message .= "Trajanje: {$data['popup_desc']}\n";
    if (!empty($data['education_date'])) {
        $message .= "Datum: {$data['education_date']}\n";
    }
    
    // Add discount information based on selected type
    $tip_kursa = strtolower(trim($data['tip_kursa']));
    if (!empty($data['discount_dates'])) {
        $message .= "Popust važi: {$data['discount_dates']}\n";
        
        // Show the appropriate discount amount based on type selected
        if ($tip_kursa === 'individualna' && !empty($data['discount_individual'])) {
            $message .= "Vaučer (Individualna): {$data['discount_individual']} KM\n";
        } elseif ($tip_kursa === 'grupna' && !empty($data['discount_group'])) {
            $message .= "Vaučer (Grupna): {$data['discount_group']} KM\n";
        }
    }
    
    $message .= "\n--- Podaci o polazniku ---\n";
    $message .= "Ime i prezime: {$data['name']}\n";
    $message .= "Email: {$data['email']}\n";
    $message .= "Telefon: {$data['phone']}\n";
    if (!empty($data['instagram'])) {
        $message .= "Instagram: {$data['instagram']}\n";
    }
    if (!empty($data['tip_kursa'])) {
        $message .= "Tip edukacije: " . ucfirst($data['tip_kursa']) . "\n";
    }

    // Headers
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    if (is_email($data['email'])) {
        $headers[] = 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>';
    }

    // Capture wp_mail errors
    $phpmailer_error = '';
    add_action('wp_mail_failed', function($error) use (&$phpmailer_error) {
        $phpmailer_error = $error->get_error_message();
    });

    // Send email
    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        wp_send_json_success('Prijava je uspješno poslana!');
    } else {
        $error_message = 'Došlo je do greške prilikom slanja emaila.';
        if ($phpmailer_error) {
            $error_message .= ' Detalji: ' . $phpmailer_error;
        }
        wp_send_json_error($error_message . ' Molimo pokušajte ponovo ili nas kontaktirajte direktno.');
    }
}

/**
 * Output Modal HTML in Footer
 */
function cats_educations_output_modal() {
    ?>
    <div id="register-popup">
        <div class="popup-content">
            <button class="close-popup">&times;</button>
            <form id="register-form">
                <h3>Prijava za Edukaciju</h3>
                
                <input type="hidden" name="popup_heading" value="">
                <input type="hidden" name="popup_desc" value="">
                <input type="hidden" name="education_date" value="">
                <input type="hidden" name="discount_dates" value="">
                <input type="hidden" name="discount_individual" value="">
                <input type="hidden" name="discount_group" value="">
                <input type="hidden" name="owner" value="">

                <div class="form-group">
                    <label for="name">Ime i Prezime *</label>
                    <input type="text" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon *</label>
                    <input type="tel" name="phone" id="phone" required>
                </div>

                <div class="form-group">
                    <label for="instagram">Instagram</label>
                    <input type="text" name="instagram" id="instagram">
                </div>

                <div class="form-group radio-group">
                    <label class="radio-group-label">Odaberite tip edukacije *</label>
                    <div class="radio-options">
                        <label class="radio-label">
                            <input type="radio" name="tip_kursa" value="individualna" required>
                            <span>Individualna</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="tip_kursa" value="grupna" required>
                            <span>Grupna</span>
                        </label>
                    </div>
                </div>

                <button type="submit">POTVRDI PRIJAVU</button>
            </form>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'cats_educations_output_modal');
