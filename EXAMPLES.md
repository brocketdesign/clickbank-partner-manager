# ClickBank Partner Manager - Usage Examples

## Quick Start Example

### 1. Initial Setup

After installation, your first steps:

```bash
# 1. Access admin panel
http://your-domain.com/admin/

# 2. Login with default credentials
Username: admin
Password: admin123

# 3. You'll see the dashboard with these stats:
- Total Clicks: 0
- Clicks Today: 0
- Active Domains: 0
- Active Partners: 0
- Active Offers: 0
- Active Rules: 0
```

### 2. Add Your First Domain

Navigate to **Domains** → **+ Add Domain**

```
Domain Name: track.yourdomain.com
[✓] Active
```

Click **Save Domain** → Success message appears

### 3. Add a ClickBank Offer

Navigate to **Offers** → **+ Add Offer**

```
Offer Name: Weight Loss Solution
ClickBank Vendor: weightloss123
Hoplink URL: https://hop.clickbank.net/?affiliate=YOURID&vendor=weightloss123
[✓] Active
```

Click **Save Offer** → Success message appears

### 4. Create a Global Redirect Rule

Navigate to **Redirect Rules** → **+ Add Rule**

```
Rule Name: Default Weight Loss Offer
Rule Type: Global (all traffic)
ClickBank Offer: Weight Loss Solution
Priority: 100
[ ] Paused
```

Click **Save Rule** → Success message appears

### 5. Test Your Setup

Open a browser and visit:
```
http://track.yourdomain.com/
```

You should be immediately redirected to:
```
https://hop.clickbank.net/?affiliate=YOURID&vendor=weightloss123
```

### 6. View Click Logs

Navigate to **Click Logs**

You should see:
```
Time                 | Domain                  | Partner | Offer                  | IP Address
2024-12-20 15:30:45 | track.yourdomain.com   | N/A     | Weight Loss Solution   | 192.168.1.1
```

---

## Advanced Example: Multi-Partner Setup

### Scenario
You have 3 partners and want to track them separately with different offers:
- Partner A → Offer 1 (Weight Loss)
- Partner B → Offer 2 (Fitness)  
- Everyone else → Offer 1 (Weight Loss)

### Step 1: Add Partners

**Partner A:**
```
Affiliate ID: partnerA
Partner Name: John's Marketing Agency
[✓] Active
```

**Partner B:**
```
Affiliate ID: partnerB
Partner Name: Sarah's Traffic Network
[✓] Active
```

### Step 2: Add Second Offer

```
Offer Name: Fitness Training Program
ClickBank Vendor: fitness456
Hoplink URL: https://hop.clickbank.net/?affiliate=YOURID&vendor=fitness456
[✓] Active
```

### Step 3: Create Partner-Specific Rules

**Rule for Partner A:**
```
Rule Name: Partner A - Weight Loss
Rule Type: Partner-specific
Partner: John's Marketing Agency (partnerA)
ClickBank Offer: Weight Loss Solution
Priority: 50
[ ] Paused
```

**Rule for Partner B:**
```
Rule Name: Partner B - Fitness
Rule Type: Partner-specific
Partner: Sarah's Traffic Network (partnerB)
ClickBank Offer: Fitness Training Program
Priority: 50
[ ] Paused
```

**Global Fallback Rule:**
```
Rule Name: Default for Unknown Traffic
Rule Type: Global (all traffic)
ClickBank Offer: Weight Loss Solution
Priority: 100
[ ] Paused
```

### Step 4: Test Each Partner

**Partner A's URL:**
```
http://track.yourdomain.com/?aff_id=partnerA
```
Redirects to Weight Loss Solution

**Partner B's URL:**
```
http://track.yourdomain.com/?aff_id=partnerB
```
Redirects to Fitness Training Program

**Unknown Partner:**
```
http://track.yourdomain.com/?aff_id=unknown
```
Redirects to Weight Loss Solution (global rule)

**No Parameter:**
```
http://track.yourdomain.com/
```
Redirects to Weight Loss Solution (global rule)

---

## Domain-Specific Example

### Scenario
You want different domains to redirect to different offers:
- track1.yourdomain.com → Offer A
- track2.yourdomain.com → Offer B

### Setup

**Add Domains:**
```
Domain 1: track1.yourdomain.com [✓] Active
Domain 2: track2.yourdomain.com [✓] Active
```

**Create Domain Rules:**

**Rule 1:**
```
Rule Name: Track1 Domain Rule
Rule Type: Domain-specific
Domain: track1.yourdomain.com
ClickBank Offer: Weight Loss Solution
Priority: 50
```

**Rule 2:**
```
Rule Name: Track2 Domain Rule
Rule Type: Domain-specific
Domain: track2.yourdomain.com
ClickBank Offer: Fitness Training Program
Priority: 50
```

### Result

