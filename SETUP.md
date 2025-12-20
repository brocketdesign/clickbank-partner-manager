# ClickBank Partner Manager - Setup Guide

This guide will walk you through setting up the ClickBank Partner Manager tracking platform step by step.

## Quick Start

### Option 1: Automated Installation (Linux/Mac)

```bash
./install.sh
```

The installation script will:
- Check PHP installation
- Prompt for database credentials
- Create the database
- Import the schema
- Update configuration

### Option 2: Manual Installation

#### Step 1: Database Setup

1. Create a MySQL/MariaDB database:
```sql
CREATE DATABASE clickbank_partner_manager;
```

2. Import the schema:
```bash
mysql -u root -p clickbank_partner_manager < database.sql
```

Or use phpMyAdmin, MySQL Workbench, or any other database tool to run the SQL in `database.sql`.

#### Step 2: Configure Database Connection

Edit `config.php` and update these lines:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_USER', 'your_username');  // Your database username
define('DB_PASS', 'your_password');  // Your database password
define('DB_NAME', 'clickbank_partner_manager');
```

#### Step 3: Web Server Configuration

##### Apache

1. Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

2. The `.htaccess` file is already configured. Ensure `AllowOverride All` is set in your Apache configuration.

##### Nginx

Add this to your site configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/clickbank-partner-manager;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /admin/ {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ ^/(database\.sql|config\.php|\.gitignore)$ {
        deny all;
    }
}
```

#### Step 4: File Permissions

Set appropriate permissions:

```bash
# Make directories readable/executable
chmod 755 admin/

# Make PHP files readable
chmod 644 *.php admin/*.php

# Protect sensitive files
chmod 600 config.php database.sql
```

## First-Time Login

1. Navigate to `http://your-domain.com/admin/`

2. Login with default credentials:
   - **Username:** admin
   - **Password:** admin123

3. **IMPORTANT:** Change your password immediately!

To change the admin password, you can update it in the database:

```php
<?php
// Use this PHP script to generate a new password hash
$new_password = 'your_new_password';
$hash = password_hash($new_password, PASSWORD_DEFAULT);
echo $hash;
```

Then update the database:
```sql
UPDATE admin_users SET password_hash = 'YOUR_NEW_HASH' WHERE username = 'admin';
```

## Configuration Walkthrough

### 1. Add Your First Domain

1. Go to **Domains** → **+ Add Domain**
2. Enter your tracking domain (e.g., `track.yourdomain.com`)
3. Check **Active**
4. Click **Save Domain**

### 2. Add ClickBank Offers

1. Go to **Offers** → **+ Add Offer**
2. Fill in:
   - **Offer Name:** A descriptive name (e.g., "Weight Loss Product")
   - **ClickBank Vendor:** The vendor ID (e.g., "vendorname")
   - **Hoplink URL:** Your full ClickBank hoplink
     - Example: `https://hop.clickbank.net/?affiliate=YOURID&vendor=vendorname`
3. Check **Active**
4. Click **Save Offer**

### 3. Add Partners (Optional)

If you're tracking different partners/affiliates:

1. Go to **Partners** → **+ Add Partner**
2. Fill in:
   - **Affiliate ID:** Unique identifier (e.g., "partner001")
   - **Partner Name:** Descriptive name (e.g., "John's Marketing")
3. Check **Active**
4. Click **Save Partner**

### 4. Create Redirect Rules

Rules determine which offer to show to incoming traffic.

#### Global Rule (Default Fallback)

1. Go to **Redirect Rules** → **+ Add Rule**
2. Fill in:
   - **Rule Name:** "Default Global Rule"
   - **Rule Type:** Global (all traffic)
   - **ClickBank Offer:** Select your offer
   - **Priority:** 100
3. Leave **Paused** unchecked
4. Click **Save Rule**

#### Domain-Specific Rule

To route traffic differently based on the tracking domain:

1. Create a new rule
2. Set **Rule Type** to "Domain-specific"
3. Select the domain
4. Select the offer
5. Set a lower priority number (e.g., 50) to give it precedence

#### Partner-Specific Rule

To send specific partners to different offers:

1. Create a new rule
2. Set **Rule Type** to "Partner-specific"
3. Select the partner
4. Select the offer
5. Priority is automatic (partner rules always have highest priority)

## Testing Your Setup

### Test Redirect

1. Create a global rule pointing to an active offer
2. Visit: `http://your-tracking-domain.com/`
3. You should be redirected to the ClickBank hoplink

### Test Partner Tracking

1. Create a partner and a partner-specific rule
2. Visit: `http://your-tracking-domain.com/?aff_id=partner001`
3. You should be redirected to the partner's assigned offer
4. Check **Click Logs** in the admin panel to verify tracking

## Common Issues

### "No redirect rule configured"

**Cause:** No active redirect rule matches the incoming traffic.

**Solution:**
- Create at least one global rule as a fallback
- Ensure the rule is not paused
- Verify the associated offer is active

### Database Connection Failed

**Cause:** Incorrect database credentials or database not running.

**Solution:**
- Verify credentials in `config.php`
- Check if MySQL/MariaDB service is running
- Test connection: `mysql -u username -p -h localhost`

### 404 Error on Admin Pages

**Cause:** Web server not configured correctly.

**Solution:**
- Verify `.htaccess` is being read (Apache)
- Check Nginx configuration
- Ensure `index.php` is accessible

### Redirects Not Working

**Cause:** URL rewriting not enabled.

**Solution:**
- Apache: Enable mod_rewrite
- Nginx: Configure try_files directive
- Test with: `http://your-domain.com/?aff_id=test`

## Advanced Configuration

### Multiple Admin Users

To add more admin users, insert into the database:

```sql
INSERT INTO admin_users (username, password_hash) 
VALUES ('newadmin', '$2y$10$...hash...');
```

Generate the password hash using PHP:
```php
echo password_hash('desired_password', PASSWORD_DEFAULT);
```

### Backup Automation

Set up automatic database backups:

```bash
#!/bin/bash
# backup.sh
mysqldump -u username -p'password' clickbank_partner_manager > backup_$(date +%Y%m%d).sql
```

Add to crontab:
```
0 2 * * * /path/to/backup.sh
```

### Performance Optimization

For high-traffic scenarios:

1. **Enable MySQL Query Cache**
2. **Add indexes** to click_logs table for frequently filtered columns
3. **Archive old click_logs** periodically
4. **Use Redis/Memcached** for session storage

### SSL/HTTPS Setup

For production, always use SSL:

1. Obtain SSL certificate (Let's Encrypt, etc.)
2. Update web server configuration
3. Force HTTPS redirects
4. Update tracking URLs to use HTTPS

## Support & Troubleshooting

If you encounter issues:

1. Check PHP error logs
2. Check web server error logs
3. Verify database connectivity
4. Review click_logs table for tracking data
5. Open an issue on GitHub

## Next Steps

After setup:

1. ✅ Change default admin password
2. ✅ Add your tracking domains
3. ✅ Configure ClickBank offers
4. ✅ Create redirect rules
5. ✅ Test with sample traffic
6. ✅ Monitor click logs
7. ✅ Set up regular backups
8. ✅ Enable SSL/HTTPS

You're ready to start tracking and managing ClickBank affiliate traffic!
