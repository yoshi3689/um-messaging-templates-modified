<?php
/**
 * Template for the UM Private Messages.
 * Used on the "Profile" page, "Messages" tab. Display single conversation.
 *
 * Changes made:
 * - Added action hooks to handle upgrade messages and conditional classes.
 * - Removed commented-out block and delete conversation links.
 * - Added variable $is_premium_or_admin to check if the user is a premium subscriber or an admin.
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

// Check if the user is a premium or an admin
$is_premium_or_admin = ($current_user_role === 'Subscriber' || $current_user_role === 'Administrator');

?>

<div class="um-message-header um-popup-header">
    <div class="um-message-header-left">
        <?php echo get_avatar( $message_to, 40 ); ?>
        <a href="<?php echo esc_url( um_user_profile_url() ) ?>"><?php echo esc_html( $contact_name ) ?></a>
    </div>
    <div class="um-message-header-right">
        <?php do_action( 'um_messaging_after_conversation_links', $message_to, $user_id ); ?>

        <a href="javascript:void(0);" class="um-message-hide um-tip-e" title="<?php esc_attr_e( 'Close chat', 'um-messaging' ); ?>">
            <i class="um-icon-android-close"></i>
        </a>
    </div>
</div>

<!-- Add action hook for conditional classes -->
<div class="um-message-body um-popup-autogrow um-message-autoheight <?php do_action( 'um_messaging_conditional_classes', $is_premium_or_admin ); ?>" data-message_to="<?php echo absint( $message_to ); ?>" data-simplebar>
    <!-- Add action hook for conditional classes -->
    <div class="um-message-ajax <?php do_action( 'um_messaging_conditional_classes', $is_premium_or_admin ); ?>"
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
        <?php do_action( 'um_messaging_upgrade_message', $is_premium_or_admin, $response, $message_to ); ?>
        <!-- Add action hook for conditional classes -->
        <div class="um-message-textarea <?php do_action( 'um_messaging_conditional_classes', $is_premium_or_admin ); ?>">
            <textarea id="um_message_text" name="um_message_text" class="um_message_text" data-maxchar="<?php echo absint( $limit ); ?>" placeholder="<?php esc_attr_e( 'Type your message...', 'um-messaging' ); ?>"></textarea>
        </div>

        <!-- Add action hook for conditional classes -->
        <div class="um-message-buttons <?php do_action( 'um_messaging_conditional_classes', $is_premium_or_admin ); ?>">
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
