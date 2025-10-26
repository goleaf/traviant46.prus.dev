# Add an Allowed IP or CIDR

This runbook explains how to exempt trusted networks from multi-account alerts and maintenance lockdowns.

## Context

- `config/multiaccount.php` controls alert heuristics. Allowlists are derived from:
  - `MULTIACCOUNT_ALLOWLIST_IPS`
  - `MULTIACCOUNT_ALLOWLIST_CIDRS`
  - `MULTIACCOUNT_ALLOWLIST_DEVICE_HASHES`
- Maintenance bypass for legacy staff accounts is managed via `config/game.php` (`GAME_MAINTENANCE_ENABLED`, `User::reservedLegacyUids()`).

## Steps

1. **Confirm the request**
   - Validate that the IP/CIDR belongs to a trusted partner or office network.  
   - Check recent alerts to ensure the exemption will not hide active investigations.

2. **Update secrets**
   - Edit the environment configuration (e.g., `production/.env`) to append the new entry:
     ```
     MULTIACCOUNT_ALLOWLIST_IPS=1.2.3.4,5.6.7.8
     MULTIACCOUNT_ALLOWLIST_CIDRS=10.0.0.0/24,203.0.113.0/28
     ```
   - Keep entries comma-separated with no spaces.
   - Document the change in the shared allowlist register (Notion: “Security Allowlist”).

3. **Deploy**
   - Redeploy the application or reload secrets so the new environment values are applied.
   - Clear cached configuration:
     ```bash
     php artisan config:clear
     php artisan cache:clear
     ```

4. **Verification**
   - Use `php artisan tinker` to confirm the value is loaded:
     ```php
     config('multiaccount.allowlist.ip_addresses');
     ```
   - Trigger a staged login from the allowlisted IP (QA VPN) and verify that no new multi-account alert is created.
   - Ensure maintenance middleware allows access if the server is in maintenance mode.

5. **Notify stakeholders**
   - Inform the requesting team and security operations.  
   - Set a calendar reminder to review allowlist entries quarterly.

## Removal

To remove an IP/CIDR, delete it from the environment variable, redeploy, and monitor alerts to ensure the removal behaves as expected.
