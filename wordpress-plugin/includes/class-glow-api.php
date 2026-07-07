<?php

class Glow_API extends WP_REST_Controller {

    protected $namespace = 'glow/v1';

    public function register_routes() {
        $namespace = $this->namespace;
        $check_user = [$this, 'check_user_logged_in'];
        $check_public = [$this, 'check_public_access'];
        $readable = WP_REST_Server::READABLE;
        $creatable = WP_REST_Server::CREATABLE;
        $routes = [
            '/user' => [
                'methods'             => $readable,
                'callback'            => [$this, 'get_user'],
                'permission_callback' => $check_user,
            ],
            '/feed' => [
                'methods'             => $readable,
                'callback'            => [$this, 'get_feed'],
                'permission_callback' => $check_public,
            ],
            '/submit' => [
                'methods'             => $creatable,
                'callback'            => [$this, 'submit_post'],
                'permission_callback' => $check_user,
            ],
            '/boost' => [
                'methods'             => $creatable,
                'callback'            => [$this, 'boost_post'],
                'permission_callback' => $check_user,
            ],
            '/stripe/checkout' => [
                'methods'             => $creatable,
                'callback'            => [$this, 'stripe_checkout'],
                'permission_callback' => $check_user,
            ],
            '/stripe/webhook' => [
                'methods'             => $creatable,
                'callback'            => [$this, 'stripe_webhook'],
                'permission_callback' => $check_public,
            ],
        ];

        foreach ($routes as $route => $args) {
            register_rest_route($namespace, $route, [
                'methods'             => $args['methods'],
                'callback'            => $args['callback'],
                'permission_callback' => $args['permission_callback'],
            ]);
        }
    }

    public function check_user_logged_in() {
        $user_id = get_current_user_id();
        $is_logged_in = $user_id !== 0;
        return $is_logged_in;
    }

    public function check_public_access() {
        return true;
    }

    public function get_user($request) {
        $user_id = get_current_user_id();
        $is_invalid_user = $user_id === 0;
        if ($is_invalid_user) {
            return new WP_Error('glow_unauthorized', 'User not logged in', ['status' => 401]);
        }

        $stats = Glow_DB::get_user_stats($user_id);
        $has_no_stats = $stats === null;
        if ($has_no_stats) {
            return new WP_Error('glow_db_error', 'Unable to fetch user stats', ['status' => 500]);
        }

        $userdata = get_userdata($user_id);
        $username = $userdata ? $userdata->user_login : '';

        $response_data = [
            'user_id'          => $stats['user_id'],
            'username'         => $username,
            'droplet_balance'  => $stats['droplet_balance'],
            'driplet_balance'  => $stats['driplet_balance'],
            'depth_multiplier' => $stats['depth_multiplier'],
        ];

        return new WP_REST_Response($response_data, 200);
    }

    public function get_feed($request) {
        global $wpdb;

        $page_param = $request->get_param('page');
        $page_int = (int) $page_param;
        $is_invalid_page = $page_int < 1;
        $page = $is_invalid_page ? 1 : $page_int;

        $tributary = $request->get_param('tributary');
        $table_posts = Glow_DB::get_table_name('posts');

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $has_tributary = !empty($tributary);
        if ($has_tributary) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_posts WHERE tributary = %s ORDER BY glow_score DESC, created_at DESC LIMIT %d OFFSET %d",
                $tributary,
                $limit,
                $offset
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_posts ORDER BY glow_score DESC, created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }

        $posts = $wpdb->get_results($query, ARRAY_A);
        $has_posts = !empty($posts);

        if (!$has_posts) {
            return new WP_REST_Response([], 200);
        }

