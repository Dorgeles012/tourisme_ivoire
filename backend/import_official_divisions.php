<?php
declare(strict_types=1);

/**
 * Génère un seed SQL idempotent depuis l’API officielle ANStat.
 * Usage : php backend/import_official_divisions.php [database/divisions_seed.sql]
 * Ce script ne se connecte pas à MySQL et ne supprime aucune donnée.
 */
const ANSTAT_BASE = 'https://api-public.anstat.ci/api/v1';
const ANSTAT_HOST = 'api-public.anstat.ci';
const ANSTAT_YEAR = '2021';
const ANSTAT_MAX_ATTEMPTS = 3;

function requestJson(string $method, string $url, ?array $form = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Extension PHP cURL absente. Activez extension=curl dans php.ini de XAMPP.');
    }
    $lastError = '';
    for ($attempt = 1; $attempt <= ANSTAT_MAX_ATTEMPTS; $attempt++) {
        $handle = curl_init($url);
        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];
        if (strtoupper($method) === 'POST') {
            // L’API ANStat documente ces paramètres comme données de formulaire.
            // Un tableau cURL les envoie en multipart/form-data.
            $options[CURLOPT_POSTFIELDS] = $form ?? [];
        }
        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($body !== false && $status >= 200 && $status < 300) {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException(
                    'Réponse JSON invalide. Méthode=' . strtoupper($method) .
                    ' URL=' . $url . ' paramètres=' . json_encode($form ?? [], JSON_UNESCAPED_UNICODE) .
                    ' HTTP=' . $status . ' réponse=' . substr($body, 0, 1200), 0, $e
                );
            }
            if (!is_array($decoded)) {
                throw new RuntimeException('Réponse API non objet : ' . strtoupper($method) . ' ' . $url . ' HTTP=' . $status);
            }
            if (($decoded['success'] ?? null) !== true) {
                throw new RuntimeException(
                    'ANStat signale success=false. Méthode=' . strtoupper($method) .
                    ' URL=' . $url . ' paramètres=' . json_encode($form ?? [], JSON_UNESCAPED_UNICODE) .
                    ' HTTP=' . $status . ' réponse=' . substr($body, 0, 1200)
                );
            }
            if (!isset($decoded['results']) || !is_array($decoded['results'])) {
                throw new RuntimeException('Champ results absent ou invalide : ' . strtoupper($method) . ' ' . $url . ' HTTP=' . $status);
            }
            return $decoded;
        }

        $responseText = is_string($body) ? trim($body) : '';
        $lastError = 'Échec ANStat. Méthode=' . strtoupper($method) .
            ' URL=' . $url .
            ' paramètres=' . json_encode($form ?? [], JSON_UNESCAPED_UNICODE) .
            ' HTTP=' . $status .
            ' cURL=' . ($curlError !== '' ? $curlError : 'aucune') .
            ' réponse=' . ($responseText !== '' ? substr($responseText, 0, 1600) : '(vide)');
        $temporary = $body === false || in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
        if (!$temporary || $attempt === ANSTAT_MAX_ATTEMPTS) break;
        usleep(350000 * $attempt);
    }
    throw new RuntimeException($lastError . ' (' . ANSTAT_MAX_ATTEMPTS . ' tentative(s) maximum).');
}

