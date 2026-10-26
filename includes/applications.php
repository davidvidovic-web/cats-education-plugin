<?php
/**
 * Cats Educations - Applications Persistence & Admin Management
 * 
 * Handles saving education applications to the database (CPT: cats_prijava),
 * admin listing, filters, unread badges, status workflow, and email notifications.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load HTML Email Templates
require_once __DIR__ . '/email-templates.php';

/**
 * Register Custom Post Type for Applications
 */
function cats_educations_register_applications_cpt() {
    $labels = array(
        'name'                  => 'Prijave za edukacije',
        'singular_name'         => 'Prijava',
        'menu_name'             => 'Prijave za edukacije',
        'name_admin_bar'        => 'Prijava za edukaciju',
        'all_items'             => 'Sve prijave',
        'add_new'               => 'Dodaj novu prijavu',
        'add_new_item'          => 'Nova prijava za edukaciju',
        'edit_item'             => 'Pregled i obrada prijave',
        'view_item'             => 'Pregled prijave',
        'search_items'          => 'Pretraži prijave',
        'not_found'             => 'Nema pronađenih prijava.',
        'not_found_in_trash'    => 'Nema prijava u smeću.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-clipboard',
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array('title'),
        'show_in_rest'       => false,
    );

    register_post_type('cats_prijava', $args);
}
add_action('init', 'cats_educations_register_applications_cpt');

/**
 * Available Application Statuses
 */
function cats_educations_get_statuses() {
    return array(
        'nova' => array(
            'label' => 'Nova prijava',
            'color' => '#1e40af',
            'bg'    => '#dbeafe',
            'dot'   => '#2563eb',
        ),
        'kontaktirano' => array(
            'label' => 'Kontaktirano',
            'color' => '#92400e',
            'bg'    => '#fef3c7',
            'dot'   => '#d97706',
        ),
        'potvrdjeno' => array(
            'label' => 'Potvrđeno / Uplaćeno',
            'color' => '#166534',
            'bg'    => '#dcfce7',
            'dot'   => '#16a34a',
        ),
        'otkazano' => array(
            'label' => 'Otkazano',
            'color' => '#991b1b',
            'bg'    => '#fee2e2',
            'dot'   => '#dc2626',
        ),
    );
}

/**
 * Get Count of "Nova" (Unprocessed) Applications
 */
function cats_educations_get_new_count() {
    $query = new WP_Query(array(
        'post_type'      => 'cats_prijava',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_cats_prijava_status',
                'value'   => 'nova',
                'compare' => '=',
            ),
            array(
                'key'     => '_cats_prijava_status',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ));
    return (int) $query->found_posts;
}

/**
 * Add Unread Count Badge in Admin Sidebar Menu
 */
function cats_educations_add_menu_badge() {
    global $menu;
    $count = cats_educations_get_new_count();
    if ($count > 0 && is_array($menu)) {
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === 'edit.php?post_type=cats_prijava') {
                $badge = sprintf(' <span class="update-plugins count-%1$d" style="background:#2563eb;color:#fff;"><span class="plugin-count" style="color:#fff;">%1$d</span></span>', $count);
                $menu[$key][0] .= $badge;
                break;
            }
        }
    }
}
add_action('admin_menu', 'cats_educations_add_menu_badge', 99);

/**
 * Save Application Data into Database
 * 
 * @param array $data Form submission fields
 * @return int|WP_Error Post ID or error
 */
