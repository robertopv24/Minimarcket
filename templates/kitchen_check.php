<?php
// DEPRECATED - DO NOT USE
// Logic has been moved to UserManager::requireKitchenAccess()
// to prevent server-side permission errors.

// This file is kept momentarily to prevent fatal errors if some legacy file still tries to include it.
// But it should NOT be used for logic.

if (isset($userManager) && isset($_SESSION)) {
    // Forward to new logic if included by legacy code
    $userManager->requireKitchenAccess($_SESSION);
}
?>