function fetchAll(string $endpoint, string $method): array
{
    $method = strtoupper($method);
    if (!in_array($endpoint, ['districts', 'regions', 'departements'], true)) {
        throw new InvalidArgumentException('Endpoint ANStat non autorisé.');
    }
    if (($endpoint === 'districts' && $method !== 'GET') ||
        (in_array($endpoint, ['regions', 'departements'], true) && $method !== 'POST')) {
        throw new InvalidArgumentException('Méthode HTTP incorrecte pour ' . $endpoint . '.');
    }

    $all = [];
    $seenPages = [];
    $page = 1;
    $totalCount = null;
    $next = true;
    while ($next !== null) {
        if ($page > 100) throw new RuntimeException('Pagination ANStat supérieure à 100 pages pour ' . $endpoint . '.');
        $params = ['page' => (string)$page, 'pagination' => 'oui', 'annee' => ANSTAT_YEAR];
        $endpointUrl = ANSTAT_BASE . '/' . $endpoint;
        $url = $method === 'GET' ? $endpointUrl . '?' . http_build_query($params) : $endpointUrl;
        $response = requestJson($method, $url, $method === 'POST' ? $params : null);
        $results = $response['results'];
        foreach ($results as $row) {
            if (!is_array($row)) throw new RuntimeException('Enregistrement invalide dans ' . $endpoint . ', page ' . $page . '.');
            $all[] = $row;
        }
        $info = $response['result_info'] ?? null;
        if (!is_array($info) || !array_key_exists('next', $info) || !array_key_exists('total_count', $info)) {
            throw new RuntimeException('result_info.next/total_count absent. Méthode=' . $method . ' URL=' . $url . ' réponse=' . json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        $totalCount = filter_var($info['total_count'], FILTER_VALIDATE_INT);
        if ($totalCount === false || $totalCount < 0) throw new RuntimeException('result_info.total_count invalide pour ' . $endpoint . '.');
        $next = $info['next'];
        if ($next !== null) {
            if (!is_string($next) || $next === '') throw new RuntimeException('Lien result_info.next invalide pour ' . $endpoint . '.');
            $parts = parse_url($next);
            if (($parts['host'] ?? '') !== ANSTAT_HOST || ($parts['path'] ?? '') !== '/api/v1/' . $endpoint) {
                throw new RuntimeException('Lien de pagination hors endpoint ANStat attendu : ' . $next);
            }
            parse_str((string)($parts['query'] ?? ''), $nextQuery);
            $nextPage = filter_var($nextQuery['page'] ?? null, FILTER_VALIDATE_INT);
            if ($nextPage === false || $nextPage < 1 || isset($seenPages[$nextPage]) || $nextPage <= $page) {
                throw new RuntimeException('Page suivante invalide ou répétée : ' . $next);
            }
            $seenPages[$page] = true;
            $page = $nextPage;
        }
        if ($next === null && count($all) !== $totalCount) {
            throw new RuntimeException('Pagination terminée avec un total incohérent pour ' . $endpoint . ' : reçus=' . count($all) . ', annoncés=' . $totalCount . '.');
        }
    }
    return $all;
}

function requiredText(array $row, string $field, string $entity): string
{
    $value = trim((string)($row[$field] ?? ''));
    if ($value === '') throw new RuntimeException('Champ ' . $field . ' absent pour ' . $entity . '.');
    return $value;
}

function assertUniqueCodes(array $rows, string $codeField, string $nameField, string $entity): array
{
    $byCode = [];
    foreach ($rows as $row) {
        $code = requiredText($row, $codeField, $entity);
        requiredText($row, $nameField, $entity . ' ' . $code);
        if (isset($byCode[$code])) throw new RuntimeException('Code ' . $code . ' dupliqué dans ' . $entity . '.');
        $byCode[$code] = $row;
    }
    return $byCode;
}

function sqlString(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function normalized(string $value): string
{
    $value = mb_strtoupper(trim($value), 'UTF-8');
    $value = strtr($value, ['É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','À'=>'A','Â'=>'A','Ä'=>'A','Ô'=>'O','Ö'=>'O','Û'=>'U','Ü'=>'U','Ï'=>'I','Î'=>'I','Ç'=>'C']);
    return preg_replace('/[^A-Z0-9]+/', '', $value) ?? $value;
}

function slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '', '-'));
    return $value !== '' ? $value : 'division';
}