function cats_educations_save_application($data) {
    $name          = sanitize_text_field($data['name'] ?? '');
    $email         = sanitize_email($data['email'] ?? '');
    $phone         = sanitize_text_field($data['phone'] ?? '');
    $instagram     = sanitize_text_field($data['instagram'] ?? '');
    $tip_kursa     = sanitize_text_field($data['tip_kursa'] ?? '');
    $popup_heading = sanitize_text_field($data['popup_heading'] ?? '');
    $popup_desc    = sanitize_text_field($data['popup_desc'] ?? '');
    $edu_date      = sanitize_text_field($data['education_date'] ?? '');
    $owner         = strtolower(trim(sanitize_text_field($data['owner'] ?? '')));
    $disc_dates    = sanitize_text_field($data['discount_dates'] ?? '');
    $disc_indiv    = sanitize_text_field($data['discount_individual'] ?? '');
    $disc_group    = sanitize_text_field($data['discount_group'] ?? '');

    // Title format: "Ime Prezime – Paket / Naziv"
    $title_parts = array_filter(array($name, $popup_heading, $popup_desc));
    $post_title  = !empty($title_parts) ? implode(' – ', $title_parts) : 'Nova prijava';

    $post_id = wp_insert_post(array(
        'post_type'   => 'cats_prijava',
        'post_title'  => $post_title,
        'post_status' => 'publish',
    ));

    if (is_wp_error($post_id) || !$post_id) {
        return $post_id;
    }

    // Save metadata
    update_post_meta($post_id, '_cats_prijava_name', $name);
    update_post_meta($post_id, '_cats_prijava_email', $email);
    update_post_meta($post_id, '_cats_prijava_phone', $phone);
    update_post_meta($post_id, '_cats_prijava_instagram', $instagram);
    update_post_meta($post_id, '_cats_prijava_tip_kursa', $tip_kursa);
    update_post_meta($post_id, '_cats_prijava_heading', $popup_heading);
    update_post_meta($post_id, '_cats_prijava_desc', $popup_desc);
    update_post_meta($post_id, '_cats_prijava_date', $edu_date);
    update_post_meta($post_id, '_cats_prijava_owner', $owner);
    update_post_meta($post_id, '_cats_prijava_discount_dates', $disc_dates);
    update_post_meta($post_id, '_cats_prijava_discount_individual', $disc_indiv);
    update_post_meta($post_id, '_cats_prijava_discount_group', $disc_group);
    update_post_meta($post_id, '_cats_prijava_status', 'nova');
    update_post_meta($post_id, '_cats_prijava_notes', '');
    update_post_meta($post_id, '_cats_prijava_submitted_at', current_time('mysql'));

    return $post_id;
}

/**
 * Send Confirmation Email to Applicant
 */
function cats_educations_send_applicant_confirmation($post_id, $data) {
    $email = sanitize_email($data['email'] ?? '');
    if (!is_email($email)) {
        return false;
    }

    $subject = 'Potvrda prijave za edukaciju – Kozmetički salon Cats';
    $message = cats_educations_get_applicant_email_html($data);

    $from_name  = get_bloginfo('name') ?: 'Kozmetički salon Cats';
    $from_email = get_option('admin_email') ?: 'info@kozmetickisaloncats.com';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    );

    $sent = wp_mail($email, $subject, $message, $headers);
    update_post_meta($post_id, '_cats_prijava_email_applicant_sent', $sent ? '1' : '0');
    return $sent;
}

/**
 * Customize Columns in Admin List Table
 */
function cats_educations_table_columns($columns) {
    $new_columns = array(
        'cb'           => $columns['cb'],
        'status_badge' => 'Status',
        'title'        => 'Polaznik',
        'education'    => 'Edukacija',
        'instruktorka' => 'Instruktorka',
        'tip'          => 'Tip',
        'datum_kursa'  => 'Termin',
        'kontakt'      => 'Kontakt',
        'date'         => 'Datum prijave',
    );
    return $new_columns;
}
add_filter('manage_cats_prijava_posts_columns', 'cats_educations_table_columns');

/**
 * Render Custom Columns in Admin List Table
 */
