<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Module Origins
    |--------------------------------------------------------------------------
    |
    | The HTTPS origins of every module that may be embedded as an iframe in
    | the portal shell.  Used by:
    |   - config/cors.php  (Access-Control-Allow-Origin)
    |   - portal-bridge.js (postMessage origin validation)
    |   - LoginResponse    (open-redirect guard — no longer used but kept for
    |                       reference if redirect_to is ever re-introduced)
    |
    | All entries MUST use https:// and match the SESSION_DOMAIN wildcard
    | (.deoris.test) so the session cookie is sent inside the iframe.
    |
    */

    'allowed' => [
        'https://entryease.deoris.test',
        'https://enrollease.deoris.test',
        'https://gradetrack.deoris.test',
        'https://meditrack.deoris.test',
        'https://librarysys.deoris.test',
        'https://taskflow.deoris.test',
        'https://careerconnect.deoris.test',
        'https://assesspay.deoris.test',
        'https://votesys.deoris.test',
        'https://clearcheck.deoris.test',
    ],

];

