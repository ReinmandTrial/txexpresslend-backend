#!/bin/sh
# One-time server provisioning: Ubuntu 24.04 + nginx + PHP 8.3-FPM + MariaDB + WordPress core.
# Tuned for a small droplet (1 vCPU, 512 MB RAM). Safe to run again: finished steps are skipped
# or simply re-applied.
#
# Runs on the server as root. From your machine (Git Bash):
#   ssh -i <key> root@<server> 'DOMAIN=thexpresslend.pp.ua sh -s' < scripts/server-setup.sh
#
# DOMAIN: the site's domain. With it, nginx serves the site on the domain and its www,
# redirects everything else there and gets an HTTPS certificate. The domain must already point
# to the server. Without DOMAIN the site is served on the server's IP over plain HTTP.
#
# After it: upload the theme with scripts/deploy.sh and install the site with scripts/setup.sh
# (see "Server" in README.md).

set -eu

SITE_ROOT="${SITE_ROOT:-/var/www/txexpresslend}"
DOMAIN="${DOMAIN:-}"
CREDENTIALS=/root/.txexpresslend-db
export DEBIAN_FRONTEND=noninteractive

# WP-CLI as www-data, so the files it creates belong to the site.
wpcli() {
	sudo -u www-data env HOME=/tmp WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp --path="$SITE_ROOT" "$@"
}

echo "→ Swap (without it, a memory spike on 512 MB kills the database)"
if ! swapon --show | grep -q /swapfile; then
	fallocate -l 1G /swapfile
	chmod 600 /swapfile
	mkswap /swapfile
	swapon /swapfile
fi
grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf
sysctl -q -p /etc/sysctl.d/99-swappiness.conf

echo "→ Packages"
apt-get update -q
apt-get install -y -q nginx mariadb-server \
	php8.3-fpm php8.3-mysql php8.3-gd php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-intl \
	unzip curl

if ! command -v wp > /dev/null; then
	curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x /usr/local/bin/wp
fi

echo "→ MariaDB"
cat > /etc/mysql/mariadb.conf.d/90-small-server.cnf << 'EOF'
# Small server: keep the database well under 100 MB of RAM.
[mysqld]
innodb_buffer_pool_size = 64M
innodb_log_buffer_size  = 8M
performance_schema      = OFF
max_connections         = 30
key_buffer_size         = 8M
tmp_table_size          = 16M
max_heap_table_size     = 16M
EOF
systemctl restart mariadb

# Database credentials are generated once and kept in a root-only file.
if [ ! -f "$CREDENTIALS" ]; then
	printf 'DB_NAME=txexpresslend\nDB_USER=txexpresslend\nDB_PASSWORD=%s\n' "$(openssl rand -hex 16)" > "$CREDENTIALS"
	chmod 600 "$CREDENTIALS"
fi
. "$CREDENTIALS"

mariadb << EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "→ PHP-FPM"
POOL=/etc/php/8.3/fpm/pool.d/www.conf
# Start PHP workers only when requests come and keep at most 4: each takes 30–60 MB.
sed -i \
	-e 's/^pm = .*/pm = ondemand/' \
	-e 's/^pm.max_children = .*/pm.max_children = 4/' \
	-e 's/^;\?pm.process_idle_timeout = .*/pm.process_idle_timeout = 10s/' \
	"$POOL"
cat > /etc/php/8.3/fpm/conf.d/90-wordpress.ini << 'EOF'
upload_max_filesize = 16M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 60
expose_php = Off
opcache.memory_consumption = 64
EOF
systemctl restart php8.3-fpm

echo "→ nginx"
cat > /etc/nginx/conf.d/txexpresslend.conf << 'EOF'
# Compression for text assets (gzip itself is switched on in nginx.conf).
gzip_vary on;
gzip_comp_level 5;
gzip_types text/css application/javascript application/json image/svg+xml text/plain;
server_tokens off;
EOF

if [ -n "$DOMAIN" ]; then
	# The site answers on the domain; requests by IP or for other names go to the domain.
	SERVER_NAME="$DOMAIN www.$DOMAIN"
	CATCH_ALL="server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    return 301 https://$DOMAIN\$request_uri;
}
"
	LISTEN="listen 80;
    listen [::]:80;"
else
	SERVER_NAME="_"
	CATCH_ALL=""
	LISTEN="listen 80 default_server;
    listen [::]:80 default_server;"
fi

cat > /etc/nginx/sites-available/txexpresslend << EOF
${CATCH_ALL}server {
    $LISTEN
    server_name $SERVER_NAME;

    root $SITE_ROOT;
    index index.php;
    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Theme build files with a hash in the name never change: cache them for a year.
    # (The regex is quoted because of the braces.)
    location ~* "/dist/assets/.+-[A-Za-z0-9_-]{8}\.(css|js|woff2?)\$" {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~* \.(jpe?g|png|gif|webp|avif|svg|ico|woff2?)\$ {
        expires 7d;
        access_log off;
    }

    # Nothing to see here: config, hidden files, PHP in uploads, XML-RPC.
    location = /wp-config.php { deny all; }
    location = /xmlrpc.php { deny all; }
    location ~ /\. { deny all; }
    location ~* ^/wp-content/uploads/.*\.php\$ { deny all; }
}
EOF
ln -sf /etc/nginx/sites-available/txexpresslend /etc/nginx/sites-enabled/txexpresslend
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "→ Firewall: SSH, HTTP and HTTPS only"
ufw allow OpenSSH > /dev/null
ufw allow 'Nginx Full' > /dev/null
ufw --force enable > /dev/null

if [ -n "$DOMAIN" ]; then
	echo "→ HTTPS for $DOMAIN (Let's Encrypt)"
	apt-get install -y -q certbot python3-certbot-nginx > /dev/null
	# Gets the certificate once and renews it automatically (certbot.timer). On re-runs it only
	# puts the existing certificate back into the nginx config written above.
	certbot --nginx --non-interactive --agree-tos --register-unsafely-without-email \
		--keep-until-expiring --redirect -d "$DOMAIN" -d "www.$DOMAIN"
fi

echo "→ WordPress core"
mkdir -p "$SITE_ROOT"
chown www-data:www-data "$SITE_ROOT"

if [ ! -f "$SITE_ROOT/wp-includes/version.php" ]; then
	wpcli core download --skip-content
fi
mkdir -p "$SITE_ROOT/wp-content/themes" "$SITE_ROOT/wp-content/plugins" "$SITE_ROOT/wp-content/mu-plugins" "$SITE_ROOT/wp-content/uploads"
chown -R www-data:www-data "$SITE_ROOT/wp-content"

if [ ! -f "$SITE_ROOT/wp-config.php" ]; then
	wpcli config create \
		--dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASSWORD" --dbhost=localhost \
		--skip-check --extra-php << 'EOF'
define( 'WP_ENVIRONMENT_TYPE', 'staging' );
define( 'DISALLOW_FILE_EDIT', true );
EOF
	chmod 640 "$SITE_ROOT/wp-config.php"
fi

echo
echo "Server is ready."
free -h | head -2
