<?php
/**
 * API Downloads
 * @since 1.3.4
 */
?>

<?php global $woocommerce; ?>

<!-- checkout__download -->
<div class="checkout__info checkout__download">
    <h4 class="checkout__subtitle"><?php _e('API Downloads', 'woocommerce-api-manager'); ?></h4>
    <ul class="checkout__table">
        <?php
        if (!empty($user_id)) :
            $user_orders = WCAM()->helpers->get_users_data($user_id);
            if (!empty($user_orders)) :
                $dropbox_app_key = get_option('woocommerce_api_manager_dropbox_dropins_saver');
                ?>
                <li class="checkout__raw checkout__raw_head">
                    <span class="checkout__table-wide"><?php _e('Product', 'woocommerce-api-manager'); ?></span>
                    <span class="checkout__table-version"><?php _e('Version', 'woocommerce-api-manager'); ?></span>
                    <span class="checkout__table-date"><?php _e('Version Date', 'woocommerce-api-manager'); ?></span>
                    <span class="checkout__table-doc"><?php _e('Documentation', 'woocommerce-api-manager'); ?></span>
                    <span class="checkout__table-load"><?php _e('Download', 'woocommerce-api-manager'); ?></span>
                </li>
                <?php
                krsort($user_orders);
                foreach ($user_orders as $order_key => $data) :
                    /**
                     * Prepare the Subscription information
                     */
                    // Finds the post ID (integer) for a product even if it is a variable product
                    if ($data['is_variable_product'] == 'no') {
                        $post_id = $data['parent_product_id'];
                    } else {
                        $post_id = $data['variable_product_id'];
                    }
                    // Finds order ID that matches the license key. Order ID is the post_id in the post meta table
                    $order_id   = $data['order_id'];
                    // Finds the product ID, which can only be the parent ID for a product
                    $product_id = $data['parent_product_id'];
                    // Get the subscription status i.e. active
                    if (WCAM()->helpers->is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
                        $status = WCAM()->helpers->get_subscription_status($user_id, $post_id, $order_id, $product_id);
                    }
                    // End Subscription information prep
                    // Software Title
                    if ($data['is_variable_product'] == 'no') {
                        $software_title = $data['_api_software_title_parent'];
                    } else if ($data['is_variable_product'] == 'yes') {
                        $software_title = $data['_api_software_title_var'];
                    } else {
                        $software_title = $data['software_title'];
                    }
                    $download_id       = WCAM()->helpers->get_download_id($post_id);
                    $downloadable_data = WCAM()->helpers->get_downloadable_data($data['order_key'], $data['license_email'], $post_id, $download_id);
                    if (is_object($downloadable_data)) {
                        $downloads_remaining = $downloadable_data->downloads_remaining;
                        $download_count      = $downloadable_data->download_count;
                        $access_expires      = $downloadable_data->access_expires;
                    }
                    $download_count_set = WCAM()->helpers->get_download_count($order_id, $data['order_key']);
                    $download           = WCAM()->helpers->get_download_url($post_id);
                    // Check if there is download permission
                    if (!empty($status) && $status != 'active' && WCAM()->helpers->is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
                        $no_download = true;
                    } else if ($data['_api_update_permission'] != 'yes' || $downloads_remaining == '0' || empty($downloadable_data) || $download_count_set === false || $access_expires > 0 && strtotime($access_expires) < current_time('timestamp')) {
                        $no_download = false;
                    } else {
                        $no_download = false;
                    }
                    if ($no_download === false) :
                        ?>
                        <li class="checkout__raw">
                            <span class="checkout__table-wide">
                                <?php
                                $download_product = get_permalink($product_id);
                                if (!empty($download_product)) :
                                    ?>
                                    <?php echo esc_attr($software_title); ?>
                                    <?php
                                endif;
                                ?>
                            </span>
                            <span class="checkout__table-version">
                                <?php
                                $download_version = get_post_meta($post_id, '_api_new_version', true);
                                if (!empty($download_version)) :
                                    echo esc_attr($download_version);
                                endif;
                                ?>
                            </span>
                            <span class="checkout__table-date">
                                <?php
                                $version_date = get_post_meta($post_id, '_api_last_updated', true);
                                if (!empty($version_date)) :
                                    echo esc_attr(date_i18n($version_date));
                                else :
                                    esc_html_e('No Date', 'woocommerce-api-manager');
                                endif;
                                ?>
                            </span>
                            <span class="checkout__table-doc">
                                <?php
                                $faq = get_post_meta($post_id, '_api_faq', true);
                                if (!empty($faq)) :
                                    ?>
                                    <a href="<?php echo esc_url(get_permalink(absint($faq))); ?>?action=zendesk-login&amp;return_url=<?php echo esc_url(get_permalink(absint($faq))); ?>" target="_blank"><?php esc_html_e('FAQ', 'woocommerce-api-manager'); ?></a>
                                    <?php
                                endif;
                                ?>
                                <br><hr>
                                <?php
                                $documentation = get_post_meta($post_id, '_api_product_documentation', true);
                                if (!empty($documentation)) :
                                    ?>
                                    <a href="<?php echo esc_url(get_permalink(absint($documentation))); ?>?action=zendesk-login&amp;return_url=<?php echo esc_url(get_permalink(absint($documentation))); ?>" target="_blank"><?php esc_html_e('Documentation', 'woocommerce-api-manager'); ?></a>
                                    <?php
                                endif;
                                ?>
                            </span>
                            <span class="checkout__table-load">
                                <?php if (!empty($download) && WCAM()->helpers->find_amazon_s3_in_url($download) === true && $no_download === false) :
                                    ?>
                                    <a href="<?php echo esc_url(WCAM()->helpers->format_secure_s3_url($download)); ?>" target="_blank"><?php esc_html_e('Download', 'woocommerce-api-manager'); ?></a>
                                <?php elseif (!empty($download) && WCAM()->helpers->find_amazon_s3_in_url($download) === false && $no_download === false && date_create_from_format('Y-m-d H:i:s', $access_expires) > new DateTime('now')) :
                                    ?>
                                    <a href="<?php echo esc_url(WCAM()->helpers->create_url($data['order_key'], $data['license_email'], $post_id, $download_id, $user_id)); ?>" target="_blank"><?php esc_html_e('Download', 'woocommerce-api-manager'); ?></a>
                                    <br/><span>You have only <?php echo $downloads_remaining; ?> downloads available, make sure to keep a copy of files</span>
                                    <?php
                                    if (date_modify(date_create_from_format('Y-m-d H:i:s', $access_expires), '-1 month') <= new DateTime('now')) {
                                        printf('<br/><a href="%s">Renew with 25%% discount</a>', wp_nonce_url(sprintf('%s?order_key=%s&license_email=%s&product_id=%d', $woocommerce->cart->get_cart_url(), $order_key, $data['license_email'], $product_id), 'renew-key', 'renew-key'));
                                    }
                                    ?>
                                    <?php
                                else :
                                    esc_html_e('Disabled', 'woocommerce-api-manager');
                                    printf('<br/><a href="%s">Renew with 25%% discount</a>', wp_nonce_url(sprintf('%s?order_key=%s&license_email=%s&product_id=%d', $woocommerce->cart->get_cart_url(), $order_key, $data['license_email'], $product_id), 'renew-key', 'renew-key'));
                                endif;
                                ?>
                                <?php /* <br><hr>
                                  <?php
                                  if ( ! empty( $dropbox_app_key ) && ! empty( $download ) && WCAM()->helpers->find_amazon_s3_in_url( $download ) === true && $no_download === false ) :
                                  ?>
                                  <a href="<?php echo esc_url( WCAM()->helpers->format_secure_s3_url( $download ) ); ?>" class="dropbox-saver nobr"></a>
                                  <?php
                                  elseif ( ! empty( $dropbox_app_key ) && ! empty( $download ) && WCAM()->helpers->find_amazon_s3_in_url( $download ) === false && $no_download === false ) : ?>
                                  <a href="<?php echo esc_url( WCAM()->helpers->create_url( $data['order_key'], $data['license_email'], $post_id, $download_id, $user_id ) ); ?>" class="dropbox-saver nobr"></a>
                                  <?php
                                  elseif ( empty( $dropbox_app_key ) ) :
                                  echo '&nbsp;';
                                  else :
                                  esc_html_e( 'Disabled', 'woocommerce-api-manager' );
                                  endif;
                                  ?> */ ?>
                            </span>
                        </li>
                    <?php else :
                        ?>
                        <li class="checkout__raw checkout__raw_noLoad">
                            <span class="checkout__table-noLoad"><?php _e('You have no downloads.', 'woocommerce-api-manager'); ?></span>
                        </li>
                    <?php
                    endif; // end if download disabled
                endforeach; // end user_orders
                /**
                 * Javascript
                 */
                if (get_option('woocommerce_api_manager_remove_all_download_links') == 'yes') :
                    ob_start();
                    ?>
                    jQuery('h2:contains("Available downloads")').css('display', 'none');
                    jQuery('.digital-downloads').css('display', 'none');
                    <?php
                    $javascript = ob_get_clean();
                    WCAM()->wc_print_js($javascript);
                endif; // end if remove
                if (!empty($dropbox_app_key)) :
                    ?>
                    <script type="text/javascript" src="https://www.dropbox.com/static/api/2/dropins.js" id="dropboxjs" data-app-key="<?php esc_attr_e($dropbox_app_key) ?>"></script>
                <?php endif; ?>
            <?php else : ?>
                <li class="checkout__raw checkout__raw_noLoad">
                    <span class="checkout__table-noLoad"><?php _e('You have no downloads.', 'woocommerce-api-manager'); ?></span>
                </li>
            <?php
            endif; // end if user_orders
        endif; // end if user_id
        ?>
    </ul>
</div>
<!-- /checkout__download -->