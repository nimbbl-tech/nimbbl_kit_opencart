<?php

namespace Opencart\Catalog\Controller\Extension\Nimbbl\Payment;

/**
 * Nimbbl catalog controller — redirect-only checkout (v1).
 *
 * Routes:
 *   GET  extension/nimbbl/payment/nimbbl          → index()    payment method panel on checkout page
 *   POST extension/nimbbl/payment/nimbbl/confirm  → confirm()  create Nimbbl order + launch Sonic
 *   GET  extension/nimbbl/payment/nimbbl/callback → callback() verify payment + update OC order
 *   POST extension/nimbbl/payment/nimbbl/webhook  → webhook()  async idempotent status update
 */
class Nimbbl extends \Opencart\System\Engine\Controller {

    // ── Logging ──────────────────────────────────────────────────────────────

    private function log(string $message): void {
        if ($this->config->get('payment_nimbbl_debug') === '1') {
            $log = new \Opencart\System\Library\Log('nimbbl.log');
            $log->write('[Nimbbl] ' . $message);
        }
    }

    // ── URL helpers ──────────────────────────────────────────────────────────

    /**
     * Replace localhost / 127.0.0.1 in a URL with the machine's real IP so
     * that Nimbbl's servers can reach the callback endpoint during local dev.
     * On preprod/prod the HTTP_SERVER is already a public hostname, so this
     * is a no-op there.
     */
    private function resolvePublicUrl(string $url): string {
        if (strpos($url, 'localhost') === false && strpos($url, '127.0.0.1') === false) {
            return $url;
        }

        // Try Docker Desktop host alias first (Mac / Windows)
        $ip = gethostbyname('host.docker.internal');
        if ($ip === 'host.docker.internal') {
            // Fallback: container's own reported server address, then hostname lookup
            $ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        }

        return str_replace(['localhost', '127.0.0.1'], $ip, $url);
    }

    // ── Nimbbl SDK ───────────────────────────────────────────────────────────

    private function loadVendor(): bool {
        $lib_path    = \DIR_EXTENSION . 'nimbbl/system/library/';
        $vendor_path = $lib_path . 'vendor/autoload.php';
        $sdk_path    = $lib_path . 'nimbbl-sdk/autoload.php';

        // Silence rmccue/requests v2 PSR-0 compatibility deprecation notices
        if (!defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS')) {
            define('REQUESTS_SILENCE_PSR0_DEPRECATIONS', true);
        }

        // Load Composer vendor autoloader (provides rmccue/requests + any future deps)
        if (file_exists($vendor_path)) {
            require_once $vendor_path;
            // Explicitly load the PSR-0 compatibility shim so `Requests` class is available
            $compat = $lib_path . 'vendor/rmccue/requests/library/Requests.php';
            if (file_exists($compat)) {
                require_once $compat;
            }
        }

        // Always load the bundled SDK autoloader (Nimbbl\Api\ namespace)
        if (file_exists($sdk_path)) {
            require_once $sdk_path;
            return true;
        }

        $this->log('loadVendor: nimbbl-sdk/autoload.php not found');
        return false;
    }

    private function getNimbblClient(): ?\Nimbbl\Api\RestClient\NimbblClient {
        if (!$this->loadVendor()) {
            return null;
        }

        $mode = $this->config->get('payment_nimbbl_mode');
        if ($mode === 'live') {
            $access_key = $this->config->get('payment_nimbbl_live_access_key');
            $secret_key = $this->config->get('payment_nimbbl_live_secret_key');
        } else {
            $access_key = $this->config->get('payment_nimbbl_test_access_key');
            $secret_key = $this->config->get('payment_nimbbl_test_secret_key');
        }

        $api_url   = rtrim($this->config->get('payment_nimbbl_api_url') ?: 'https://api.nimbbl.tech', '/') . '/api/v3';
        $log_file  = ($this->config->get('payment_nimbbl_debug') === '1') ? DIR_LOGS . 'nimbbl.log' : null;
        $encrypt   = $this->config->get('payment_nimbbl_encrypt_payload') === '1';
        $debug     = $this->config->get('payment_nimbbl_debug') === '1';

        return new \Nimbbl\Api\RestClient\NimbblClient(
            $access_key,
            $secret_key,
            $api_url,
            $log_file,
            $encrypt,
            $debug
        );
    }

