<?php
/**
 * =============================================================================
 * INSTALL.PHP — Installateur de l'Agent Scientifique Autonome v3.0
 * =============================================================================
 * 
 * ⚠️  À SUPPRIMER APRÈS INSTALLATION POUR DES RAISONS DE SÉCURITÉ
 * 
 * Ce script guide l'utilisateur à travers :
 * 1. Vérification des pré-requis système
 * 2. Création de la structure de dossiers
 * 3. Génération du fichier config.php
 * 4. Configuration initiale de sécurité
 * 
 * Licence: MIT — Laurent Voanh 2024
 * =============================================================================
 */

declare(strict_types=1);

// Désactiver l'affichage des erreurs en production (activé ici pour le debug install)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// =============================================================================
// CONFIGURATION DE BASE
// =============================================================================

define('APP_NAME', 'Agent Scientifique Autonome');
define('APP_VERSION', '3.0.0');
define('REQUIRED_PHP_VERSION', '8.1');
define('REQUIRED_EXTENSIONS', ['curl', 'json', 'mbstring']);
define('WRITABLE_DIRS', ['data', 'logs']);
define('SUB_DIRS', [
    'data' => ['reports', 'memory', 'cache', 'proposals', 'auto_apis', 'insights'],
    'logs' => []
]);

// =============================================================================
// FONCTIONS UTILITAIRES
// =============================================================================

/**
 * Vérifie la version PHP
 */
function check_php_version(): array {
    $current = phpversion();
    $required = REQUIRED_PHP_VERSION;
    $ok = version_compare($current, $required, '>=');
    
    return [
        'name' => 'Version PHP',
        'required' => "≥ {$required}",
        'current' => $current,
        'ok' => $ok,
        'message' => $ok ? '✅ OK' : "❌ PHP {$required}+ requis"
    ];
}

/**
 * Vérifie les extensions PHP requises
 */
function check_extensions(): array {
    $results = [];
    foreach (REQUIRED_EXTENSIONS as $ext) {
        $loaded = extension_loaded($ext);
        $results[] = [
            'name' => "Extension {$ext}",
            'required' => 'Activée',
            'current' => $loaded ? 'Oui' : 'Non',
            'ok' => $loaded,
            'message' => $loaded ? '✅ OK' : "❌ Activer dans php.ini"
        ];
    }
    return $results;
}

/**
 * Vérifie les permissions d'écriture des dossiers
 */
function check_writable_dirs(): array {
    $results = [];
    foreach (WRITABLE_DIRS as $dir) {
        $path = __DIR__ . '/' . $dir;
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        
        $results[] = [
            'name' => "Dossier {$dir}/",
            'required' => 'Existe + Writable',
            'current' => $exists ? ($writable ? 'Writable' : 'Non-writable') : 'Manquant',
            'ok' => $writable,
            'message' => $writable ? '✅ OK' : ($exists ? '❌ chmod 755 requis' : '❌ À créer')
        ];
    }
    return $results;
}

/**
 * Crée la structure de dossiers nécessaire
 */
function create_directory_structure(): array {
    $results = [];
    
    foreach (SUB_DIRS as $base => $subs) {
        $base_path = __DIR__ . '/' . $base;
        
        // Créer le dossier de base si nécessaire
        if (!is_dir($base_path)) {
            $created = mkdir($base_path, 0755, true);
            $results[] = [
                'path' => $base . '/',
                'action' => 'Création',
                'ok' => $created,
                'message' => $created ? '✅ Créé' : '❌ Échec'
            ];
        }
        
        // Créer les sous-dossiers
        foreach ($subs as $sub) {
            $sub_path = $base_path . '/' . $sub;
            if (!is_dir($sub_path)) {
                $created = mkdir($sub_path, 0755, true);
                $results[] = [
                    'path' => "{$base}/{$sub}/",
                    'action' => 'Création',
                    'ok' => $created,
                    'message' => $created ? '✅ Créé' : '❌ Échec'
                ];
            }
        }
    }
    
    // Créer les fichiers de log vides si nécessaire
    $log_files = [
        'logs/agent.log',
        'logs/execution.jsonl',
        'data/memory/brain_state.json',
        'data/memory/index.json',
        'data/memory/state.json'
    ];
    
    foreach ($log_files as $file) {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) {
            $content = ($file === 'data/memory/brain_state.json' || $file === 'data/memory/index.json' || $file === 'data/memory/state.json') 
                ? '{}' 
                : '';
            $created = file_put_contents($path, $content) !== false;
            $results[] = [
                'path' => $file,
                'action' => 'Initialisation',
                'ok' => $created,
                'message' => $created ? '✅ Initialisé' : '❌ Échec'
            ];
        }
    }
    
    return $results;
}

