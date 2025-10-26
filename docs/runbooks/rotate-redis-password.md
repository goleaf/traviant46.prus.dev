# Rotate Redis Password

This runbook covers rotating credentials for the Redis instances used by sessions, cache, and queues.

## Prerequisites

- Access to the managed Redis console (e.g., AWS ElastiCache, Azure Cache, or DigitalOcean Redis).
- Ability to update secret storage (1Password vault: `TravianT/Production/Redis`).
- SSH access to application servers or deployment pipeline credentials.

## Steps

1. **Schedule a maintenance window**  
   - Coordinate with support to avoid key gameplay events.  
   - Announce a 5–10 minute read-only window in the moderator Slack channel.

2. **Generate a new password**  
   - Use the provider console to trigger credential rotation, or generate a 64-character random string.  
   - Update the shared secret vault entry with the new password and timestamp the change.

3. **Update Redis service**  
   - Apply the new password to the Redis instance. For clustered setups, rotate primary then replicas.  
   - Verify the instance is accepting connections using the provider's diagnostic console (`AUTH <password>` followed by `PING`).

4. **Deploy application configuration**  
   - Update the environment variables for each environment:
     - `REDIS_PASSWORD`
     - `REDIS_URL` (if using a URL-based connection string)  
   - Commit changes to deployment configuration repositories if required. Do **not** commit secrets to Git.

5. **Restart workers & Horizon**  
   - Run `php artisan horizon:terminate` on each application host (systemd will restart it).  
   - Restart queue workers or containers so they pick up the new environment variables.  
   - Reload PHP-FPM / Octane instances if applicable.

6. **Verification**
   - Execute `php artisan tinker` and run:
     ```php
     Redis::connection('default')->command('PING');
     Redis::connection('session')->command('PING');
     ```
     Both should return `PONG`.
   - Run a login via the QA account to confirm sessions persist.
   - Check the application logs for connection errors (`storage/logs/laravel.log`).

7. **Close the window**
   - Notify support that rotation completed successfully.  
   - Monitor Horizon and Redis dashboards for 15 minutes for lingering issues.

## Rollback

If clients fail to authenticate:

- Reapply the previous password from the secret vault history.  
- Redeploy prior environment variables.  
- Investigate connection counts and timeouts before retrying the rotation.
