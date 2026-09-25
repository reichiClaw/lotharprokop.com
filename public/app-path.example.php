<?php
/**
 * Nur nötig, wenn der Anwendungsordner (app/, config/, storage/, templates/) NICHT eine Ebene
 * über diesem Verzeichnis liegt – typisch bei FTP-Hosting, wo dieses Verzeichnis „htdocs“ oder
 * „public_html“ heißt und der Anwendungsordner daneben liegt.
 *
 * Kopie als app-path.php anlegen und den absoluten oder relativen Pfad zum Anwendungsordner
 * zurückgeben (das Verzeichnis, in dem app/bootstrap.php liegt).
 */
return dirname(__DIR__) . '/lotharprokop';
