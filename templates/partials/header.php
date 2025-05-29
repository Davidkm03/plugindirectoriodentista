<?php
/**
 * Template part for displaying the header
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="dental-container">
    <header class="dental-header">
        <div class="dental-logo">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <h2><?php esc_html_e( 'Dental Directory', 'dental-directory-system' ); ?></h2>
            </a>
        </div>

        <?php if ( is_user_logged_in() ) : ?>
            <?php
            $current_user = wp_get_current_user();
            $is_dentist = dental_is_dentist();
            $is_patient = dental_is_patient();
            $dashboard_url = '';
            
            if ( $is_dentist ) {
                $dashboard_id = get_option( 'dental_page_dashboard_dentista' );
                if ( $dashboard_id ) {
                    $dashboard_url = get_permalink( $dashboard_id );
                }
            } elseif ( $is_patient ) {
                $dashboard_id = get_option( 'dental_page_dashboard_paciente' );
                if ( $dashboard_id ) {
                    $dashboard_url = get_permalink( $dashboard_id );
                }
            }
            ?>
            
            <div class="dental-user-menu">
                <div class="dental-user-info">
                    <?php echo get_avatar( $current_user->ID, 40 ); ?>
                    <span><?php echo esc_html( $current_user->display_name ); ?></span>
                </div>
                
                <nav class="dental-nav">
                    <ul>
                        <?php if ( ! empty( $dashboard_url ) ) : ?>
                            <li><a href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Dashboard', 'dental-directory-system' ); ?></a></li>
                        <?php endif; ?>
                        
                        <?php
                        $directory_id = get_option( 'dental_page_directorio' );
                        if ( $directory_id ) :
                        ?>
                            <li><a href="<?php echo esc_url( get_permalink( $directory_id ) ); ?>"><?php esc_html_e( 'Directory', 'dental-directory-system' ); ?></a></li>
                        <?php endif; ?>
                        
                        <?php
                        $chats_id = get_option( 'dental_page_mis_chats' );
                        if ( $chats_id ) :
                        ?>
                            <li><a href="<?php echo esc_url( get_permalink( $chats_id ) ); ?>"><?php esc_html_e( 'Messages', 'dental-directory-system' ); ?></a></li>
                        <?php endif; ?>
                        
                        <li><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Logout', 'dental-directory-system' ); ?></a></li>
                    </ul>
                </nav>
            </div>
        <?php else : ?>
            <div class="dental-auth-buttons">
                <?php
                $login_id = get_option( 'dental_page_login' );
                if ( $login_id ) {
                    $login_url = get_permalink( $login_id );
                } else {
                    $login_url = wp_login_url( home_url() );
                }
                
                $register_dentist_id = get_option( 'dental_page_registro_dentista' );
                $register_patient_id = get_option( 'dental_page_registro_paciente' );
                ?>
                
                <a href="<?php echo esc_url( $login_url ); ?>" class="dental-btn dental-btn-secondary"><?php esc_html_e( 'Login', 'dental-directory-system' ); ?></a>
                
                <?php if ( $register_dentist_id ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $register_dentist_id ) ); ?>" class="dental-btn"><?php esc_html_e( 'Register as Dentist', 'dental-directory-system' ); ?></a>
                <?php endif; ?>
                
                <?php if ( $register_patient_id ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $register_patient_id ) ); ?>" class="dental-btn"><?php esc_html_e( 'Register as Patient', 'dental-directory-system' ); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </header>
</div>
