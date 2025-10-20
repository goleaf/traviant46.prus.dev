# Staff Role Matrix

The new policy layer centralises authorisation for the product-facing tools. The
matrix below documents which staff roles have access to each domain.

| Role | Core Catalog & Products | Orders | Customers | Legal Library | Settings |
| --- | --- | --- | --- | --- | --- |
| Administrator | ✅ Full access | ✅ Full access | ✅ Full access | ✅ Full access | ✅ Full access |
| Product Manager | ✅ Manage catalog & products | ✅ Manage orders | ✅ Manage customers | ❌ No access | ❌ No access |
| Catalog Manager | ✅ Manage catalog & products | ❌ No access | ❌ No access | ❌ No access | ❌ No access |
| Order Manager | ❌ No access | ✅ Manage orders | ✅ Assist customers | ❌ No access | ❌ No access |
| Customer Support | ❌ No access | ❌ No access | ✅ Manage customers | ❌ No access | ❌ No access |
| Legal | ❌ No access | ❌ No access | ❌ No access | ✅ Review & update legal docs | ❌ No access |
| Settings Manager | ❌ No access | ❌ No access | ❌ No access | ❌ No access | ✅ Manage platform settings |
| Viewer | ✅ Read-only catalog listings | ❌ No access | ❌ No access | ❌ No access | ❌ No access |
| Player (default) | ❌ No access | ❌ No access | ❌ No access | ❌ No access | ❌ No access |

* Viewers rely on policy `viewAny` permissions without create/update access.
* Players represent non-staff accounts and are blocked from every admin domain.

Roles are stored on the `users.role` column (`App\Enums\StaffRole`) and are
referenced by the new policies to keep authorisation rules consistent across the
front-end controller and Filament resources.
