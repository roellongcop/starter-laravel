-- Runs once, only when the postgres data volume is first initialized
-- (/docker-entrypoint-initdb.d). Creates the database the integration test suite
-- (`make test-pg`, group "pg") uses, so it never touches the dev database.
-- For existing volumes the script won't re-run; `make test-pg` creates it on demand.
SELECT 'CREATE DATABASE keen_admin_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'keen_admin_test')\gexec
