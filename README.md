# txexpresslend-backend

The TX Express Lend website on WordPress. The markup is ported from
[txexpresslend-frontend](https://github.com/ReinmandTrial/txexpresslend-frontend).
All texts and images are editable in the admin.

## Project layout

```
docker-compose.yml                    local environment: WordPress + MySQL + Mailpit
scripts/setup.sh                      one-command site installation (locally and on the server)
scripts/server-setup.sh               server provisioning: nginx, PHP, MariaDB, HTTPS
scripts/deploy.sh                     uploads the code to the server
scripts/seed.php                      content from the design: pages, images, fields, menus
scripts/seed-images/                  images for seed.php
wp-content/mu-plugins/local-mail.php  sends all local mail to Mailpit
wp-content/themes/txexpresslend/      the theme: most of the work happens here
```

WordPress core and plugins aren't stored in git. They live in a docker volume and are installed by `setup.sh`.

### Theme

| File | What it is |
|---|---|
| `header.php`, `footer.php` | header with the mobile menu and the footer, shared by all pages |
| `page.php` | every page, the home page included: the page builder blocks |
| `inc/blocks.php` | the page builder: list of block types, spacing between blocks, one h1 per page |
| `template-parts/blocks/` | one file per block type, markup taken from the design |
| `template-parts/benefits.php` | list of benefits next to the full-screen form blocks |
| `404.php`, `index.php` | "not found" page and the fallback template |
| `inc/` | PHP logic: asset loading, fields, forms, leads, helpers |
| `acf-json/` | admin field groups (the SCF plugin) |
| `src/` | CSS, JS, fonts, icons. Same as in the frontend, built by Vite into `dist/` |

## First run

You need Docker Desktop (running), Node 22 and pnpm.

```bash
# 1. Build the theme's styles and scripts
cd wp-content/themes/txexpresslend
pnpm install
pnpm build
cd ../../..

# 2. Start the site
docker compose up -d

# 3. Install WordPress and plugins, and import the content from the design
docker compose run --rm cli sh /scripts/setup.sh
```

After that:

- site: http://localhost:8080
- admin: http://localhost:8080/wp-admin (login `admin`, password `admin`)
- emails sent by the site: http://localhost:8025

> In Git Bash on Windows, prefix commands that use `/scripts/...` paths with
> `MSYS_NO_PATHCONV=1`, otherwise Git Bash rewrites the path. PowerShell and cmd don't need this.

## Day to day

```bash
docker compose up -d                             # start the site
cd wp-content/themes/txexpresslend && pnpm dev   # rebuild CSS/JS on save
```

Here `pnpm dev` runs `vite build --watch`: after a change, refresh the browser yourself,
there is no hot reload. PHP templates don't need rebuilding: WordPress reads them on every
request. But if a template gets a new Tailwind class, `pnpm dev` picks it up.

Tailwind looks for classes in the text of `.php` files, so a class can't be concatenated
from pieces (`'lg:row-span-' . $n`). Write it out in full. There's an example in `template-parts/blocks/regulations.php`.

Stop: `docker compose down`. Delete everything, including the database: `docker compose down -v`.

## Where content is edited

| What | Where in the admin |
|---|---|
| Header, hero with Quick Loan, loan amounts, Get Started block, footer, form messages, lead email | **Site Settings** |
| Any page: which blocks, in what order, their texts and images | **Pages → the page → Blocks** |
| Partner form code for `/apply` (two fields: the widget and the code before `</body>`) | **Pages → Get Started** (`/apply`) → Partner form block |
| Header and footer menu items | **Appearance → Menus** |
| Form submissions | **Leads** |

## Page builder

Every page is a list of blocks (the SCF Flexible Content field "Blocks"). A block is added with
"Add block", dragged by its handle to reorder, removed with the cross. Every block has an
"Anchor" (id for menu links like `/#faq`) and a "Hide this block" switch that keeps its content.

| Block | Notes |
|---|---|
| Hero with Quick Loan | title and text can be overridden per page; everything else is in Site Settings → Hero |
| Loan options, Steps, How it works, Rates table, Eligibility, Regulations, Callout, FAQ | the sections of the home page |
| Contacts and contact form | sits right under the previous block, without a gap |
| Text | an article in the visual editor; its title falls back to the page title |
| Loan form, Partner form | full-screen blocks, meant to be the only block on their page |

Spacing between blocks is set in `txl_block_spacing()` (`inc/blocks.php`). A page gets exactly one
`h1`: the first Text block's title, else the Loan form heading, else the first Hero title.
Form blocks make sense once per page.

### Adding a block type

1. **SCF → Field Groups → Page Builder** (if it's only under "Sync available", click "Sync" first):
   add a layout with its fields, plus the `anchor` and `hidden` fields every block has. Saving
   rewrites `acf-json/group_txl_builder.json`; commit it.
2. Add the markup to `template-parts/blocks/<name>.php`. The block's fields come in `$args`
   (`$args['title']`), plus `$args['h1']` and `$args['uid']` (see `txl_render_blocks()`).
3. Register the layout in `txl_blocks()` in `inc/blocks.php`.

## Adding a field

1. Go to **SCF → Field Groups**. If a group only exists in `acf-json/` and hasn't been opened
   yet, it's under the "Sync available" tab: click "Sync".
2. Add the field and save the group. SCF rewrites the JSON in `acf-json/` itself; commit that file.
3. Output the field in the template. In a block: `<?php echo esc_html( $args['field_name'] ); ?>`.
   For fields from Site Settings: `txl_option( 'field_name' )`.
   For an image: `txl_image( get_field( 'field' ), 'classes' )`.
   The helpers live in `inc/helpers.php`.

## Forms

- **Quick Loan** (in the hero) doesn't submit a lead. It opens the loan form `/form/?amount=…`.
  The email is never put in the URL (it would end up in server logs and analytics):
  `src/main.js` carries it over in `sessionStorage` and fills both fields in on the loan form.
- The **loan form** and the **contact form** are handled in `inc/forms.php`: anti-spam checks,
  server-side validation, the lead is saved under **Leads** and emailed to the address from
  Site Settings → Forms.
- Where to send leads next (CRM, lead network, API) hasn't been decided yet. The place for
  that is the `txl_deliver_lead()` function in `inc/forms.php`.

### Spam protection and page caching

The forms don't use a WP nonce: it protects nothing on an anonymous form and breaks once
pages are cached. Instead (all of it works with any page cache):

- a honeypot field, an Origin check and a time trap (submitted faster than 2 seconds or with
  JS disabled = bot);
- at most 5 leads per IP in 10 minutes (`TXL_RATE_LIMIT`, can be changed in `wp-config.php`);
- pages opened after a submission (`?txl_status=…`) are never cached.

**Before launch:** if the site sits behind Cloudflare or a load balancer, check that WordPress
sees the visitor's real IP in `REMOTE_ADDR`. Otherwise all visitors share one rate-limit
counter — then add `define( 'TXL_RATE_LIMIT', 0 );` to `wp-config.php`.

If spam still gets through, the next step is Cloudflare Turnstile (an invisible captcha).

## WP-CLI

Any WordPress command from the console: `docker compose run --rm cli wp <command>`.

```bash
docker compose run --rm cli wp plugin list
docker compose run --rm cli wp post list --post_type=txl_lead            # leads
docker compose run --rm cli wp eval-file /scripts/seed.php --user=admin  # re-import content (overwrites admin edits)
```

## Server

The site runs at **https://thexpresslend.pp.ua** (DigitalOcean droplet `165.227.57.188`,
Ubuntu 24.04, 1 vCPU, 512 MB RAM + 1 GB swap). No Docker there: nginx + PHP 8.3-FPM + MariaDB,
tuned for the small server. Requests by IP, over HTTP and on `www` redirect to the main domain.

| What | Where |
|---|---|
| WordPress | `/var/www/txexpresslend` (owned by `www-data`) |
| Setup scripts and seed content | `/opt/txexpresslend/scripts` |
| Database credentials | `/root/.txexpresslend-db` |
| nginx config | `/etc/nginx/sites-available/txexpresslend` |
| HTTPS certificate | Let's Encrypt, renewed automatically by `certbot.timer` |

The site is hidden from search engines (Settings → Reading → "Discourage search engines") and runs
with `WP_ENVIRONMENT_TYPE = staging`. Before the launch, untick that box.

### Deploying changes

Code changes (theme, mu-plugins, scripts) are uploaded from your machine. The script builds the
theme and replaces it on the server; the database and uploaded media aren't touched.

```bash
DEPLOY_HOST=root@165.227.57.188 SSH_KEY=~/.ssh/thexpresslend sh scripts/deploy.sh
```

Field groups are edited locally (SCF writes them to `acf-json/`) and deployed with the theme.
Edits made to field groups in the server's admin would be overwritten by the next deploy.

### WP-CLI on the server

```bash
ssh -i ~/.ssh/thexpresslend root@165.227.57.188
cd /var/www/txexpresslend && sudo -u www-data env HOME=/tmp wp <command>
```

### Setting up a server from scratch

```bash
# 1. System, database, nginx, HTTPS and WP core (the domain must already point to the server)
ssh root@<server> 'DOMAIN=thexpresslend.pp.ua sh -s' < scripts/server-setup.sh
# 2. Theme, mu-plugins and scripts
DEPLOY_HOST=root@<server> sh scripts/deploy.sh
# 3. WordPress install and content
ssh root@<server> "cd /var/www/txexpresslend && sudo -u www-data env HOME=/tmp   SITE_URL=https://thexpresslend.pp.ua ADMIN_PASSWORD=<password> WP_ROOT=/var/www/txexpresslend   sh /opt/txexpresslend/scripts/setup.sh"
```

### Mail

The server can't send email yet: there's no mail server, and DigitalOcean blocks port 25.
Leads are still saved under **Leads**. To get the notifications, connect an SMTP service
(Mailgun, Brevo, a mailbox on the domain) with an SMTP plugin and set the sending domain's
SPF/DKIM records.

## Open questions

- Where to send leads besides email and the admin.
- The partner form on `/apply`: how it fits with our own `/form`, and whether the Quick Loan amount and email can be passed into it.
- Texts for the footer pages (About Us, Terms of Use, etc.): they're placeholders for now.
- SMTP for email from the server (see "Mail" above) and an SEO plugin.
