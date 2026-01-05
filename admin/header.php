<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ClickBank Partner Manager</title>
    <link rel="icon" href="/favicon_io/favicon.ico" />
    <link rel="shortcut icon" href="/favicon_io/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png" />
    <link rel="manifest" href="/favicon_io/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #0ea5e9;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --sidebar-width: 260px;
            --header-height: 64px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
        }

        /* Layout */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--gray-900) 0%, var(--gray-800) 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header img {
            height: 32px;
            border-radius: 8px;
        }

        .sidebar-header h1 {
            font-size: 15px;
            font-weight: 600;
            color: white;
            line-height: 1.3;
        }

        .sidebar-nav {
            padding: 16px 12px;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-400);
            padding: 0 12px;
            margin-bottom: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--gray-300);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: var(--gray-100);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .user-menu:hover {
            background: var(--gray-200);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-700);
        }

        /* Container */
        .container {
            padding: 32px;
            max-width: 1600px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 32px;
        }

        .page-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--gray-500);
            font-size: 15px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-100);
        }

        .card h2, .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .stat-card:nth-child(2)::before { background: linear-gradient(180deg, var(--success) 0%, #059669 100%); }
        .stat-card:nth-child(3)::before { background: linear-gradient(180deg, var(--warning) 0%, #d97706 100%); }
        .stat-card:nth-child(4)::before { background: linear-gradient(180deg, var(--danger) 0%, #dc2626 100%); }
        .stat-card:nth-child(5)::before { background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%); }
        .stat-card:nth-child(6)::before { background: linear-gradient(180deg, #06b6d4 0%, #0891b2 100%); }

        .stat-card h3 {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .stat-card .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
        }

        .stat-card .stat-change {
            margin-top: 8px;
            font-size: 13px;
            color: var(--success);
            font-weight: 500;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
        }

        table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        table th:first-child {
            border-radius: 8px 0 0 8px;
        }

        table th:last-child {
            border-radius: 0 8px 8px 0;
        }

        table tbody tr {
            transition: background 0.15s ease;
        }

        table tbody tr:hover {
            background: var(--gray-50);
        }

        table td {
            color: var(--gray-700);
        }

        table code {
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 13px;
            color: var(--primary-dark);
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
            transform: none;
            box-shadow: none;
        }

        .btn-small {
            padding: 6px 14px;
            font-size: 13px;
        }

        .btn-icon {
            padding: 8px;
            border-radius: 8px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-active {
            background: var(--success-light);
            color: #065f46;
        }

        .badge-inactive {
            background: var(--danger-light);
            color: #991b1b;
        }

        .badge-paused, .badge-pending {
            background: var(--warning-light);
            color: #92400e;
        }

        /* Forms */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--gray-500);
            font-size: 13px;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: var(--success-light);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: var(--danger-light);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: var(--warning-light);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* Actions Group */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Login Page */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-800) 50%, var(--primary-dark) 100%);
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 420px;
        }

        .login-box .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .login-box .logo img {
            height: 40px;
            border-radius: 8px;
        }

        .login-box h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 8px;
        }

        .login-box .subtitle {
            text-align: center;
            color: var(--gray-500);
            margin-bottom: 32px;
            font-size: 15px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: var(--gray-600);
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .container {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-header {
                padding: 12px 16px;
            }

            .page-title {
                font-size: 18px;
            }

            .card {
                padding: 16px;
            }

            .actions {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
            }

            table {
                font-size: 13px;
            }

            table th, table td {
                padding: 10px 8px;
            }
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Chart Container */
        .chart-container {
            height: 200px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 20px 0;
        }

        .chart-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chart-bar-fill {
            width: 100%;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 6px 6px 0 0;
            transition: height 0.3s ease;
            min-height: 4px;
        }

        .chart-bar-label {
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray-500);
        }

        .chart-bar-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        /* Table Wrapper for overflow */
        .table-wrapper {
            overflow-x: auto;
            margin: 0 -24px;
            padding: 0 24px;
        }
    </style>
</head>
<body>
