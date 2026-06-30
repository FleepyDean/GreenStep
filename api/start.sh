#!/bin/bash
# Railway startup script with PHP configuration

# Create a custom php.ini in the runtime directory
cat > /tmp/custom-php.ini << EOF
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 60
EOF

# Show configuration
echo "PHP Configuration:"
php -c /tmp/custom-php.ini -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -c /tmp/custom-php.ini -r "echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"

# Start PHP server with custom ini
exec php -c /tmp/custom-php.ini -S 0.0.0.0:$PORT -t public
