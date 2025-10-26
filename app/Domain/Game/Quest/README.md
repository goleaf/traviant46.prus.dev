# Quest Domain

This namespace groups services that manage tutorial, daily, and special quest flows for players.

## Responsibilities
- Evaluate quest objectives, track progress, and determine completion state per user.
- Coordinate reward distribution, including resource grants, hero items, and unlock tokens.
- Provide summaries consumed by UI layers, notifications, or scheduled jobs.

## Service Guidelines
- Keep quest evaluation idempotent so repeated checks do not double-award rewards.
- Store persistence logic in repositories or actions, leaving services focused on orchestration and formatting.
