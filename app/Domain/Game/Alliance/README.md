# Alliance Domain

This namespace captures services that coordinate alliance governance and collaboration mechanics between players.

## Responsibilities
- Manage alliance membership, ranks, and role-based permissions.
- Support diplomacy workflows such as pacts, wars, and invitations.
- Expose reusable helpers for shared resources, chat integrations, and internal notifications.

## Service Guidelines
- Keep orchestration logic here while persisting state through dedicated repositories or models.
- Ensure alliance services stay decoupled from UI concerns so Livewire and API layers can reuse them.
