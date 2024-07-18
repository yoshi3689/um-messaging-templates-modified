<?php
// Hook function to display upgrade messages
/**
 * Displays upgrade messages if the user is not a premium subscriber or admin.
 *
 * @param bool $is_premium_or_admin Whether the user is a premium subscriber or admin.
 * @param array $response The response data from the conversation.
 * @param int $message_to The ID of the user the message is being sent to.
 */
function display_upgrade_message($is_premium_or_admin, $response, $message_to) {
    if (!$is_premium_or_admin) {
        $should_return = false;

        if (UM()->Messaging_API()->api()->limit_reached()) {
            echo '<span>' . esc_html__('You have reached your limit for sending messages.', 'um-messaging') . '</span>';
            $should_return = true;
        } elseif (!UM()->roles()->um_user_can('can_reply_pm') && !empty($response)) {
            echo '<span>' . esc_html__('You are not allowed to reply to private messages.', 'um-messaging') . '</span>';
            $should_return = true;
        } elseif (UM()->roles()->um_user_can('can_reply_pm') && !empty($response) && !empty(UM()->roles()->um_user_can('can_reply_access'))) {
            $roles = UM()->roles()->um_user_can('can_reply_roles');
            $receiver_roles = UM()->roles()->get_all_user_roles($message_to);
            if (!empty($roles) && empty(array_intersect($roles, $receiver_roles))) {
                echo '<span>' . esc_html__('You are not allowed to reply to private messages with this user.', 'um-messaging') . '</span>';
                $should_return = true;
            }
        } elseif (!UM()->roles()->um_user_can('can_start_pm') && empty($response) && empty($other_message)) {
            echo '<span>' . esc_html__('You are not allowed to start conversations.', 'um-messaging') . '</span>';
            $should_return = true;
        } elseif (UM()->roles()->um_user_can('can_start_pm') && empty($response) && empty($other_message) && !empty(UM()->roles()->um_user_can('can_start_access'))) {
            $roles = UM()->roles()->um_user_can('can_start_roles');
            $receiver_roles = UM()->roles()->get_all_user_roles($message_to);
            if (!empty($roles) && empty(array_intersect($roles, $receiver_roles))) {
                echo '<span>' . esc_html__('You are not allowed to start conversations with this user.', 'um-messaging') . '</span>';
                $should_return = true;
            }
        }

        if ($should_return) {
            echo '<a href="https://careservice.ca/premium-plans/" class="um-upgrade-button">' . esc_html__('Upgrade', 'um-messaging') . '</a>';
            return;
        }

        if (UM()->roles()->um_user_can('can_start_pm') && empty($response) && empty($other_message) && !UM()->roles()->um_user_can('can_reply_pm')) {
            echo '<span>' . esc_html__('You can send the first message but will not be able to reply with current membership level.', 'um-messaging') . '</span>';
            echo '<a href="https://careservice.ca/premium-plans/" class="um-upgrade-button">' . esc_html__('Upgrade', 'um-messaging') . '</a>';
        }
    }
}
add_action('um_messaging_upgrade_message', 'display_upgrade_message', 10, 3);
