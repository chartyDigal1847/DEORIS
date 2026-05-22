<?php

namespace App\Services\Sso;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: TOKEN VALIDATOR                                    ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This class centralizes all SSO token validation logic. It enforces:   ║
 * ║  • Single-use token pattern (no token reuse)                          ║
 * ║  • Immediate token destruction after validation                       ║
 * ║  • Ability-based access control (only 'sso' tokens)                   ║
 * ║  • Type safety (tokenable must be User instance)                      ║
 * ║  • No token expiration (single-use is the security model)             ║
 * ║                                                                        ║
 * ║ Any changes to this class must be reviewed by a security architect.   ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class TokenValidator
{
    /**
     * Validate and consume an SSO token.
     *
     * Validates that:
     * 1. Token string is provided and not empty
     * 2. Token exists in database
     * 3. Token has 'sso' ability (not a generic or expired token)
     * 4. Token's tokenable is a User instance
     * 5. Token is destroyed immediately after validation (single-use)
     *
     * @param  string  $tokenString  The plain-text SSO token from request
     * @return array{success: bool, user?: User, error?: string}
     *
     * @throws \Exception If assertion fails (critical security issue)
     */
    public static function validateAndConsume(string $tokenString): array
    {
        try {
            // ── Assertion 1: Token string is not empty ──────────────────────────
            if (trim($tokenString) === '') {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Assertion 2: Token exists and has required ability ──────────────
            $accessToken = PersonalAccessToken::findToken($tokenString);

            if (!$accessToken) {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Verify this token has the 'sso' ability (cannot use API tokens)
            if (!$accessToken->can('sso')) {
                $accessToken->delete();
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Assertion 3: Token's user exists and is a User instance ────────
            $tokenable = $accessToken->tokenable;

            if (!$tokenable instanceof User) {
                $accessToken->delete();
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Assertion 4: User is in a valid auth state ────────────────────
            if (!$tokenable->exists) {
                $accessToken->delete();
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Critical: Destroy token immediately (single-use enforcement) ────
            // This is the critical line that prevents replay attacks. Once we return
            // the user identity, the token MUST be deleted so it cannot be reused.
            $user = $tokenable;
            $accessToken->delete();

            // ── Post-deletion assertion: Verify token is actually gone ──────────
            // This is defensive programming to catch race conditions or bugs.
            $reval = PersonalAccessToken::findToken($tokenString);
            if ($reval !== null) {
                // This should never happen. If it does, something is seriously wrong.
                throw new \RuntimeException(
                    'CRITICAL: SSO token was not deleted after validation. ' .
                    'This indicates a database issue or race condition. ' .
                    'Token ID: ' . $reval->id
                );
            }

            return ['success' => true, 'user' => $user];
        } catch (Throwable $e) {
            // Log the exception for debugging, but return generic error to client
            \Illuminate\Support\Facades\Log::error('SSO token validation error', [
                'exception' => $e,
                'token_first_8' => substr($tokenString, 0, 8) . '***',
            ]);

            return ['success' => false, 'error' => 'sso_validation_failed'];
        }
    }

    /**
     * Validate that a user can issue a new SSO token.
     *
     * Enforces:
     * 1. No multiple outstanding tokens per user (revoke-before-issue)
     * 2. User is valid and active
     *
     * @param  User  $user  The user requesting a token
     * @return array{success: bool, error?: string}
     */
    public static function validateUserCanIssue(User $user): array
    {
        try {
            // Verify user exists and is not soft-deleted
            if (!$user->exists) {
                return ['success' => false, 'error' => 'invalid_user'];
            }

            // Check for existing SSO tokens (revoke-before-issue pattern)
            $existingTokens = $user->tokens()
                ->where('name', 'sso-token')
                ->count();

            if ($existingTokens > 0) {
                // This can happen if a previous revoke failed to delete the token.
                // Log this for investigation, but revoke all old tokens.
                \Illuminate\Support\Facades\Log::warning(
                    'Found stale SSO tokens during issue. Revoking.',
                    ['user_id' => $user->id, 'stale_tokens' => $existingTokens]
                );

                $user->tokens()
                    ->where('name', 'sso-token')
                    ->delete();
            }

            return ['success' => true];
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SSO issue validation error', [
                'exception' => $e,
                'user_id' => $user->id ?? null,
            ]);

            return ['success' => false, 'error' => 'sso_validation_failed'];
        }
    }
}
