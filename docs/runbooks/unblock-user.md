# Unblock a User Account

This runbook guides moderators through lifting a ban on a TravianT account while maintaining auditability.

## Prerequisites

- Access to the moderation dashboard and the production database (read/write via `php artisan tinker` or Octane console).
- Incident ticket ID documenting the unblock request.
- Confirmation that the investigation or punishment period is complete.

## Steps

1. **Review the case**
   - Read the associated alert (`multi_account_alerts`) and moderation notes.
   - Confirm that leadership or policy permits the unblock.

2. **Identify the account**
   - Use the username or email to fetch the user ID:
     ```php
     $user = App\Models\User::query()->where('username', 'playerone')->first();
     $user?->id;
     ```
   - If no record exists, stop and escalate.

3. **Lift the ban**
   - In Tinker (production environment requires SSH + `php artisan tinker` with read-only guard disabled):
     ```php
     $user->forceFill([
         'is_banned' => false,
         'ban_reason' => null,
         'ban_issued_at' => null,
         'ban_expires_at' => null,
     ])->save();
     ```
   - Confirm the change:
     ```php
     $user->fresh()->is_banned; // false
     ```

4. **Document the action**
   - Add a moderation note citing the ticket ID, staff member, and timestamp.
   - Update the incident ticket with confirmation and any follow-up instructions for the player.

5. **Verification**
   - Ask the player to log in and confirm access to `/home`.
   - Monitor `login_activities` for the next successful login to ensure it records correctly.

## Escalation

- If unblocking fails with a database error, capture the stack trace and page the on-call engineer.
- For repeated offences, consult the policy team before lifting the ban to avoid policy violations.
