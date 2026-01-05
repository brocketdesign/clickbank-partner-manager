        </div><!-- /.main-content -->
    </div><!-- /.app-layout -->

    <footer style="
        background: white;
        border-top: 1px solid var(--gray-200);
        padding: 16px 32px;
        margin-left: var(--sidebar-width);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    ">
        <div style="color: var(--gray-500); font-size: 13px;">
            © <?php echo date('Y'); ?> <strong style="color: var(--gray-700);">AdeasyNow</strong>. All rights reserved.
        </div>
        <div style="display: flex; gap: 20px; font-size: 13px;">
            <a href="email_test.php" style="color: var(--gray-500); text-decoration: none; transition: color 0.2s;">
                Email Test Dashboard
            </a>
            <span style="color: var(--gray-300);">|</span>
            <span style="color: var(--gray-400);">v2.0.0</span>
        </div>
    </footer>

    <style>
        @media (max-width: 1024px) {
            footer {
                margin-left: 0 !important;
            }
        }
    </style>
</body>
</html>
