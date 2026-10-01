#!/bin/sh
# First-time site setup: WP core, plugins, the theme and content from the design.
#
# Locally (Docker):
#   docker compose up -d
#   docker compose run --rm cli sh /scripts/setup.sh
#
# On the server it runs as www-data from the WordPress folder, with SITE_URL, ADMIN_PASSWORD
# and WP_ROOT set (see "Server" in README.md).
#
# Safe to run again: steps that are already done are skipped.
# Content is imported only once. To re-import it (overwrites edits made in the admin):
#   docker compose run --rm cli wp eval-file /scripts/seed.php --user=admin

set -e

SITE_URL="${SITE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@txexpresslend.test}"
WP_ROOT="${WP_ROOT:-/var/www/html}"

echo "→ Waiting for WP core (in Docker the wordpress container unpacks it on start)"
i=0
until [ -f "$WP_ROOT/wp-config.php" ]; do
	i=$((i + 1))
	if [ "$i" -gt 60 ]; then
		echo "wp-config.php not found. Is the site running? Run: docker compose up -d"
		exit 1
	fi
	sleep 2
done

if ! wp core is-installed 2>/dev/null; then
	echo "→ Installing WordPress"
	wp core install \
		--url="$SITE_URL" \
		--title="TX Express Lend" \
		--admin_user="$ADMIN_USER" \
		--admin_password="$ADMIN_PASSWORD" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email
fi

echo "→ Settings"
wp option update blogdescription "Payday Loans in Texas"
wp option update timezone_string "America/Chicago"
# Keep the local copy away from search engines.
wp option update blog_public 0
# URLs like /contacts/ instead of /?page_id=12
wp rewrite structure '/%postname%/'
wp rewrite flush

echo "→ Plugins"
wp plugin is-installed secure-custom-fields || wp plugin install secure-custom-fields
wp plugin activate secure-custom-fields
for plugin in akismet hello; do
	if wp plugin is-installed "$plugin"; then wp plugin delete "$plugin"; fi
done

echo "→ Theme"
wp theme activate txexpresslend
for theme in $(wp theme list --status=inactive --field=name); do
	wp theme delete "$theme"
done

if [ "$(wp option get txl_seeded 2>/dev/null)" != "1" ]; then
	echo "→ Content from the design"
	wp eval-file "$(dirname "$0")/seed.php" --user="$ADMIN_USER"
fi

echo
echo "Done."
echo "  Site:  $SITE_URL"
echo "  Admin: $SITE_URL/wp-admin  ($ADMIN_USER / $ADMIN_PASSWORD)"
case "$SITE_URL" in *localhost*) echo "  Mail:  http://localhost:8025" ;; esac
