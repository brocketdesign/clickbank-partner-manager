# ClickBank Partner Manager - System Overview

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PARTNER TRAFFIC                          │
│   http://track.domain.com/?aff_id=partner123               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                  index.php (Entry Point)                    │
│  • Captures request data (domain, aff_id, IP, user agent)  │
│  • Identifies domain and partner                           │
│  • Applies routing rules (priority-based)                  │
│  • Logs click to database                                  │
│  • Performs 302 redirect to ClickBank hoplink              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                  ClickBank Hoplink                          │
│  https://hop.clickbank.net/?affiliate=X&vendor=Y&tid=Z      │
└─────────────────────────────────────────────────────────────┘
```

## Admin Dashboard Structure

```
/admin/
├── login.php          → Authentication
├── index.php          → Dashboard (stats, graphs, recent clicks)
├── domains.php        → List domains
├── domain_edit.php    → Add/Edit domain
├── partners.php       → List partners
├── partner_edit.php   → Add/Edit partner
├── offers.php         → List ClickBank offers
├── offer_edit.php     → Add/Edit offer
├── rules.php          → List redirect rules
├── rule_edit.php      → Add/Edit rule
├── clicks.php         → Click logs with filters
└── logout.php         → End session
```

## Database Schema

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   domains    │     │   partners   │     │    offers    │
├──────────────┤     ├──────────────┤     ├──────────────┤
│ id           │     │ id           │     │ id           │
│ domain_name  │     │ aff_id       │     │ offer_name   │
│ is_active    │     │ partner_name │     │ vendor       │
│ created_at   │     │ is_active    │     │ hoplink      │
└──────┬───────┘     └──────┬───────┘     │ is_active    │
       │                    │              └──────┬───────┘
       │                    │                     │
       └────────┬───────────┴─────────────────────┘
                │
                ▼
     ┌──────────────────────┐
     │  redirect_rules      │
     ├──────────────────────┤
     │ id                   │
     │ rule_name            │
     │ rule_type (enum)     │ ← global/domain/partner
     │ domain_id (FK)       │
     │ partner_id (FK)      │
     │ offer_id (FK)        │
     │ is_paused            │
     │ priority             │
     └──────────┬───────────┘
                │
                ▼
     ┌──────────────────────┐
     │    click_logs        │
     ├──────────────────────┤
     │ id                   │
     │ domain_id (FK)       │
     │ partner_id (FK)      │
     │ offer_id (FK)        │
     │ rule_id (FK)         │
     │ ip_address           │
     │ user_agent           │
     │ referer              │
     │ redirect_url         │
     │ clicked_at           │
     └──────────────────────┘
```

## Routing Rule Priority

The system applies rules in this order:

1. **Partner-specific rules** (highest priority)
   - If `aff_id` matches a partner AND partner has a rule
   - Example: partner123 → Offer A

2. **Domain-specific rules** (medium priority)
   - If request domain matches AND domain has a rule
   - Example: track.site1.com → Offer B

3. **Global rules** (fallback)
   - Applies to all traffic that doesn't match above
   - Example: * → Offer C

Within each level, rules are sorted by `priority` (lower number = higher priority).

## Key Features

### 1. Server-Side Redirects
- Pure PHP 302 redirects
- No JavaScript required
- No redirect chains
- Fast and SEO-friendly

### 2. Flexible Routing
- Create rules for different scenarios
- Pause rules without deleting
- Swap offers instantly
- Priority-based matching

### 3. Complete Tracking
- Every click logged with:
  - Timestamp
  - IP address
  - User agent
  - Referer
  - Matched domain
  - Matched partner
  - Applied offer
  - Applied rule

### 4. Analytics Dashboard
- Real-time statistics
- 7-day trend graphs
- Filter by domain/partner/offer/date
- Paginated click logs

### 5. Easy Management
- CRUD operations for all entities
- Active/inactive toggles
- Search and filters
- Responsive UI

## Typical Workflow

### Setup Phase
1. Add tracking domains
2. Add ClickBank offers with hoplinks
3. Add partners (affiliates)
4. Create redirect rules

### Operations Phase
1. Share tracking URLs: `http://track.domain.com/?aff_id=partner123`
2. Monitor clicks in dashboard
3. Analyze trends and performance
4. Swap offers as needed
5. Pause/resume rules for testing

### Example Scenarios

**Scenario 1: Single Global Offer**
- Create one global rule pointing to Offer A
- All traffic redirects to Offer A

**Scenario 2: Different Offers per Partner**
- Partner 1 → Offer A (partner rule)
- Partner 2 → Offer B (partner rule)
- Everyone else → Offer C (global rule)

**Scenario 3: A/B Testing by Domain**
- track1.domain.com → Offer A (domain rule)
- track2.domain.com → Offer B (domain rule)

**Scenario 4: Temporary Pause**
- Pause a rule temporarily
- System falls back to next priority rule
- Resume when ready

## Security Features

- Password hashing with bcrypt
- Session-based authentication
- Prepared SQL statements (prevents injection)
- Input sanitization (prevents XSS)
- Security headers (.htaccess)
- Sensitive file protection
- HTTPS support

## Performance Considerations

- Indexed database columns for fast lookups
- Efficient SQL queries with JOINs
- Pagination for large datasets
- Minimal dependencies (pure PHP + MySQL)

## Installation Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Apache (mod_rewrite) or Nginx
- 10MB disk space minimum

## File Structure

```
clickbank-partner-manager/
├── admin/              → Admin dashboard pages
├── config.php          → Database configuration
├── index.php           → Main redirect handler
├── database.sql        → Database schema
├── .htaccess           → Apache configuration
├── nginx.conf.example  → Nginx configuration
├── install.sh          → Automated installer
├── README.md           → User documentation
├── SETUP.md            → Installation guide
├── LICENSE             → MIT License
└── .gitignore          → Git ignore rules
```

## Support & Maintenance

- Regular database backups recommended
- Monitor click_logs table size
- Archive old data periodically
- Update ClickBank hoplinks as needed
- Review security headers regularly

---

**Built with**: Pure PHP, MySQL, HTML, CSS (no frameworks)
**License**: MIT
**Purpose**: Track and manage ClickBank affiliate traffic
