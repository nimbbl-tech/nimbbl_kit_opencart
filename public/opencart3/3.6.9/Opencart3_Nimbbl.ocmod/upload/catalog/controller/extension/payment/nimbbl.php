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
		error_log('DEBUG: arg_order_data for Nimbbl order creation: ' . print_r($arg_order_data, true));


        // Always create a new Nimbbl order with a unique invoice_id
        $unique_invoice_id = $order_info['order_id'] . '-' . time();
        $arg_order_data['invoice_id'] = $unique_invoice_id;
        $newOrder = $this->nimbbl_api->order->create($arg_order_data);
        // Print the complete raw JSON response from the API
        if (isset($newOrder->raw_response)) {
            error_log('DEBUG: Raw JSON response from Nimbbl create order: ' . $newOrder->raw_response);
        } else {
            error_log('DEBUG: $newOrder (print_r): ' . print_r($newOrder, true));
        }
        error_log('DEBUG: Created new Nimbbl order: ' . print_r($newOrder, true));
        if ($newOrder->error) {
            error_log('ERROR: Nimbbl order creation error: ' . print_r($newOrder->error, true));
            return [
                'error' => 'Nimbbl order creation failed: ' . print_r($newOrder->error, true),
                'data' => ''
            ];
        }
        if (empty($newOrder->token)) {
            error_log('ERROR: Nimbbl order token is missing. $newOrder: ' . print_r($newOrder, true));
            return [
                'error' => 'Nimbbl order token missing, cannot initialize checkout.',
                'data' => ''
            ];
        }
        $nimbblorder = $newOrder->attributes; // Optional, for reference

		// Ensure $nimbblorder is set and has a token before proceeding
		if (empty($nimbblorder) || !isset($nimbblorder['token'])) {
			error_log('ERROR: Nimbbl order token is missing or $nimbblorder is not set.');
			return [
				'error' => 'Nimbbl order token missing, cannot initialize checkout.',
				'data' => ''
			];
		}

		$html = '<form id="nimbblform" name="nimbblform" action="'.$surl.'" method="POST">
			<input type="hidden" name="nimbbl_order_id" id="nimbbl_order_id">
			<input type="hidden" name="nimbbl_transaction_id" id="nimbbl_transaction_id">
			<input type="hidden" name="nimbbl_signature" id="nimbbl_signature">
			<input type="hidden" name="nimbbl_status" id="nimbbl_status">
			<input type="hidden" name="nimbbl_reason" id="nimbbl_reason">
			<input type="hidden" name="cart_order_id" id="cart_order_id" value="'.$this->session->data['order_id'].'">
		</form>';

		// Remove old checkout.js and use module import
		$html .= "<div class='buttons'>
			<div class='pull-right'>
				<input type='submit'
					id='nimbbl-confirm-btn'
					value='".$this->language->get('button_confirm')."'
					class='btn btn-primary' />
			</div>
		</div>";

		$html .= '<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/sonic-widget@latest/dist/index.min.js" />';

		// Remove old checkout.js and use module import
		$html .= '<script type="text/javascript">

			// Debug line for the JavaScript console to confirm token value
			console.log("DEBUG: Nimbbl checkout token being used:", "'.$newOrder->token.'");

			// const checkout = new Checkout({ token: "'.$newOrder->token.'" }); 
			// You must have this token from your backend

			document.addEventListener("DOMContentLoaded", function () {
				document.addEventListener("click", function (event) {
					if (event.target && event.target.id === "nimbbl-confirm-btn") {
						event.preventDefault();
						console.log("Dynamic button clicked!");
					}
				}
			});

			function openModal() {
				const options = {
					callback_handler: function(response) {
						console.log("callback_handler - ", response); // Keep this existing debug

						if (response.status === "success") {
							document.getElementById("nimbbl_order_id").value = response.order_id;
							document.getElementById("nimbbl_transaction_id").value = response.transaction_id;
							document.getElementById("nimbbl_signature").value = response.signature;
							document.getElementById("nimbbl_status").value = response.status;
							document.nimbblform.submit();
						} else {
							document.getElementById("nimbbl_order_id").value = response.order_id;
							document.getElementById("nimbbl_status").value = response.status;
							document.getElementById("nimbbl_reason").value = response.reason;
							document.nimbblform.submit();
						}
					}
				};

				// checkout.open(options);
			}
			
		</script>';

		// Debug line before returning the final HTML
		error_log('DEBUG: Nimbbl payment HTML generated and returned successfully.');

		return [
			'error' => '',
			'data' => $html
		];
		
	}
	
	public function callback() {
		$this->init();
		if (isset($this->request->post['nimbbl_order_id']) || !empty($this->request->post['nimbbl_status'])) {
			$this->language->load('extension/payment/nimbbl');
			$this->load->model('checkout/order');
				
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			
			$attributes = [
                'transaction' => [
                    'signature' => $this->request->post['nimbbl_signature'],
                    'signature_version' => $this->request->post['signature_version'] ?? null,
                    'transaction_amount' => $this->request->post['transaction_amount'] ?? $order_info['total'],
                    'transaction_currency' => $this->request->post['transaction_currency'] ?? $order_info['currency_code'],
                    'status' => $this->request->post['status'] ?? $this->request->post['nimbbl_status'] ?? null,
                    'transaction_type' => $this->request->post['transaction_type'] ?? null,
                ],
                'nimbbl_transaction_id' => $this->request->post['nimbbl_transaction_id'],
                'order' => [
                    'invoice_id' => $this->session->data['order_id'],
                ]
            ];
            $verified = $this->nimbbl_api->util->verifyPaymentSignature($attributes, $order_info['total']);
			
			
			$message='';
			try {
                if($verified && !empty($order_info)){					                    

                        if($this->request->post['nimbbl_status']!= 'success'){
							$message .='Payment cancelled or failed - '.$this->request->post['nimbbl_reason'];
							$this->session->data['error'] = $message;		
							$this->response->redirect($this->url->link('checkout/checkout', '', true));							
                        }

                        if($order_info['order_status_id'] != $this->orderstatusid && $this->request->post['nimbbl_status'] == 'success')
						{
							$this->session->data['success'] = "Payment is successful...";
							$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->orderstatusid,'Payment Successful',true);
							$this->response->redirect($this->url->link('checkout/success', '', true));				
                        }						
						elseif($order_info['order_status_id'] != $this->orderstatusid && $this->request->post['nimbbl_status'] == 'failed')
						{
									$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->orderfailstatusid,$message,true);					
									$this->session->data['error'] = "Payment is cancelled/failed...";
									$this->response->redirect($this->url->link('checkout/checkout', '', true));
						} else {
                            $message ="Invalid payment data received...";
							$this->session->data['error'] = $message;		
							$this->response->redirect($this->url->link('checkout/checkout', '', true));                               
                        }                    
                } else {                    
					$message .= "Error:: Signature validation failed..";
					$this->session->data['error'] = $message;		
					$this->response->redirect($this->url->link('checkout/checkout', '', true));
                }

            } catch (Throwable $exception){
               
				$message .= "Error:: " . $exception->getMessage();
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
		
		$webhook_data = json_decode($post, true);
		
		$this->log->write("Webhook data:".$post);
		
		if (isset($webhook_data['nimbbl_transaction_id']) || !empty($webhook_data['order']['invoice_id'])) {
			$this->language->load('extension/payment/nimbbl');
			$this->load->model('checkout/order');
							
			$order_info = $this->model_checkout_order->getOrder($webhook_data['order']['invoice_id']);
			
			$attributes = [
                'transaction' => [
                    'signature' => $webhook_data['nimbbl_signature'],
                    'signature_version' => $webhook_data['transaction']['signature_version'] ?? null,
                    'transaction_amount' => $webhook_data['transaction']['transaction_amount'] ?? $order_info['total'],
                    'transaction_currency' => $webhook_data['transaction']['transaction_currency'] ?? $order_info['currency_code'],
                    'status' => $webhook_data['transaction']['status'] ?? null,
                    'transaction_type' => $webhook_data['transaction']['transaction_type'] ?? null,
                ],
                'nimbbl_transaction_id' => $webhook_data['nimbbl_transaction_id'],
                'order' => [
                    'invoice_id' => $webhook_data['order']['invoice_id'],
                ]
            ];
            $verified = $this->nimbbl_api->util->verifyPaymentSignature($attributes, $order_info['total']);
			
			try {
                if($verified && !empty($order_info)){					                    

                        if($order_info['order_status_id'] != $this->orderstatusid && $webhook_data['transaction']['status'] === 'succeeded')
						{
							$this->model_checkout_order->addOrderHistory($webhook_data['order']['invoice_id'], $this->orderstatusid,'Payment Successful (updated via webhook)',true);
							
                        }						
						elseif($order_info['order_status_id'] == $this->orderstatusid && $webhook_data['transaction']['status'] === 'failed')
						{
									$this->model_checkout_order->addOrderHistory($webhook_data['order']['invoice_id'], $this->orderfailstatusid,'Payment Failed (updated via webhook)',true);									

						}                    
                } 

            } catch (Throwable $exception){               							
				$this->log->write("Webhook Error:: " . $exception->getMessage());
            }										
		}
	}	
}
?>