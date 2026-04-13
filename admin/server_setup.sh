#!/bin/bash
# Run once on server as root:
#   bash /var/www/selektiert.com/admin/server_setup.sh
set -e

echo "=== [1/4] Installing PHP 8.3-FPM ==="
apt-get update -qq
apt-get install -y -qq php8.3-fpm php8.3-sqlite3 php8.3-mbstring php8.3-fileinfo

echo "=== [2/4] Creating SQLite data directory ==="
mkdir -p /var/data/selektiert
chown www-data:www-data /var/data/selektiert
chmod 750 /var/data/selektiert

mkdir -p /var/www/selektiert.com/admin/uploads
chown www-data:www-data /var/www/selektiert.com/admin/uploads
chmod 755 /var/www/selektiert.com/admin/uploads

mkdir -p /var/www/test.selektiert.com/admin/uploads
chown www-data:www-data /var/www/test.selektiert.com/admin/uploads
chmod 755 /var/www/test.selektiert.com/admin/uploads

echo "=== [3/4] Updating nginx configs for PHP ==="

# Production
cat > /etc/nginx/sites-available/selektiert.com << 'NGINX_PROD'
server {
    listen 80;
    server_name selektiert.com www.selektiert.com;
    return 301 https://selektiert.com$request_uri;
}

server {
    listen 443 ssl;
    server_name www.selektiert.com;
    ssl_certificate     /etc/letsencrypt/live/selektiert.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/selektiert.com/privkey.pem;
    return 301 https://selektiert.com$request_uri;
}

server {
    listen 443 ssl;
    server_name selektiert.com;
    root /var/www/selektiert.com;
    index index.php index.html;

    ssl_certificate     /etc/letsencrypt/live/selektiert.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/selektiert.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # PHP
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Deny direct access to includes
    location ~ ^/admin/includes/ {
        deny all;
    }

    location / {
        try_files $uri $uri/ =404;
    }
}
NGINX_PROD

# Test
cat > /etc/nginx/sites-available/test.selektiert.com << 'NGINX_TEST'
server {
    listen 80;
    server_name test.selektiert.com;
    return 301 https://test.selektiert.com$request_uri;
}

server {
    listen 443 ssl;
    server_name test.selektiert.com;
    root /var/www/test.selektiert.com;
    index index.php index.html;

    ssl_certificate     /etc/letsencrypt/live/test.selektiert.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/test.selektiert.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    # PHP
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Deny direct access to includes
    location ~ ^/admin/includes/ {
        deny all;
    }

    location / {
        try_files $uri $uri/ =404;
    }
}
NGINX_TEST

echo "=== [4/4] Reloading services ==="
systemctl enable php8.3-fpm
systemctl restart php8.3-fpm
nginx -t && systemctl reload nginx

echo ""
echo "=== DONE ==="
echo ""
echo "Next step: visit https://selektiert.com/admin/setup.php?key=SETUP_SELEKTIERT_2026"
echo "Then delete setup.php: rm /var/www/selektiert.com/admin/setup.php"