/**
 * Génère le fichier config.php à partir des variables d'environnement
 */
function generate_config_file(array $env_vars): array {
    $config_path = __DIR__ . '/config.php';
    
    // Template du fichier config.php
    $config_template = <<<'PHPEOF'
<?php
/**
 * =============================================================================
 * CONFIG.PHP — Configuration de l'Agent Scientifique Autonome v3.0
 * =============================================================================
 * 
 * ⚠️  FICHIER GÉNÉRÉ AUTOMATIQUEMENT — NE PAS MODIFIER MANUELLEMENT
 * ⚠️  Pour changer la configuration, éditez le fichier .env à la racine
 * ⚠️  Ce fichier NE DOIT PAS être commité dans le repository Git
 * 
 * Généré le: {{GENERATED_AT}}
 * =============================================================================
 */

declare(strict_types=1);

return [
    'app' => [
        'name' => '{{APP_NAME}}',
        'version' => '{{APP_VERSION}}',
        'url' => getenv('APP_URL') ?: 'http://localhost',
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    ],
    
    'admin' => [
        'email' => getenv('ADMIN_EMAIL') ?: '',
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
        'allowed_ips' => array_filter(explode(',', getenv('ADMIN_IPS') ?: '')),
    ],
    
    'mistral' => [
        'endpoint' => 'https://api.mistral.ai/v1/chat/completions',
        'keys' => array_filter([
            getenv('MISTRAL_KEY_1'),
            getenv('MISTRAL_KEY_2'),
            getenv('MISTRAL_KEY_3'),
        ]),
        'models' => [
            'fast' => getenv('MISTRAL_MODEL_FAST') ?: 'mistral-tiny',
            'balanced' => getenv('MISTRAL_MODEL_BALANCED') ?: 'mistral-small-latest',
            'deep' => getenv('MISTRAL_MODEL_DEEP') ?: 'mistral-medium-latest',
            'coder' => getenv('MISTRAL_MODEL_CODER') ?: 'codestral-latest',
        ],
        'timeout_seconds' => 30,
        'max_retries' => 3,
    ],
    
    'apis' => [
        'pubmed' => [
            'base_url' => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
            'rate_limit_per_sec' => 3,
            'cache_ttl' => 7200,
        ],
        'uniprot' => [
            'base_url' => 'https://rest.uniprot.org/uniprotkb/',
            'rate_limit_per_sec' => 10,
            'cache_ttl' => 3600,
        ],
        'ensembl' => [
            'base_url' => 'https://rest.ensembl.org/',
            'rate_limit_per_sec' => 15,
            'cache_ttl' => 3600,
        ],
        'clinvar' => [
            'base_url' => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
            'rate_limit_per_sec' => 3,
            'cache_ttl' => 7200,
        ],
        'github' => [
            'base_url' => 'https://api.github.com/',
            'rate_limit_per_min' => 10,
            'cache_ttl' => 1800,
        ],
    ],
    
    'google_news' => [
        'base_url' => 'https://news.google.com/rss/search',
        'params' => 'hl=fr&gl=FR&ceid=FR:fr',
        'topics' => [
            'genetics' => 'genetics+OR+genomic+OR+CRISPR+OR+gene+therapy',
            'cancer' => 'cancer+OR+oncology+OR+tumor+OR+biomarker',
            'neuroscience' => 'neuroscience+OR+brain+OR+Alzheimer+OR+Parkinson',
            'ai_science' => 'artificial+intelligence+OR+machine+learning+OR+protein+folding',
            'clinical_trials' => 'clinical+trial+OR+phase+3+OR+FDA+approval',
        ],
        'exclude_terms' => ['smartphone', 'promotion', 'gaming', 'laptop', 'price'],
    ],
    
    'autoloop' => [
        'enabled' => true,
        'interval_ms' => (int)(getenv('AUTOLOOP_INTERVAL_MS') ?: 180000),
        'min_scientific_value' => (float)(getenv('MIN_SCIENTIFIC_VALUE') ?: 0.6),
        'self_improve_every_n_cycles' => (int)(getenv('SELF_IMPROVE_CYCLES') ?: 10),
        'max_reports_per_day' => (int)(getenv('MAX_REPORTS_PER_DAY') ?: 50),
    ],
    
    'storage' => [
        'type' => getenv('STORAGE_TYPE') ?: 'json',
        'reports_dir' => __DIR__ . '/data/reports',
        'memory_dir' => __DIR__ . '/data/memory',
        'cache_dir' => __DIR__ . '/data/cache',
        'logs_dir' => __DIR__ . '/logs',
    ],
    
    'wordpress' => [
        'enabled' => filter_var(getenv('WP_AUTO_PUBLISH'), FILTER_VALIDATE_BOOLEAN),
        'api_url' => getenv('WP_API_URL') ?: '',
        'app_password' => getenv('WP_APP_PASSWORD') ?: '',
        'category' => getenv('WP_CATEGORY') ?: 'recherche-autonome',
        'auto_publish' => filter_var(getenv('WP_AUTO_PUBLISH'), FILTER_VALIDATE_BOOLEAN),
    ],
    
    'security' => [
        'cors_origins' => array_filter(explode(',', getenv('CORS_ORIGINS') ?: '')),
        'require_https' => true,
        'rate_limit_requests_per_minute' => 60,
        'max_upload_size_mb' => 10,
    ],
    
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600,
        'purge_old_after_days' => 30,
    ],
];
PHPEOF;

    // Remplacer les placeholders
    $config_content = str_replace(
        ['{{GENERATED_AT}}', '{{APP_NAME}}', '{{APP_VERSION}}'],
        [date('Y-m-d H:i:s'), APP_NAME, APP_VERSION],
        $config_template
    );
    
    // Sauvegarder le fichier
    $saved = file_put_contents($config_path, $config_content, LOCK_EX) !== false;
    
    if ($saved) {
        // Sécuriser le fichier (chmod 644)
        @chmod($config_path, 0644);
    }
    
    return [
        'path' => 'config.php',
        'ok' => $saved,
        'message' => $saved ? '✅ Généré' : '❌ Échec d\'écriture',
        'keys_count' => count(array_filter([
            $env_vars['MISTRAL_KEY_1'] ?? '',
            $env_vars['MISTRAL_KEY_2'] ?? '',
            $env_vars['MISTRAL_KEY_3'] ?? '',
        ]))
    ];
}