try {
    $districts = fetchAll('districts', 'GET');
    $districtsByCode = assertUniqueCodes($districts, 'code_district', 'nom_district', 'districts');
    if (count($districts) !== 14) throw new RuntimeException('Nombre de districts inattendu : ' . count($districts) . ' (14 attendus par le référentiel ANStat documenté).');

    $regionsApi = fetchAll('regions', 'POST');
    $regionsApiByCode = assertUniqueCodes($regionsApi, 'cod_reg', 'nom_reg', 'régions ANStat');
    $districtByRegionCode = [];
    foreach ($districts as $district) {
        if (!str_contains(normalized((string)$district['nom_district']), 'AUTONOME')) continue;
        $districtAlias = preg_replace('/^(?:DISTRICT)?AUTONOME(?:DE|D)?/', '', normalized((string)$district['nom_district'])) ?? '';
        foreach ($regionsApi as $region) {
            if (normalized((string)$region['nom_reg']) === $districtAlias) {
                $districtByRegionCode[(string)$region['cod_reg']] = $district;
            }
        }
    }
    if (count($districtByRegionCode) !== 2) throw new RuntimeException('Les deux codes régionaux de référence des districts autonomes n’ont pas été identifiés sans ambiguïté.');
    $regions = array_values(array_filter($regionsApi, static fn(array $row): bool => !isset($districtByRegionCode[(string)$row['cod_reg']])));
    if (count($regions) !== 31) throw new RuntimeException('Nombre de régions inattendu après retrait des alias autonomes ANStat : ' . count($regions) . ' (31 attendues).');
    $regionsByCode = assertUniqueCodes($regions, 'cod_reg', 'nom_reg', 'régions');
    foreach ($regions as $region) {
        $districtCode = requiredText($region, 'cod_dist', 'région ' . $region['nom_reg']);
        if (!isset($districtsByCode[$districtCode])) throw new RuntimeException('District parent introuvable pour la région ' . $region['nom_reg'] . ' (cod_dist=' . $districtCode . ').');
        if (str_contains(normalized((string)$districtsByCode[$districtCode]['nom_district']), 'AUTONOME')) throw new RuntimeException('Une région ordinaire pointe vers un district autonome : ' . $region['nom_reg'] . '.');
    }

    $departments = fetchAll('departements', 'POST');
    assertUniqueCodes($departments, 'cod_dep', 'nom_dep', 'départements');
    if (count($departments) !== 111) throw new RuntimeException('Nombre de départements inattendu : ' . count($departments) . ' (111 attendus).');
    foreach ($departments as $department) {
        $parentCode = requiredText($department, 'cod_reg', 'département ' . $department['nom_dep']);
        if (!isset($regionsApiByCode[$parentCode])) throw new RuntimeException('Région/district parent introuvable pour le département ' . $department['nom_dep'] . ' (cod_reg=' . $parentCode . ').');
        if (!isset($regionsByCode[$parentCode]) && !isset($districtByRegionCode[$parentCode])) throw new RuntimeException('Le code parent ' . $parentCode . ' du département ' . $department['nom_dep'] . ' n’est ni une région ni un alias de district autonome.');
    }

    $lines = [
        '-- Référentiel officiel des divisions administratives ivoiriennes.',
        '-- Source des codes et noms : API publique ANStat, année de référence 2021.',
        '-- Répétable : INSERT ... ON DUPLICATE KEY UPDATE; aucune suppression.',
        '-- Généré le ' . gmdate('Y-m-d') . ' par backend/import_official_divisions.php.',
        'USE tourisme_ivoire;', 'SET NAMES utf8mb4;', 'START TRANSACTION;',
    ];
    foreach ($districts as $district) {
        $name = requiredText($district, 'nom_district', 'district');
        $code = requiredText($district, 'code_district', 'district ' . $name);
        $type = str_contains(normalized($name), 'AUTONOME') ? 'autonome' : 'ordinaire';
        $lines[] = 'INSERT INTO districts(nom,code,type,slug,code_officiel,statut) VALUES(' . implode(',', [sqlString($name),sqlString($code),sqlString($type),sqlString(slugify($name)),sqlString($code),"'actif'"]) . ') ON DUPLICATE KEY UPDATE nom=VALUES(nom),type=VALUES(type),slug=VALUES(slug),code_officiel=VALUES(code_officiel),statut=VALUES(statut);';
    }
    foreach ($regions as $region) {
        $name = requiredText($region, 'nom_reg', 'région');
        $code = requiredText($region, 'cod_reg', 'région ' . $name);
        $districtCode = requiredText($region, 'cod_dist', 'région ' . $name);
        $districtExpr = '(SELECT id FROM districts WHERE code=' . sqlString($districtCode) . ' LIMIT 1)';
        $lines[] = 'INSERT INTO regions(nom,code,slug,code_officiel,statut,district_id) VALUES(' . implode(',', [sqlString($name),sqlString($code),sqlString(slugify($name)),sqlString($code),"'actif'",$districtExpr]) . ') ON DUPLICATE KEY UPDATE nom=VALUES(nom),slug=VALUES(slug),code_officiel=VALUES(code_officiel),district_id=VALUES(district_id),statut=VALUES(statut);';
    }
    foreach ($departments as $department) {
        $name = requiredText($department, 'nom_dep', 'département');
        $code = requiredText($department, 'cod_dep', 'département ' . $name);
        $parentCode = requiredText($department, 'cod_reg', 'département ' . $name);
        if (isset($districtByRegionCode[$parentCode])) {
            $regionExpr = 'NULL';
            $districtExpr = '(SELECT id FROM districts WHERE code=' . sqlString((string)$districtByRegionCode[$parentCode]['code_district']) . ' LIMIT 1)';
        } else {
            $regionExpr = '(SELECT id FROM regions WHERE code=' . sqlString($parentCode) . ' LIMIT 1)';
            $districtExpr = 'NULL';
        }
        $values = [sqlString($name),sqlString($code),sqlString($code),$regionExpr,$districtExpr,"'actif'"];
        $lines[] = 'INSERT INTO departements(nom,code,code_officiel,region_id,district_id,statut) VALUES(' . implode(',', $values) . ') ON DUPLICATE KEY UPDATE nom=VALUES(nom),code_officiel=VALUES(code_officiel),region_id=VALUES(region_id),district_id=VALUES(district_id),statut=VALUES(statut);';
    }
    $lines[] = 'COMMIT;';
    $lines[] = '-- Volumes source contrôlés : districts=' . count($districts) . ', régions=' . count($regions) . ', départements=' . count($departments) . '.';
    $lines[] = "SELECT (SELECT COUNT(*) FROM districts WHERE statut='actif') AS districts, (SELECT COUNT(*) FROM districts WHERE type='autonome' AND statut='actif') AS districts_autonomes, (SELECT COUNT(*) FROM regions WHERE statut='actif') AS regions, (SELECT COUNT(*) FROM departements WHERE statut='actif') AS departements;";
    $output = implode("\n", $lines) . "\n";

    $destination = $argv[1] ?? dirname(__DIR__) . '/database/divisions_seed.sql';
    if (str_contains($destination, '..')) throw new RuntimeException('Chemin de destination invalide.');
    $directory = realpath(dirname($destination));
    if ($directory === false || !is_dir($directory)) throw new RuntimeException('Dossier de destination inexistant : ' . dirname($destination));
    $destination = $directory . DIRECTORY_SEPARATOR . basename($destination);
    $temporary = tempnam($directory, '.divisions_seed_');
    if ($temporary === false) throw new RuntimeException('Impossible de préparer le fichier SQL temporaire dans ' . $directory);
    try {
        if (file_put_contents($temporary, $output, LOCK_EX) === false) throw new RuntimeException('Écriture du fichier temporaire impossible : ' . $temporary);
        if (!rename($temporary, $destination)) throw new RuntimeException('Impossible de remplacer le fichier SQL : ' . $destination);
    } finally {
        if (is_file($temporary)) @unlink($temporary);
    }
    fwrite(STDOUT, 'Districts récupérés : ' . count($districts) . PHP_EOL);
    fwrite(STDOUT, 'Régions récupérées : ' . count($regions) . PHP_EOL);
    fwrite(STDOUT, 'Départements récupérés : ' . count($departments) . PHP_EOL);
    fwrite(STDOUT, 'Fichier SQL généré : ' . $destination . PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDERR, 'Import non généré : ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
