$ErrorActionPreference = 'Stop'
Push-Location (Split-Path $PSScriptRoot -Parent)
$previous = @{}
try {
    foreach ($line in [System.IO.File]::ReadAllLines((Join-Path (Get-Location) '.env.testing'))) {
        if ($line -match '^([^#=]+)=(.*)$') {
            $name = $matches[1]
            $value = $matches[2]
            $previous[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
            [Environment]::SetEnvironmentVariable($name, $value, 'Process')
        }
    }
    php tests/verify-disposable-postgres.php
    if ($LASTEXITCODE -ne 0) { throw 'Database verification failed. No migration or database tests ran.' }
    $previous['ALLOW_DISPOSABLE_DATABASE_TESTS'] = $env:ALLOW_DISPOSABLE_DATABASE_TESTS
    $env:ALLOW_DISPOSABLE_DATABASE_TESTS = '1'
    php artisan migrate --env=testing --force --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Disposable test schema migration failed.' }
    php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --filter 'ContactTurnstileTest|LoginTest|EcImpactAssessmentFormCreationTest|ImpactAssessmentSubmissionTest|ProgramDeletionTest|ParticipantAccountIdTest|PublicEcRegistrationTest|PublicEcRegistrationRouteTest'
    if ($LASTEXITCODE -ne 0) { throw 'Targeted functional tests failed.' }
} finally {
    foreach ($name in $previous.Keys) {
        [Environment]::SetEnvironmentVariable($name, $previous[$name], 'Process')
    }
    Pop-Location
}