function cats_educations_render_custom_columns($column, $post_id) {
    switch ($column) {
        case 'status_badge':
            $status   = get_post_meta($post_id, '_cats_prijava_status', true) ?: 'nova';
            $statuses = cats_educations_get_statuses();
            $conf     = $statuses[$status] ?? $statuses['nova'];
            printf(
                '<span class="cats-status-pill" style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:%s;color:%s;"><span style="width:7px;height:7px;border-radius:50%%;background:%s;display:inline-block;"></span>%s</span>',
                esc_attr($conf['bg']),
                esc_attr($conf['color']),
                esc_attr($conf['dot']),
                esc_html($conf['label'])
            );
            break;

        case 'education':
            $heading = get_post_meta($post_id, '_cats_prijava_heading', true);
            $desc    = get_post_meta($post_id, '_cats_prijava_desc', true);
            if ($heading) {
                echo '<strong style="font-size:13px;display:block;">' . esc_html($heading) . '</strong>';
            }
            if ($desc) {
                echo '<span style="color:#64748b;font-size:12px;">' . esc_html($desc) . '</span>';
            }
            break;

        case 'instruktorka':
            $owner = get_post_meta($post_id, '_cats_prijava_owner', true);
            if ($owner === 'suza') {
                echo '<span style="background:#fce7f3;color:#9d174d;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">Suzana</span>';
            } elseif ($owner === 'sladja') {
                echo '<span style="background:#e0f2fe;color:#075985;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">Slađana</span>';
            } else {
                echo '<span style="color:#94a3b8;">—</span>';
            }
            break;

        case 'tip':
            $tip = get_post_meta($post_id, '_cats_prijava_tip_kursa', true);
            if (strtolower($tip) === 'individualna') {
                echo '<span style="background:#f1f5f9;color:#334155;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;">Individualna</span>';
            } elseif (strtolower($tip) === 'grupna') {
                echo '<span style="background:#ede9fe;color:#5b21b6;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;">Grupna</span>';
            } else {
                echo esc_html($tip ?: '—');
            }
            break;

        case 'datum_kursa':
            $date = get_post_meta($post_id, '_cats_prijava_date', true);
            if ($date) {
                echo '<span style="font-weight:600;font-size:12px;color:#0f172a;">' . esc_html($date) . '</span>';
            } else {
                echo '<span style="color:#64748b;font-size:12px;">Po dogovoru</span>';
            }
            break;

        case 'kontakt':
            $phone = get_post_meta($post_id, '_cats_prijava_phone', true);
            $email = get_post_meta($post_id, '_cats_prijava_email', true);
            $ig    = get_post_meta($post_id, '_cats_prijava_instagram', true);

            echo '<div style="font-size:12px;line-height:1.6;">';
            if ($phone) {
                // Format phone for WhatsApp
                $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                if (substr($clean_phone, 0, 1) === '0') {
                    $clean_phone = '387' . substr($clean_phone, 1);
                }
                echo '<div>';
                echo '<a href="tel:' . esc_attr($phone) . '" style="font-weight:600;color:#0f172a;text-decoration:none;">' . esc_html($phone) . '</a> ';
                echo '<a href="https://wa.me/' . esc_attr($clean_phone) . '" target="_blank" title="WhatsApp chat" style="display:inline-block;padding:0 5px;background:#166534;color:#fff;border-radius:3px;font-size:10px;text-decoration:none;font-weight:bold;margin-left:4px;">WA</a>';
                echo '</div>';
            }
            if ($email) {
                echo '<div><a href="mailto:' . esc_attr($email) . '" style="color:#0284c7;text-decoration:none;">' . esc_html($email) . '</a></div>';
            }
            if ($ig) {
                $clean_ig = ltrim(trim($ig), '@');
                echo '<div><a href="https://instagram.com/' . esc_attr($clean_ig) . '" target="_blank" style="color:#475569;text-decoration:none;">@' . esc_html($clean_ig) . '</a></div>';
            }
            echo '</div>';
            break;
    }
}
add_action('manage_cats_prijava_posts_custom_column', 'cats_educations_render_custom_columns', 10, 2);

/**
 * Filter Views (Tabs) at the Top of List Table
 */
function cats_educations_filter_views($views) {
    global $wpdb;

    $statuses = cats_educations_get_statuses();
    $current  = isset($_GET['prijava_status']) ? sanitize_text_field($_GET['prijava_status']) : '';

    // Count all
    $total_count = wp_count_posts('cats_prijava')->publish;
    $class_all   = empty($current) ? ' class="current"' : '';
    $all_url     = admin_url('edit.php?post_type=cats_prijava');
    $views['all'] = '<a href="' . esc_url($all_url) . '"' . $class_all . '>Sve <span class="count">(' . $total_count . ')</span></a>';

    foreach ($statuses as $slug => $data) {
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'cats_prijava' AND p.post_status = 'publish'
             AND pm.meta_key = '_cats_prijava_status' AND pm.meta_value = %s",
            $slug
        ));

        // For 'nova', also include posts without status meta
        if ($slug === 'nova') {
            $no_meta_count = (int) $wpdb->get_var(
                "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
                 WHERE p.post_type = 'cats_prijava' AND p.post_status = 'publish'
                 AND p.ID NOT IN (
                     SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cats_prijava_status'
                 )"
            );
            $count += $no_meta_count;
        }

        $class = ($current === $slug) ? ' class="current"' : '';
        $url   = add_query_arg(array('post_type' => 'cats_prijava', 'prijava_status' => $slug), admin_url('edit.php'));
        $views[$slug] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url($url),
            $class,
            esc_html($data['label']),
            $count
        );
    }

    return $views;
}
add_filter('views_edit-cats_prijava', 'cats_educations_filter_views');

/**
 * Add Dropdown Filters (Instructor and Course Type)
 */
function cats_educations_add_table_filters($post_type) {
    if ($post_type !== 'cats_prijava') {
        return;
    }

    // Instruktorka Filter
    $selected_owner = isset($_GET['filter_owner']) ? sanitize_text_field($_GET['filter_owner']) : '';
    ?>
    <select name="filter_owner" id="filter_owner">
        <option value="">Sve instruktorke</option>
        <option value="suza" <?php selected($selected_owner, 'suza'); ?>>Suzana Mlađenović</option>
        <option value="sladja" <?php selected($selected_owner, 'sladja'); ?>>Slađana</option>
    </select>

    <?php
    // Tip Edukacije Filter
    $selected_tip = isset($_GET['filter_tip']) ? sanitize_text_field($_GET['filter_tip']) : '';
    ?>
    <select name="filter_tip" id="filter_tip">
        <option value="">Svi tipovi edukacije</option>
        <option value="individualna" <?php selected($selected_tip, 'individualna'); ?>>Individualna</option>
        <option value="grupna" <?php selected($selected_tip, 'grupna'); ?>>Grupna</option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'cats_educations_add_table_filters');

