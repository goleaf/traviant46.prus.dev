# TravianT4.6
A really nice travian script.

Needs
php 7.3-7.4
geoip php extension
redis php extension

## Background processing

Background jobs and scheduled tasks are handled through Laravel's queue and
scheduler tooling.  See [docs/queue-system.md](docs/queue-system.md) for the
architecture and deployment requirements.

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

### 8.3 Testing

- Unit tests for game formulas
- Feature tests for critical flows (battle, building, trading)
- Integration tests for job system
- Load testing for concurrent users
- Test all 5 tribes (Romans, Teutons, Gauls, Egyptians, Huns)
