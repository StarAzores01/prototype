<?php

// Read-only preflight. It never reads .env, creates schema, or changes data.
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

function requireTestCondition(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

try {
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env.testing'));
    foreach ([
        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '5432', 'DB_DATABASE' => 'pathrive_merge_test_20261019_9168ed6a', 'DB_USERNAME' => 'pathrive_merge_role_20261019_9168ed6a',
        'DB_URL' => '', 'APP_CONFIG_CACHE' => 'bootstrap/cache/testing-config.php',
        'APP_ROUTES_CACHE' => 'bootstrap/cache/testing-routes.php',
    ] as $key => $expected) {
        requireTestCondition(($settings[$key] ?? null) === $expected, 'Unexpected test setting: '.$key);
    }
    requireTestCondition(! empty($settings['DB_PASSWORD']), 'Missing dedicated test password.');
    requireTestCondition(! is_file($root.'/'.$settings['APP_CONFIG_CACHE']), 'Remove only the testing configuration cache before testing.');
    requireTestCondition(! is_file($root.'/'.$settings['APP_ROUTES_CACHE']), 'Remove only the testing routes cache before testing.');
    foreach ($settings as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
    $app = require $root.'/bootstrap/app.php';
    $app->loadEnvironmentFrom('.env.testing');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    requireTestCondition($app->environment('testing') && ! $app->configurationIsCached(), 'Unsafe application configuration.');
    foreach (['host' => '127.0.0.1', 'port' => '5432', 'database' => 'pathrive_merge_test_20261019_9168ed6a', 'username' => 'pathrive_merge_role_20261019_9168ed6a'] as $key => $value) {
        requireTestCondition((string) config('database.connections.pgsql.'.$key) === $value, 'Unexpected effective PostgreSQL setting: '.$key);
    }
    requireTestCondition(config('database.default') === 'pgsql', 'Wrong effective driver.');
    requireTestCondition(empty(config('database.connections.pgsql.url')), 'DB_URL must be empty.');
    requireTestCondition(config('database.connections.pgsql.password') === $settings['DB_PASSWORD'], 'Unexpected effective test credentials.');
    $db = Illuminate\Support\Facades\DB::connection();
    $identity = $db->selectOne(
        "SELECT current_database() AS name, current_user AS role, inet_server_addr()::text AS host,
            pg_get_userbyid(d.datdba) AS owner, shobj_description(d.oid, 'pg_database') AS marker
         FROM pg_database d WHERE d.datname = current_database()"
    );
    requireTestCondition($identity->name === 'pathrive_merge_test_20261019_9168ed6a' && $identity->role === 'pathrive_merge_role_20261019_9168ed6a'
        && $identity->owner === 'pathrive_merge_role_20261019_9168ed6a' && in_array($identity->host, ['127.0.0.1/32', '127.0.0.1', '::1/128', '::1'], true)
        && $identity->marker === 'PAThrive disposable local functional tests', 'Database identity or disposable marker mismatch.');
    $role = $db->selectOne('SELECT rolsuper, rolcreatedb, rolcreaterole, rolreplication FROM pg_roles WHERE rolname = current_user');
    foreach (['rolsuper', 'rolcreatedb', 'rolcreaterole', 'rolreplication'] as $attribute) {
        requireTestCondition(! $role->$attribute, 'The test role has cluster administration privileges.');
    }
    echo "Verified local disposable pathrive_merge_test_20261019_9168ed6a, dedicated restricted owner, test credentials, and uncached testing configuration.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Test preflight refused: ".$exception->getMessage()."\n");
    exit(1);
}