/**
 * Filter Query Based on Admin Dropdowns and Status Tabs
 */
function cats_educations_parse_admin_query($query) {
    global $pagenow;
    if ($pagenow !== 'edit.php' || !$query->is_main_query() || $query->get('post_type') !== 'cats_prijava') {
        return;
    }

    $meta_query = array('relation' => 'AND');

    // Status filter
    if (!empty($_GET['prijava_status'])) {
        $status = sanitize_text_field($_GET['prijava_status']);
        if ($status === 'nova') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_cats_prijava_status',
                    'value'   => 'nova',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_cats_prijava_status',
                    'compare' => 'NOT EXISTS',
                ),
            );
        } else {
            $meta_query[] = array(
                'key'     => '_cats_prijava_status',
                'value'   => $status,
                'compare' => '=',
            );
        }
    }

    // Owner filter
    if (!empty($_GET['filter_owner'])) {
        $meta_query[] = array(
            'key'     => '_cats_prijava_owner',
            'value'   => sanitize_text_field($_GET['filter_owner']),
            'compare' => '=',
        );
    }

    // Tip edukacije filter
    if (!empty($_GET['filter_tip'])) {
        $meta_query[] = array(
            'key'     => '_cats_prijava_tip_kursa',
            'value'   => sanitize_text_field($_GET['filter_tip']),
            'compare' => '=',
        );
    }

    if (count($meta_query) > 1) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('parse_query', 'cats_educations_parse_admin_query');

/**
 * Extend Admin Search to Search in Meta Fields
 */
function cats_educations_admin_search($search, $wp_query) {
    global $wpdb;

    if (!is_admin() || !$wp_query->is_main_query() || $wp_query->get('post_type') !== 'cats_prijava') {
        return $search;
    }

    $term = $wp_query->get('s');
    if (empty($term)) {
        return $search;
    }

    $term_like = '%' . $wpdb->esc_like($term) . '%';
    $search = $wpdb->prepare(
        " AND (
            {$wpdb->posts}.post_title LIKE %s
            OR EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} 
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
                AND {$wpdb->postmeta}.meta_key IN ('_cats_prijava_name', '_cats_prijava_email', '_cats_prijava_phone', '_cats_prijava_instagram', '_cats_prijava_heading', '_cats_prijava_desc')
                AND {$wpdb->postmeta}.meta_value LIKE %s
            )
        ) ",
        $term_like,
        $term_like
    );

    return $search;
}
add_filter('posts_search', 'cats_educations_admin_search', 10, 2);

/**
 * Register Meta Box for Single Application View / Edit
 */
