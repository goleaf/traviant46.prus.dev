# TravianT4.6
A really nice travian script.

Needs
php 7.3-7.4
geoip php extension
redis php extension

everything is here to install and setup servers
all generic domain has example.com feel free to replace to your domain
server used nginx and mysql

i can explain how to step by step do it.
if you are compitent then you can figure it out

enjoy if anyone does want instructions to insstall figure it out and make a pull request editing this readme.
this is a fan made recreation and not a part of any offical trvian company source etc etc.

search for 
YOUR_DOMAIN
USERNAME_HERE
REDACTED

---

# Legacy Travian Archive

This directory preserves the legacy Travian PHP codebase inside a single `_travian/` namespace.  Each subdirectory mirrors the
old `main_script` layout so future migrations can reference the original relative paths without keeping the files at the project
root.

| `_travian/` path | Legacy source | Notes |
| ---------------- | ------------- | ----- |
| `controllers/`   | `main_script/include/Controller/` | Controllers, AJAX endpoints, and dispatchers. |
| `models/`        | `main_script/include/Model/` | Data-access and business logic models. |
| `services/`      | `main_script/include/Game/` | Game helper classes and calculators. |
| `views/`         | `main_script/include/resources/Templates/` | Legacy template fragments. |
| `public/`        | `main_script/copyable/public/` | Public assets copied during deployment. |
| `config/`        | `main_script/include/config/` | Legacy configuration files. |
| `schema/`        | `main_script/include/schema/` | SQL dumps and schema assets. |
| `core/`          | `main_script/include/Core/` | Core framework utilities. |
| `legacy/`        | Remaining `main_script` and ancillary tools (`services/`, `TaskWorker/`, etc.). | Catch-all archive. |

When porting files from the legacy system, copy them into the matching `_travian/` subdirectory while maintaining their original
internal structure.  This preserves predictable include paths for documentation and tooling that still references the classic
framework while the Laravel rewrite progresses.
