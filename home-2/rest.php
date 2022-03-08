<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Lead form REST API for saving to Disciple.Tools
 *
 * @version 1
 */

if ( ! class_exists( 'L1a_Porch_Lead_Form' ) ) {
    class L1a_Porch_Lead_Form {

        private static $_instance = null;
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct() {
            if ( ! defined( 'PORCH_ROOT' ) || ! defined( 'PORCH_TYPE' ) ) {
                dt_write_log( 'You must define PORCH_ROOT and PORCH_TYPE in the loader.php file' );
                return;
            }
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        }

        public function add_api_routes() {
            $namespace = PORCH_ROOT . '/v1';
            register_rest_route(
                $namespace, '/' . PORCH_TYPE, [
                    [
                        'methods'  => "POST",
                        'callback' => [ $this, 'endpoint' ],
                        'permission_callback' => '__return_true',
                    ],
                ]
            );
        }

        public function endpoint( WP_REST_Request $request ) {
            $params = $request->get_params();

            if ( ! isset( $params['parts'], $params['action'] ) ) {
                return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
            }

            $params = dt_recursive_sanitize_array( $params );
            $action = sanitize_text_field( wp_unslash( $params['action'] ) );

            switch ( $action ) {
                case 'followup':
                    return $this->save_contact_lead( $params['data'] );

                default:
                    return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
            }
        }

        public function save_contact_lead( $data ) {
            $content = get_option( 'landing_content' );
            $fields = [];

            $data = dt_recursive_sanitize_array( $data );
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $name = $data['name'] ?? '';
            $location = $data['location'] ?? '';
            $comment = $data['comment'] ?? '';

            if ( empty( $email ) && empty( $phone ) ){
                return new WP_Error( __METHOD__, 'Must have either phone number or email address to create record.', [ 'status' => 400 ] );
            }

            if ( ! empty( $lname ) ) {
                $full_name = $name . ' ' . $lname;
            } else {
                $full_name = $name;
            }

            $fields['title'] = $full_name;
            if ( ! empty( $email ) ) {
                $fields['contact_email'] = [
                    [ "value" => $email ]
                ];
            }
            if ( ! empty( $phone ) ) {
                $fields['contact_phone'] = [
                    [ "value" => $phone ]
                ];
            }
            $fields['type'] = 'access';

            if ( isset( $content['assigned_user_for_followup'] ) && ! empty( $content['assigned_user_for_followup'] ) ) {
                $fields['assigned_to'] = $content['assigned_user_for_followup'];
            }

            if ( isset( $content['status_for_subscriptions'] ) && ! empty( $content['status_for_subscriptions'] ) ) {
                $fields['overall_status'] = $content['status_for_subscriptions'];
            }

            if ( isset( $content['source_for_subscriptions'] ) && ! empty( $content['source_for_subscriptions'] ) ) {
                $fields['sources'] = [
                    "values" => [
                        [ "value" => $content['source_for_subscriptions'] ],
                    ]
                ];
            }

            $ip_address = '';
            if ( class_exists( 'DT_Ipstack_API' ) && ! empty( DT_Ipstack_API::get_key() ) ) {
                $ip_result = DT_Ipstack_API::geocode_current_visitor();
                $ip_address = DT_Ipstack_API::get_real_ip_address();
                if ( ! empty( $ip_result ) ) {
                    $fields['location_grid_meta'] = [
                        'values' => [
                            [
                                'lng' => DT_Ipstack_API::parse_raw_result( $ip_result, 'lng' ),
                                'lat' => DT_Ipstack_API::parse_raw_result( $ip_result, 'lat' )
                            ]
                        ]
                    ];
                }
            }

            // geolocate IP address
            $fields['notes'] = [];
            $fields['notes'][] = $comment;
            $fields['notes'][] = $location;
            if ( $ip_address ) {
                $fields['notes'][] = $ip_address;
            }

            $contact = DT_Posts::create_post( 'contacts', $fields, true, false );
            if ( is_wp_error( $contact ) ) {
                return false;
            } else {
                return true;
            }
        }
    }
    L1a_Porch_Lead_Form::instance();
}
