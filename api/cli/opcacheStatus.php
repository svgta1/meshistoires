<?php
// Liste tous les fichiers en cache
$stats = opcache_get_status();
print_r($stats);
echo PHP_EOL;
if ($stats && isset($stats['scripts'])) {
    foreach ($stats['scripts'] as $file => $data) {
        echo $file . PHP_EOL;
    }
} else {
    echo "Opcache non activé ou impossible de récupérer le statut." . PHP_EOL;

}

