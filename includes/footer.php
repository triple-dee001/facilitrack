<?php
/**
 * Shared Footer Include
 */
$user = current_user();
?>
        <?php if ($user): ?>
        </div><!-- .page-content -->
    </main><!-- .main-content -->
        <?php else: ?>
    </main><!-- .auth-main -->
        <?php endif; ?>
    
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
