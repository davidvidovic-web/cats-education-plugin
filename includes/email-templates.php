<?php
/**
 * Cats Educations - Editorial HTML Email Templates
 * 
 * Clean, typography-driven, zero-emoji HTML email templates for:
 * 1. Applicant confirmation email
 * 2. Salon lead notification email
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Salon Email Logo URL (always ensures a publicly accessible URL for email clients)
 */
function cats_educations_get_email_logo_url() {
    $logo_url = get_option('woocommerce_email_header_image');
    if (!$logo_url) {
        $custom_logo_id = get_theme_mod('custom_logo') ?: get_option('site_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'medium');
        }
    }
    if (!$logo_url) {
        $logo_url = 'https://kozmetickisaloncats.com/wp-content/uploads/2022/04/cropped-Logo-pozitiv-Cats-club.png';
    }
    // If running in local development environment (.local / localhost), rewrite to live domain
    // so that external email clients (Gmail, Apple Mail, Outlook) can actually download and display the logo image
    if (strpos($logo_url, '.local') !== false || strpos($logo_url, 'localhost') !== false) {
        $logo_url = preg_replace('#^https?://[^/]+#', 'https://kozmetickisaloncats.com', $logo_url);
    }
    return $logo_url;
}

/**
 * Get Public Live Website URL for Emails
 */
function cats_educations_get_public_site_url() {
    $url = home_url('/');
    if (strpos($url, '.local') !== false || strpos($url, 'localhost') !== false) {
        return 'https://kozmetickisaloncats.com/';
    }
    return $url;
}

/**
 * Generate HTML Confirmation Email for Applicant
 * 
 * @param array $data Application data
 * @return string Complete HTML email
 */
