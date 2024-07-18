<?php
// Hook function to add conditional classes
/**
 * Adds conditional classes to elements based on user role.
 *
 * @param bool $is_premium_or_admin Whether the user is a premium subscriber or admin.
 */
function add_conditional_classes($is_premium_or_admin) {
    echo $is_premium_or_admin ? '' : 'blurred-content';
}
add_action('um_messaging_conditional_classes', 'add_conditional_classes');
