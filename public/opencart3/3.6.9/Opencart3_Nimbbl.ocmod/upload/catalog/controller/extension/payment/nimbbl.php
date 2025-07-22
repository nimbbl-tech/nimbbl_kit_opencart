<?php
require_once DIR_SYSTEM .'library/nimbbl-sdk/Nimbbl.php';
use Nimbbl\Api\Api;
use Nimbbl\Api\NimbblApi;

class ControllerExtensionPaymentNimbbl extends Controller {
	
	
	private $payment_mode='';
	private $endpoint='';
	private $publickey='';
	private $privatekey='';
	private $orderstatusid='';
	private $orderfailstatusid='';
	
	private $nimbbl_api='';
	
	const SHA256 = 'sha256';
	
	public function index()
	{
		$this->load->model('checkout/order');	
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		//$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'), "Default order status before payment.", false);
		
		$data=$this->process_nimbbl();
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . 'extension/payment/nimbbl')) {
			return $this->load->view($this->config->get('config_template') . 'extension/payment/nimbbl', $data);	
		} else {
			return $this->load->view('extension/payment/nimbbl', $data);
		}		
		
	}
	
	public function init()
	{
		$this->payment_mode = $this->config->get('payment_nimbbl_mode');
		$this->endpoint = $this->config->get('payment_nimbbl_testendpoint');
		$this->publickey = $this->config->get('payment_nimbbl_testpublickey');
		$this->privatekey = $this->config->get('payment_nimbbl_testprivatekey');
		$this->orderstatusid = $this->config->get('payment_nimbbl_order_status_id');
		$this->orderfailstatusid = $this->config->get('payment_nimbbl_order_fail_status_id');
		if($this->payment_mode == 'live')
		{
			$this->endpoint = $this->config->get('payment_nimbbl_liveendpoint');
			$this->publickey = $this->config->get('payment_nimbbl_livepublickey');
			$this->privatekey = $this->config->get('payment_nimbbl_liveprivatekey');
		}
		if(strrpos($this->endpoint,'/') == true) //Viatechs - this code added to remove confusion by merchant
			$this->endpoint = rtrim($this->endpoint, '/');
		$this->nimbbl_api = new NimbblApi($this->publickey, $this->privatekey,$this->endpoint); //UAT url-
	}

	private function process_nimbbl() {	
    	
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');
		$this->language->load('extension/payment/nimbbl');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		/////////////////////////////////////Start Layer Vital  Information /////////////////////////////////
		$this->init();

		$surl = $this->url->link('extension/payment/nimbbl/callback');
		$arg_user_data = [
				'mobile_number' => (isset($order_info['telephone']) ? $order_info['telephone'] : ''),
				'email' => $order_info['email'],
				'first_name' => (isset($order_info['payment_firstname']) ? $order_info['payment_firstname'] : ''),
				'last_name' => (isset($order_info['payment_lastname']) ? $order_info['payment_lastname'] : ''),
			];
		$arg_shipping_address_data = [
				'area' => $order_info['shipping_address_1'] . ', ' . $order_info['shipping_address_2'],
				'city' => (isset($order_info['shipping_city']) ? $order_info['shipping_city'] : $order_info['payment_city']),
				'state' => (isset($order_info['shipping_zone']) ? $order_info['shipping_zone'] : $order_info['payment_zone']),
				'pincode' => (isset($order_info['shipping_postcode']) ? $order_info['shipping_postcode'] : $order_info['payment_postcode']),
				'address_type' => 'home'
			];
		$arg_order_item_data = array();
		$order_products = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);	
		$total_tax=0;
		foreach ($order_products as $item) {
			$full_product = $this->model_catalog_product->getProduct($item['product_id']);
			$product = array(
				'sku_id' => $full_product['sku'],
				'title' => $item['name'],
				'description' => strip_tags($full_product['description']),
				'image_url' => $this->url->link('image/'.$full_product['image']),
				'rate' => (float)$item['price'],
				'quantity' => (int)$item['quantity'],
				'amount_before_tax' => (float)$item['price'],
				'tax' => (float)$item['tax'],
				'total_amount' => (float)$item['total'],
				// Optionally add serial_numbers if available
			);
			$total_tax += (float)$item['tax'];
			array_push($arg_order_item_data, $product);
		}
		
		// Get client IP address
		$ip_address = '';
		if (!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$ip_address = $this->request->server['HTTP_CLIENT_IP'];
		} elseif (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($this->request->server['REMOTE_ADDR'])) {
			$ip_address = $this->request->server['REMOTE_ADDR'];
		}

		// Optionally, set offer_enabled and validate_order_line_item to false by default
		$offer_enabled = false;
		$validate_order_line_item = false;

		// Optionally, add bank_account and custom_attributes if available (example static values)
		$bank_account = array(
			'account_number' => '',
			'name' => '',
			'ifsc' => ''
		);
		$custom_attributes = array();

		// If you have logic to fill these, do so here. Otherwise, leave as empty/default.

		$arg_order_data = array(
			'referrer_platform' => 'Opencart v3',
			'referrer_platform_version' => 'version v2',
			'merchant_shopfront_domain' => $order_info['store_url'],
			'invoice_id' => $order_info['order_id'],
			'order_date' => date('Y-m-d H:i:s'),
			'currency' => $order_info['currency_code'],
			'amount_before_tax' => (float)($order_info['total'] - $total_tax),
			'tax' => (float)$total_tax,
			'total_amount' => (float)$order_info['total'],
			'user' => $arg_user_data,
			'shipping_address' => $arg_shipping_address_data,
			'order_line_items' => $arg_order_item_data,
			'description' => $order_info['comment'],
			'ip_address' => $ip_address,
			'offer_enabled' => $offer_enabled,
			'validate_order_line_item' => $validate_order_line_item,
			'bank_account' => $bank_account,
			'custom_attributes' => $custom_attributes
		);
		// Add debug line for the arguments passed to create()
		//error_log('DEBUG: arg_order_data for Nimbbl order creation: ' . print_r($arg_order_data, true), 0);


        // Always create a new Nimbbl order with a unique invoice_id
        $arg_order_data['invoice_id'] = $order_info['order_id'];
        $newOrder = $this->nimbbl_api->order->create($arg_order_data);
        // Print the complete raw JSON response from the API
        /*
		if (isset($newOrder->raw_response)) {
            error_log('DEBUG: Raw JSON response from Nimbbl create order: ' . $newOrder->raw_response, 0);
        } else {
            error_log('DEBUG: $newOrder (print_r): ' . print_r($newOrder, true), 0);
        }
		*/	
        error_log('DEBUG: Created new Nimbbl order: ' . print_r($newOrder, true), 0);
        if ($newOrder->error) {
            error_log('ERROR: Nimbbl order creation error: ' . print_r($newOrder->error, true), 0);
            return [
                'error' => 'Nimbbl order creation failed: ' . print_r($newOrder->error, true),
                'data' => ''
            ];
        }
        if (empty($newOrder->token)) {
            error_log('ERROR: Nimbbl order token is missing. $newOrder: ' . print_r($newOrder, true), 0);
            return [
                'error' => 'Nimbbl order token missing, cannot initialize checkout.',
                'data' => ''
            ];
        }
        $nimbblorder = $newOrder->attributes; // Optional, for reference

		// Ensure $nimbblorder is set and has a token before proceeding
		/*
		if (empty($nimbblorder) || !isset($nimbblorder['token'])) {
			error_log('ERROR: Nimbbl order token is missing or $nimbblorder is not set.', 0);
			return [
				'error' => 'Nimbbl order token missing, cannot initialize checkout.',
				'data' => ''
			];
		}
		*/	

		$html = '<form id="nimbblform" name="nimbblform" action="'.$surl.'" method="POST">
			<input type="hidden" name="nimbbl_order_id" id="nimbbl_order_id">
			<input type="hidden" name="nimbbl_transaction_id" id="nimbbl_transaction_id">
			<input type="hidden" name="nimbbl_signature" id="nimbbl_signature">
			<input type="hidden" name="nimbbl_status" id="nimbbl_status">
			<input type="hidden" name="nimbbl_reason" id="nimbbl_reason">
			<input type="hidden" name="cart_order_id" id="cart_order_id" value="'.$this->session->data['order_id'].'">
			<input type="hidden" name="transaction_amount" id="transaction_amount">
			<input type="hidden" name="transaction_currency" id="transaction_currency">
			<input type="hidden" name="transaction_type" id="transaction_type">
			<input type="hidden" name="signature_version" id="signature_version">
			<input type="hidden" name="transaction_status" id="transaction_status">
		</form>';

		$html .= '<script type="text/javascript" src="https://api.nimbbl.tech/static/assets/js/checkout.js" />';

		//$html .= '<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/sonic-widget@latest/dist/index.min.js" />';

		$html .= <<<EOT
