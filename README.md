# ClickBank Partner Manager

A comprehensive PHP/HTML tracking and redirect platform for managing ClickBank affiliate traffic. Traffic from partner sites hits your domain, the system logs clicks, applies routing rules, then performs server-side redirects to ClickBank hoplinks.

## Features

- **Server-side redirects** - No JavaScript, no redirect chains
- **Domain management** - Configure multiple tracking domains
- **Partner tracking** - Track affiliate partners by their unique aff_id
- **ClickBank offer management** - Store and manage multiple ClickBank offers
- **Flexible redirect rules** - Create global, domain-specific, or partner-specific rules with priority
- **Click logging** - Complete click tracking with IP, user agent, referer
- **Analytics dashboard** - View click trends, statistics, and detailed logs
- **Pause switches** - Instantly pause/resume redirect rules
- **Instant offer swaps** - Change offers without code modifications

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/brocketdesign/clickbank-partner-manager.git
   cd clickbank-partner-manager
   ```

2. **Create the database**
   - Import the SQL schema:
   ```bash
   mysql -u root -p < database.sql
   ```
   
   Or manually:
   - Create a database named `clickbank_partner_manager`
   - Run the SQL commands in `database.sql`

3. **Configure database connection**
   - Edit `config.php` and update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'clickbank_partner_manager');
   ```

4. **Configure web server**
   
   **Apache (.htaccess)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_URI} !^/admin/
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```
   
   **Nginx**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   
   location /admin/ {
       try_files $uri $uri/ =404;
   }
   ```

5. **Set permissions**
   ```bash
   chmod 755 admin/
   chmod 644 *.php admin/*.php
   ```

6. **Access admin panel**
   - Navigate to `http://your-domain.com/admin/`
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`
   - **Important:** Change the default password after first login!

## Usage

### Setting Up Traffic Tracking

1. **Add Domains**
   - Go to Admin → Domains
   - Add each tracking domain you want to use
   - Mark as Active

2. **Add Partners**
   - Go to Admin → Partners
   - Add affiliate partners with their unique `aff_id`
   - This ID will be used in tracking URLs

3. **Add ClickBank Offers**
   - Go to Admin → Offers
   - Add your ClickBank offers with their hoplinks
   - Example hoplink: `https://hop.clickbank.net/?affiliate=YOUR_ID&vendor=vendorname`

4. **Create Redirect Rules**
   - Go to Admin → Redirect Rules
   - Create rules to determine which offer gets shown to which traffic
   - Rule types:
     - **Global**: Applies to all traffic
     - **Domain-specific**: Only for traffic on a specific domain
     - **Partner-specific**: Only for a specific partner's traffic
   - Priority: Lower number = higher priority (default: 100)

### Tracking URLs

Send traffic to your domain with the partner's affiliate ID:

```
http://your-tracking-domain.com/?aff_id=partner123
```

The system will:
1. Log the click
2. Find the appropriate redirect rule
3. Redirect to the ClickBank hoplink
4. Append tracking parameters

### Managing Rules

- **Pause/Resume**: Instantly pause a rule without deleting it
- **Priority**: Control which rule takes precedence when multiple rules match
- **Rule hierarchy**: Partner rules > Domain rules > Global rules

### Analytics

- **Dashboard**: View overall statistics and click trends
- **Click Logs**: Detailed logs with filters for domain, partner, offer, and date
- **Graphs**: Visual representation of traffic over time

## Security Notes

- Change the default admin password immediately
- Use strong database passwords
- Consider implementing SSL/HTTPS for the admin panel
- Regularly backup your database
- Review click logs for suspicious activity

## Database Schema

- `domains` - Tracking domains
- `partners` - Affiliate partners
- `offers` - ClickBank offers
- `redirect_rules` - Traffic routing rules
- `click_logs` - Click tracking data
- `admin_users` - Admin authentication

## Troubleshooting

**"No redirect rule configured" error**
- Make sure you have at least one active redirect rule
- Check that the rule is not paused
- Verify the associated offer is active

**Database connection errors**
- Verify database credentials in `config.php`
- Ensure MySQL/MariaDB is running
- Check database user permissions

**Redirects not working**
- Verify web server URL rewriting is enabled
- Check that index.php is accessible
- Review web server error logs

## License

MIT License - Feel free to use and modify for your needs.

## Support

For issues and questions, please open an issue on GitHub.