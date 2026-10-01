#!/bin/sh
# Deploys the code to the server: builds the theme locally and uploads the theme,
# mu-plugins and these scripts over SSH. The database and uploads on the server are not touched.
#
# Runs on your machine (Git Bash):
#   DEPLOY_HOST=root@165.227.57.188 SSH_KEY=~/.ssh/thexpresslend sh scripts/deploy.sh
#
# Server preparation and the first install are described in "Server" in README.md.

set -eu

: "${DEPLOY_HOST:?Set DEPLOY_HOST, for example root@165.227.57.188}"
SITE_ROOT="${SITE_ROOT:-/var/www/txexpresslend}"
SCRIPTS_DIR=/opt/txexpresslend/scripts
THEME=wp-content/themes/txexpresslend
ROOT=$(cd "$(dirname "$0")/.." && pwd)

remote() {
	if [ -n "${SSH_KEY:-}" ]; then
		ssh -i "$SSH_KEY" "$DEPLOY_HOST" "$@"
	else
		ssh "$DEPLOY_HOST" "$@"
	fi
}

echo "→ Building the theme"
(cd "$ROOT/$THEME" && pnpm install --frozen-lockfile && pnpm build)

echo "→ Uploading to $DEPLOY_HOST"
tar -C "$ROOT" --exclude="$THEME/node_modules" -czf - "$THEME" wp-content/mu-plugins scripts | remote "
	set -e
	tmp=\$(mktemp -d)
	tar -xzf - -C \"\$tmp\"

	# The theme is swapped as a whole, so files deleted locally disappear on the server too.
	rm -rf '$SITE_ROOT/$THEME.old'
	if [ -d '$SITE_ROOT/$THEME' ]; then mv '$SITE_ROOT/$THEME' '$SITE_ROOT/$THEME.old'; fi
	mv \"\$tmp/$THEME\" '$SITE_ROOT/$THEME'
	rm -rf '$SITE_ROOT/$THEME.old'

	mkdir -p '$SITE_ROOT/wp-content/mu-plugins'
	cp -r \"\$tmp/wp-content/mu-plugins/.\" '$SITE_ROOT/wp-content/mu-plugins/'
	chown -R www-data:www-data '$SITE_ROOT/wp-content/themes' '$SITE_ROOT/wp-content/mu-plugins'

	rm -rf '$SCRIPTS_DIR'
	mkdir -p \"\$(dirname '$SCRIPTS_DIR')\"
	mv \"\$tmp/scripts\" '$SCRIPTS_DIR'
	rm -rf \"\$tmp\"

	if sudo -u www-data wp --path='$SITE_ROOT' core is-installed 2> /dev/null; then
		sudo -u www-data env HOME=/tmp WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp --path='$SITE_ROOT' cache flush
	fi
"

echo "Done."
