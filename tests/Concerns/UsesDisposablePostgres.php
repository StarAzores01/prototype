<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait UsesDisposablePostgres
{
    protected function beginDisposableDatabaseTransaction(): void
    {
        // Fail before connecting or writing unless the local fixture database
        // has been explicitly confirmed disposable by the test operator.
        $this->assertSame('1', (string) getenv('ALLOW_DISPOSABLE_DATABASE_TESTS'));
        $this->assertTrue(app()->environment('testing'));
        $this->assertFalse(app()->configurationIsCached());
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('pgsql', config('database.connections.pgsql.driver'));
        $this->assertContains(config('database.connections.pgsql.host'), ['127.0.0.1', 'localhost', '::1']);
        $this->assertSame('pathrive_review_test_20261019_62bc983c', config('database.connections.pgsql.database'));
        $this->assertEmpty(config('database.connections.pgsql.url'));
        $this->assertSame('pathrive_review_role_20261019_62bc983c', config('database.connections.pgsql.username'));
        $settings = \Dotenv\Dotenv::parse(file_get_contents(base_path('.env.testing')));
        $this->assertNotEmpty($settings['DB_PASSWORD'] ?? '');
        $this->assertSame($settings['DB_PASSWORD'], config('database.connections.pgsql.password'));
        $connection = DB::connection();
        $this->assertSame('pathrive_review_test_20261019_62bc983c', $connection->getDatabaseName());
        $this->assertSame('pgsql', $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame('pathrive_review_test_20261019_62bc983c', $connection->selectOne('SELECT current_database() AS name')->name);
        $identity = $connection->selectOne(
            "SELECT current_user AS role, pg_get_userbyid(d.datdba) AS owner,
                shobj_description(d.oid, 'pg_database') AS marker
             FROM pg_database d WHERE d.datname = current_database()"
        );
        $this->assertSame('pathrive_review_role_20261019_62bc983c', $identity->role);
        $this->assertSame('pathrive_review_role_20261019_62bc983c', $identity->owner);
        $this->assertSame('PAThrive disposable local functional tests', $identity->marker);
        $role = $connection->selectOne('SELECT rolsuper, rolcreatedb, rolcreaterole, rolreplication FROM pg_roles WHERE rolname = current_user');
        foreach (['rolsuper', 'rolcreatedb', 'rolcreaterole', 'rolreplication'] as $attribute) {
            $this->assertFalse((bool) $role->$attribute, 'The test role must have no cluster administration privileges.');
        }
        $connection->beginTransaction();
        $this->beforeApplicationDestroyed(function () use ($connection) {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack(0);
            }
        });
    }
}
