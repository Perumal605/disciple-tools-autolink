<?php
/**
 * @var $logo_url string
 * @var $register_url string
 * @var $form_action string
 * @var $error string
 */
?>
<?php include( 'parts/header.php' ); ?>

<div class="container login">
    <dt-tile>
        <div class="section__inner">
            <div class="logo">
                <img src="<?php echo esc_url( $logo_url ) ?>"
                    alt="Disciple.Tools"
                    class="logo__image">
            </div>

            <form action="<?php echo esc_attr( $form_action ) ?>"
                method="POST">
                <?php wp_nonce_field( 'dt_autolink_login' ); ?>

                <?php if ( !empty( $error ) ) : ?>
                    <dt-alert context="alert"
                            dismissable>
                        <?php echo esc_html( strip_tags( $error ) ) ?>
                    </dt-alert>
                <?php endif; ?>

                <dt-text name="username"
                        placeholder="<?php esc_attr_e( 'Username', 'disciple-tools-autolink' ); ?>"
                        value=""
                        required
                        tabindex="1"
                        ></dt-text>
                <dt-text name="password"
                        placeholder="<?php esc_attr_e( 'Password', 'disciple-tools-autolink' ); ?>"
                        value=""
                        type="password"
                        tabindex="2"
                        required></dt-text>

                <div class="login__buttons">
                    <dt-button context="success"
                            tabindex="3"
                            type="submit">
                        <?php esc_html_e( 'Login', 'disciple-tools-autolink' ) ?>
                    </dt-button>

                    <dt-button context="link"
                            href="<?php echo esc_url( $register_url ); ?>"
                            tabindex="`4"
                            title="<?php esc_attr_e( 'Create Account', 'disciple-tools-autolink' ); ?>">
                        <?php esc_html_e( 'Create Account', 'disciple-tools-autolink' ) ?>
                        <dt-chevron-right></dt-chevron-right>
                    </dt-button>
                </div>
            </form>
        </div>
    </dt-tile>
    <div class="login__footer">
        <dt-button context="link" href="<?php echo esc_url( $reset_url ); ?>">
            <?php esc_html_e( 'Forgot Password?', 'disciple-tools-autolink' ); ?>
            </dt-button>
</div>

<?php include( 'parts/footer.php' ); ?>
