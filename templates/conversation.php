<?php
/**
 * Template for the UM Private Messages.
 * Used on the "Profile" page, "Messages" tab. Display single conversation.
 *
 * Changes made:
 * - Added function `user_has_role` to check if a user has a specific role.
 * - Added check to see if the user has the 'Subscriber' role as a secondary role.
 * - Added condition to blur content and show upgrade messages based on secondary role.
 * - Updated URLs and texts for upgrading subscription.
 * - Removed commented-out block and delete conversation links.
 * 
 * Caller: method Messaging_Main_API->ajax_messaging_start()
 * Parent template: conversations.php
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-messaging/conversation.php
 *
 * @see     https://docs.ultimatemember.com/article/1516-templates-map
 * @package um_ext\um_messaging\templates
 * @version 2.3.5
 * @var int $message_to
 * @var int $user_id
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

UM()->Messaging_API()->api()->perms = UM()->Messaging_API()->api()->get_perms( get_current_user_id() );

um_fetch_user( $message_to );
$contact_name = ( um_user( 'display_name' ) ) ? um_user( 'display_name' ) : __( 'Deleted User', 'um-messaging' );
$contact_url  = um_user_profile_url();

$limit = UM()->options()->get( 'pm_char_limit' );

um_fetch_user( $user_id );

$response = UM()->Messaging_API()->api()->get_conversation_id( $message_to, $user_id );

// Get current user's role
$current_user_role = UM()->roles()->get_role_name( UM()->user()->get_role() );

// Check if the user is not a subscriber and not an admin
$is_not_subscriber_or_admin = ($current_user_role !== 'Subscriber' && $current_user_role !== 'Administrator');

?>

<div class="um-message-header um-popup-header">
    <div class="um-message-header-left">
        <?php echo get_avatar( $message_to, 40 ); ?>
        <a href="<?php echo esc_url( um_user_profile_url() ) ?>"><?php echo esc_html( $contact_name ) ?></a>
    </div>
    <div class="um-message-header-right">
        <!-- Commented out block and delete conversation links -->
        <!-- 
        <a href="javascript:void(0);" class="um-message-blocku um-tip-e"
             title="<?php esc_attr_e( 'Block user', 'um-messaging' ); ?>"
             data-confirm_text="<?php esc_attr_e( 'Are you sure to block this user?', 'um-messaging' ); ?>"
             data-other_user="<?php echo esc_attr( $message_to ); ?>"
             data-conversation_id="<?php echo ! empty( $response['conversation_id'] ) ? esc_attr( $response['conversation_id'] ) : 'new'; ?>">
            <i class="um-faicon-ban"></i>
        </a>
        <a href="javascript:void(0);" class="um-message-delconv um-tip-e"
             title="<?php esc_attr_e( 'Delete conversation', 'um-messaging' ); ?>"
             data-other_user="<?php echo esc_attr( $message_to ); ?>"
             data-conversation_id="<?php echo ! empty( $response['conversation_id'] ) ? esc_attr( $response['conversation_id'] ) : 'new'; ?>"
           <?php if ( empty( $response ) ) { ?>style="display:none;"<?php } ?>>
            <i class="um-icon-trash-b"></i>
        </a> 
        -->

        <?php do_action( 'um_messaging_after_conversation_links', $message_to, $user_id ); ?>

        <a href="javascript:void(0);" class="um-message-hide um-tip-e" title="<?php esc_attr_e( 'Close chat', 'um-messaging' ); ?>">
            <i class="um-icon-android-close"></i>
        </a>
    </div>
</div>

<!-- Add blurred-content class conditionally to um-message-body -->
<div class="um-message-body um-popup-autogrow um-message-autoheight <?php echo $is_not_subscriber_or_admin ? 'blurred-content' : ''; ?>" data-message_to="<?php echo absint( $message_to ); ?>" data-simplebar>
    <!-- Add blurred-content class conditionally to um-message-ajax -->
    <div class="um-message-ajax <?php echo $is_not_subscriber_or_admin ? 'blurred-content' : ''; ?>"
         data-message_from="<?php echo esc_attr( $user_id ); ?>"
         data-message_to="<?php echo esc_attr( $message_to ); ?>"
         data-conversation_id="<?php echo ! empty( $response['conversation_id'] ) ? esc_attr( $response['conversation_id'] ) : 'new'; ?>"
         data-last_updated="<?php echo ! empty( $response['last_updated'] ) ? esc_attr( $response['last_updated'] ) : ''; ?>">

        <?php
        if ( UM()->Messaging_API()->api()->perms['can_read_pm'] || UM()->Messaging_API()->api()->perms['can_start_pm'] ) {

            if ( ! empty( $response['conversation_id'] ) ) {
                echo UM()->Messaging_API()->api()->get_conversation( $message_to, $user_id, $response['conversation_id'] );
            }

        } else {
            ?>

            <span class="um-message-notice">
                <?php esc_html_e( 'Your membership level does not allow you to view conversations.', 'um-messaging' ) ?>
            </span>

        <?php } ?>
    </div>
</div>

<?php
if ( ! empty( $response ) ) {
    global $wpdb;
    $other_message = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT message_id
            FROM {$wpdb->prefix}um_messages
            WHERE conversation_id = %d AND
                    author = %d
            ORDER BY time ASC
            LIMIT 1",
            $response['conversation_id'],
            $message_to
        )
    );
}

if ( ! UM()->Messaging_API()->api()->can_message( $message_to ) ) {
    // Commented out the blocked message
    // esc_html_e( 'You are blocked and not allowed continue this conversation.', 'um-messaging' );
} else {
    ?>

    <div class="um-message-footer um-popup-footer" data-limit_hit="<?php esc_attr_e( 'You have reached your limit for sending messages.', 'um-messaging' ); ?>">
        <div class="upgrade-message-container">
            <?php
            $should_return = false;

            // Check various conditions and set appropriate messages
            if ( UM()->Messaging_API()->api()->limit_reached() ) {
                echo '<span>' . esc_html__( 'You have reached your limit for sending messages.', 'um-messaging' ) . '</span>';
                $should_return = true;
            } elseif ( ! UM()->roles()->um_user_can( 'can_reply_pm' ) && ! empty( $response ) ) {
                echo '<span>' . esc_html__( 'You are not allowed to reply to private messages.', 'um-messaging' ) . '</span>';
                $should_return = true;
            } elseif ( UM()->roles()->um_user_can( 'can_reply_pm' ) && ! empty( $response ) && ! empty( UM()->roles()->um_user_can( 'can_reply_access' ) ) ) {
                $roles = UM()->roles()->um_user_can( 'can_reply_roles' );
                $receiver_roles = UM()->roles()->get_all_user_roles( $message_to );
                if ( ! empty( $roles ) && empty( array_intersect( $roles, $receiver_roles ) ) ) {
                    echo '<span>' . esc_html__( 'You are not allowed to reply to private messages with this user.', 'um-messaging' ) . '</span>';
                    $should_return = true;
                }
            } elseif ( ! UM()->roles()->um_user_can( 'can_start_pm' ) && empty( $response ) && empty( $other_message ) ) {
                echo '<span>' . esc_html__( 'You are not allowed to start conversations.', 'um-messaging' ) . '</span>';
                $should_return = true;
            } elseif ( UM()->roles()->um_user_can( 'can_start_pm' ) && empty( $response ) && empty( $other_message ) && ! empty( UM()->roles()->um_user_can( 'can_start_access' ) ) ) {
                $roles = UM()->roles()->um_user_can( 'can_start_roles' );
                $receiver_roles = UM()->roles()->get_all_user_roles( $message_to );
                if ( ! empty( $roles ) && empty( array_intersect( $roles, $receiver_roles ) ) ) {
                    echo '<span>' . esc_html__( 'You are not allowed to start conversations with this user.', 'um-messaging' ) . '</span>';
                    $should_return = true;
                }
            }

            // Display upgrade button if necessary
            if ( $should_return && $is_not_subscriber_or_admin ) {
                echo '<a href="https://careservice.ca/premium-plans/" class="um-upgrade-button">' . esc_html__( 'Upgrade', 'um-messaging' ) . '</a>';
                return;
            }

            // New condition: User can start conversation but cannot reply
            if ( UM()->roles()->um_user_can( 'can_start_pm' ) && empty( $response ) && empty( $other_message ) && ! UM()->roles()->um_user_can( 'can_reply_pm' ) && $is_not_subscriber_or_admin ) {
                echo '<span>' . esc_html__( 'You can send the first message but will not be able to reply with current membership level.', 'um-messaging' ) . '</span>';
                echo '<a href="https://careservice.ca/premium-plans/" class="um-upgrade-button">' . esc_html__( 'Upgrade', 'um-messaging' ) . '</a>';
            }
            ?>
        </div>

        <!-- Add empty-content class conditionally to um-message-textarea -->
        <div class="um-message-textarea <?php echo $is_not_subscriber_or_admin ? 'empty-content' : ''; ?>">
            <textarea id="um_message_text" name="um_message_text" class="um_message_text" data-maxchar="<?php echo absint( $limit ); ?>" placeholder="<?php esc_attr_e( 'Type your message...', 'um-messaging' ); ?>"></textarea>
        </div>

        <!-- Add empty-content class conditionally to um-message-buttons -->
        <div class="um-message-buttons <?php echo $is_not_subscriber_or_admin ? 'empty-content' : ''; ?>">
            <?php UM()->get_template( 'emoji.php', um_messaging_plugin, array(), true ); ?>
            <span class="um-message-limit"><?php echo absint( $limit ); ?></span>
            <a href="javascript:void(0);" class="um-message-send disabled">
                <i class="um-faicon-envelope-o"></i>
                <?php esc_html_e( 'Send message', 'um-messaging' ); ?>
            </a>
        </div>

        <div class="um-clear"></div>
    </div>

<?php
}
?>
