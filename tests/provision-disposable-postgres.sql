\set ON_ERROR_STOP on
-- This script only creates a NEW local role and database. It never drops or
-- reuses an existing database, and stops if either requested name exists.
SELECT current_database() = 'postgres'
    AND inet_server_addr() IN ('127.0.0.1'::inet, '::1'::inet) AS local_admin_connection \gset
\if :local_admin_connection
\else
    \echo 'Refusing provisioning outside the local postgres maintenance database.'
        SELECT 1 / 0; -- ON_ERROR_STOP guarantees a nonzero exit status.
\endif
SELECT NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'pathrive_merge_test_20261019_9168ed6a')
    AND NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'pathrive_merge_role_20261019_9168ed6a') AS names_available \gset
\if :names_available
\else
    \echo 'Refusing to reuse existing pathrive_merge_test_20261019_9168ed6a or pathrive_merge_role_20261019_9168ed6a; inspect their ownership and purpose first.'
        SELECT 1 / 0; -- ON_ERROR_STOP guarantees a nonzero exit status.
\endif
\getenv test_password PATHRIVE_TEST_PASSWORD
CREATE ROLE pathrive_merge_role_20261019_9168ed6a LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION PASSWORD :'test_password';
CREATE DATABASE pathrive_merge_test_20261019_9168ed6a OWNER pathrive_merge_role_20261019_9168ed6a;
COMMENT ON DATABASE pathrive_merge_test_20261019_9168ed6a IS 'PAThrive disposable local functional tests';
REVOKE ALL ON DATABASE pathrive_merge_test_20261019_9168ed6a FROM PUBLIC;
