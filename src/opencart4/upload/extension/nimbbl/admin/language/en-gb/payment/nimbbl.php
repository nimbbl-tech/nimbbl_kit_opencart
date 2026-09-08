<?php
// Heading
$_['heading_title'] = 'Nimbbl Payment Gateway';

// Text
$_['text_extension']    = 'Extensions';
$_['text_success']      = 'Success: You have modified the Nimbbl payment gateway settings!';
$_['text_edit']         = 'Edit Nimbbl Payment Gateway';
$_['text_enabled']      = 'Enabled';
$_['text_disabled']     = 'Disabled';
$_['text_test']         = 'Test';
$_['text_live']         = 'Live';
$_['text_yes']          = 'Yes';
$_['text_no']           = 'No';
$_['text_all_zones']    = 'All Zones';
$_['text_none']         = '--- None ---';

// Tab labels
$_['tab_general']       = 'General';
$_['tab_api_keys']      = 'API Keys';
$_['tab_advanced']      = 'Advanced';
$_['tab_webhooks']      = 'Webhooks';

// Entry — General
$_['entry_status']         = 'Status';
$_['entry_title']          = 'Title';
$_['entry_mode']           = 'Mode';
$_['entry_geo_zone']       = 'Geo Zone';
$_['entry_total']          = 'Minimum Order Total';
$_['entry_sort_order']     = 'Sort Order';

// Entry — API Keys
$_['entry_test_access_key']  = 'Test Access Key';
$_['entry_test_secret_key']  = 'Test Secret Key';
$_['entry_live_access_key']  = 'Live Access Key';
$_['entry_live_secret_key']  = 'Live Secret Key';
$_['entry_api_url']          = 'API Base URL';
$_['entry_checkout_host']    = 'Checkout Host (Sonic)';

// Entry — Advanced
$_['entry_order_status']       = 'Successful Payment Status';
$_['entry_order_fail_status']  = 'Failed Payment Status';
$_['entry_debug']              = 'Debug Logging';
$_['entry_encrypt_payload']    = 'Encrypt Payload';

// Entry — Webhooks
$_['entry_webhook_url'] = 'Webhook URL (copy to Nimbbl dashboard)';

// Help text
$_['help_mode']              = 'Use Test mode during development. Switch to Live for production.';
$_['help_total']             = 'Minimum order total (in store currency) to show this payment method. Set 0 to disable.';
$_['help_api_url']           = 'Default: https://api.nimbbl.tech — change only for QA/staging.';
$_['help_checkout_host']     = 'Default: https://sonic.nimbbl.tech — the Sonic checkout URL.';
$_['help_debug']             = 'Writes detailed request/response logs to system/storage/logs/nimbbl.log.';
$_['help_encrypt_payload']   = 'Encrypt order payload sent to Nimbbl API.';
$_['help_webhook_url']       = 'Configure this URL in your Nimbbl dashboard to receive async payment notifications.';

// Error
$_['error_permission']        = 'Warning: You do not have permission to modify the Nimbbl payment gateway!';
$_['error_test_access_key']   = 'Test Access Key is required when mode is Test.';
$_['error_test_secret_key']   = 'Test Secret Key is required when mode is Test.';
$_['error_live_access_key']   = 'Live Access Key is required when mode is Live.';
$_['error_live_secret_key']   = 'Live Secret Key is required when mode is Live.';

// Button
$_['button_save']   = 'Save';
$_['button_cancel'] = 'Cancel';
