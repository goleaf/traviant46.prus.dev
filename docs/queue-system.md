# Background task and scheduler overview

This project does **not** use Laravel's queue features. Instead, background
work is coordinated by the custom TaskWorker that ships with the repository.
The worker polls the MySQL `taskQueue` table for pending tasks and executes
them through the provisioning logic found under `TaskWorker/include/Core`.

The components involved are:

- **Task queue database table** – Each entry in `taskQueue` represents a unit
  of work created by the control panel. New rows default to the `pending`
  status until processed by the worker, which will mark them as either `done`
  or `failed`.
- **Task worker service** – `TaskWorker/runTasks.php` boots the framework,
  connects to the global database configured in `globalConfig.php`, and loops
  forever. Every five seconds it selects pending tasks, instantiates
  `Core\Task`, and forwards the work to `Core\ServerManager`.
- **Server actions** – `ServerManager` implements the concrete task handlers
  for job types such as `install`, `uninstall`, `flushTokens`,
  `restart-engine`, `start-engine`, and `stop-engine`.
- **Maintenance cron** – The `Manager/sync.sh` helper installs a cron entry
  that runs `travian --cron` daily. The cron mode performs housekeeping by
  deleting leftover archive files under `/travian` and `/home`.

## Installing the worker

Deployment is driven by `Manager/sync.sh`. Running

```bash
sudo ./Manager/sync.sh --install
```

(from the project root) links the packaged systemd unit files in
`services/main` and ensures the TaskWorker script is executable. The critical
unit is `TravianTaskWorker.service`, which launches the PHP worker located at
`/travian/TaskWorker/runTasks.php` and restarts it automatically if it exits.

After installation the usual systemd commands can be used to control the
worker:

```bash
sudo systemctl status TravianTaskWorker.service
sudo systemctl restart TravianTaskWorker.service
```

Running the service is required for any background provisioning tasks to
complete.

## Adding new task types

1. Insert pending rows into the `taskQueue` table with the desired `type`,
   JSON `data`, and optional description. The worker processes tasks in the
   order they appear in the table.
2. Extend `Core\\ServerManager` with a method that handles the new `type` and
   updates the task via `setAsCompleted()` or `setAsFailed()`.
3. Update the `switch` statement inside `TaskWorker/runTasks.php` so the new
   type is routed to the method you created.

The worker automatically picks up the new type the next time it polls the
queue; no additional configuration is required.

## Housekeeping and scheduling

The cron entry installed by `Manager/sync.sh` invokes `travian --cron` daily at
midnight. This mode only performs maintenance (removing stale archives) and
runs independently from the task queue. Additional recurring jobs should be
implemented as new cron modes inside `sync.sh` so that they can share the same
operational path.
