<?php

class L1a_Porch_Admin {
    public $title = 'L1A Porch';
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        add_filter( 'dt_remove_menu_pages', [ $this, 'add_media_tab' ], 10, 1 );
        add_filter( 'upload_mimes', [ $this, 'add_additional_mime_types' ], 1, 1 );

        if ( '/wp-admin/upload.php' === $_SERVER['REQUEST_URI'] ) {
            $this->p4m_add_media_page_warning();
        }

        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    public function add_media_tab( $list ) {
        if ( isset( $list['media'] ) ) {
            unset( $list['media'] );
        }
        return $list;
    }
    public function add_additional_mime_types( $mime_types){
        $mime_types['svg'] = 'image/svg+xml'; //Adding svg extension
        $mime_types['psd'] = 'image/vnd.adobe.photoshop'; //Adding photoshop files
        $mime_types['pdf'] = 'application/pdf'; //Adding photoshop files
        $mime_types['docx'] = 'application/vnd.openxmlformats-'; //Adding photoshop files
        $mime_types['doc'] = 'application/msword'; //Adding photoshop files
        $mime_types['csv'] = 'text/csv'; //Adding photoshop files
        $mime_types['zip'] = 'application/zip'; //Adding photoshop files
        return $mime_types;
    }

    public function p4m_add_media_page_warning() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>SECURITY WARNING: <BR>ALL IMAGES AND MEDIA FILES ADDED HERE ARE PUBLICLY ACCESSIBLE TO THE INTERNET. <BR>DO NOT STORE SENSITIVE FILES!</p>
        </div>
        <?php
    }

    public function admin_menu() {
        add_menu_page( $this->title, $this->title, 'manage_dt', 'l1a_landing_page', [ $this, 'landing_admin_page' ], 'dashicons-admin-generic', 70 );
    }

    public function landing_admin_page(){
        $slug = 'l1a_landing_page';

        if ( !current_user_can( 'manage_options' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'settings';
        }

        $link = 'admin.php?page='.$slug.'&tab=';

        ?>
        <div class="wrap">
            <h2><?php echo $this->title; ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'settings' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'settings' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Settings
                </a>
                <a href="<?php echo esc_attr( $link ) . 'ipstack' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'ipstack' ) ? 'nav-tab-active' : '' ); ?>">IpStack
                </a>

            </h2>

            <?php
            switch ($tab) {
                case "settings":
                    $this->settings();
                    break;
                case "ipstack":
                    $this->ipstack();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->
        <?php
    }

    public function settings(){
        $content = l1a_porch_fields();
        if ( isset( $_POST['l1a_landing_page'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['l1a_landing_page'] ) ), 'l1a_landing_page'.get_current_user_id() ) && isset( $_POST['list'] ) ) {
            $list = dt_recursive_sanitize_array( $_POST['list'] );
            foreach ( $list as $pk => $pv ) {
                if ( isset( $content[$pk] ) ) {
                    if ( 'textarea' === $content[$pk]['type'] ) {
                        $content[$pk]['value'] = wp_unslash( $_POST['list'][$pk] );
                    } else {
                        $content[$pk]['value'] = $pv;
                    }
                }
            }
            update_option( 'l1a_porch_fields', $content );
            $content = l1a_porch_fields();
        }
        ?>
        <style>
            .text-wide {
                width:100%;
            }
        </style>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <!-- Box -->
                        <form method="post">
                            <?php wp_nonce_field( 'l1a_landing_page'.get_current_user_id(), 'l1a_landing_page' ) ?>
                            <table class="widefat striped">
                                <thead>
                                <tr>
                                    <th colspan="2">Configurable Content</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                foreach( $content as $key => $item ) {
                                    if ( 'text' === $item['type'] ) {
                                        ?>
                                        <tr>
                                            <td style="width:150px;">
                                                <label for="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $item['label'] ) ?></label>
                                            </td>
                                            <td>
                                                <input type="text" name="list[<?php echo esc_attr( $key ) ?>]" id="<?php echo esc_attr( $key ) ?>" class="text-wide" value="<?php echo esc_html( $item['value'] ) ?>" />
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    else if ( 'textarea' === $item['type'] ) {
                                        ?>
                                        <tr>
                                            <td style="width:150px;">
                                                <label for="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $item['label'] ) ?></label>
                                            </td>
                                            <td>
                                                <textarea name="list[<?php echo esc_attr( $key ) ?>]" id="<?php echo esc_attr( $key ) ?>" class="text-wide"><?php echo $item['value'] ?></textarea>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    else if ( 'url' === $item['type'] ) {
                                        ?>
                                        <tr>
                                            <td style="width:150px;">
                                                <label for="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $item['label'] ) ?></label>
                                            </td>
                                            <td>
                                                <input type="text" name="list[<?php echo esc_attr( $key ) ?>]" id="<?php echo esc_attr( $key ) ?>" class="text-wide" value="<?php echo esc_html( $item['value'] ) ?>" />
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    else if ( 'select' === $item['type'] ) {
                                        ?>
                                        <tr>
                                            <td style="width:150px;">
                                                <label for="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $item['label'] ) ?></label>
                                            </td>
                                            <td>
                                                <select name="list[<?php echo esc_attr( $key ) ?>]" class="text-wide">
                                                    <?php if( ! empty( $item['value'] ) ) : ?>
                                                        <option value="<?php echo esc_html( $key ) ?>"><?php echo esc_html( $item['label'] ) ?></option>
                                                    <?php endif; ?>

                                                    <?php
                                                    foreach( $item['default'] as $k => $v ) {
                                                        ?>
                                                        <option value="<?php echo esc_attr( $k ) ?>"><?php echo esc_html( $v ) ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    else if ( 'break' === $item['type'] ) {
                                        ?>
                                        <tr>
                                            <td  colspan="2" style="background:darkgrey;">
                                                <span style="color:white;"><?php echo esc_html( $item['label'] ) ?></span>
                                            </td>
                                        </tr>
                                        <?php
                                    }

                                } // foreach

                                ?>
                                <tr>
                                    <td colspan="2">
                                        <button type="submit" class="button">Update</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                        </form>
                        <!-- End Box -->
                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function ipstack() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php DT_Ipstack_API::metabox_for_admin(); ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }
}
L1a_Porch_Admin::instance();
