<?php
require_once '../config.php';
requireLogin();

$page_title = 'Documentation';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <style>
        .docs-container {
            max-width: 900px;
        }
        .docs-nav {
            background: white;
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
        }
        .docs-nav-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .docs-nav-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .docs-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--gray-100);
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .docs-nav-link:hover, .docs-nav-link.active {
            background: var(--primary);
            color: white;
        }
        .docs-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 32px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
        }
        .docs-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .docs-section h2 .icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .docs-section .subtitle {
            color: var(--gray-500);
            font-size: 15px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--gray-200);
        }
        .docs-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 28px 0 16px;
        }
        .docs-section h3:first-of-type {
            margin-top: 0;
        }
        .docs-section p {
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .docs-section ul, .docs-section ol {
            color: var(--gray-600);
            line-height: 1.8;
            margin-bottom: 16px;
            padding-left: 24px;
        }
        .docs-section li {
            margin-bottom: 8px;
        }
        .docs-section code {
            background: var(--gray-100);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            color: var(--primary-dark);
        }
        .info-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.08) 100%);
            border-left: 4px solid var(--primary);
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .info-box.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.08) 100%);
            border-left-color: var(--success);
        }
        .info-box.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(217, 119, 6, 0.08) 100%);
            border-left-color: var(--warning);
        }
        .info-box.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.08) 100%);
            border-left-color: var(--danger);
        }
        .info-box p {
            margin: 0;
            color: var(--gray-700);
        }
        .info-box strong {
            color: var(--gray-900);
        }
        .workflow-step {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: 12px;
        }
        .workflow-step-number {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .workflow-step-content h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 8px;
        }
        .workflow-step-content p {
            margin: 0;
            color: var(--gray-600);
            font-size: 14px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.pending {
            background: var(--warning-light);
            color: #92400e;
        }
        .status-badge.approved {
            background: var(--success-light);
            color: #065f46;
        }
        .status-badge.rejected {
            background: var(--danger-light);
            color: #991b1b;
        }
        .status-badge.verified {
            background: var(--success-light);
            color: #065f46;
        }
        .status-badge.failed {
            background: var(--danger-light);
            color: #991b1b;
        }
        .button-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .button-table th, .button-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        .button-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .button-table td {
            color: var(--gray-600);
            font-size: 14px;
        }
        .button-preview {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        .button-preview.view { background: var(--primary); color: white; }
        .button-preview.approve { background: var(--success); color: white; }
        .button-preview.reject { background: var(--danger); color: white; }
        .button-preview.edit { background: var(--gray-200); color: var(--gray-700); }
        .button-preview.delete { background: var(--danger); color: white; }
        .button-preview.toggle { background: var(--warning); color: white; }
        .diagram {
            background: var(--gray-900);
            border-radius: 12px;
            padding: 24px;
            overflow-x: auto;
            margin: 20px 0;
        }
        .diagram pre {
            color: #e5e7eb;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }
        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 14px;
        }
        .field-table th {
            background: var(--gray-50);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
        }
        .field-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-600);
        }
        .field-table tr:hover {
            background: var(--gray-50);
        }
        .required-badge {
            background: var(--danger-light);
            color: #991b1b;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .optional-badge {
            background: var(--gray-100);
            color: var(--gray-500);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>

    <div class="docs-container">
        <!-- Navigation -->
        <div class="docs-nav">
            <div class="docs-nav-title">📚 Documentation Sections</div>
            <div class="docs-nav-list">
                <a href="#applications" class="docs-nav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Applications
                </a>
                <a href="#partners" class="docs-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Partners
                </a>
                <a href="#domains" class="docs-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    Domains
                </a>
                <a href="#offers" class="docs-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Offers
                </a>
                <a href="#rules" class="docs-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Redirect Rules
                </a>
            </div>
        </div>

        <!-- Applications Section -->
        <section id="applications" class="docs-section">
            <h2>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                Applications Module
            </h2>
            <p class="subtitle">Manage partner applications from submission to approval</p>

            <h3>📋 What is an Application?</h3>
            <p>An <strong>Application</strong> represents a request from a potential partner who wants to join your affiliate program. When someone fills out the partner application form on your website, their information is stored as an application that you can review, verify, and either approve or reject.</p>

            <div class="info-box">
                <p><strong>💡 Key Concept:</strong> Applications are separate from Partners. An application is a <em>pending request</em> that becomes a Partner only after you approve it.</p>
            </div>

            <h3>🔄 Application Lifecycle</h3>
            <p>Every application goes through the following workflow:</p>

            <div class="workflow-step">
                <div class="workflow-step-number">1</div>
                <div class="workflow-step-content">
                    <h4>Submission</h4>
                    <p>A visitor fills out the application form with their name, email, blog URL, and estimated traffic. The system automatically validates the CAPTCHA, checks for disposable emails, applies rate limiting, and verifies domain reachability.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-step-number">2</div>
                <div class="workflow-step-content">
                    <h4>Email Notifications</h4>
                    <p>Upon successful submission, two emails are sent: a confirmation email to the applicant, and a notification email to the admin (you) with full application details.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-step-number">3</div>
                <div class="workflow-step-content">
                    <h4>Admin Review</h4>
                    <p>You review the application in this admin panel. Check the applicant's website, verify their traffic claims, and assess if they're a good fit for your program.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-step-number">4</div>
                <div class="workflow-step-content">
                    <h4>Decision</h4>
                    <p>Approve or reject the application. Approved applications can be converted into active Partners who can then be assigned to redirect rules.</p>
                </div>
            </div>

            <h3>📊 Application Statuses</h3>
            <p>Applications have two types of statuses that you'll see in the list view:</p>

            <h4 style="margin-top: 20px; font-size: 15px; color: var(--gray-700);">Application Status</h4>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Badge</th>
                        <th>Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>pending</code></td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>Awaiting your review. This is the default status for new applications.</td>
                    </tr>
                    <tr>
                        <td><code>approved</code></td>
                        <td><span class="status-badge approved">Approved</span></td>
                        <td>You've accepted this application. The applicant is now a partner.</td>
                    </tr>
                    <tr>
                        <td><code>rejected</code></td>
                        <td><span class="status-badge rejected">Rejected</span></td>
                        <td>You've declined this application. The applicant was not accepted.</td>
                    </tr>
                </tbody>
            </table>

            <h4 style="margin-top: 24px; font-size: 15px; color: var(--gray-700);">Domain Verification Status</h4>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Badge</th>
                        <th>Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>verified</code></td>
                        <td><span class="status-badge verified">Verified</span></td>
                        <td>The domain is reachable and responding properly. Good sign!</td>
                    </tr>
                    <tr>
                        <td><code>failed</code></td>
                        <td><span class="status-badge failed">Failed</span></td>
                        <td>The domain could not be reached. May be a red flag or temporary issue.</td>
                    </tr>
                    <tr>
                        <td><code>pending</code></td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>Domain verification is still in progress or hasn't been checked yet.</td>
                    </tr>
                    <tr>
                        <td><code>unchecked</code></td>
                        <td><span class="status-badge pending">Unchecked</span></td>
                        <td>The domain hasn't been verified yet.</td>
                    </tr>
                </tbody>
            </table>

            <h3>📝 Application Data Fields</h3>
            <p>Here's what information is collected for each application:</p>

            <table class="field-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>name</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The applicant's full name</td>
                    </tr>
                    <tr>
                        <td><code>email</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>Contact email address (must be unique, no duplicates allowed)</td>
                    </tr>
                    <tr>
                        <td><code>blog_url</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The applicant's website or blog URL</td>
                    </tr>
                    <tr>
                        <td><code>traffic_estimate</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>Monthly traffic estimate (1K-5K, 5K-10K, 10K-25K, 25K-50K, or 50K+)</td>
                    </tr>
                    <tr>
                        <td><code>notes</code></td>
                        <td><span class="optional-badge">Optional</span></td>
                        <td>Additional notes or comments from the applicant</td>
                    </tr>
                    <tr>
                        <td><code>ip_address</code></td>
                        <td><span class="optional-badge">Auto</span></td>
                        <td>Automatically captured IP address for fraud prevention</td>
                    </tr>
                    <tr>
                        <td><code>created_at</code></td>
                        <td><span class="optional-badge">Auto</span></td>
                        <td>Timestamp when the application was submitted</td>
                    </tr>
                </tbody>
            </table>

            <h3>🖱️ Understanding the Buttons</h3>
            <p>On the Applications page, you'll find these action buttons:</p>

            <table class="button-table">
                <thead>
                    <tr>
                        <th>Button</th>
                        <th>Preview</th>
                        <th>What It Does</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Refresh</strong></td>
                        <td><span class="button-preview edit">🔄 Refresh</span></td>
                        <td>Reloads the applications list to show the latest data. Use this to check for new applications.</td>
                    </tr>
                    <tr>
                        <td><strong>View</strong></td>
                        <td><span class="button-preview view">👁️ View</span></td>
                        <td>Opens the full application details. Here you can see all submitted information, review the applicant's website, and make your decision.</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box success">
                <p><strong>✅ Pro Tip:</strong> Always visit the applicant's blog/website before approving. Check that it's a real site with actual content and traffic potential.</p>
            </div>

            <h3>🔒 Built-in Security Features</h3>
            <p>The application system includes several automatic protections:</p>

            <ul>
                <li><strong>CAPTCHA Verification:</strong> Simple math challenge prevents automated bot submissions</li>
                <li><strong>Disposable Email Detection:</strong> Blocks temporary/throwaway email addresses</li>
                <li><strong>Rate Limiting:</strong> Maximum 5 applications per IP address within 24 hours</li>
                <li><strong>Duplicate Detection:</strong> Only one application per email address allowed</li>
                <li><strong>Domain Verification:</strong> Automatically checks if the submitted blog URL is reachable</li>
                <li><strong>IP Logging:</strong> Records the applicant's IP for fraud investigation if needed</li>
            </ul>

            <h3>📧 Email Notifications</h3>
            <p>When an application is submitted, two emails are automatically sent:</p>

            <div class="workflow-step">
                <div class="workflow-step-number">📬</div>
                <div class="workflow-step-content">
                    <h4>To You (Admin)</h4>
                    <p>A notification with full application details including name, email, blog URL, traffic estimate, domain verification status, IP address, and notes. Includes a direct link to review the application.</p>
                </div>
            </div>

            <div class="workflow-step">
                <div class="workflow-step-number">📨</div>
                <div class="workflow-step-content">
                    <h4>To the Applicant</h4>
                    <p>A confirmation email thanking them for applying, explaining the next steps (verification → review → decision), summarizing their submission, and setting expectations for the 3-5 business day review period.</p>
                </div>
            </div>
        </section>

        <!-- Partners Section -->
        <section id="partners" class="docs-section">
            <h2>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                Partners Module
            </h2>
            <p class="subtitle">Manage your approved affiliate partners</p>

            <h3>👥 What is a Partner?</h3>
            <p>A <strong>Partner</strong> is an approved affiliate who can drive traffic to your ClickBank offers. Each partner has a unique <code>aff_id</code> (affiliate ID) that is used in tracking URLs to attribute clicks and conversions to them.</p>

            <div class="info-box">
                <p><strong>💡 Key Concept:</strong> Partners are created either by approving an Application or by manually adding them. The affiliate ID is how the system knows which partner sent the traffic.</p>
            </div>

            <h3>📝 Partner Data Fields</h3>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>aff_id</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>Unique affiliate identifier used in tracking URLs (e.g., <code>partner123</code>)</td>
                    </tr>
                    <tr>
                        <td><code>partner_name</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>Display name for the partner</td>
                    </tr>
                    <tr>
                        <td><code>is_active</code></td>
                        <td><span class="optional-badge">Toggle</span></td>
                        <td>Whether the partner is active (can receive traffic) or inactive</td>
                    </tr>
                </tbody>
            </table>

            <h3>🖱️ Understanding the Buttons</h3>
            <table class="button-table">
                <thead>
                    <tr>
                        <th>Button</th>
                        <th>Preview</th>
                        <th>What It Does</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Add Partner</strong></td>
                        <td><span class="button-preview approve">+ Add Partner</span></td>
                        <td>Opens the form to manually create a new partner. Use this when you want to add a partner without going through the application process.</td>
                    </tr>
                    <tr>
                        <td><strong>Edit</strong></td>
                        <td><span class="button-preview edit">Edit</span></td>
                        <td>Modify the partner's affiliate ID or name. Changes take effect immediately.</td>
                    </tr>
                    <tr>
                        <td><strong>Activate/Deactivate</strong></td>
                        <td><span class="button-preview toggle">Deactivate</span></td>
                        <td>Toggle the partner's active status. Deactivated partners won't receive traffic from redirect rules.</td>
                    </tr>
                    <tr>
                        <td><strong>Delete</strong></td>
                        <td><span class="button-preview delete">Delete</span></td>
                        <td>Permanently removes the partner. <strong>Warning:</strong> This also deletes all associated redirect rules!</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box warning">
                <p><strong>⚠️ Important:</strong> Before deleting a partner, consider deactivating them instead. Deleting will remove all their redirect rules and click history.</p>
            </div>

            <h3>🔗 How Partner IDs Work</h3>
            <p>When traffic comes to your tracking URL with an <code>aff_id</code> parameter, the system:</p>
            <ol>
                <li>Looks up the partner by their affiliate ID</li>
                <li>Checks if the partner is active</li>
                <li>Finds applicable redirect rules for that partner</li>
                <li>Redirects the visitor to the appropriate ClickBank offer</li>
                <li>Logs the click with partner attribution</li>
            </ol>

            <div class="diagram">
                <pre>Visitor clicks: https://track.domain.com/?aff_id=partner123
                       ↓
            System looks up "partner123"
                       ↓
         Partner found & is_active = true
                       ↓
          Find redirect rule for partner
                       ↓
    Redirect to ClickBank hoplink with tracking</pre>
            </div>
        </section>

        <!-- Domains Section -->
        <section id="domains" class="docs-section">
            <h2>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                Domains Module
            </h2>
            <p class="subtitle">Manage tracking domains for your affiliate links</p>

            <h3>🌐 What is a Domain?</h3>
            <p>A <strong>Domain</strong> is a tracking domain that you use to redirect affiliate traffic. You can have multiple domains for different purposes (e.g., different niches, A/B testing, or regional targeting).</p>

            <h3>📝 Domain Data Fields</h3>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>domain_name</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The full domain name (e.g., <code>track.example.com</code>)</td>
                    </tr>
                    <tr>
                        <td><code>is_active</code></td>
                        <td><span class="optional-badge">Toggle</span></td>
                        <td>Whether the domain is active and processing traffic</td>
                    </tr>
                </tbody>
            </table>

            <h3>🖱️ Understanding the Buttons</h3>
            <table class="button-table">
                <thead>
                    <tr>
                        <th>Button</th>
                        <th>Preview</th>
                        <th>What It Does</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Add Domain</strong></td>
                        <td><span class="button-preview approve">+ Add Domain</span></td>
                        <td>Register a new tracking domain in the system.</td>
                    </tr>
                    <tr>
                        <td><strong>Edit</strong></td>
                        <td><span class="button-preview edit">Edit</span></td>
                        <td>Modify the domain name or settings.</td>
                    </tr>
                    <tr>
                        <td><strong>Activate/Deactivate</strong></td>
                        <td><span class="button-preview toggle">Deactivate</span></td>
                        <td>Toggle domain status. Inactive domains won't process redirect traffic.</td>
                    </tr>
                    <tr>
                        <td><strong>Delete</strong></td>
                        <td><span class="button-preview delete">Delete</span></td>
                        <td>Removes the domain and its associated redirect rules.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Offers Section -->
        <section id="offers" class="docs-section">
            <h2>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                Offers Module
            </h2>
            <p class="subtitle">Configure ClickBank products and hoplinks</p>

            <h3>🏷️ What is an Offer?</h3>
            <p>An <strong>Offer</strong> represents a ClickBank product that you want to promote. Each offer contains the vendor information and hoplink URL that visitors will be redirected to when they click your tracking links.</p>

            <h3>📝 Offer Data Fields</h3>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>offer_name</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>A friendly name for the offer (e.g., "Weight Loss Guide")</td>
                    </tr>
                    <tr>
                        <td><code>clickbank_vendor</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The ClickBank vendor/product ID</td>
                    </tr>
                    <tr>
                        <td><code>clickbank_hoplink</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The full ClickBank hoplink URL</td>
                    </tr>
                    <tr>
                        <td><code>is_active</code></td>
                        <td><span class="optional-badge">Toggle</span></td>
                        <td>Whether the offer is available for use in redirect rules</td>
                    </tr>
                </tbody>
            </table>

            <h3>🖱️ Understanding the Buttons</h3>
            <table class="button-table">
                <thead>
                    <tr>
                        <th>Button</th>
                        <th>Preview</th>
                        <th>What It Does</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Add Offer</strong></td>
                        <td><span class="button-preview approve">+ Add Offer</span></td>
                        <td>Create a new ClickBank offer configuration.</td>
                    </tr>
                    <tr>
                        <td><strong>Edit</strong></td>
                        <td><span class="button-preview edit">Edit</span></td>
                        <td>Modify offer details like name or hoplink URL.</td>
                    </tr>
                    <tr>
                        <td><strong>Activate/Deactivate</strong></td>
                        <td><span class="button-preview toggle">Deactivate</span></td>
                        <td>Toggle offer status. Inactive offers can't be used in new rules.</td>
                    </tr>
                    <tr>
                        <td><strong>Delete</strong></td>
                        <td><span class="button-preview delete">Delete</span></td>
                        <td>Remove the offer. Rules using this offer will also be deleted.</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-box">
                <p><strong>💡 Tip:</strong> You can get your hoplink from ClickBank's marketplace. Make sure to use your affiliate ID in the hoplink to receive commission.</p>
            </div>
        </section>

        <!-- Redirect Rules Section -->
        <section id="rules" class="docs-section">
            <h2>
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                Redirect Rules Module
            </h2>
            <p class="subtitle">Configure how traffic is routed to different offers</p>

            <h3>⚡ What is a Redirect Rule?</h3>
            <p>A <strong>Redirect Rule</strong> defines how incoming traffic should be redirected to ClickBank offers. Rules can be global (apply to all traffic), domain-specific, or partner-specific, allowing for sophisticated traffic routing.</p>

            <h3>🎯 Rule Types</h3>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>partner</code></td>
                        <td>🥇 Highest</td>
                        <td>Applies to traffic from a specific partner. Takes precedence over all other rules.</td>
                    </tr>
                    <tr>
                        <td><code>domain</code></td>
                        <td>🥈 Medium</td>
                        <td>Applies to traffic from a specific domain. Used when no partner rule matches.</td>
                    </tr>
                    <tr>
                        <td><code>global</code></td>
                        <td>🥉 Lowest</td>
                        <td>Applies to all traffic as a fallback when no other rules match.</td>
                    </tr>
                </tbody>
            </table>

            <h3>📝 Rule Data Fields</h3>
            <table class="field-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>rule_name</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>A descriptive name for the rule</td>
                    </tr>
                    <tr>
                        <td><code>rule_type</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>One of: <code>global</code>, <code>domain</code>, or <code>partner</code></td>
                    </tr>
                    <tr>
                        <td><code>domain_id</code></td>
                        <td><span class="optional-badge">Conditional</span></td>
                        <td>Required when rule_type is <code>domain</code></td>
                    </tr>
                    <tr>
                        <td><code>partner_id</code></td>
                        <td><span class="optional-badge">Conditional</span></td>
                        <td>Required when rule_type is <code>partner</code></td>
                    </tr>
                    <tr>
                        <td><code>offer_id</code></td>
                        <td><span class="required-badge">Required</span></td>
                        <td>The offer to redirect traffic to</td>
                    </tr>
                    <tr>
                        <td><code>priority</code></td>
                        <td><span class="optional-badge">Optional</span></td>
                        <td>Numeric priority (lower = higher priority). Default is 100.</td>
                    </tr>
                    <tr>
                        <td><code>is_paused</code></td>
                        <td><span class="optional-badge">Toggle</span></td>
                        <td>Paused rules are temporarily disabled</td>
                    </tr>
                </tbody>
            </table>

            <h3>🖱️ Understanding the Buttons</h3>
            <table class="button-table">
                <thead>
                    <tr>
                        <th>Button</th>
                        <th>Preview</th>
                        <th>What It Does</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Add Rule</strong></td>
                        <td><span class="button-preview approve">+ Add Rule</span></td>
                        <td>Create a new redirect rule. Choose the type and configure the targeting.</td>
                    </tr>
                    <tr>
                        <td><strong>Edit</strong></td>
                        <td><span class="button-preview edit">Edit</span></td>
                        <td>Modify rule settings, change the target offer, or adjust priority.</td>
                    </tr>
                    <tr>
                        <td><strong>Pause/Resume</strong></td>
                        <td><span class="button-preview toggle">Pause</span></td>
                        <td>Temporarily disable a rule without deleting it. Useful for A/B testing.</td>
                    </tr>
                    <tr>
                        <td><strong>Delete</strong></td>
                        <td><span class="button-preview delete">Delete</span></td>
                        <td>Permanently remove the rule.</td>
                    </tr>
                </tbody>
            </table>

            <h3>🔄 Rule Evaluation Order</h3>
            <p>When traffic arrives, rules are evaluated in this order:</p>

            <div class="diagram">
                <pre>Incoming Traffic
       ↓
┌──────────────────────────────────┐
│ 1. Check Partner-specific rules  │ ← Highest priority
│    (if aff_id is provided)       │
└──────────────┬───────────────────┘
               ↓ No match?
┌──────────────────────────────────┐
│ 2. Check Domain-specific rules   │ ← Medium priority
│    (based on request domain)     │
└──────────────┬───────────────────┘
               ↓ No match?
┌──────────────────────────────────┐
│ 3. Use Global fallback rule      │ ← Lowest priority
└──────────────┬───────────────────┘
               ↓
         Redirect to Offer</pre>
            </div>

            <div class="info-box success">
                <p><strong>✅ Best Practice:</strong> Always have at least one global rule as a fallback. This ensures all traffic gets redirected somewhere, even if no specific rules match.</p>
            </div>
        </section>

        <!-- Quick Reference Card -->
        <div class="docs-section" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
            <h2 style="color: white;">
                <div class="icon" style="background: rgba(255,255,255,0.2);">
                    🚀
                </div>
                Quick Start Checklist
            </h2>
            <p style="color: rgba(255,255,255,0.9); border-color: rgba(255,255,255,0.2);" class="subtitle">Get up and running in 5 steps</p>

            <div style="display: grid; gap: 16px;">
                <div style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <span style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</span>
                    <div>
                        <strong>Add a Domain</strong>
                        <p style="margin: 4px 0 0; opacity: 0.9; font-size: 14px;">Register your tracking domain(s) in the Domains section</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <span style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</span>
                    <div>
                        <strong>Create Offers</strong>
                        <p style="margin: 4px 0 0; opacity: 0.9; font-size: 14px;">Add your ClickBank products with their hoplinks</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <span style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</span>
                    <div>
                        <strong>Set Up a Global Rule</strong>
                        <p style="margin: 4px 0 0; opacity: 0.9; font-size: 14px;">Create a global redirect rule as your default/fallback</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <span style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">4</span>
                    <div>
                        <strong>Review Applications</strong>
                        <p style="margin: 4px 0 0; opacity: 0.9; font-size: 14px;">Approve partner applications and convert them to active partners</p>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <span style="background: rgba(255,255,255,0.2); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">5</span>
                    <div>
                        <strong>Create Partner Rules</strong>
                        <p style="margin: 4px 0 0; opacity: 0.9; font-size: 14px;">Set up specific redirect rules for each partner as needed</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Smooth scroll for nav links
document.querySelectorAll('.docs-nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href.startsWith('#')) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Update active state
                document.querySelectorAll('.docs-nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        }
    });
});

// Update active nav on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.docs-section[id]');
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        if (pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    document.querySelectorAll('.docs-nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});
</script>

<?php include 'footer.php'; ?>
