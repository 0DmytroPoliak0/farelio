# farelio
Cheapest trip planner in PHP. Enter From and To and get flight plus hotel bundles with one clear total. Runs on XAMPP with MySQL.

Simple PHP app on XAMPP. Finds a few cheapest flight + hotel bundles and shows clear totals.

## Run locally (XAMPP)
1) Create DB `travel_app` in phpMyAdmin.
2) Import `db/schema.sql`, then `db/seed.sql`.
3) Copy `.env.example` to `.env` and set DB values.
4) Place repo in `htdocs/triptotal`.
5) Open http://localhost/triptotal/public (http://localhost/farelio/public/index.php)

## Folders
- /public       entry point (index.php)
- /app          PHP includes and logic
- /assets       css, js, images
- /db           schema.sql and seed.sql

## Team workflow
- Always Pull before you start.
- Commit small changes with clear messages.
- Do not commit `.env`.