/**
 * Génère le fichier .htaccess de sécurité
 */
function generate_htaccess(): array {
    $htaccess_root = __DIR__ . '/.htaccess';
    $htaccess_data = __DIR__ . '/data/.htaccess';
    $htaccess_logs = __DIR__ . '/logs/.htaccess';
    
    $results = [];
    
    // .htaccess racine
    $root_htaccess = <<<'HTACCESS'
# =============================================================================
# .htaccess — Agent Scientifique Autonome v3.0
# =============================================================================
# Sécurité de base — À personnaliser selon votre hébergement
# =============================================================================

# Bloquer l'accès aux fichiers sensibles
<FilesMatch "^(config\.php|\.env|install\.php|test\.php|\.git)">
    Deny from all
</FilesMatch>

# Forcer HTTPS (décommenter en production)
# <IfModule mod_rewrite.c>
#     RewriteEngine On
#     RewriteCond %{HTTPS} off
#     RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
# </IfModule>

# Headers de sécurité
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
    Header unset X-Powered-By
</IfModule>

# Limites PHP (si mod_php est disponible)
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>

# Compression GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/json application/javascript
</IfModule>

# Cache statique
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
HTACCESS;

    // .htaccess pour data/ et logs/
    $protected_htaccess = <<<'HTACCESS'
Deny from all
<Files ~ "\.(json|html)$">
    Allow from all
</Files>
HTACCESS;

    // Écrire .htaccess racine
    if (file_put_contents($htaccess_root, $root_htaccess, LOCK_EX) !== false) {
        $results[] = [
            'path' => '.htaccess (racine)',
            'ok' => true,
            'message' => '✅ Généré'
        ];
    }
    
    // Écrire .htaccess pour data/
    if (file_put_contents($htaccess_data, $protected_htaccess, LOCK_EX) !== false) {
        $results[] = [
            'path' => 'data/.htaccess',
            'ok' => true,
            'message' => '✅ Généré (accès protégé)'
        ];
    }
    
    // Écrire .htaccess pour logs/
    if (file_put_contents($htaccess_logs, $protected_htaccess, LOCK_EX) !== false) {
        $results[] = [
            'path' => 'logs/.htaccess',
            'ok' => true,
            'message' => '✅ Généré (accès protégé)'
        ];
    }
    
    return $results;
}

