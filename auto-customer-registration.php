<?php
/*
Plugin Name: Auto Customer Registration
Plugin URI: https://bitzspace.com
Description: Automatically registers customers during WooCommerce checkout and ensures a valid email is added to the contact info.
Version: 1.0
Author: Ajay
Author URI: https://bitzspace.com
License: GPL2
*/

// Hook into WooCommerce checkout order processed action
add_action( 'woocommerce_checkout_order_processed', 'ajay_register', 10, 1 );

function ajay_register( $order_id ) {
    // Get the order object
    $order = wc_get_order( $order_id );

    // Get the billing email
    $email = $order->get_billing_email();

    // Check if the email is empty and try to get it from other sources
    if ( empty( $email ) ) {
        // Attempt to get the shipping email (if applicable)
        $shipping_email = $order->get_shipping_email();
        if ( ! empty( $shipping_email ) ) {
            $email = $shipping_email;
        } else {
            // You can implement a function to get email from the payment method if needed
            // $payment_method_email = get_payment_method_email( $order->get_payment_method() );
            // if ( ! empty( $payment_method_email ) ) {
            //     $email = $payment_method_email;
            // }
        }
    }

    // If the email is still empty, set a default email address (optional)
    if ( empty( $email ) ) {
        $email = 'default@example.com'; // Set a default email address
    }

    // Check if the email or username already exists
    if ( ! email_exists( $email ) && ! username_exists( $email ) ) {
        // Create a new customer
        $customer_id = wc_create_new_customer( $email, '', '', array(
            'first_name' => sanitize_text_field( $order->get_billing_first_name() ),
            'last_name'  => sanitize_text_field( $order->get_billing_last_name() ),
        ));
        
        // Check for errors during customer creation
        if ( is_wp_error( $customer_id ) ) {
            error_log('Customer registration failed: ' . $customer_id->get_error_message());
            return; // Stop execution if user creation fails
        }

        // Link past orders to the new customer
        wc_update_new_customer_past_orders( $customer_id );

        // Log in the new customer by setting the authentication cookie
        wc_set_customer_auth_cookie( $customer_id );

    } else {
        // If the user already exists, get their ID
        $user = get_user_by( 'email', $email );
        
        // Link past orders to the existing customer
        wc_update_new_customer_past_orders( $user->ID );
    }
}