        $grouped_posts = [];
        foreach ($posts as $post) {
            $is_empty_group = empty($grouped_posts);
            if ($is_empty_group) {
                $grouped_posts[] = $post;
                continue;
            }

            $last_index = count($grouped_posts) - 1;
            $prev_post = $grouped_posts[$last_index];

            $is_both_thoughts = $post['type'] === 'thought' && $prev_post['type'] === 'thought';
            $is_same_user = (int) $post['user_id'] === (int) $prev_post['user_id'];
            $is_consecutive_thought = $is_both_thoughts && $is_same_user;

            if ($is_consecutive_thought) {
                $grouped_posts[$last_index]['content'] .= ' | ' . $post['content'];
                $grouped_posts[$last_index]['passenger_count'] = (int) $prev_post['passenger_count'] + (int) $post['passenger_count'];
                $grouped_posts[$last_index]['glow_score'] = (int) $prev_post['glow_score'] + (int) $post['glow_score'];
            } else {
                $grouped_posts[] = $post;
            }
        }

        return new WP_REST_Response($grouped_posts, 200);
    }

    public function submit_post($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $is_invalid_user = $user_id === 0;
        if ($is_invalid_user) {
            return new WP_Error('glow_unauthorized', 'User not logged in', ['status' => 401]);
        }

        $params = $request->get_json_params();
        $type = isset($params['type']) ? $params['type'] : '';
        $content = isset($params['content']) ? trim($params['content']) : '';
        $link = isset($params['link']) ? $params['link'] : null;
        $title = isset($params['title']) ? $params['title'] : null;
        $tributary = isset($params['tributary']) ? $params['tributary'] : null;

        $is_empty_content = empty($content);
        if ($is_empty_content) {
            return new WP_Error('glow_invalid_data', 'Content cannot be empty', ['status' => 400]);
        }

        $is_drop = $type === 'drop';
        $is_thought = $type === 'thought';
        $is_invalid_type = !$is_drop && !$is_thought;
        if ($is_invalid_type) {
            return new WP_Error('glow_invalid_type', 'Invalid post type', ['status' => 400]);
        }

        $stats = Glow_DB::get_user_stats($user_id);
        $has_no_stats = $stats === null;
        if ($has_no_stats) {
            return new WP_Error('glow_db_error', 'User stats not found', ['status' => 500]);
        }

        $cost = 10;
        if ($is_drop) {
            $has_insufficient_droplets = $stats['droplet_balance'] < $cost;
            if ($has_insufficient_droplets) {
                return new WP_Error('glow_insufficient_balance', 'Insufficient droplet balance', ['status' => 400]);
            }
        }

        $txn_started = $wpdb->query('START TRANSACTION');
        $has_txn_failed = $txn_started === false;
        if ($has_txn_failed) {
            return new WP_Error('glow_db_error', 'Failed to start transaction', ['status' => 500]);
        }

        try {
            if ($is_drop) {
                $new_droplets = $stats['droplet_balance'] - $cost;
                $table_stats = Glow_DB::get_table_name('user_stats');
                $updated_stats = $wpdb->update(
                    $table_stats,
                    ['droplet_balance' => $new_droplets],
                    ['user_id' => $user_id],
                    ['%d'],
                    ['%s']
                );

                $has_update_failed = $updated_stats === false;
                if ($has_update_failed) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('glow_db_error', 'Failed to update balance', ['status' => 500]);
                }

                $logged_tx = Glow_DB::log_transaction($user_id, -$cost, 'droplet', 'drop_cost', 'Link submission cost');
                $has_log_failed = !$logged_tx;
                if ($has_log_failed) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('glow_db_error', 'Failed to log transaction', ['status' => 500]);
                }
            }

            $table_posts = Glow_DB::get_table_name('posts');
            $post_data = [
                'user_id'         => $user_id,
                'type'            => $type,
                'content'         => $content,
                'link'            => $link,
                'title'           => $title,
                'tributary'       => $tributary,
                'passenger_count' => 1,
                'glow_score'      => 0,
                'created_at'      => current_time('mysql'),
            ];

            $inserted_post = $wpdb->insert(
                $table_posts,
                $post_data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
            );

            $has_insert_failed = $inserted_post === false;
            if ($has_insert_failed) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('glow_db_error', 'Failed to insert post', ['status' => 500]);
            }

            $post_id = $wpdb->insert_id;
            $wpdb->query('COMMIT');

            $latest_stats = Glow_DB::get_user_stats($user_id);
            $response_data = [
                'success' => true,
                'post_id' => $post_id,
                'balance' => [
                    'user_id'          => $latest_stats['user_id'],
                    'droplet_balance'  => $latest_stats['droplet_balance'],
                    'driplet_balance'  => $latest_stats['driplet_balance'],
                    'depth_multiplier' => $latest_stats['depth_multiplier'],
                ],
            ];

            return new WP_REST_Response($response_data, 200);

        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('glow_db_error', $e->getMessage(), ['status' => 500]);
        }
    }

    public function boost_post($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $is_invalid_user = $user_id === 0;
        if ($is_invalid_user) {
            return new WP_Error('glow_unauthorized', 'User not logged in', ['status' => 401]);
        }

        $params = $request->get_json_params();
        $post_id = isset($params['post_id']) ? (int) $params['post_id'] : 0;

        $is_invalid_post_id = $post_id <= 0;
        if ($is_invalid_post_id) {
            return new WP_Error('glow_invalid_data', 'Invalid post ID', ['status' => 400]);
        }

        $table_posts = Glow_DB::get_table_name('posts');
        $query_post = $wpdb->prepare("SELECT * FROM $table_posts WHERE id = %d", $post_id);
        $post = $wpdb->get_row($query_post, ARRAY_A);

        $post_does_not_exist = $post === null;
        if ($post_does_not_exist) {
            return new WP_Error('glow_post_not_found', 'Post not found', ['status' => 404]);
        }

        $stats = Glow_DB::get_user_stats($user_id);
        $has_no_stats = $stats === null;
        if ($has_no_stats) {
            return new WP_Error('glow_db_error', 'User stats not found', ['status' => 500]);
        }

        $txn_started = $wpdb->query('START TRANSACTION');
        $has_txn_failed = $txn_started === false;
        if ($has_txn_failed) {
            return new WP_Error('glow_db_error', 'Failed to start transaction', ['status' => 500]);
        }

        try {
            $new_glow_score = (int) $post['glow_score'] + 1;
            $updated_post = $wpdb->update(
                $table_posts,
                ['glow_score' => $new_glow_score],
                ['id' => $post_id],
                ['%d'],
                ['%d']
            );

            $has_update_failed = $updated_post === false;
            if ($has_update_failed) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('glow_db_error', 'Failed to update post glow score', ['status' => 500]);
            }

            $reward_driplets = 15;
            $total_driplets = $stats['driplet_balance'] + $reward_driplets;
            $additional_droplets = floor($total_driplets / 100);
            $remaining_driplets = $total_driplets % 100;

            $new_droplets = $stats['droplet_balance'] + $additional_droplets;
            $new_driplets = $remaining_driplets;

            $table_stats = Glow_DB::get_table_name('user_stats');
            $updated_stats = $wpdb->update(
                $table_stats,
                [
                    'droplet_balance' => $new_droplets,
                    'driplet_balance' => $new_driplets,
                ],
                ['user_id' => $user_id],
                ['%d', '%d'],
                ['%s']
            );

            $has_stats_update_failed = $updated_stats === false;
            if ($has_stats_update_failed) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('glow_db_error', 'Failed to update user balance', ['status' => 500]);
            }

            $logged_tx = Glow_DB::log_transaction($user_id, $reward_driplets, 'driplet', 'boost_reward', "Boosted post $post_id");
            $has_log_failed = !$logged_tx;
            if ($has_log_failed) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('glow_db_error', 'Failed to log transaction', ['status' => 500]);
            }

            $wpdb->query('COMMIT');

            $response_data = [
                'success'         => true,
                'earned_driplets' => $reward_driplets,
                'user_balance'    => [
                    'user_id'          => $user_id,
                    'droplet_balance'  => $new_droplets,
                    'driplet_balance'  => $new_driplets,
                    'depth_multiplier' => $stats['depth_multiplier'],
                ],
                'new_glow_score'  => $new_glow_score,
            ];

            return new WP_REST_Response($response_data, 200);

        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('glow_db_error', $e->getMessage(), ['status' => 500]);
        }
    }

    public function stripe_checkout($request) {
        $user_id = get_current_user_id();
        $is_invalid_user = $user_id === 0;
        if ($is_invalid_user) {
            return new WP_Error('glow_unauthorized', 'User not logged in', ['status' => 401]);
        }

        $params = $request->get_json_params();
        $pack_id = isset($params['pack_id']) ? $params['pack_id'] : '';
        $coupon_raw = isset($params['coupon']) ? $params['coupon'] : '';

        $packages = Glow_Stripe::PACKAGES;
        $is_invalid_pack = !array_key_exists($pack_id, $packages);
        if ($is_invalid_pack) {
            return new WP_Error('glow_invalid_pack', 'Invalid package selected', ['status' => 400]);
        }

        $package = $packages[$pack_id];
        $original_price_cents = $package['price_cents'];
        $discounted_price_cents = $original_price_cents;
        $discount_percent = 0;

        $coupon_code = strtoupper(trim($coupon_raw));
        $has_coupon = !empty($coupon_code);
        if ($has_coupon) {
            $coupon_upper = $coupon_code;
            $coupons = Glow_Stripe::COUPONS;
            $is_valid_coupon = array_key_exists($coupon_upper, $coupons);
            if (!$is_valid_coupon) {
                return new WP_Error('glow_invalid_coupon', 'Coupon is not valid', ['status' => 400]);
            }

            global $wpdb;
            $table_tx = Glow_DB::get_table_name('transactions');
            $coupon_search = '%[coupon: ' . $coupon_upper . ']%';
            $query_coupon_used = $wpdb->prepare(
                "SELECT id FROM $table_tx WHERE user_id = %d AND details LIKE %s",
                $user_id,
                $coupon_search
            );
            $coupon_used_id = $wpdb->get_var($query_coupon_used);
            if ($coupon_used_id !== null) {
                return new WP_Error('glow_coupon_already_used', 'Coupon has already been used', ['status' => 400]);
            }

            $discount_percent = $coupons[$coupon_upper]['discount_percent'];
            $discount_amount_cents = ($original_price_cents * $discount_percent) / 100;
            $discounted_price_cents = $original_price_cents - $discount_amount_cents;
        }

        $success_url = isset($params['success_url']) ? $params['success_url'] : '';
        $cancel_url = isset($params['cancel_url']) ? $params['cancel_url'] : '';
        $is_valid_success = !empty($success_url) && filter_var($success_url, FILTER_VALIDATE_URL);
        $is_valid_cancel = !empty($cancel_url) && filter_var($cancel_url, FILTER_VALIDATE_URL);

        if (!$is_valid_success || !$is_valid_cancel) {
            return new WP_Error('glow_invalid_url', 'Invalid redirect URLs', ['status' => 400]);
        }

        $home_host = parse_url(home_url(), PHP_URL_HOST);
        $success_host = parse_url($success_url, PHP_URL_HOST);
        $cancel_host = parse_url($cancel_url, PHP_URL_HOST);

        $is_matching_success = !empty($success_host) && strcasecmp($success_host, $home_host) === 0;
        $is_matching_cancel = !empty($cancel_host) && strcasecmp($cancel_host, $home_host) === 0;

        if (!$is_matching_success || !$is_matching_cancel) {
            return new WP_Error('glow_invalid_url', 'Invalid redirect URLs', ['status' => 400]);
        }

        $is_free_purchase = $discounted_price_cents <= 0;
        if ($is_free_purchase) {
            global $wpdb;
            $transaction_started = $wpdb->query('START TRANSACTION');
            $is_txn_failed = $transaction_started === false;
            if ($is_txn_failed) {
                return new WP_Error('glow_db_error', 'Transaction initialization failed', ['status' => 500]);
            }

            try {
                $stats = Glow_DB::get_user_stats($user_id);
                $is_stats_empty = $stats === null;
                if ($is_stats_empty) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('glow_db_error', 'User stats could not be retrieved', ['status' => 500]);
                }

                $new_droplets = $stats['droplet_balance'] + $package['droplets'];
                $table_stats = Glow_DB::get_table_name('user_stats');
                $updated_stats = $wpdb->update(
                    $table_stats,
                    ['droplet_balance' => $new_droplets],
                    ['user_id' => $user_id],
                    ['%d'],
                    ['%s']
                );

                $is_update_failed = $updated_stats === false;
                if ($is_update_failed) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('glow_db_error', 'Fulfillment database update failed', ['status' => 500]);
                }

                $transaction_details = 'Free droplet pack: ' . $pack_id;
                if ($has_coupon) {
                    $transaction_details .= ' with [coupon: ' . $coupon_code . ']';
                }
                $logged_transaction = Glow_DB::log_transaction(
                    $user_id,
                    $package['droplets'],
                    'droplet',
                    'purchase',
                    $transaction_details
                );

                $is_log_failed = !$logged_transaction;
                if ($is_log_failed) {
                    $wpdb->query('ROLLBACK');
                    return new WP_Error('glow_db_error', 'Failed to log free purchase transaction', ['status' => 500]);
                }

                $wpdb->query('COMMIT');

                $checkout_url = 'https://checkout.stripe.com/mock-session-free-' . $pack_id;
                if (!empty($success_url)) {
                    $checkout_url = add_query_arg('success', 'true', $success_url);
                }

                $response_data = [
                    'success' => true,
                    'checkout_url' => $checkout_url,
                ];
                return new WP_REST_Response($response_data, 200);

            } catch (\Throwable $exception) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('glow_db_error', $exception->getMessage(), ['status' => 500]);
            }
        }

        $stripe_secret = defined('GLOW_STRIPE_SECRET_KEY') ? GLOW_STRIPE_SECRET_KEY : '';
        $is_secret_empty = empty($stripe_secret);
        if ($is_secret_empty) {
            $checkout_url = 'https://checkout.stripe.com/mock-session-' . $pack_id;
            if ($has_coupon) {
                $checkout_url .= '-coupon-' . strtolower($coupon_code);
            }
            $response_data = [
                'success' => true,
                'checkout_url' => $checkout_url,
            ];
            return new WP_REST_Response($response_data, 200);
        }

        $stripe_api_endpoint = 'https://api.stripe.com/v1/checkout/sessions';
        $request_payload = [
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'line_items[0][price_data][currency]' => 'usd',
            'line_items[0][price_data][product_data][name]' => $package['name'],
            'line_items[0][price_data][unit_amount]' => $discounted_price_cents,
            'line_items[0][quantity]' => 1,
            'metadata[user_id]' => $user_id,
            'metadata[pack_id]' => $pack_id,
            'metadata[droplets]' => $package['droplets'],
            'metadata[coupon]' => $coupon_code,
        ];

        $http_arguments = [
            'headers' => [
                'Authorization' => 'Bearer ' . $stripe_secret,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($request_payload),
        ];

        $api_response = wp_remote_post($stripe_api_endpoint, $http_arguments);
        $is_http_error = is_wp_error($api_response);
        if ($is_http_error) {
            return new WP_Error('glow_stripe_api_error', $api_response->get_error_message(), ['status' => 500]);
        }

        $status_code = wp_remote_retrieve_response_code($api_response);
        $response_body = wp_remote_retrieve_body($api_response);
        $parsed_data = json_decode($response_body, true);

        $is_unsuccessful = $status_code !== 200 || empty($parsed_data['url']);
        if ($is_unsuccessful) {
            $error_message = isset($parsed_data['error']['message']) ? $parsed_data['error']['message'] : 'Stripe checkout session creation failed';
            return new WP_Error('glow_stripe_api_error', $error_message, ['status' => 500]);
        }

        $response_data = [
            'success' => true,
            'checkout_url' => $parsed_data['url'],
        ];
        return new WP_REST_Response($response_data, 200);
    }

    public function stripe_webhook($request) {
        $sig_header = $request->get_header('stripe-signature');
        $is_empty_sig = empty($sig_header);
        if ($is_empty_sig) {
            return new WP_Error('glow_invalid_signature', 'Invalid signature', ['status' => 400]);
        }

        $raw_body = $request->get_body();
        $webhook_secret = defined('GLOW_STRIPE_WEBHOOK_SECRET') ? GLOW_STRIPE_WEBHOOK_SECRET : '';
        $is_secret_empty = empty($webhook_secret);
        if ($is_secret_empty) {
            $webhook_secret = get_option('glow_stripe_webhook_secret');
        }

        $is_verified = Glow_Stripe::verify_stripe_signature($raw_body, $sig_header, $webhook_secret);
        if (!$is_verified) {
            return new WP_Error('glow_invalid_signature', 'Invalid signature', ['status' => 400]);
        }

        $event = json_decode($raw_body, true);
        $is_invalid_json = json_last_error() !== JSON_ERROR_NONE;
        if ($is_invalid_json) {
            return new WP_Error('glow_invalid_payload', 'Invalid payload', ['status' => 400]);
        }

        $event_type = isset($event['type']) ? $event['type'] : '';
        $is_checkout_complete = $event_type === 'checkout.session.completed';
        if ($is_checkout_complete) {
            $session = $event['data']['object'];
            $payment_status = isset($session['payment_status']) ? $session['payment_status'] : 'paid';
            $is_paid = $payment_status === 'paid';
            if (!$is_paid) {
                return new WP_Error('glow_unpaid_session', 'Session is not paid', ['status' => 400]);
            }

            $session_id = isset($session['id']) ? $session['id'] : '';
            $lock_key = '';
            if (!empty($session_id)) {
                $lock_key = 'glow_stripe_lock_' . md5($session_id);
                $is_locked = get_transient($lock_key);
                if ($is_locked) {
                    return new WP_REST_Response(['success' => true, 'already_processed' => true], 200);
                }
                set_transient($lock_key, '1', 60);
            }

            if (!empty($session_id)) {
                global $wpdb;
                $table_tx = Glow_DB::get_table_name('transactions');
                $session_like = '%' . $wpdb->esc_like($session_id) . '%';
                $query_tx = $wpdb->prepare("SELECT id FROM $table_tx WHERE details LIKE %s", $session_like);
                $existing_tx_id = $wpdb->get_var($query_tx);
                if ($existing_tx_id !== null) {
                    $response_data = [
                        'success' => true,
                        'already_processed' => true,
                    ];
                    return new WP_REST_Response($response_data, 200);
                }
            }

            $metadata = isset($session['metadata']) ? $session['metadata'] : [];
            $user_id = isset($metadata['user_id']) ? (int) $metadata['user_id'] : 0;
            $pack_id = isset($metadata['pack_id']) ? $metadata['pack_id'] : '';
            $coupon_raw = isset($metadata['coupon']) ? $metadata['coupon'] : '';

            $coupon_upper = strtoupper(trim($coupon_raw));
            $has_coupon = !empty($coupon_upper);

            $is_invalid_metadata = $user_id <= 0 || empty($pack_id);
            if ($is_invalid_metadata) {
                if (!empty($lock_key)) {
                    delete_transient($lock_key);
                }
                return new WP_Error('glow_invalid_metadata', 'Required metadata missing from session', ['status' => 400]);
            }

            $packages = Glow_Stripe::PACKAGES;
            $is_invalid_pack = !array_key_exists($pack_id, $packages);
            if ($is_invalid_pack) {
                if (!empty($lock_key)) {
                    delete_transient($lock_key);
                }
                return new WP_Error('glow_invalid_pack', 'Invalid package in metadata', ['status' => 400]);
            }

            if ($has_coupon) {
                global $wpdb;
                $table_tx = Glow_DB::get_table_name('transactions');
                $coupon_search = '%[coupon: ' . $coupon_upper . ']%';
                $query_coupon_used = $wpdb->prepare(
                    "SELECT id FROM $table_tx WHERE user_id = %d AND details LIKE %s",
                    $user_id,
                    $coupon_search
                );
                $coupon_used_id = $wpdb->get_var($query_coupon_used);
                if ($coupon_used_id !== null) {
                    if (!empty($lock_key)) {
                        delete_transient($lock_key);
                    }
                    return new WP_Error('glow_coupon_already_used', 'Coupon has already been used', ['status' => 400]);
                }
            }

            $package = $packages[$pack_id];
            $droplet_amount = $package['droplets'];

            global $wpdb;
            $transaction_started = $wpdb->query('START TRANSACTION');
            $is_txn_failed = $transaction_started === false;
            if ($is_txn_failed) {
                if (!empty($lock_key)) {
                    delete_transient($lock_key);
                }
                return new WP_Error('glow_db_error', 'Transaction initialization failed', ['status' => 500]);
            }

            try {
                $stats = Glow_DB::get_user_stats($user_id);
                $is_stats_empty = $stats === null;
                if ($is_stats_empty) {
                    $wpdb->query('ROLLBACK');
                    if (!empty($lock_key)) {
                        delete_transient($lock_key);
                    }
                    return new WP_Error('glow_user_not_found', 'User stats not found', ['status' => 404]);
                }

                $new_droplets = $stats['droplet_balance'] + $droplet_amount;
                $table_stats = Glow_DB::get_table_name('user_stats');
                $updated_stats = $wpdb->update(
                    $table_stats,
                    ['droplet_balance' => $new_droplets],
                    ['user_id' => $user_id],
                    ['%d'],
                    ['%s']
                );

                $is_update_failed = $updated_stats === false;
                if ($is_update_failed) {
                    $wpdb->query('ROLLBACK');
                    if (!empty($lock_key)) {
                        delete_transient($lock_key);
                    }
                    return new WP_Error('glow_db_error', 'Fulfillment database update failed', ['status' => 500]);
                }

                $transaction_details = 'Stripe Session ID: ' . $session_id . ' | Purchased pack: ' . $pack_id . ($has_coupon ? ' with [coupon: ' . $coupon_upper . ']' : '');

                $logged_transaction = Glow_DB::log_transaction(
                    $user_id,
                    $droplet_amount,
                    'droplet',
                    'purchase',
                    $transaction_details
                );

                $is_log_failed = !$logged_transaction;
                if ($is_log_failed) {
                    $wpdb->query('ROLLBACK');
                    if (!empty($lock_key)) {
                        delete_transient($lock_key);
                    }
                    return new WP_Error('glow_db_error', 'Fulfillment transaction logging failed', ['status' => 500]);
                }

                $wpdb->query('COMMIT');

            } catch (\Throwable $exception) {
                $wpdb->query('ROLLBACK');
                if (!empty($lock_key)) {
                    delete_transient($lock_key);
                }
                return new WP_Error('glow_db_error', $exception->getMessage(), ['status' => 500]);
            }
        }

        $response_data = [
            'success' => true,
        ];
        return new WP_REST_Response($response_data, 200);
    }
}