```
http://track1.yourdomain.com/ → Weight Loss Solution
http://track2.yourdomain.com/ → Fitness Training Program
```

---

## Offer Swap Example

### Scenario
You want to instantly change which offer Partner A sees.

### Current State
```
Partner A (partnerA) → Weight Loss Solution
```

### To Change

1. Navigate to **Redirect Rules**
2. Find "Partner A - Weight Loss" rule
3. Click **Edit**
4. Change **ClickBank Offer** to "Fitness Training Program"
5. Click **Save Rule**

### Result (Instant)
```
http://track.yourdomain.com/?aff_id=partnerA
```
Now redirects to Fitness Training Program

**No code changes required!**

---

## Pause/Resume Example

### Scenario
You want to temporarily stop a rule for testing.

### Steps

1. Navigate to **Redirect Rules**
2. Find the rule you want to pause
3. Click **Pause** button
4. Rule status changes to "Paused"

When a rule is paused:
- It won't match any traffic
- System falls back to next priority rule
- Can be resumed anytime with one click

**Use Case:** Testing new offers before full deployment

---

## Analytics Example

### View Click Trends

Dashboard shows 7-day graph:
```
Day 1: 45 clicks  ███████████████
Day 2: 32 clicks  ███████████
Day 3: 78 clicks  ████████████████████████
Day 4: 56 clicks  ██████████████████
Day 5: 91 clicks  ███████████████████████████
Day 6: 67 clicks  ████████████████████
Day 7: 43 clicks  ██████████████
```

### Filter Click Logs

Navigate to **Click Logs** and use filters:

**Filter by Partner:**
```
Partner: [John's Marketing Agency (partnerA)] [Apply Filters]
```
Shows only clicks from Partner A

**Filter by Date:**
```
Date: [2024-12-20] [Apply Filters]
```
Shows only clicks from that date

**Filter by Offer:**
```
Offer: [Weight Loss Solution] [Apply Filters]
```
Shows only clicks that matched that offer

**Combined Filters:**
```
Partner: [John's Marketing Agency]
Date: [2024-12-20]
Offer: [Weight Loss Solution]
[Apply Filters]
```
Shows Partner A's clicks for Weight Loss Solution on Dec 20

---

## Priority Example

### Scenario
Multiple rules could match the same request.

### Rules Setup

```
Rule 1: Partner A to Offer X (partner rule, priority 50)
Rule 2: Track1 Domain to Offer Y (domain rule, priority 50)
Rule 3: Global to Offer Z (global rule, priority 100)
```

### Request Analysis

**Request:** `http://track1.yourdomain.com/?aff_id=partnerA`

**Matching Process:**
1. Check partner rules → Found "Partner A to Offer X" ✓
2. Skip domain rules (partner rule found)
3. Skip global rules (partner rule found)

**Result:** Redirects to Offer X

**Priority Hierarchy:** Partner > Domain > Global

Within each level, lower priority number wins.

---

## Monitoring Example

### Daily Operations

**Morning Check:**
1. Login to admin dashboard
2. View "Clicks Today" statistic
3. Check 7-day trend graph
4. Review recent clicks table

**Performance Review:**
1. Navigate to **Click Logs**
2. Filter by partner to see individual performance
3. Export data (can be enhanced)
4. Analyze conversion rates externally

**Rule Management:**
1. Test new offers with low-priority rules
2. Gradually increase priority
3. Pause underperforming rules
4. Update hoplinks as needed

---

## Troubleshooting Examples

### Problem: "No redirect rule configured"

**Diagnosis:**
```
Check: Admin → Redirect Rules
Result: No active rules found
```

**Solution:**
```
Create at least one global rule as fallback
Ensure rule is not paused
Ensure associated offer is active
```

### Problem: Partner not being tracked

**Diagnosis:**
```
Request: http://track.domain.com/?aff_id=partner123
Logs show: Partner = N/A
```

**Solution:**
```
Check: Admin → Partners
Verify: partner123 exists and is active
Fix: Add partner or fix aff_id in URL
```

### Problem: Wrong offer being served

**Diagnosis:**
```
Expected: Offer A
Actual: Offer B
```

**Solution:**
```
Check: Admin → Redirect Rules
Review: Rule priorities and types
Fix: Adjust priorities or pause conflicting rules
```

---

## Best Practices

1. **Always have a global fallback rule**
2. **Use descriptive rule names**
3. **Test with different aff_id values**
4. **Monitor click logs daily**
5. **Backup database regularly**
6. **Change default admin password**
7. **Use HTTPS in production**
8. **Archive old click logs periodically**

---

## Summary

This system allows you to:
- ✓ Track all incoming clicks
- ✓ Route traffic intelligently
- ✓ Manage multiple partners
- ✓ Swap offers instantly
- ✓ Analyze performance
- ✓ Control with pause switches

All without touching code!