<script type="text/javascript">
function openModal() {
	var options = {
		access_key: "{$this->publickey}",
		order_id: "{$nimbblorder['order_id']}",
		callback_handler: function (response) {
			console.log("callback_handler - ", response);
			if (response.status === "success") {
				document.getElementById("nimbbl_order_id").value = response.order_id;
				document.getElementById("nimbbl_transaction_id").value = response.transaction_id;
				document.getElementById("nimbbl_signature").value = response.signature;
				document.getElementById("nimbbl_status").value = response.status;
				document.getElementById("transaction_amount").value = response.transaction ? response.transaction.transaction_amount || '' : '';
				document.getElementById("transaction_currency").value = response.transaction ? response.transaction.transaction_currency || '' : '';
				document.getElementById("transaction_type").value = response.transaction ? response.transaction.transaction_type || '' : '';
				document.getElementById("signature_version").value = response.transaction ? response.transaction.signature_version || '' : '';
				document.getElementById("transaction_status").value = response.transaction ? response.transaction.status || '' : '';
				document.nimbblform.submit();
			} else {
				document.nimbblform.action = document.getElementById("nimbbl_cancel_url") ? document.getElementById("nimbbl_cancel_url").value : document.nimbblform.action;
				document.getElementById("nimbbl_order_id").value = response.order_id;
				document.getElementById("nimbbl_status").value = response.status;
				document.getElementById("nimbbl_reason").value = response.reason;
				document.getElementById("transaction_amount").value = response.transaction ? response.transaction.transaction_amount || '' : '';
				document.getElementById("transaction_currency").value = response.transaction ? response.transaction.transaction_currency || '' : '';
				document.getElementById("transaction_type").value = response.transaction ? response.transaction.transaction_type || '' : '';
				document.getElementById("signature_version").value = response.transaction ? response.transaction.signature_version || '' : '';
				document.getElementById("transaction_status").value = response.transaction ? response.transaction.status || '' : '';
				document.nimbblform.submit();
			}
		}
	};
	window.checkout = new NimbblCheckout(options);
	window.checkout.open("{$nimbblorder['order_id']}");
}
</script>
EOT;

		$html .= "<div class='buttons'>
			<div class='pull-right'><input type='submit' 
				value='".$this->language->get('button_confirm')."' class='btn btn-primary' onclick='event.preventDefault(); openModal(); return false;' /></div>
			</div>";
			

		// Debug line before returning the final HTML
		error_log('DEBUG: Nimbbl payment HTML generated and returned successfully.', 0);

		return [
			'error' => '',
			'data' => $html
		];
		
	}
	
	public function callback() {
		$this->init();
		$this->language->load('extension/payment/nimbbl');
		$this->load->model('checkout/order');

		// Log the raw input for debugging
		$rawInput = file_get_contents('php://input');
		//error_log('DEBUG: Raw callback input: ' . $rawInput, 0);
		$postData = json_decode($rawInput, true);

		// Fallback to POST if JSON is invalid
		if (json_last_error() !== JSON_ERROR_NONE || empty($postData)) {
			$postData = $this->request->post;
		}
	
		if (!empty($postData['nimbbl_order_id']) || !empty($postData['nimbbl_status'])) {
			// Use only flat POST fields from the form
			$order_id = $postData['cart_order_id'] ?? '';
			$order_info = $this->model_checkout_order->getOrder($order_id);
			if (empty($order_info)) {
				$this->session->data['error'] = "Order not found.";
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}

			$nimbbl_transaction_id = $postData['nimbbl_transaction_id'] ?? '';
			$signature = $postData['nimbbl_signature'] ?? '';
			$signature_version = $postData['signature_version'] ?? 'v3';
			$transaction_amount = $postData['transaction_amount'] ?? '';
			$transaction_currency = $postData['transaction_currency'] ?? '';
			$transaction_status = $postData['transaction_status'] ?? $postData['nimbbl_status'] ?? 'failed';
			$transaction_type = $postData['transaction_type'] ?? 'payment';

			$attributes = [
				'transaction' => [
					'signature' => $signature,
					'signature_version' => $signature_version,
					'transaction_amount' => $transaction_amount,
					'transaction_currency' => $transaction_currency,
					'status' => $transaction_status,
					'transaction_type' => $transaction_type,
				],
				'nimbbl_transaction_id' => $nimbbl_transaction_id,
				'order' => [
					'invoice_id' => $order_id,
				]
			];
	
			try {
				$verified = $this->nimbbl_api->util->verifyPaymentSignature($attributes, $order_info['total']);
	
				$message = '';
	
				if ($verified && !empty($order_info)) {
					if ($transaction_status != 'success' && $transaction_status != 'succeeded') {
						$message = 'Payment cancelled or failed - ' . ($postData['nimbbl_reason'] ?? '');
						$this->session->data['error'] = $message;
						$this->response->redirect($this->url->link('checkout/checkout', '', true));
					}
	
					if ($order_info['order_status_id'] != $this->orderstatusid && ($transaction_status == 'success' || $transaction_status == 'succeeded')) {
						$this->session->data['success'] = "Payment is successful...";
						$this->model_checkout_order->addOrderHistory($order_id, $this->orderstatusid, 'Payment Successful', true);
						$this->response->redirect($this->url->link('checkout/success', '', true));
					} elseif ($order_info['order_status_id'] != $this->orderstatusid && $transaction_status == 'failed') {
						$this->model_checkout_order->addOrderHistory($order_id, $this->orderfailstatusid, $message, true);
						$this->session->data['error'] = "Payment is cancelled/failed...";
						$this->response->redirect($this->url->link('checkout/checkout', '', true));
					} else {
						$message = "Invalid payment data received...";
						$this->session->data['error'] = $message;
						$this->response->redirect($this->url->link('checkout/checkout', '', true));
					}
				} else {
					$message = "Error:: Signature validation failed.";
					$this->session->data['error'] = $message;
					$this->response->redirect($this->url->link('checkout/checkout', '', true));
				}
			} catch (Throwable $exception) {
				$message = "Error:: " . $exception->getMessage();
				$this->session->data['error'] = $message;
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}
		}
	}
	
	
	public function webhook() {		
		$this->init();
		$post = file_get_contents('php://input');
		if(!$post)
			$this->response->redirect($this->url->link('', '', true));
		
		//error_log('DEBUG: Raw webhook data: ' . $post, 0);
		$webhook_data = json_decode($post, true);
		//error_log('DEBUG: Parsed webhook_data: ' . print_r($webhook_data, true), 0);
		
		if (isset($webhook_data['nimbbl_transaction_id']) || !empty($webhook_data['order']['invoice_id'])) {
			$this->language->load('extension/payment/nimbbl');
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($webhook_data['order']['invoice_id']);

			$transaction_status = $webhook_data['transaction']['status'] ?? null;

			$attributes = [
                'transaction' => [
                    'signature' => $webhook_data['nimbbl_signature'],
                    'signature_version' => $webhook_data['transaction']['signature_version'] ?? null,
                    'transaction_amount' => $webhook_data['transaction']['transaction_amount'] ?? $order_info['total'],
                    'transaction_currency' => $webhook_data['transaction']['transaction_currency'] ?? $order_info['currency_code'],
                    'status' => $transaction_status,
                    'transaction_type' => $webhook_data['transaction']['transaction_type'] ?? null,
                ],
                'nimbbl_transaction_id' => $webhook_data['nimbbl_transaction_id'],
                'order' => [
                    'invoice_id' => $webhook_data['order']['invoice_id'],
                ]
            ];
            error_log('DEBUG: Nimbbl webhook attributes: ' . print_r($attributes, true), 0);
            error_log('DEBUG: Nimbbl webhook order amount: ' . print_r($order_info['total'], true), 0);

            $verified = $this->nimbbl_api->util->verifyPaymentSignature($attributes, $order_info['total']);
            error_log('DEBUG: Nimbbl webhook signature verification result: ' . ($verified ? 'true' : 'false'), 0);
            
			try {
                if($verified && !empty($order_info)){
                    if ($transaction_status != 'success' && $transaction_status != 'succeeded') {
                        $this->model_checkout_order->addOrderHistory($webhook_data['order']['invoice_id'], $this->orderfailstatusid,'Payment Failed (updated via webhook)',true);
                    } elseif($order_info['order_status_id'] != $this->orderstatusid && ($transaction_status == 'success' || $transaction_status == 'succeeded')) {
                        $this->model_checkout_order->addOrderHistory($webhook_data['order']['invoice_id'], $this->orderstatusid,'Payment Successful (updated via webhook)',true);
                    }
                }
            } catch (Throwable $exception){
                error_log('Webhook Error:: ' . $exception->getMessage(), 0);
            }
        }
    }	
}
?>