function cats_educations_get_applicant_email_html($data) {
    $name          = esc_html($data['name'] ?? 'polaznice/če');
    $tip_kursa     = ucfirst(esc_html($data['tip_kursa'] ?? ''));
    $popup_heading = esc_html($data['popup_heading'] ?? '');
    $popup_desc    = esc_html($data['popup_desc'] ?? '');
    $edu_date      = esc_html($data['education_date'] ?? '');
    $phone         = esc_html($data['phone'] ?? '');
    $owner         = strtolower(trim($data['owner'] ?? ''));
    $instructor    = ($owner === 'suza') ? 'Suzana Mlađenović' : 'Slađana';
    $logo_url      = cats_educations_get_email_logo_url();
    $site_url      = cats_educations_get_public_site_url();

    // Voucher details
    $voucher_html = '';
    $tip_lower = strtolower($data['tip_kursa'] ?? '');
    if (!empty($data['discount_dates'])) {
        $disc_dates = esc_html($data['discount_dates']);
        $amount = '';
        if ($tip_lower === 'individualna' && !empty($data['discount_individual'])) {
            $amount = esc_html($data['discount_individual']);
        } elseif ($tip_lower === 'grupna' && !empty($data['discount_group'])) {
            $amount = esc_html($data['discount_group']);
        }
        if ($amount) {
            $validity = (stripos($disc_dates, 'do ') === 0) ? 'važi ' . $disc_dates : 'važi do ' . $disc_dates;
            $voucher_html = '
            <tr>
                <td style="padding: 12px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Pogodnost</td>
                <td style="padding: 12px 0; border-top: 1px solid #ebeae5; color: #b91c1c; font-size: 14px; font-weight: 600; vertical-align: top;">
                    Vaučer popust ' . $amount . ' KM (' . $validity . ')
                </td>
            </tr>';
        }
    }

    $termin_display = !empty($edu_date) ? $edu_date : 'Po dogovoru (individualni termin)';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="bs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Potvrda prijave za edukaciju – Kozmetički salon Cats</title>
        <style type="text/css">
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
            body { margin: 0; padding: 0; width: 100% !important; background-color: #f7f6f2; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing: antialiased; }
            @media only screen and (max-width: 600px) {
                .container { width: 100% !important; }
                .content-box { padding: 28px 20px !important; }
                .mobile-stack { display: block !important; width: 100% !important; text-align: left !important; }
                .btn-table { width: 100% !important; }
                .btn-table td { display: block !important; width: 100% !important; text-align: center !important; margin-bottom: 10px !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 36px 12px; background-color: #f7f6f2;">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    
                    <!-- Main Card -->
                    <table role="presentation" class="container" width="580" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #e7e5df; border-radius: 4px; overflow: hidden;">
                        
                        <!-- Header with subtle brand line -->
                        <tr>
                            <td style="padding: 36px 40px 24px 40px; border-bottom: 1px solid #f0eee9;" class="content-box">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="text-decoration: none; display: inline-block;">
                                                <?php if ($logo_url): ?>
                                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Kozmetički salon Cats" width="140" style="display: block; max-width: 140px; height: auto;">
                                                <?php else: ?>
                                                    <span style="font-size: 20px; font-weight: 700; letter-spacing: 0.05em; color: #121212;">CATS</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td align="right" style="vertical-align: middle;">
                                            <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; color: #8c8983;">
                                                Potvrda prijave
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Editorial Introduction -->
                        <tr>
                            <td style="padding: 36px 40px 24px 40px;" class="content-box">
                                <h1 style="margin: 0 0 14px 0; font-size: 22px; font-weight: 700; line-height: 1.35; color: #121212; letter-spacing: -0.01em;">
                                    Prijava za edukaciju je zaprimljena.
                                </h1>
                                <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4a4944;">
                                    Poštovana/i <?php echo $name; ?>,<br><br>
                                    Hvala Vam na ukazanom povjerenju i interesovanju za stručne edukacije u Kozmetičkom salonu Cats. Vaša prijava je uspješno zabilježena.
                                </p>
                            </td>
                        </tr>

                        <!-- Specification Table (Clean Ledger Style) -->
                        <tr>
                            <td style="padding: 0 40px 32px 40px;" class="content-box">
                                <div style="margin-bottom: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8c8983;">
                                    Pregled odabranog programa
                                </div>
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid #121212; border-bottom: 1px solid #121212;">
                                    <?php if ($popup_heading): ?>
                                    <tr>
                                        <td style="padding: 14px 0 10px 0; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Program</td>
                                        <td style="padding: 14px 0 10px 0; color: #121212; font-size: 15px; font-weight: 700; vertical-align: top;">
                                            <?php echo $popup_heading; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php if ($popup_desc): ?>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Opis</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #2e2d29; font-size: 14px; vertical-align: top;">
                                            <?php echo $popup_desc; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Instruktorka</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px; font-weight: 600; vertical-align: top;">
                                            <?php echo esc_html($instructor); ?>
                                        </td>
                                    </tr>

                                    <?php if ($tip_kursa): ?>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Format</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px; font-weight: 500; vertical-align: top;">
                                            <?php echo $tip_kursa; ?> edukacija
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Termin</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px; font-weight: 600; vertical-align: top;">
                                            <?php echo $termin_display; ?>
                                        </td>
                                    </tr>

                                    <?php echo $voucher_html; ?>
                                </table>
                            </td>
                        </tr>

                        <!-- Next Steps (Prose Style, No Gimmicks) -->
                        <tr>
                            <td style="padding: 0 40px 32px 40px;" class="content-box">
                                <div style="margin-bottom: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8c8983;">
                                    Naredni koraci
                                </div>
                                <p style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.6; color: #4a4944;">
                                    Naš tim trenutno provjerava raspored i slobodna mjesta za navedeni termin. Uskoro ćemo Vas kontaktirati putem navedenog broja telefona (<strong><?php echo $phone ?: 'Vaš telefon'; ?></strong>) radi konačnog usaglašavanja datuma, odgovora na sva Vaša pitanja i dostave detaljnog plana rada.
                                </p>
                                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #4a4944;">
                                    Ukoliko Vam je potrebna brza informacija ili želite odmah rezervisati termin, možete nas kontaktirati direktno putem telefona ili porukom.
                                </p>
                            </td>
                        </tr>

                        <!-- Clean Actions -->
                        <tr>
                            <td style="padding: 0 40px 36px 40px;" class="content-box">
                                <table role="presentation" border="0" cellspacing="0" cellpadding="0" class="btn-table">
                                    <tr>
                                        <td align="left" style="padding-right: 12px;">
                                            <a href="https://wa.me/38765242222" target="_blank" style="background-color: #121212; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; padding: 12px 24px; border-radius: 3px; display: inline-block;">
                                                WhatsApp poruka
                                            </a>
                                        </td>
                                        <td align="left">
                                            <a href="tel:+38765242222" style="background-color: transparent; border: 1px solid #121212; color: #121212; text-decoration: none; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; padding: 11px 22px; border-radius: 3px; display: inline-block;">
                                                Pozovite 065/242-222
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Editorial Signature -->
                        <tr>
                            <td style="padding: 0 40px 36px 40px;" class="content-box">
                                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #4a4944;">
                                    Srdačan pozdrav,<br>
                                    <strong style="color: #121212;">Kozmetički salon Cats</strong>
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding: 24px 40px; background-color: #faf9f6; border-top: 1px solid #ebeae5; color: #76746e; font-size: 12px; line-height: 1.65;" class="content-box">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="mobile-stack" style="vertical-align: top; padding-bottom: 8px;">
                                            <strong style="color: #121212;">Kozmetički salon Cats</strong><br>
                                            Dr. Mladena Stojanovića 119, 78000 Banja Luka<br>
                                            Ponedeljak – Petak: 08:00 – 20:00 &nbsp;|&nbsp; Subota: 08:00 – 14:00
                                        </td>
                                        <td class="mobile-stack" align="right" style="vertical-align: top;">
                                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="color: #121212; text-decoration: none; font-weight: 600; display: block; margin-bottom: 4px;">kozmetickisaloncats.com</a>
                                            <a href="https://www.instagram.com/salon_cats_club/" target="_blank" style="color: #76746e; text-decoration: underline;">@salon_cats_club</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                    
                    <!-- Quiet Colophon -->
                    <table role="presentation" width="580" class="container" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td align="center" style="padding: 16px 0; font-size: 11px; color: #a3a099;">
                                Kozmetički salon Cats Banja Luka
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Generate HTML Lead Notification Email for Salon
 * 
 * @param int $post_id Application post ID
 * @param array $data Application data
 * @return string Complete HTML email
 */
function cats_educations_get_salon_email_html($post_id, $data) {
    $name          = esc_html($data['name'] ?? 'Nepoznato');
    $email         = esc_html($data['email'] ?? '');
    $phone         = esc_html($data['phone'] ?? '');
    $instagram     = esc_html($data['instagram'] ?? '');
    $tip_kursa     = ucfirst(esc_html($data['tip_kursa'] ?? ''));
    $popup_heading = esc_html($data['popup_heading'] ?? '');
    $popup_desc    = esc_html($data['popup_desc'] ?? '');
    $edu_date      = esc_html($data['education_date'] ?? '');
    $owner         = strtolower(trim($data['owner'] ?? ''));
    $instructor    = ($owner === 'suza') ? 'Suzana Mlađenović' : 'Slađana';
    $logo_url      = cats_educations_get_email_logo_url();
    $admin_url     = admin_url('post.php?post=' . $post_id . '&action=edit');
    $site_url      = cats_educations_get_public_site_url();

    // Clean phone for WhatsApp
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($clean_phone, 0, 1) === '0') {
        $clean_phone = '387' . substr($clean_phone, 1);
    }

    // Voucher details
    $voucher_html = '';
    $tip_lower = strtolower($data['tip_kursa'] ?? '');
    if (!empty($data['discount_dates'])) {
        $disc_dates = esc_html($data['discount_dates']);
        $amount = '';
        if ($tip_lower === 'individualna' && !empty($data['discount_individual'])) {
            $amount = esc_html($data['discount_individual']);
        } elseif ($tip_lower === 'grupna' && !empty($data['discount_group'])) {
            $amount = esc_html($data['discount_group']);
        }
        if ($amount) {
            $validity = (stripos($disc_dates, 'do ') === 0) ? 'važi ' . $disc_dates : 'važi do ' . $disc_dates;
            $voucher_html = '
            <tr>
                <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 140px; vertical-align: top;">Vaučer</td>
                <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #b91c1c; font-size: 14px; font-weight: 600; vertical-align: top;">
                    ' . $amount . ' KM (' . $validity . ')
                </td>
            </tr>';
        }
    }

    $termin_display = !empty($edu_date) ? $edu_date : 'Po dogovoru';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="bs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Nova prijava: <?php echo $name; ?></title>
        <style type="text/css">
            body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
            body { margin: 0; padding: 0; width: 100% !important; background-color: #f7f6f2; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing: antialiased; }
            @media only screen and (max-width: 600px) {
                .container { width: 100% !important; }
                .content-box { padding: 28px 20px !important; }
                .btn-block { display: block !important; width: 100% !important; text-align: center !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 36px 12px; background-color: #f7f6f2;">
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    
                    <!-- Main Card -->
                    <table role="presentation" class="container" width="580" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #e7e5df; border-radius: 4px; overflow: hidden;">
                        
                        <!-- Header -->
                        <tr>
                            <td style="padding: 32px 40px 20px 40px; border-bottom: 1px solid #f0eee9;" class="content-box">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($site_url); ?>" target="_blank" style="text-decoration: none;">
                                                <?php if ($logo_url): ?>
                                                    <img src="<?php echo esc_url($logo_url); ?>" alt="Cats" width="120" style="display: block; max-width: 120px; height: auto;">
                                                <?php else: ?>
                                                    <span style="font-size: 18px; font-weight: 700; color: #121212;">CATS</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td align="right">
                                            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #b91c1c;">
                                                Nova prijava
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Headline & Lead -->
                        <tr>
                            <td style="padding: 32px 40px 24px 40px;" class="content-box">
                                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8c8983; margin-bottom: 8px;">
                                    Obavijest za salon
                                </div>
                                <h1 style="margin: 0 0 10px 0; font-size: 22px; font-weight: 700; color: #121212; letter-spacing: -0.01em;">
                                    <?php echo $name; ?>
                                </h1>
                                <p style="margin: 0; font-size: 14px; line-height: 1.55; color: #5a5953;">
                                    Zaprimljena je nova prijava za edukaciju i sačuvana u WordPress bazi pod brojem <strong>#<?php echo $post_id; ?></strong>.
                                </p>
                            </td>
                        </tr>

                        <!-- Primary CTA Button -->
                        <tr>
                            <td style="padding: 0 40px 28px 40px;" class="content-box">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td align="center" style="background-color: #121212; border-radius: 3px; padding: 13px 20px;">
                                            <a href="<?php echo esc_url($admin_url); ?>" target="_blank" style="color: #ffffff; font-size: 14px; font-weight: 600; letter-spacing: 0.04em; text-decoration: none; display: block;">
                                                Otvori prijavu u WordPress panelu
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Polaznik Specification -->
                        <tr>
                            <td style="padding: 0 40px 24px 40px;" class="content-box">
                                <div style="margin-bottom: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8c8983;">
                                    Podaci o polazniku
                                </div>
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid #121212; border-bottom: 1px solid #121212;">
                                    <tr>
                                        <td style="padding: 12px 0 8px 0; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 130px;">Ime i prezime</td>
                                        <td style="padding: 12px 0 8px 0; color: #121212; font-size: 15px; font-weight: 700;">
                                            <?php echo $name; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Telefon</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; font-size: 14px; font-weight: 600; color: #121212;">
                                            <a href="tel:<?php echo esc_attr($phone); ?>" style="color: #121212; text-decoration: underline;"><?php echo $phone; ?></a>
                                            &nbsp;&nbsp;
                                            <a href="https://wa.me/<?php echo esc_attr($clean_phone); ?>" target="_blank" style="color: #166534; font-size: 12px; font-weight: 600; text-decoration: underline;">WhatsApp chat</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Email</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; font-size: 14px; color: #121212;">
                                            <a href="mailto:<?php echo esc_attr($email); ?>" style="color: #121212; text-decoration: underline;"><?php echo $email; ?></a>
                                        </td>
                                    </tr>
                                    <?php if ($instagram): ?>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Instagram</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; font-size: 14px; font-weight: 500;">
                                            <a href="https://instagram.com/<?php echo esc_attr(ltrim(trim($instagram), '@')); ?>" target="_blank" style="color: #121212; text-decoration: underline;"><?php echo $instagram; ?></a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </td>
                        </tr>

                        <!-- Edukacija Specification -->
                        <tr>
                            <td style="padding: 0 40px 36px 40px;" class="content-box">
                                <div style="margin-bottom: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8c8983;">
                                    Detalji edukacije
                                </div>
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top: 1px solid #121212; border-bottom: 1px solid #121212;">
                                    <tr>
                                        <td style="padding: 12px 0 8px 0; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; width: 130px;">Paket</td>
                                        <td style="padding: 12px 0 8px 0; color: #121212; font-size: 15px; font-weight: 700;">
                                            <?php echo $popup_heading; ?>
                                            <?php if ($popup_desc): ?>
                                                <div style="font-size: 13px; font-weight: 400; color: #5a5953; margin-top: 2px;"><?php echo $popup_desc; ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Instruktorka</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px; font-weight: 600;">
                                            <?php echo esc_html($instructor); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Format</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px;">
                                            <?php echo $tip_kursa ?: '—'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #6e6d68; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;">Termin</td>
                                        <td style="padding: 10px 0; border-top: 1px solid #ebeae5; color: #121212; font-size: 14px; font-weight: 600;">
                                            <?php echo $termin_display; ?>
                                        </td>
                                    </tr>
                                    <?php echo $voucher_html; ?>
                                </table>
                            </td>
                        </tr>

                        <!-- Sub-Footer -->
                        <tr>
                            <td style="padding: 20px 40px; background-color: #faf9f6; border-top: 1px solid #ebeae5; text-align: center; color: #76746e; font-size: 12px;" class="content-box">
                                Prijava #<?php echo $post_id; ?> zaprimljena u <?php echo date_i18n('d.m.Y. \u H:i'); ?> &bull; <a href="<?php echo esc_url($admin_url); ?>" style="color: #121212; font-weight: 600; text-decoration: underline;">Obrada prijave</a>
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