    // ── Auth token ───────────────────────────────────────────────────────────

    private function generateToken(): array {
        $client = $this->getNimbblClient();
        if (!$client) {
            return ['result' => 'fail', 'message' => 'Nimbbl SDK not available'];
        }
        try {
            $response = $client->auth()->generateToken();
            if (is_array($response) && !empty($response['token'])) {
                return ['result' => 'success', 'token' => $response['token']];
            }
            return ['result' => 'fail', 'message' => 'Invalid access key or secret key'];
        } catch (\Exception $e) {
            $this->log('generateToken: ' . $e->getMessage());
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    // ── Create Nimbbl order ──────────────────────────────────────────────────

    private function createNimbblOrder(array $order_data, string $auth_token): array {
        $client = $this->getNimbblClient();
        if (!$client) {
            return ['result' => 'fail', 'message' => 'Nimbbl SDK not available'];
        }
        try {
            $response = $client->orders()->createOrder($order_data, $auth_token);
            if (is_array($response) && isset($response['error'])) {
                $err = $response['error'];
                $msg = (is_array($err) ? ($err['nimbbl_merchant_message'] ?? $err['message'] ?? json_encode($err)) : (string) $err);
                return ['result' => 'fail', 'message' => $msg];
            }
            return ['result' => 'success', 'data' => $response];
        } catch (\Exception $e) {
            $this->log('createNimbblOrder: ' . $e->getMessage());
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    // ── Transaction enquiry (authoritative status) ───────────────────────────

    private function transactionEnquiry(string $transaction_id, string $auth_token): array {
        $client = $this->getNimbblClient();
        if (!$client) {
            return ['result' => 'fail', 'message' => 'SDK not available'];
        }
        try {
            $response = $client->transactions()->transactionEnquiry(
                ['transaction_id' => $transaction_id],
                $auth_token
            );
            if (is_array($response) && isset($response['error'])) {
                $err = $response['error'];
                $msg = (is_array($err) ? ($err['message'] ?? $err['nimbbl_merchant_message'] ?? json_encode($err)) : (string) $err);
                return ['result' => 'fail', 'message' => $msg];
            }
            return ['result' => 'success', 'data' => $response];
        } catch (\Exception $e) {
            $this->log('transactionEnquiry: ' . $e->getMessage());
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    // ── Resolve callback / webhook payload ───────────────────────────────────

    /**
     * Verifies the signature, then calls Transaction Enquiry for authoritative status.
     *
     * @return array {
     *   verified: bool,
     *   payload:  array,
     *   order_id: string|null,          — nimbbl_order_id
     *   transaction_id: string|null,    — nimbbl_transaction_id
     *   invoice_id: string|null,        — {oc_order_id}_{timestamp}
     *   payment_mode: string,
     *   status: string,                 — raw payment_status from Transaction Enquiry
     *   outcome: 'success'|'authorized'|'failed'|'pending',
     *   message: string,
     * }
     */
    private function resolveCallback(string $raw): array {
        $out = [
            'verified'       => false,
            'payload'        => [],
            'order_id'       => null,
            'transaction_id' => null,
            'invoice_id'     => null,
            'payment_mode'   => '',
            'status'         => 'unknown',
            'outcome'        => 'failed',
            'message'        => '',
        ];

        if (empty($raw)) {
            $out['message'] = 'Empty response payload';
            return $out;
        }

        if (!$this->loadVendor()) {
            $out['message'] = 'Nimbbl SDK not available';
            return $out;
        }

        if (!class_exists('Nimbbl\Api\Common\SignatureVerifier')) {
            $out['message'] = 'SignatureVerifier not available';
            return $out;
        }

        $mode       = $this->config->get('payment_nimbbl_mode');
        $secret_key = ($mode === 'live')
            ? $this->config->get('payment_nimbbl_live_secret_key')
            : $this->config->get('payment_nimbbl_test_secret_key');

        try {
            $verifier = new \Nimbbl\Api\Common\SignatureVerifier();
            $verify   = $verifier->verifyCallback($raw, $secret_key);
        } catch (\Exception $e) {
            $this->log('resolveCallback: verifyCallback exception: ' . $e->getMessage());
            $out['message'] = $e->getMessage();
            return $out;
        }

        $out['verified'] = is_array($verify) && !empty($verify['success']);
        $out['payload']  = (is_array($verify) && is_array($verify['payload'] ?? null)) ? $verify['payload'] : [];

        if (!$out['verified']) {
            $out['message'] = $verify['message'] ?? 'Signature verification failed';
            return $out;
        }

        $p = $out['payload'];

        // Extract IDs — v4 top-level, legacy nested
        $out['order_id']       = $p['nimbbl_order_id']      ?? $p['order']['order_id']          ?? null;
        $out['transaction_id'] = $p['nimbbl_transaction_id'] ?? $p['transaction']['transaction_id'] ?? $p['transaction_id'] ?? null;
        $out['invoice_id']     = $p['invoice_id']            ?? $p['order']['invoice_id']        ?? null;

        // Callback status (may be stale — Transaction Enquiry is authoritative)
        $callback_status = $p['checkout_status'] ?? $p['transaction']['status'] ?? $p['status'] ?? null;

        // ── Transaction Enquiry ──────────────────────────────────────────────
        $enquiry_status = null;
        if (!empty($out['transaction_id'])) {
            $auth = $this->generateToken();
            if ($auth['result'] === 'success') {
                $enq = $this->transactionEnquiry($out['transaction_id'], $auth['token']);
                if ($enq['result'] === 'success' && isset($enq['data'])) {
                    $txns = $enq['data']['transaction'] ?? [];
                    $txn  = (is_array($txns) && isset($txns[0])) ? $txns[0] : (is_array($txns) ? $txns : []);
                    $enquiry_status       = $txn['payment_status'] ?? null;
                    $out['payment_mode']  = $txn['payment_mode']   ?? '';
                    $out['message']       = $txn['message']        ?? '';
                    $this->log('resolveCallback: enquiry_status=' . $enquiry_status);
                }
            }
        }

        $final_status  = $enquiry_status ?? $callback_status ?? 'unknown';
        $out['status'] = $final_status;
        $s             = strtolower((string) $final_status);

        if (in_array($s, ['succeeded', 'success'], true)) {
            $out['outcome'] = 'success';
        } elseif ($s === 'authorized') {
            $out['outcome'] = 'authorized';
        } elseif (in_array($s, ['failed', 'cancelled', 'canceled', 'expired', 'declined', 'voided'], true)) {
            $out['outcome'] = 'failed';
        } else {
            $out['outcome'] = 'pending';
        }

        return $out;
    }

    // ── Build Nimbbl order data from OC order ────────────────────────────────

    private function buildOrderData(array $order_info, int $oc_order_id): array {
        $invoice_id   = $oc_order_id . '_' . time();
        $callback_url = $this->resolvePublicUrl(
            $this->url->link('extension/nimbbl/payment/nimbbl.callback', '', true)
        );

        $this->load->model('checkout/order');
        $products = $this->model_checkout_order->getProducts($oc_order_id);

        $line_items   = [];
        $item_count   = 0;
        foreach ($products as $product) {
            $qty        = (int) $product['quantity'];
            $unit_price = (float) $product['price'];
            $tax        = isset($product['tax']) ? (float) $product['tax'] : 0.0;
            $rate_incl  = $unit_price + ($qty > 0 ? $tax : 0.0);
            $total_incl = $rate_incl * $qty;
            $item_count += $qty;

            $line_items[] = [
                'sku_id'          => (string) $product['product_id'],
                'title'           => $product['name'],
                'description'     => '',
                'quantity'        => $qty,
                'rate'            => (float) $rate_incl,
                'amount_before_tax' => (float) ($unit_price * $qty),
                'tax'             => (float) ($tax * $qty),
                'total_amount'    => (float) $total_incl,
                'image_url'       => '',
            ];
        }

        $data = [
            'total_amount'     => (float) $order_info['total'],
            'currency'         => $order_info['currency_code'],
            'invoice_id'       => $invoice_id,
            'callback_url'     => $callback_url,
            'quantity'         => $item_count,
            'order_line_items' => $line_items,
        ];

        // Billing address
        $billing = $this->buildBillingAddress($order_info);
        if (!empty($billing)) {
            $data['billing_address'] = $billing;
        }

        // Shipping address (skip if same as billing or virtual order)
        $shipping = $this->buildShippingAddress($order_info);
        if (!empty($shipping)) {
            $data['shipping_address'] = $shipping;
        }

        // User — omit entirely if mobile_number is absent (API spec: key not sent, not null)
        $user = $this->buildUser($order_info);
        if (!empty($user)) {
            $data['user'] = $user;
        }

        return $data;
    }

    private function buildBillingAddress(array $o): array {
        $street  = trim($o['payment_address_1'] ?? '');
        $area    = trim($o['payment_address_2'] ?? '') ?: $street;
        $city    = trim($o['payment_city']      ?? '');
        $state   = trim($o['payment_zone']      ?? '');
        $pincode = trim($o['payment_postcode']  ?? '');
        if ($street === '' && $city === '' && $pincode === '') {
            return [];
        }
        return [
            'street'       => $street,
            'area'         => $area,
            'city'         => $city,
            'state'        => $state,
            'pincode'      => $pincode,
            'address_type' => 'residential',
        ];
    }

    private function buildShippingAddress(array $o): array {
        $street  = trim($o['shipping_address_1'] ?? '');
        $area    = trim($o['shipping_address_2'] ?? '') ?: $street;
        $city    = trim($o['shipping_city']       ?? '');
        $state   = trim($o['shipping_zone']       ?? '');
        $pincode = trim($o['shipping_postcode']   ?? '');
        if ($street === '' && $city === '' && $pincode === '') {
            return [];
        }
        return [
            'street'       => $street,
            'area'         => $area,
            'city'         => $city,
            'state'        => $state,
            'pincode'      => $pincode,
            'address_type' => 'residential',
        ];
    }

    private function buildUser(array $o): array {
        $phone = trim($o['telephone']         ?? '');
        $email = trim($o['email']             ?? '');
        $first = trim($o['payment_firstname'] ?? '');
        $last  = trim($o['payment_lastname']  ?? '');

        // mobile_number is required by Nimbbl — skip the user block entirely if absent
        if ($phone === '') {
            return [];
        }

        $country_code = $this->dialCode($o['payment_iso_code_2'] ?? 'IN');

        return [
            'country_code'  => $country_code,
            'mobile_number' => $phone,
            'email'         => $email,
            'first_name'    => $first,
            'last_name'     => $last,
        ];
    }

    /**
     * Map ISO 3166-1 alpha-2 country code → international dialing prefix.
     * Covers the most common countries; defaults to +91 (India) for unknowns
     * since Nimbbl is an India-first gateway.
     */
    private function dialCode(string $iso2): string {
        static $map = [
            'IN' => '+91',  'US' => '+1',   'GB' => '+44',  'AU' => '+61',
            'CA' => '+1',   'AE' => '+971', 'SG' => '+65',  'NZ' => '+64',
            'ZA' => '+27',  'MY' => '+60',  'PH' => '+63',  'BD' => '+880',
            'PK' => '+92',  'LK' => '+94',  'NP' => '+977', 'MM' => '+95',
            'DE' => '+49',  'FR' => '+33',  'NL' => '+31',  'IT' => '+39',
            'ES' => '+34',  'JP' => '+81',  'KR' => '+82',  'CN' => '+86',
            'HK' => '+852', 'ID' => '+62',  'TH' => '+66',  'VN' => '+84',
        ];
        return $map[strtoupper($iso2)] ?? '+91';
    }

    // ── Routes ───────────────────────────────────────────────────────────────

    /**
     * index() — returns payment method description HTML embedded in checkout page.
     * Called before the order is placed; no session order_id yet.
     */
    public function index(): string {
        $this->load->language('extension/nimbbl/payment/nimbbl');

        $data['logo_url']      = \HTTP_SERVER . 'extension/nimbbl/admin/view/image/payment/nimbbllogo.png';
        $data['text_title']    = $this->language->get('text_title');
        $data['button_confirm'] = $this->language->get('button_confirm');

        return $this->load->view('extension/nimbbl/payment/nimbbl', $data);
    }

    /**
     * confirm() — full-page handler called after OC creates the order.
     * 1. Generates auth token
     * 2. Creates Nimbbl order
     * 3. Renders Sonic redirect page
     */
    public function confirm(): void {
        $this->load->language('extension/nimbbl/payment/nimbbl');

        $json = [];

        $order_id = (int) ($this->session->data['order_id'] ?? 0);
        if (!$order_id) {
            $this->log('confirm: no order_id in session');
            $json['error'] = $this->language->get('error_payment');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if (!isset($this->session->data['payment_method']) ||
            ($this->session->data['payment_method']['code'] ?? '') !== 'nimbbl.nimbbl') {
            $this->log('confirm: payment method mismatch');
            $json['error'] = $this->language->get('error_payment');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            $this->log('confirm: order not found order_id=' . $order_id);
            $json['error'] = $this->language->get('error_payment');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // ── Step 1: Auth token ───────────────────────────────────────────────
        $this->log('confirm: generating token for order_id=' . $order_id);
        $auth = $this->generateToken();
        if ($auth['result'] !== 'success') {
            $this->log('confirm: auth failed — ' . $auth['message']);
            $json['error'] = $this->language->get('error_payment');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // ── Step 2: Create Nimbbl order ──────────────────────────────────────
        $order_data = $this->buildOrderData($order_info, $order_id);
        $this->log('confirm: creating order invoice_id=' . $order_data['invoice_id']);

        $order_result = $this->createNimbblOrder($order_data, $auth['token']);
        if ($order_result['result'] !== 'success') {
            $this->log('confirm: order create failed — ' . $order_result['message']);
            $json['error'] = $this->language->get('error_payment');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $nimbbl_token    = $order_result['data']['token'];
        $nimbbl_order_id = $order_result['data']['order_id'];
        $this->log('confirm: nimbbl_order_id=' . $nimbbl_order_id);

        // ── Step 3: Store in session and redirect to Sonic redirect page ──────
        $this->session->data['nimbbl_order_id']    = $nimbbl_order_id;
        $this->session->data['nimbbl_token']        = $nimbbl_token;

        $json['redirect'] = $this->url->link('extension/nimbbl/payment/nimbbl.redirect', '', true);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * redirect() — intermediate full-page that auto-redirects to Sonic checkout.
     * Reads nimbbl_token from session, renders nimbbl_redirect.twig.
     */
    public function redirect(): void {
        $this->load->language('extension/nimbbl/payment/nimbbl');

        $nimbbl_token = $this->session->data['nimbbl_token'] ?? '';
        if (!$nimbbl_token) {
            $this->log('redirect: no nimbbl_token in session');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        $api_url       = $this->config->get('payment_nimbbl_api_url') ?: 'https://api.nimbbl.tech';
        $parsed        = parse_url($api_url);
        $api_host      = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'api.nimbbl.tech');
        $checkout_host = rtrim($this->config->get('payment_nimbbl_checkout_host') ?: 'https://sonic.nimbbl.tech', '/') . '/';
        $callback_url  = $this->resolvePublicUrl(
            $this->url->link('extension/nimbbl/payment/nimbbl.callback', '', true)
        );

        $data['nimbbl_token']   = $nimbbl_token;
        $data['api_host']       = $api_host;
        $data['checkout_host']  = $checkout_host;
        $data['callback_url']   = $callback_url;
        $data['loading_text']   = $this->language->get('text_loading');
        $data['redirect_msg']   = $this->language->get('text_redirect_message');
        $data['catalog']        = \HTTP_SERVER;

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/nimbbl/payment/nimbbl_redirect', $data));
    }

    /**
     * callback() — browser redirect after Sonic completes payment.
     * Verifies signature → Transaction Enquiry → update OC order → redirect.
     */
    public function callback(): void {
        $this->load->language('extension/nimbbl/payment/nimbbl');
        $this->load->model('checkout/order');

        // Sonic sends the response as a URL-encoded redirect;
        // raw body may carry the signed payload or it may arrive as a GET/POST param.
        $raw = (string) file_get_contents('php://input');
        if (empty($raw) && isset($this->request->get['response'])) {
            $raw = $this->request->get['response'];
        }
        if (empty($raw) && isset($this->request->post['response'])) {
            $raw = $this->request->post['response'];
        }

        $this->log('callback: raw length=' . strlen($raw));

        if (empty($raw)) {
            $this->session->data['error'] = $this->language->get('error_payment');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        $r = $this->resolveCallback($raw);
        $this->log('callback: verified=' . ($r['verified'] ? '1' : '0') . ' outcome=' . $r['outcome']);

        if (!$r['verified']) {
            $this->session->data['error'] = $this->language->get('error_invalid_signature');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        if (!in_array($r['outcome'], ['success', 'authorized'], true)) {
            $this->session->data['error'] = !empty($r['message'])
                ? $r['message']
                : $this->language->get('error_payment_failed');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        // ── Update OC order ──────────────────────────────────────────────────
        $order_id = (int) ($this->session->data['order_id'] ?? 0);
        if ($order_id) {
            $order_info = $this->model_checkout_order->getOrder($order_id);
            if ($order_info) {
                $success_status_id  = (int) $this->config->get('payment_nimbbl_order_status_id');
                $current_status_id  = (int) $order_info['order_status_id'];

                if ($current_status_id !== $success_status_id) {
                    $comment = 'Nimbbl payment successful.';
                    if (!empty($r['transaction_id'])) {
                        $comment .= ' Transaction ID: ' . $r['transaction_id'];
                    }
                    if (!empty($r['payment_mode'])) {
                        $comment .= ' Payment mode: ' . $r['payment_mode'];
                    }
                    $this->model_checkout_order->addHistory(
                        $order_id,
                        $success_status_id,
                        $comment,
                        true
                    );
                    $this->log('callback: order ' . $order_id . ' → status ' . $success_status_id);
                } else {
                    $this->log('callback: order ' . $order_id . ' already in success status, skipping');
                }
            }
        }

        // Clear session payment state
        unset($this->session->data['order_id']);
        unset($this->session->data['nimbbl_order_id']);

        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    /**
     * webhook() — async server-to-server payment notification from Nimbbl.
     * Always returns 200 JSON. Idempotent — never downgrades a completed order.
     */
    public function webhook(): void {
        $this->load->model('checkout/order');

        $respond = function (string $status, string $message): void {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'status'  => $status,
                'message' => $message,
            ]));
        };

        $raw = (string) file_get_contents('php://input');
        $this->log('webhook: raw length=' . strlen($raw));

        if (empty($raw)) {
            $respond('error', 'Empty payload');
            return;
        }

        $r = $this->resolveCallback($raw);
        $this->log('webhook: verified=' . ($r['verified'] ? '1' : '0') . ' outcome=' . $r['outcome']);

        if (!$r['verified']) {
            $respond('error', 'Signature verification failed');
            return;
        }

        // Derive OC order ID from invoice_id (format: {oc_order_id}_{timestamp})
        $invoice_id = $r['invoice_id'] ?? ($r['payload']['invoice_id'] ?? '');
        if (empty($invoice_id)) {
            $this->log('webhook: missing invoice_id for nimbbl_order_id=' . $r['order_id']);
            $respond('ok', 'No invoice_id, nothing to update');
            return;
        }

        $oc_order_id = (int) explode('_', $invoice_id)[0];
        if (!$oc_order_id) {
            $respond('error', 'Could not parse OC order_id from invoice_id=' . $invoice_id);
            return;
        }

        $order_info = $this->model_checkout_order->getOrder($oc_order_id);
        if (!$order_info) {
            $this->log('webhook: OC order not found oc_order_id=' . $oc_order_id);
            $respond('ok', 'Order not found');
            return;
        }

        $success_status_id = (int) $this->config->get('payment_nimbbl_order_status_id');
        $fail_status_id    = (int) $this->config->get('payment_nimbbl_order_fail_status_id');
        $current_status_id = (int) $order_info['order_status_id'];

        if (in_array($r['outcome'], ['success', 'authorized'], true)) {
            if ($current_status_id !== $success_status_id) {
                $comment = 'Nimbbl webhook: payment confirmed.';
                if (!empty($r['transaction_id'])) {
                    $comment .= ' TxnID: ' . $r['transaction_id'];
                }
                $this->model_checkout_order->addHistory($oc_order_id, $success_status_id, $comment, false);
                $this->log('webhook: order ' . $oc_order_id . ' → success status');
            } else {
                $this->log('webhook: order ' . $oc_order_id . ' already successful, skipping');
            }
        } elseif ($r['outcome'] === 'failed') {
            // Never downgrade a completed order
            if ($current_status_id !== $success_status_id && $current_status_id !== $fail_status_id) {
                $this->model_checkout_order->addHistory($oc_order_id, $fail_status_id, 'Nimbbl webhook: payment failed.', false);
                $this->log('webhook: order ' . $oc_order_id . ' → fail status');
            }
        }

        $respond('ok', 'Processed');
    }
}