/**
 * Test de connexion à Mistral API
 */
function test_mistral_connection(string $api_key): array {
    $url = 'https://api.mistral.ai/v1/chat/completions';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'mistral-tiny',
            'messages' => [['role' => 'user', 'content' => 'OK']],
            'max_tokens' => 10
        ])
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $success = ($http_code === 200 || $http_code === 400); // 400 = requête valide mais contenu minimal
    
    return [
        'ok' => $success,
        'http_code' => $http_code,
        'message' => $success ? '✅ Connexion OK' : "❌ Erreur: {$error} (HTTP {$http_code})",
        'response_sample' => $success ? substr($response ?? '', 0, 100) . '...' : null
    ];
}

// =============================================================================
// LOGIQUE PRINCIPALE DE L'INSTALLATEUR
// =============================================================================

$step = $_GET['step'] ?? $_POST['step'] ?? 'check';
$messages = [];
$errors = [];
$success = false;

// Traitement du formulaire de configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'configure') {
    $env_vars = [
        'APP_URL' => rtrim($_POST['app_url'] ?? '', '/'),
        'APP_TIMEZONE' => $_POST['timezone'] ?? 'Europe/Paris',
        'APP_DEBUG' => isset($_POST['debug']) ? 'true' : 'false',
        'ADMIN_EMAIL' => filter_var($_POST['admin_email'] ?? '', FILTER_VALIDATE_EMAIL) ? $_POST['admin_email'] : '',
        'ADMIN_PASSWORD_HASH' => !empty($_POST['admin_password']) ? password_hash($_POST['admin_password'], PASSWORD_DEFAULT) : '',
        'ADMIN_IPS' => $_POST['admin_ips'] ?? '',
        'MISTRAL_KEY_1' => trim($_POST['mistral_key_1'] ?? ''),
        'MISTRAL_KEY_2' => trim($_POST['mistral_key_2'] ?? ''),
        'MISTRAL_KEY_3' => trim($_POST['mistral_key_3'] ?? ''),
        'MISTRAL_MODEL_FAST' => $_POST['model_fast'] ?? 'mistral-tiny',
        'MISTRAL_MODEL_BALANCED' => $_POST['model_balanced'] ?? 'mistral-small-latest',
        'MISTRAL_MODEL_DEEP' => $_POST['model_deep'] ?? 'mistral-medium-latest',
        'MISTRAL_MODEL_CODER' => $_POST['model_coder'] ?? 'codestral-latest',
        'WP_API_URL' => rtrim($_POST['wp_api_url'] ?? '', '/'),
        'WP_APP_PASSWORD' => $_POST['wp_app_password'] ?? '',
        'WP_CATEGORY' => $_POST['wp_category'] ?? 'recherche-autonome',
        'WP_AUTO_PUBLISH' => isset($_POST['wp_auto_publish']) ? 'true' : 'false',
        'STORAGE_TYPE' => $_POST['storage_type'] ?? 'json',
        'CORS_ORIGINS' => $_POST['cors_origins'] ?? '',
        'AUTOLOOP_INTERVAL_MS' => $_POST['autoloop_interval'] ?? '180000',
        'MIN_SCIENTIFIC_VALUE' => $_POST['min_scientific_value'] ?? '0.6',
        'SELF_IMPROVE_CYCLES' => $_POST['self_improve_cycles'] ?? '10',
        'MAX_REPORTS_PER_DAY' => $_POST['max_reports_per_day'] ?? '50',
    ];
    
    // Validation minimale
    if (empty($env_vars['MISTRAL_KEY_1'])) {
        $errors[] = 'Au moins une clé Mistral API est requise';
    }
    
    if (empty($errors)) {
        // Générer .env
        $env_content = "# =============================================================================\n";
        $env_content .= "# .env — Variables d'environnement — Agent Scientifique Autonome v3.0\n";
        $env_content .= "# ⚠️  NE PAS COMMITER CE FICHIER DANS GIT\n";
        $env_content .= "# =============================================================================\n\n";
        
        foreach ($env_vars as $key => $value) {
            if ($value !== '' && $value !== null) {
                $env_content .= "{$key}={$value}\n";
            }
        }
        
        if (file_put_contents(__DIR__ . '/.env', $env_content, LOCK_EX) !== false) {
            @chmod(__DIR__ . '/.env', 0600);
            $messages[] = '✅ Fichier .env créé';
        }
        
        // Générer config.php
        $config_result = generate_config_file($env_vars);
        if ($config_result['ok']) {
            $messages[] = $config_result['message'] . " ({$config_result['keys_count']} clés Mistral configurées)";
        } else {
            $errors[] = $config_result['message'];
        }
        
        // Générer .htaccess
        $htaccess_results = generate_htaccess();
        foreach ($htaccess_results as $res) {
            if ($res['ok']) {
                $messages[] = $res['message'];
            } else {
                $errors[] = $res['message'];
            }
        }
        
        // Tester la connexion Mistral si une clé est fournie
        if (!empty($env_vars['MISTRAL_KEY_1'])) {
            $mistral_test = test_mistral_connection($env_vars['MISTRAL_KEY_1']);
            if ($mistral_test['ok']) {
                $messages[] = '✅ Test Mistral API: ' . $mistral_test['message'];
            } else {
                $errors[] = '⚠️ Mistral API: ' . $mistral_test['message'];
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $messages[] = '🎉 Installation terminée avec succès !';
            $messages[] = '⚠️  SUPPRIMEZ MAINTENANT install.php ET test.php';
        }
    }
}

