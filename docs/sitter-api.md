# Sitter API Reference

Authenticated players can manage sitter delegations through the REST API surface exposed by `App\Http\Controllers\SitterController`. All routes require:

- An active Fortify-authenticated session (`auth` middleware).
- Email verification (`verified` middleware).
- Passing custom guards (`game.verified`, `game.banned`, `game.maintenance`).

Base URL: `https://<host>/sitters`

## Representation

```json
{
  "id": 1,
  "sitter": {
    "id": 42,
    "username": "ally_lead",
    "name": "Ally Lead"
  },
  "permissions": ["marketplace.manage", "army.send"],
  "effective_permissions": ["marketplace.manage", "army.send"],
  "preset": "custom",
  "expires_at": "2025-03-01T09:00:00Z",
  "created_at": "2025-02-18T16:12:58Z",
  "updated_at": "2025-02-18T16:15:09Z"
}
```

- `permissions` is nullable; when `null` it denotes full access and `effective_permissions` lists every capability.
- `preset` reveals the matched `SitterPermissionPreset` (`full`, `farming`, `minimal`, or `custom`).
- Delegations are stored in the `sitter_delegations` table.

## Endpoints

### List assignments

`GET /sitters`

Returns all sitter delegations, eager loading sitter metadata and revealing sitter context for the current session.

#### Response

```json
{
  "data": [
    {
      "id": 7,
      "sitter": {
        "id": 37,
        "username": "faction_mate",
        "name": "Faction Mate"
      },
      "permissions": null,
      "expires_at": null,
      "created_at": "2025-02-10T12:01:44Z"
    }
  ],
  "acting_as_sitter": false,
  "acting_sitter_id": null,
  "available_permissions": [
    {
      "value": "army.send",
      "label": "Send troops"
    },
    {
      "value": "marketplace.manage",
      "label": "Manage marketplace"
    }
  ],
  "presets": [
    {
      "value": "farming",
      "label": "Farming support",
      "description": "Can raid, cannot spend gold",
      "permissions": [
        "army.send"
      ]
    },
    {
      "value": "full",
      "label": "Full access",
      "description": "All permissions enabled",
      "permissions": []
    }
  ]
}
```

### Create or update an assignment

`POST /sitters`

```json
{
  "sitter_username": "faction_mate",
  "permissions": ["marketplace.manage", "village.view_only"],
  "expires_at": "2025-03-01T09:00:00Z"
}
```

- The combination of `owner_user_id` (account owner) and `sitter_user_id` (delegated sitter) is unique. Reposting updates permissions and expiry.
- Assignments are idempotent; updating returns `201 Created` with the refreshed resource.

#### Success Response

HTTP `201 Created`

```json
{
  "data": {
    "id": 7,
    "owner_user_id": 18,
    "sitter_user_id": 37,
    "permissions": ["marketplace.manage", "village.view_only"],
    "expires_at": "2025-03-01T09:00:00Z",
    "created_by": 18,
    "updated_by": 18,
    "created_at": "2025-02-18T16:12:58Z",
    "updated_at": "2025-02-18T16:15:09Z",
    "sitter": {
      "id": 37,
      "username": "faction_mate",
      "name": "Faction Mate"
    }
  }
}
```

### Remove an assignment

`DELETE /sitters/{sitter}`

- The `{sitter}` parameter uses the sitter's numeric user ID (resolved via route model binding).
- Detaches both the pivot (`user_sitters`) and row in `sitter_delegations`.

#### Success Response

HTTP `204 No Content`

## Error Handling

| Scenario | Status | Payload |
|----------|--------|---------|
| Missing auth/verification | `401 Unauthorized` or `403 Forbidden` | Standard Fortify JSON |
| Username not provided | `422 Unprocessable Entity` | `{"errors":{"sitter_username":["The sitter username field is required."]}}` |
| Username does not exist | `422 Unprocessable Entity` | `{"errors":{"sitter_username":["The selected sitter username is invalid."]}}` |
| Assigning self | `422 Unprocessable Entity` | `{"message":"You cannot assign yourself as a sitter."}` |
| Invalid permissions payload | `422 Unprocessable Entity` | Validation errors for `permissions` or `permissions.*` |
| Expiry parse failure | `422 Unprocessable Entity` | `expires_at` must be a valid date string |

## Operational Notes

- Use `SitterDelegation::active()` to filter out expired rows when building dashboards.
- API responses include `acting_as_sitter` and `acting_sitter_id` to surface when the current session is operating under delegated access, enabling clients to display context warnings.
- Rate limiting leverages core Laravel middleware; heavy automation should back off on 429 responses.
