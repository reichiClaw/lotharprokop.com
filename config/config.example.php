<?php
/**
 * Konfiguration – Kopie als config/config.php anlegen und anpassen.
 * Diese Datei liegt außerhalb von public/ und ist nicht öffentlich erreichbar.
 */
return [
    // Öffentliche Basis-URL ohne abschließenden Schrägstrich (für Canonical, Sitemap, Social-Vorschau).
    'base_url' => 'https://lotharprokop.com',

    // Anzeigename der Website
    'site_name' => 'Lothar Prokop Fotografie',

    // Debug-Ausgaben nur in der Entwicklung aktivieren.
    'debug' => false,

    // Einmaliger Schlüssel für die erstmalige Einrichtung des Adminkontos über /admin/setup.
    // Nach Anlage des ersten Benutzers ist /admin/setup dauerhaft deaktiviert.
    // Alternativ: php bin/create-user.php (empfohlen, dann kann dieser Wert leer bleiben).
    'setup_key' => '',

    // Pfade. 'storage' liegt standardmäßig im Anwendungsordner; 'public_media' im tatsächlich
    // ausgelieferten öffentlichen Verzeichnis (bei Web-Aufrufen automatisch erkannt).
    // Bei getrennter FTP-Installation (htdocs neben dem Anwendungsordner) 'public_media' hier
    // ausdrücklich setzen, damit auch die Kommandozeilen-Skripte den richtigen Ordner nutzen,
    // z. B. dirname(__DIR__, 2) . '/htdocs/media'.
    'paths' => [
        'storage' => dirname(__DIR__) . '/storage',
        'public_media' => (defined('PUBLIC_ROOT') ? PUBLIC_ROOT : dirname(__DIR__) . '/public') . '/media',
    ],

    // Sitzungen
    'session' => [
        'name' => 'lp_admin',
        'idle_timeout' => 3600,     // Sekunden ohne Aktivität bis zum Logout
        'absolute_timeout' => 43200, // maximale Sitzungsdauer in Sekunden
    ],

    // Schutz vor wiederholten Loginversuchen (persistent in SQLite)
    'login' => [
        'max_attempts' => 5,        // Fehlversuche ...
        'window_seconds' => 900,    // ... innerhalb dieses Zeitfensters
        'lock_seconds' => 900,      // Sperrdauer danach
    ],

    // Bildverarbeitung
    'images' => [
        // 'auto' nutzt Imagick, wenn vorhanden, sonst GD. 'gd' erzwingt GD (z. B. bei fehlerhafter Imagick-Installation).
        'backend' => 'auto',
        'max_upload_bytes' => 40 * 1024 * 1024, // 40 MB – muss unter upload_max_filesize/post_max_size liegen
        'max_pixels' => 100_000_000,            // maximal 100 Megapixel (mit GD zusätzlich durch memory_limit begrenzt)
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
        // Erzeugte Breiten der Ausgabevarianten (längste Kante); die Originale bleiben privat.
        'widths' => [480, 960, 1600, 2400],
        'jpeg_quality' => 86,
        'webp_quality' => 84,
        // AVIF nur aktivieren, wenn Imagick mit AVIF-Unterstützung zuverlässig verfügbar ist.
        'avif' => false,
    ],

    // Kontaktformular – nur aktivieren, wenn der Mailversand auf dem Server funktioniert.
    'mail' => [
        'enabled' => false,
        'to' => 'office@lotharprokop.com',
        // Absenderadresse muss zur Domain des Servers passen (SPF), sonst landen Mails im Spam.
        'from' => 'website@lotharprokop.com',
        'subject_prefix' => '[lotharprokop.com] ',
        'min_seconds' => 4, // Formular schneller ausgefüllt = wahrscheinlich Bot
        'max_per_hour_per_ip' => 5,
    ],
];
