<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * DEORIS-pinned PersonalAccessToken.
 *
 * Extends Sanctum's default model to force all token lookups onto the
 * named 'deoris' connection, which always points to deoris_identity_db.
 *
 * This prevents a XAMPP connection-reuse bug where a module's MySQL
 * connection (e.g. assespaydb) bleeds into DEORIS's token table lookups
 * when Apache reuses the same PHP worker thread across vhosts.
 *
 * @see config/database.php  'deoris' connection
 * @see app/Providers/AppServiceProvider.php  Sanctum::usePersonalAccessTokenModel()
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Always use the named 'deoris' connection — never the default.
     * The default connection can be contaminated by module DB_DATABASE
     * values when PHP worker threads are reused across vhosts in XAMPP.
     */
    protected $connection = 'deoris';
}
