# Deploying to Railway

So your professor (or anyone else) can open a live URL and see whatever is
currently on `main`, without you running anything locally.

Railway watches your GitHub repo and redeploys automatically on every push —
that's the "live updates from GitHub" part. This file is the one-time setup;
after that, `git push` is the whole workflow.

## 1. Create the project

1. Go to [railway.app](https://railway.app) and sign in with GitHub.
2. **New Project → Deploy from GitHub repo** → pick `Student_Portfolio_System`.
3. Railway will try to build immediately and fail — that's expected, there's
   no database or environment variables yet. Keep going.
4. Leave **Settings → Root Directory** blank. The Laravel app is the repo
   root on GitHub (`composer.json`, `artisan`, `app/` all sit directly in
   `Student_Portfolio_System`) — `beta/cpe-plo-portfolio` was only ever a
   local folder name on disk, never part of the git repo itself.

## 2. Add the database

1. In the same project, **New → Database → Add MySQL**.
2. That's it — Railway provisions it and exposes connection variables to
   the rest of the project automatically.

## 3. Environment variables

Open your app service (not the database) → **Variables**, and add these.
Where a value says `${{...}}`, that's Railway's reference syntax — type it
literally; Railway resolves it to the MySQL service's real value.

```
APP_NAME=CpE Student Development Portfolio
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
APP_URL=https://<your-app>.up.railway.app

APP_LOCALE=en
APP_FAKER_LOCALE=en_PH

INSTITUTION_NAME=University of Saint Louis Tuguegarao
INSTITUTION_UNIT=School of Architecture, Computing and Engineering
INSTITUTION_DEPARTMENT=Computer Engineering Department
INSTITUTION_PROGRAM=BS Computer Engineering

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_LIFETIME=120
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS=portfolio@usl.edu.ph
MAIL_FROM_NAME="${APP_NAME}"

PLO_ATTAINMENT_TARGET=3.00
PLO_MIN_EVIDENCE_QUALITY=3
```

Then add one more, generated locally — Railway has no shell to run
`artisan key:generate` against before the first deploy:

```bash
php artisan key:generate --show
```

Copy the `base64:...` output into an `APP_KEY` variable in Railway.

`APP_URL` needs the real Railway-assigned domain, which you only get after
the first successful deploy — come back and fix it once you have it (under
**Settings → Networking → Generate Domain** if one wasn't assigned
automatically).

## 4. Add a persistent volume

Container filesystems reset on every deploy. Without a volume, every
uploaded evidence file and 2x2 photo would vanish the next time you push.

1. On your app service, **Settings → Volumes → New Volume**.
2. Mount path: `/app/storage/app`
3. Redeploy once after adding it.

## 5. First deploy and seeding

Once variables and the volume are in place, trigger a deploy (Railway does
this automatically after you save variables, or use **Deploy** manually).

`nixpacks.toml` in the repo already runs migrations on every boot. To seed
demo accounts so your professor has something to click into immediately,
run one command from Railway's web shell (service → **... → Shell** in the
dashboard, or `railway run` via their CLI if you install it locally):

```bash
php artisan db:seed
```

Demo logins afterward (all use password `password`):

| Role | Email |
| --- | --- |
| Student | `student@usl.edu.ph` |
| Faculty | `villanueva@usl.edu.ph` |
| Chair | `chair@usl.edu.ph` |
| Admin | `admin@usl.edu.ph` |

Delete `database/seeders/DemoDataSeeder.php` (see the README's note on this)
before anyone puts real student data in.

## 6. Sharing it

Send your professor the Railway-generated URL
(`https://<your-app>.up.railway.app`). Every push to `main` redeploys it in
place — same link, updated content, no action needed on their end.

## Known limitations of this setup

- `php artisan serve` is a development server; it's fine for a class
  project at low traffic, but if this ever needs to handle real concurrent
  users, switch the start command in `nixpacks.toml` to a proper
  PHP-FPM + nginx/Caddy setup (or Laravel Octane).
- `php artisan schedule:run` (deadline reminders, overdue flags) needs
  something to actually trigger it on a timer. Railway doesn't run cron for
  you on the hobby plan — add a **Cron Job** service in the same project
  pointed at `php artisan schedule:run`, scheduled hourly, if you want
  reminders to fire without you doing it by hand.