// =============================================================================
// INTERFACE HTML
// =============================================================================

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧬 Installation — <?= APP_NAME ?> v<?= APP_VERSION ?></title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
            --primary: #3b82f6;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 2rem;
            min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; }
        header {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        header h1 { font-size: 1.8rem; margin-bottom: 0.5rem; }
        header .version { color: var(--muted); font-size: 0.9rem; }
        
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .check-list { list-style: none; }
        .check-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }
        .check-list li:last-child { border-bottom: none; }
        .check-list .name { color: var(--muted); }
        .check-list .status { font-weight: 600; }
        .check-list .ok { color: var(--success); }
        .check-list .error { color: var(--error); }
        
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-group label { font-size: 0.9rem; color: var(--muted); }
        .form-group input, .form-group select {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.6rem 0.8rem;
            color: var(--text);
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-section { margin: 1.5rem 0; padding-top: 1rem; border-top: 1px solid var(--border); }
        .form-section h3 { font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary); }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: var(--card); border: 1px solid var(--border); }
        .btn-danger { background: var(--error); }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .alert.success { background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); }
        .alert.warning { background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); }
        
        .messages { margin: 1rem 0; }
        .messages .success { color: var(--success); }
        .messages .error { color: var(--error); }
        
        .progress {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            justify-content: center;
        }
        .progress-step {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            background: var(--card);
            color: var(--muted);
        }
        .progress-step.active {
            background: var(--primary);
            color: white;
        }
        .progress-step.done {
            background: var(--success);
            color: white;
        }
        
        .code-block {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 1rem;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 0.5rem 0;
        }
        
        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--warning);
            padding: 1rem;
            margin: 1rem 0;
        }
        
        footer {
            text-align: center;
            padding: 2rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        @media (max-width: 600px) {
            body { padding: 1rem; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>🧬 <?= APP_NAME ?></h1>
        <p class="version">Installateur v<?= APP_VERSION ?></p>
    </header>
    
    <!-- Barre de progression -->
    <div class="progress">
        <div class="progress-step <?= $step === 'check' ? 'active' : ($step !== 'check' ? 'done' : '') ?>">1. Vérifications</div>
        <div class="progress-step <?= $step === 'configure' ? 'active' : ($step === 'complete' ? 'done' : '') ?>">2. Configuration</div>
        <div class="progress-step <?= $step === 'complete' ? 'active' : '' ?>">3. Finalisation</div>
    </div>
    
    <!-- Étape 1: Vérifications système -->
    <?php if ($step === 'check'): ?>
        <div class="card">
            <h2>🔍 Vérifications système</h2>
            
            <?php
            $php_check = check_php_version();
            $ext_checks = check_extensions();
            $dir_checks = check_writable_dirs();
            $all_ok = $php_check['ok'] 
                && array_reduce($ext_checks, fn($carry, $c) => $carry && $c['ok'], true)
                && array_reduce($dir_checks, fn($carry, $c) => $carry && $c['ok'], true);
            ?>
            
            <h3>PHP</h3>
            <ul class="check-list">
                <li>
                    <span class="name"><?= $php_check['name'] ?></span>
                    <span class="status <?= $php_check['ok'] ? 'ok' : 'error' ?>">
                        <?= $php_check['message'] ?> (<?= $php_check['current'] ?>)
                    </span>
                </li>
            </ul>
            
            <h3>Extensions requises</h3>
            <ul class="check-list">
                <?php foreach ($ext_checks as $check): ?>
                <li>
                    <span class="name"><?= $check['name'] ?></span>
                    <span class="status <?= $check['ok'] ? 'ok' : 'error' ?>"><?= $check['message'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <h3>Dossiers</h3>
            <ul class="check-list">
                <?php foreach ($dir_checks as $check): ?>
                <li>
                    <span class="name"><?= $check['name'] ?></span>
                    <span class="status <?= $check['ok'] ? 'ok' : 'error' ?>"><?= $check['message'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!$all_ok): ?>
                <div class="alert error">
                    ⚠️ Certaines vérifications ont échoué. Corrigez les erreurs ci-dessus avant de continuer.
                </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <form method="get" style="flex: 1;">
                    <input type="hidden" name="step" value="check">
                    <button type="submit" class="btn btn-secondary" style="width: 100%;">🔄 Re-vérifier</button>
                </form>
                <form method="get" style="flex: 1;" onsubmit="return <?= $all_ok ? 'true' : 'false' ?>;">
                    <input type="hidden" name="step" value="configure">
                    <button type="submit" class="btn" style="width: 100%;" <?= $all_ok ? '' : 'disabled' ?>>
                        Continuer →
                    </button>
                </form>
            </div>
        </div>
        
    <!-- Étape 2: Configuration -->
    <?php elseif ($step === 'configure'): ?>
        <div class="card">
            <h2>⚙️ Configuration</h2>
            
            <?php if (!empty($messages)): ?>
                <div class="messages">
                    <?php foreach ($messages as $msg): ?>
                        <div class="success">✓ <?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <strong>Erreurs :</strong>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="post">
                <input type="hidden" name="step" value="configure">
                
                <div class="form-section">
                    <h3>🌐 Application</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="app_url">URL de l'application *</label>
                            <input type="url" id="app_url" name="app_url" 
                                   value="<?= htmlspecialchars($_POST['app_url'] ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="timezone">Fuseau horaire</label>
                            <select id="timezone" name="timezone">
                                <option value="Europe/Paris" <?= ($_POST['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris</option>
                                <option value="UTC" <?= ($_POST['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= ($_POST['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                <option value="Africa/Algiers" <?= ($_POST['timezone'] ?? '') === 'Africa/Algiers' ? 'selected' : '' ?>>Africa/Algiers</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>
                                <input type="checkbox" name="debug" <?= isset($_POST['debug']) ? 'checked' : '' ?>>
                                Mode debug (afficher les erreurs détaillées)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🔐 Administration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="admin_email">Email administrateur</label>
                            <input type="email" id="admin_email" name="admin_email" 
                                   value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Mot de passe admin</label>
                            <input type="password" id="admin_password" name="admin_password" 
                                   placeholder="Laisser vide pour désactiver l'auth">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="admin_ips">IPs autorisées (optionnel)</label>
                            <input type="text" id="admin_ips" name="admin_ips" 
                                   value="<?= htmlspecialchars($_POST['admin_ips'] ?? '') ?>" 
                                   placeholder="1.2.3.4,5.6.7.8">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🤖 Mistral API *</h3>
                    <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 1rem;">
                        Obtenez vos clés sur <a href="https://console.mistral.ai/api-keys" target="_blank" style="color: var(--primary);">console.mistral.ai</a>
                    </p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mistral_key_1">Clé API #1 *</label>
                            <input type="password" id="mistral_key_1" name="mistral_key_1" 
                                   value="<?= htmlspecialchars($_POST['mistral_key_1'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mistral_key_2">Clé API #2 (rotation)</label>
                            <input type="password" id="mistral_key_2" name="mistral_key_2" 
                                   value="<?= htmlspecialchars($_POST['mistral_key_2'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="mistral_key_3">Clé API #3 (rotation)</label>
                            <input type="password" id="mistral_key_3" name="mistral_key_3" 
                                   value="<?= htmlspecialchars($_POST['mistral_key_3'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label for="model_fast">Modèle rapide</label>
                            <select id="model_fast" name="model_fast">
                                <option value="mistral-tiny" <?= ($_POST['model_fast'] ?? '') === 'mistral-tiny' ? 'selected' : '' ?>>mistral-tiny</option>
                                <option value="open-mistral-7b" <?= ($_POST['model_fast'] ?? '') === 'open-mistral-7b' ? 'selected' : '' ?>>open-mistral-7b</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="model_balanced">Modèle équilibré</label>
                            <select id="model_balanced" name="model_balanced">
                                <option value="mistral-small-latest" <?= ($_POST['model_balanced'] ?? '') === 'mistral-small-latest' ? 'selected' : '' ?>>mistral-small-latest</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="model_deep">Modèle approfondi</label>
                            <select id="model_deep" name="model_deep">
                                <option value="mistral-medium-latest" <?= ($_POST['model_deep'] ?? '') === 'mistral-medium-latest' ? 'selected' : '' ?>>mistral-medium-latest</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="model_coder">Modèle code</label>
                            <select id="model_coder" name="model_coder">
                                <option value="codestral-latest" <?= ($_POST['model_coder'] ?? '') === 'codestral-latest' ? 'selected' : '' ?>>codestral-latest</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🔁 Boucle autonome</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="autoloop_interval">Intervalle (ms)</label>
                            <input type="number" id="autoloop_interval" name="autoloop_interval" 
                                   value="<?= $_POST['autoloop_interval'] ?? '180000' ?>" min="60000" step="1000">
                        </div>
                        <div class="form-group">
                            <label for="min_scientific_value">Score minimum</label>
                            <input type="number" id="min_scientific_value" name="min_scientific_value" 
                                   value="<?= $_POST['min_scientific_value'] ?? '0.6' ?>" min="0" max="1" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="self_improve_cycles">Auto-amélioration tous les N cycles</label>
                            <input type="number" id="self_improve_cycles" name="self_improve_cycles" 
                                   value="<?= $_POST['self_improve_cycles'] ?? '10' ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label for="max_reports_per_day">Rapports max/jour</label>
                            <input type="number" id="max_reports_per_day" name="max_reports_per_day" 
                                   value="<?= $_POST['max_reports_per_day'] ?? '50' ?>" min="1">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>📦 WordPress (optionnel)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="wp_api_url">URL API WordPress</label>
                            <input type="url" id="wp_api_url" name="wp_api_url" 
                                   value="<?= htmlspecialchars($_POST['wp_api_url'] ?? '') ?>" 
                                   placeholder="https://monsite.com/wp-json">
                        </div>
                        <div class="form-group">
                            <label for="wp_app_password">App Password WordPress</label>
                            <input type="password" id="wp_app_password" name="wp_app_password" 
                                   value="<?= htmlspecialchars($_POST['wp_app_password'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="wp_category">Catégorie par défaut</label>
                            <input type="text" id="wp_category" name="wp_category" 
                                   value="<?= htmlspecialchars($_POST['wp_category'] ?? 'recherche-autonome') ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>
                                <input type="checkbox" name="wp_auto_publish" <?= isset($_POST['wp_auto_publish']) ? 'checked' : '' ?>>
                                Publier automatiquement les rapports sur WordPress
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🔒 Sécurité</h3>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="cors_origins">Origins CORS autorisés</label>
                            <input type="text" id="cors_origins" name="cors_origins" 
                                   value="<?= htmlspecialchars($_POST['cors_origins'] ?? ($_SERVER['HTTP_ORIGIN'] ?? '')) ?>" 
                                   placeholder="https://mondomaine.com,https://autre.com">
                        </div>
                        <div class="form-group">
                            <label for="storage_type">Type de stockage</label>
                            <select id="storage_type" name="storage_type">
                                <option value="json" <?= ($_POST['storage_type'] ?? '') === 'json' ? 'selected' : '' ?>>JSON (fichiers)</option>
                                <option value="sqlite" <?= ($_POST['storage_type'] ?? '') === 'sqlite' ? 'selected' : '' ?>>SQLite</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ Important :</strong> Après installation, supprimez immédiatement les fichiers 
                    <code>install.php</code> et <code>test.php</code> pour des raisons de sécurité.
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <a href="?step=check" class="btn btn-secondary" style="flex: 1;">← Retour</a>
                    <button type="submit" class="btn" style="flex: 2;">🚀 Installer l'agent</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
        <!-- Étape 3: Finalisation -->
        <div class="card">
            <h2>🎉 Installation terminée !</h2>
            
            <div class="alert success">
                ✅ Votre Agent Scientifique Autonome est prêt à fonctionner !
            </div>
            
            <h3>📋 Prochaines étapes :</h3>
            <ol style="margin-left: 1.5rem; margin: 1rem 0;">
                <li><strong>Supprimez les fichiers d'installation :</strong>
                    <div class="code-block">rm install.php test.php</div>
                </li>
                <li><strong>Vérifiez les permissions :</strong>
                    <div class="code-block">chmod 755 data/ logs/</div>
                </li>
                <li><strong>Accédez à l'interface :</strong>
                    <div class="code-block"><a href="<?= htmlspecialchars(rtrim($_POST['app_url'] ?? '', '/') . '/index.php') ?>" target="_blank" style="color: var(--primary);">
                        <?= htmlspecialchars(rtrim($_POST['app_url'] ?? '', '/') . '/index.php') ?>
                    </a></div>
                </li>
                <li><strong>Cliquez sur "▶ Démarrer"</strong> pour lancer le premier cycle de recherche</li>
            </ol>
            
            <h3>🔗 Liens utiles :</h3>
            <ul style="margin-left: 1.5rem;">
                <li><a href="https://console.mistral.ai/api-keys" target="_blank" style="color: var(--primary);">Gérer vos clés Mistral</a></li>
                <li><a href="https://pubmed.ncbi.nlm.nih.gov" target="_blank" style="color: var(--primary);">PubMed API docs</a></li>
                <li><a href="https://rest.uniprot.org" target="_blank" style="color: var(--primary);">UniProt API docs</a></li>
            </ul>
            
            <div style="margin-top: 2rem; text-align: center;">
                <a href="<?= htmlspecialchars(rtrim($_POST['app_url'] ?? '', '/') . '/index.php') ?>" class="btn" target="_blank">
                    🧬 Accéder à l'Agent Scientifique
                </a>
            </div>
        </div>
        <?php endif; ?>
        
    <?php elseif ($step === 'complete'): ?>
        <div class="card">
            <h2>✅ Installation terminée</h2>
            <p>Rendez-vous sur <a href="index.php" style="color: var(--primary);">index.php</a> pour démarrer l'agent.</p>
            <p class="alert warning">⚠️ N'oubliez pas de supprimer <code>install.php</code> et <code>test.php</code>.</p>
        </div>
    <?php endif; ?>
    
    <footer>
        <p>🧬 <?= APP_NAME ?> v<?= APP_VERSION ?> — Licence MIT</p>
        <p style="margin-top: 0.5rem;">Projet open-source — <a href="https://github.com/LaurentVoanh" target="_blank" style="color: var(--primary);">GitHub</a></p>
    </footer>
</div>

<script>
// Auto-submit si toutes les vérifications sont OK et que l'utilisateur vient de charger la page
<?php if ($step === 'check'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const allOk = <?= $all_ok ? 'true' : 'false' ?>;
    if (allOk) {
        // Optionnel : auto-redirect après 2 secondes
        // setTimeout(() => window.location.href = '?step=configure', 2000);
    }
});
<?php endif; ?>
</script>
</body>
</html>