function cats_educations_register_metabox() {
    add_meta_box(
        'cats_prijava_details',
        'Pregled i obrada prijave',
        'cats_educations_render_metabox',
        'cats_prijava',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cats_educations_register_metabox');

/**
 * Custom CSS for Application Edit Screen
 */
function cats_educations_admin_edit_styles() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen) {
        if ($screen->post_type !== 'cats_prijava' || !in_array($screen->base, array('post', 'post-new'), true)) {
            return;
        }
    } else {
        global $post, $pagenow;
        if (!in_array($pagenow, array('post.php', 'post-new.php'), true)) {
            return;
        }
        if (!$post || $post->post_type !== 'cats_prijava') {
            return;
        }
    }
    ?>
    <style>
        /* Hide WordPress post edit clutter */
        .post-type-cats_prijava #titlediv,
        .post-type-cats_prijava #submitdiv,
        .post-type-cats_prijava #pageparentdiv,
        .post-type-cats_prijava #slugdiv,
        .post-type-cats_prijava #authordiv,
        .post-type-cats_prijava #commentstatusdiv,
        .post-type-cats_prijava #commentsdiv,
        .post-type-cats_prijava #postbox-container-1 {
            display: none !important;
        }

        /* Full width content area */
        .post-type-cats_prijava #poststuff #post-body.columns-2 {
            margin-right: 0 !important;
        }
        .post-type-cats_prijava #poststuff #post-body-content {
            margin-bottom: 0 !important;
        }
        .post-type-cats_prijava #post-body.columns-2 #postbox-container-2 {
            margin-right: 0 !important;
        }

        /* Seamless Meta Box Container */
        .post-type-cats_prijava #cats_prijava_details {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .post-type-cats_prijava #cats_prijava_details > .postbox-header {
            display: none !important;
        }
        .post-type-cats_prijava #cats_prijava_details > .inside {
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Application CRM Styles */
        .cats-crm-wrap {
            max-width: 1100px;
            margin: 10px 0 40px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #121212;
        }
        .cats-crm-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #73716a;
            text-decoration: none;
            margin-bottom: 16px;
            transition: color 0.15s ease;
        }
        .cats-crm-back:hover {
            color: #121212;
        }

        /* Header Bar */
        .cats-crm-header {
            background: #ffffff;
            border: 1px solid #e7e5df;
            border-radius: 6px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .cats-crm-title-area h1 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #121212;
            line-height: 1.25;
        }
        .cats-crm-meta-line {
            font-size: 13px;
            color: #73716a;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cats-crm-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .cats-btn-primary {
            background: #121212 !important;
            color: #ffffff !important;
            border: 1px solid #121212 !important;
            border-radius: 4px !important;
            padding: 8px 18px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.15s ease;
        }
        .cats-btn-primary:hover {
            background: #2a2926 !important;
            color: #ffffff !important;
        }
        .cats-btn-secondary {
            background: #ffffff !important;
            color: #121212 !important;
            border: 1px solid #d5d3cb !important;
            border-radius: 4px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease;
        }
        .cats-btn-secondary:hover {
            border-color: #121212 !important;
            color: #121212 !important;
            background: #faf9f6 !important;
        }
        .cats-btn-wa-action {
            background: #166534 !important;
            color: #ffffff !important;
            border: 1px solid #166534 !important;
            border-radius: 4px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            text-decoration: none;
            display: inline-block;
        }
        .cats-btn-wa-action:hover {
            background: #14532d !important;
            color: #ffffff !important;
        }

        /* Grid Layout */
        .cats-crm-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
        }
        @media (max-width: 960px) {
            .cats-crm-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Cards */
        .cats-crm-card {
            background: #ffffff;
            border: 1px solid #e7e5df;
            border-radius: 6px;
            overflow: hidden;
        }
        .cats-crm-card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f0eee9;
            background: #faf9f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cats-crm-card-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #121212;
        }
        .cats-crm-card-body {
            padding: 24px;
        }

        /* Ledger Table */
        .cats-ledger {
            width: 100%;
            border-collapse: collapse;
        }
        .cats-ledger tr {
            border-bottom: 1px solid #f2f0ea;
        }
        .cats-ledger tr:last-child {
            border-bottom: none;
        }
        .cats-ledger td {
            padding: 12px 0;
            vertical-align: top;
            font-size: 14px;
        }
        .cats-ledger td.cats-label {
            width: 130px;
            color: #73716a;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding-right: 12px;
        }
        .cats-ledger td.cats-val {
            color: #121212;
            font-weight: 500;
        }

        /* Section divider inside card */
        .cats-section-divider {
            margin: 20px 0 14px 0;
            padding-top: 16px;
            border-top: 1px solid #e7e5df;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #8c8983;
        }

        /* Status Radio Cards */
        .cats-status-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .cats-status-item {
            position: relative;
        }
        .cats-status-item input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .cats-status-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid #e7e5df;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #4a4944;
            background: #ffffff;
            transition: all 0.15s ease;
        }
        .cats-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .cats-status-item input[type="radio"]:checked + .cats-status-label {
            border-color: #121212;
            color: #121212;
            background: #faf9f6;
            box-shadow: 0 0 0 1px #121212;
        }

        /* Textarea */
        .cats-notes-input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d5d3cb;
            border-radius: 4px;
            padding: 12px 14px;
            font-size: 14px;
            line-height: 1.55;
            color: #121212;
            min-height: 130px;
            font-family: inherit;
            resize: vertical;
        }
        .cats-notes-input:focus {
            outline: none;
            border-color: #121212;
            box-shadow: 0 0 0 1px #121212;
        }

        /* Email status rows */
        .cats-delivery-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 2px 8px;
            border-radius: 3px;
        }
        .cats-delivery-badge.ok {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .cats-delivery-badge.pending {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Footer actions */
        .cats-card-footer {
            padding: 16px 24px;
            border-top: 1px solid #f0eee9;
            background: #faf9f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cats-delete-link {
            color: #b91c1c;
            font-size: 13px;
            text-decoration: underline;
            cursor: pointer;
        }
        .cats-delete-link:hover {
            color: #7f1d1d;
        }
    </style>
    <?php
}
add_action('admin_head', 'cats_educations_admin_edit_styles');

/**
 * Customize Post Updated Messages for Applications
 */
function cats_educations_updated_messages($messages) {
    $messages['cats_prijava'] = array(
        0  => '',
        1  => 'Prijava je uspješno ažurirana.',
        4  => 'Prijava je ažurirana.',
        6  => 'Prijava je sačuvana.',
        7  => 'Prijava je sačuvana.',
    );
    return $messages;
}
add_filter('post_updated_messages', 'cats_educations_updated_messages');

/**
 * Render Single Application Meta Box (Full CRM Lead Sheet)
 */
function cats_educations_render_metabox($post) {
    wp_nonce_field('cats_prijava_metabox_nonce', 'cats_prijava_nonce');

    $name          = get_post_meta($post->ID, '_cats_prijava_name', true);
    $email         = get_post_meta($post->ID, '_cats_prijava_email', true);
    $phone         = get_post_meta($post->ID, '_cats_prijava_phone', true);
    $instagram     = get_post_meta($post->ID, '_cats_prijava_instagram', true);
    $tip_kursa     = get_post_meta($post->ID, '_cats_prijava_tip_kursa', true);
    $heading       = get_post_meta($post->ID, '_cats_prijava_heading', true);
    $desc          = get_post_meta($post->ID, '_cats_prijava_desc', true);
    $date          = get_post_meta($post->ID, '_cats_prijava_date', true);
    $owner         = get_post_meta($post->ID, '_cats_prijava_owner', true);
    $disc_dates    = get_post_meta($post->ID, '_cats_prijava_discount_dates', true);
    $disc_indiv    = get_post_meta($post->ID, '_cats_prijava_discount_individual', true);
    $disc_group    = get_post_meta($post->ID, '_cats_prijava_discount_group', true);
    $status        = get_post_meta($post->ID, '_cats_prijava_status', true) ?: 'nova';
    $notes         = get_post_meta($post->ID, '_cats_prijava_notes', true);
    $submitted_at  = get_post_meta($post->ID, '_cats_prijava_submitted_at', true) ?: $post->post_date;
    $salon_sent    = get_post_meta($post->ID, '_cats_prijava_email_salon_sent', true);
    $app_sent      = get_post_meta($post->ID, '_cats_prijava_email_applicant_sent', true);

    $statuses   = cats_educations_get_statuses();
    $current_status_conf = $statuses[$status] ?? $statuses['nova'];

    // Format clean phone for WhatsApp
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($clean_phone, 0, 1) === '0') {
        $clean_phone = '387' . substr($clean_phone, 1);
    }

    $instructor = ($owner === 'suza') ? 'Suzana Mlađenović' : 'Slađana';

    // Preview URLs
    $preview_app_url   = wp_nonce_url(admin_url('admin-post.php?action=cats_preview_email&type=applicant&id=' . $post->ID), 'cats_preview_email_nonce');
    $preview_salon_url = wp_nonce_url(admin_url('admin-post.php?action=cats_preview_email&type=salon&id=' . $post->ID), 'cats_preview_email_nonce');
    $delete_url        = get_delete_post_link($post->ID);
    ?>

    <div class="cats-crm-wrap">
        
        <!-- Breadcrumb back link -->
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=cats_prijava')); ?>" class="cats-crm-back">
            &larr; Sve prijave za edukacije
        </a>

        <!-- Top Header Bar -->
        <div class="cats-crm-header">
            <div class="cats-crm-title-area">
                <h1><?php echo esc_html($name ?: 'Neimenovana prijava'); ?></h1>
                <div class="cats-crm-meta-line">
                    <span style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:<?php echo esc_attr($current_status_conf['bg']); ?>;color:<?php echo esc_attr($current_status_conf['color']); ?>;">
                        <span style="width:6px;height:6px;border-radius:50%;background:<?php echo esc_attr($current_status_conf['dot']); ?>;"></span>
                        <?php echo esc_html($current_status_conf['label']); ?>
                    </span>
                    <span>&bull;</span>
                    <span>Broj prijave: #<?php echo $post->ID; ?></span>
                    <span>&bull;</span>
                    <span>Zaprimljeno: <?php echo esc_html(date_i18n('d. F Y. \u H:i', strtotime($submitted_at))); ?></span>
                </div>
            </div>

            <!-- Header Quick Action Buttons -->
            <div class="cats-crm-actions">
                <?php if ($clean_phone): ?>
                    <a href="https://wa.me/<?php echo esc_attr($clean_phone); ?>" target="_blank" class="cats-btn-wa-action">
                        WhatsApp chat
                    </a>
                    <a href="tel:<?php echo esc_attr($phone); ?>" class="cats-btn-secondary">
                        Pozovi
                    </a>
                <?php endif; ?>
                <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="cats-btn-secondary">
                        Email
                    </a>
                <?php endif; ?>
                <button type="submit" name="save" class="cats-btn-primary">
                    Sačuvaj izmjene
                </button>
            </div>
        </div>

        <!-- Two Column Grid -->
        <div class="cats-crm-grid">
            
            <!-- Left Column: Details Ledger -->
            <div class="cats-crm-card">
                <div class="cats-crm-card-header">
                    <h2>Podaci o prijavi i edukaciji</h2>
                    <span style="font-size: 12px; color: #8c8983; font-weight: 500;">
                        <?php echo esc_html($instructor); ?>
                    </span>
                </div>
                <div class="cats-crm-card-body">
                    
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: #8c8983; margin-bottom: 10px;">
                        Polaznik
                    </div>
                    <table class="cats-ledger">
                        <tr>
                            <td class="cats-label">Ime i prezime</td>
                            <td class="cats-val" style="font-size: 15px; font-weight: 700;">
                                <?php echo esc_html($name ?: '—'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Telefon</td>
                            <td class="cats-val">
                                <?php if ($phone): ?>
                                    <a href="tel:<?php echo esc_attr($phone); ?>" style="color: #121212; text-decoration: underline; font-weight: 600;">
                                        <?php echo esc_html($phone); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a3a099;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Email</td>
                            <td class="cats-val">
                                <?php if ($email): ?>
                                    <a href="mailto:<?php echo esc_attr($email); ?>" style="color: #121212; text-decoration: underline;">
                                        <?php echo esc_html($email); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a3a099;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Instagram</td>
                            <td class="cats-val">
                                <?php if ($instagram): 
                                    $clean_ig = ltrim(trim($instagram), '@'); ?>
                                    <a href="https://instagram.com/<?php echo esc_attr($clean_ig); ?>" target="_blank" style="color: #121212; text-decoration: underline; font-weight: 500;">
                                        @<?php echo esc_html($clean_ig); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a3a099;">Nije navedeno</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <div class="cats-section-divider">
                        Edukacija
                    </div>
                    <table class="cats-ledger">
                        <tr>
                            <td class="cats-label">Program</td>
                            <td class="cats-val" style="font-weight: 700;">
                                <?php echo esc_html($heading ?: '—'); ?>
                            </td>
                        </tr>
                        <?php if ($desc): ?>
                        <tr>
                            <td class="cats-label">Opis</td>
                            <td class="cats-val" style="color: #4a4944;">
                                <?php echo esc_html($desc); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="cats-label">Instruktorka</td>
                            <td class="cats-val">
                                <?php if ($owner === 'suza'): ?>
                                    <span style="background: #fdf2f8; color: #9d174d; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Suzana Mlađenović</span>
                                <?php elseif ($owner === 'sladja'): ?>
                                    <span style="background: #f0f9ff; color: #0369a1; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Slađana</span>
                                <?php else: ?>
                                    <span style="color: #a3a099;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Format</td>
                            <td class="cats-val">
                                <?php echo ucfirst(esc_html($tip_kursa ?: '—')); ?> edukacija
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Termin</td>
                            <td class="cats-val" style="font-weight: 600;">
                                <?php echo esc_html($date ?: 'Po dogovoru (individualni termin)'); ?>
                            </td>
                        </tr>
                        <?php if ($disc_dates || $disc_indiv || $disc_group): ?>
                        <tr>
                            <td class="cats-label">Pogodnost</td>
                            <td class="cats-val" style="color: #b91c1c; font-weight: 600;">
                                <?php
                                $val_amount = (strtolower($tip_kursa) === 'individualna') ? $disc_indiv : $disc_group;
                                if ($val_amount) {
                                    echo 'Vaučer ' . esc_html($val_amount) . ' KM';
                                }
                                if ($disc_dates) {
                                    echo ' (važi do: ' . esc_html($disc_dates) . ')';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                </div>
            </div>

            <!-- Right Column: Status & Processing -->
            <div class="cats-crm-card">
                <div class="cats-crm-card-header">
                    <h2>Obrada i bilješke salona</h2>
                </div>
                <div class="cats-crm-card-body">
                    
                    <!-- Status Selector Cards -->
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: #8c8983; margin-bottom: 10px;">
                        Status prijave
                    </div>
                    <div class="cats-status-options">
                        <?php foreach ($statuses as $s_key => $s_data): ?>
                            <div class="cats-status-item">
                                <input type="radio" 
                                       name="cats_prijava_status" 
                                       id="status_<?php echo esc_attr($s_key); ?>" 
                                       value="<?php echo esc_attr($s_key); ?>" 
                                       <?php checked($status, $s_key); ?>>
                                <label for="status_<?php echo esc_attr($s_key); ?>" class="cats-status-label">
                                    <span class="cats-status-dot" style="background: <?php echo esc_attr($s_data['dot']); ?>;"></span>
                                    <span><?php echo esc_html($s_data['label']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Internal Notes -->
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: #8c8983; margin-bottom: 8px;">
                        Interna napomena salona
                    </div>
                    <textarea name="cats_prijava_notes" 
                              id="cats_prijava_notes" 
                              class="cats-notes-input" 
                              placeholder="Upišite zabilješke o dogovoru (npr. kontaktirano 26.10., usaglašen termin za subotu, uplaćen depozit 100 KM...)..."><?php echo esc_textarea($notes); ?></textarea>

                    <!-- Email Delivery Status & Preview -->
                    <div class="cats-section-divider">
                        Email komunikacija
                    </div>
                    <table class="cats-ledger" style="margin-bottom: 16px;">
                        <tr>
                            <td class="cats-label">Notifikacija salona</td>
                            <td class="cats-val">
                                <?php if ($salon_sent === '1'): ?>
                                    <span class="cats-delivery-badge ok">Isporučeno</span>
                                <?php else: ?>
                                    <span class="cats-delivery-badge pending">Nije zabilježeno</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="cats-label">Potvrda polazniku</td>
                            <td class="cats-val">
                                <?php if ($app_sent === '1'): ?>
                                    <span class="cats-delivery-badge ok">Isporučeno</span>
                                <?php else: ?>
                                    <span class="cats-delivery-badge pending">Nije zabilježeno</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="<?php echo esc_url($preview_app_url); ?>" target="_blank" class="cats-btn-secondary" style="font-size: 12px; padding: 7px 12px;">
                            Pregledaj Email Polaznika
                        </a>
                        <a href="<?php echo esc_url($preview_salon_url); ?>" target="_blank" class="cats-btn-secondary" style="font-size: 12px; padding: 7px 12px;">
                            Pregledaj Email Salona
                        </a>
                    </div>

                </div>

                <!-- Footer with Save & Delete -->
                <div class="cats-card-footer">
                    <div>
                        <?php if ($delete_url): ?>
                            <a href="<?php echo esc_url($delete_url); ?>" class="cats-delete-link" onclick="return confirm('Da li ste sigurni da želite obrisati ovu prijavu?');">
                                Premjesti u smeće
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="save" class="cats-btn-primary">
                        Sačuvaj izmjene
                    </button>
                </div>

            </div>

        </div>

    </div>
    <?php
}

/**
 * Handle Live Email Preview in Admin
 */
function cats_educations_handle_email_preview() {
    if (!current_user_can('edit_posts')) {
        wp_die('Nemate dozvolu za pregled ovog emaila.');
    }

    check_admin_referer('cats_preview_email_nonce');

    $post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $type    = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'applicant';

    if (!$post_id) {
        wp_die('Nevažeći ID prijave.');
    }

    $data = array(
        'name'                => get_post_meta($post_id, '_cats_prijava_name', true),
        'email'               => get_post_meta($post_id, '_cats_prijava_email', true),
        'phone'               => get_post_meta($post_id, '_cats_prijava_phone', true),
        'instagram'           => get_post_meta($post_id, '_cats_prijava_instagram', true),
        'tip_kursa'           => get_post_meta($post_id, '_cats_prijava_tip_kursa', true),
        'popup_heading'       => get_post_meta($post_id, '_cats_prijava_heading', true),
        'popup_desc'          => get_post_meta($post_id, '_cats_prijava_desc', true),
        'education_date'      => get_post_meta($post_id, '_cats_prijava_date', true),
        'owner'               => get_post_meta($post_id, '_cats_prijava_owner', true),
        'discount_dates'      => get_post_meta($post_id, '_cats_prijava_discount_dates', true),
        'discount_individual' => get_post_meta($post_id, '_cats_prijava_discount_individual', true),
        'discount_group'      => get_post_meta($post_id, '_cats_prijava_discount_group', true),
    );

    if ($type === 'salon') {
        echo cats_educations_get_salon_email_html($post_id, $data);
    } else {
        echo cats_educations_get_applicant_email_html($data);
    }
    exit;
}
add_action('admin_post_cats_preview_email', 'cats_educations_handle_email_preview');

/**
 * Save Meta Box Data
 */
function cats_educations_save_metabox($post_id) {
    if (!isset($_POST['cats_prijava_nonce']) || !wp_verify_nonce($_POST['cats_prijava_nonce'], 'cats_prijava_metabox_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['cats_prijava_status'])) {
        $allowed = array_keys(cats_educations_get_statuses());
        $status  = sanitize_text_field($_POST['cats_prijava_status']);
        if (in_array($status, $allowed, true)) {
            update_post_meta($post_id, '_cats_prijava_status', $status);
        }
    }

    if (isset($_POST['cats_prijava_notes'])) {
        update_post_meta($post_id, '_cats_prijava_notes', sanitize_textarea_field($_POST['cats_prijava_notes']));
    }
}
add_action('save_post_cats_prijava', 'cats_educations_save_metabox');
