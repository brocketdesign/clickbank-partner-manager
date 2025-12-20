#!/bin/bash
# ClickBank Partner Manager - Quick Setup Script

echo "================================================"
echo "ClickBank Partner Manager - Installation"
echo "================================================"
echo ""

# Check for PHP
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP is not installed"
    echo "Please install PHP 7.4 or higher"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✓ PHP $PHP_VERSION detected"

# Check for MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠️  Warning: MySQL client not found in PATH"
    echo "Please ensure MySQL/MariaDB is installed and accessible"
fi

echo ""
echo "Database Setup"
echo "------------------------------------------------"
echo "Please enter your database credentials:"
echo ""

read -p "Database host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Database user [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Database password: " DB_PASS
echo ""

read -p "Database name [clickbank_partner_manager]: " DB_NAME
DB_NAME=${DB_NAME:-clickbank_partner_manager}

echo ""
echo "------------------------------------------------"
echo "Creating database and importing schema..."

# Create database and import schema
if command -v mysql &> /dev/null; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "✓ Database created"
        
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database.sql 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "✓ Schema imported successfully"
        else
            echo "❌ Error importing schema. Please import database.sql manually"
        fi
    else
        echo "❌ Error creating database. Please create it manually and import database.sql"
    fi
else
    echo "⚠️  Please import database.sql manually using:"
    echo "   mysql -h $DB_HOST -u $DB_USER -p $DB_NAME < database.sql"
fi

echo ""
echo "------------------------------------------------"
echo "Updating config.php..."

# Update config.php with database credentials
sed -i.bak "s/define('DB_HOST', 'localhost');/define('DB_HOST', '$DB_HOST');/" config.php
sed -i.bak "s/define('DB_USER', 'root');/define('DB_USER', '$DB_USER');/" config.php
sed -i.bak "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" config.php
sed -i.bak "s/define('DB_NAME', 'clickbank_partner_manager');/define('DB_NAME', '$DB_NAME');/" config.php

if [ -f config.php.bak ]; then
    rm config.php.bak
fi

echo "✓ Configuration updated"

echo ""
echo "================================================"
echo "Installation Complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Configure your web server to serve this directory"
echo "2. Access the admin panel at: http://your-domain/admin/"
echo "3. Login with default credentials:"
echo "   Username: admin"
echo "   Password: admin123"
echo "4. ⚠️  IMPORTANT: Change the default password immediately!"
echo ""
echo "For Apache, ensure mod_rewrite is enabled."
echo "For Nginx, configure URL rewriting (see README.md)"
echo ""
echo "Documentation: README.md"
echo "================================================"
