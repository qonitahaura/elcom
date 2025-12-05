#!/bin/bash

# Default port jika Railway tidak memberi PORT
PORT=${PORT:-8080}

# Replace Apache ports.conf
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

# Replace VirtualHost
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Apache listening on port ${PORT}"
apache2-foreground
