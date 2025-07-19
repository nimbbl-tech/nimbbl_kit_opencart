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
				"title" => $item['name'],
				"quantity" => $item['quantity'],
				'uom' => '',
				'image_url' => $this->url->link('image/'.$full_product['image']),
				'description' => strip_tags($full_product['description']),
				'sku_id' => $full_product['sku'],
				'rate' => $item['price'],
				'amount_before_tax' => $item['price'],
				'tax' => $item['tax'],
				"total_amount" => $item['total'], 
				);			
			$total_tax += $item['tax'];
			array_push($arg_order_item_data, $product);
		}
		
		$arg_order_data = array(
				'referrer_platform' => 'Opencart v3',
				'referrer_platform_version' => 'version v2',
				'merchant_shopfront_domain' => $order_info['store_url'],
				'invoice_id' => $order_info['order_id'],
				'order_date' => date('Y-m-d H:i:s'),
				'currency' => $order_info['currency_code'],
				'amount_before_tax' => $order_info['total'] - $total_tax,
				'tax' => $total_tax,
				'total_amount' => $order_info['total'],
				"user" => $arg_user_data,
				'shipping_address' => $arg_shipping_address_data,
				"order_line_items" => $arg_order_item_data,
				'description' => $order_info['comment'],
			);
		$newOrder = $this->nimbbl_api->order->create($arg_order_data);
		if ($newOrder->error) {
			return [
                'error' => $newOrder->error,
				'data'=> ''				
            ];
		}
		$nimbblorder=$newOrder->attributes;
		
		$html = '<form id="nimbblform" name="nimbblform" action="'.$surl.'" method="POST">
				<input type="hidden" name="nimbbl_order_id" id="nimbbl_order_id">
				<input type="hidden" name="nimbbl_transaction_id" id="nimbbl_transaction_id">
				<input type="hidden" name="nimbbl_signature" id="nimbbl_signature">
				<input type="hidden" name="nimbbl_status" id="nimbbl_status">
				<input type="hidden" name="nimbbl_reason" id="nimbbl_reason">				    
				<input type="hidden" name="cart_order_id" id="cart_order_id" value="'.$this->session->data['order_id'].'">				
				</form>
				<script type="text/javascript" src="'.$this->endpoint . '/static/assets/js/checkout.js'.'"></script>';
		
		$html .='<script type="text/javascript">

					function openModal() {					
						var options = {
							"access_key": "'.$this->publickey.'", 
							"order_id": "'.$nimbblorder['order_id'].'",
        
							"callback_handler": function (response) {
								console.log("callback_handler - ", response);

								if (response.status === "success") {									
									document.getElementById("nimbbl_order_id").value = response.order_id;
									document.getElementById("nimbbl_transaction_id").value = response.transaction_id;
									document.getElementById("nimbbl_signature").value = response.signature;
									document.getElementById("nimbbl_status").value = response.status;
									document.nimbblform.submit();
								} else {
									document.nimbblform.action = document.getElementById("nimbbl_cancel_url").value;
									document.getElementById("nimbbl_order_id").value = response.order_id;
									document.getElementById("nimbbl_status").value = response.status;
									document.getElementById("nimbbl_reason").value = response.reason;
									document.nimbblform.submit();
								}
							},
							custom: {
								"key_1": "val_1",
								"key_2": "val_2"
							},
						};

						window.checkout = new NimbblCheckout(options);
						window.checkout.open("'.$nimbblorder['order_id'].'");
					}
				</script>';
		$html .= "<div class='buttons'>
				<div class='pull-right'><input type='submit' 
					value='".$this->language->get('button_confirm')."' class='btn btn-primary' onclick='event.preventDefault(); openModal(); return false;' /></div>
				</div>";
						

		return [
			'error' => '',
			'data'=> $html		
        ];
		
	}
	
	public function callback() {
		$this->init();
		if (isset($this->request->post['nimbbl_order_id']) || !empty($this->request->post['nimbbl_status'])) {
			$this->language->load('extension/payment/nimbbl');
			$this->load->model('checkout/order');
				
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			
			$verified = $this->nimbbl_api->util->verifyPaymentSignature([
					'nimbbl_signature' => $this->request->post['nimbbl_signature'],
					'nimbbl_transaction_id' => $this->request->post['nimbbl_transaction_id'],
					'merchant_order_id' => $this->session->data['order_id'],
					'order_amount' => $order_info['total'],
					'order_currency' => $order_info['currency_code']
				]);
			
			
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
							
			$verified = $this->nimbbl_api->util->verifyPaymentSignature([
					'nimbbl_signature' => $webhook_data['nimbbl_signature'],
					'nimbbl_transaction_id' => $webhook_data['nimbbl_transaction_id'],
					'merchant_order_id' => $webhook_data['order']['invoice_id'],
				]);
			
			$order_info = $this->model_checkout_order->getOrder($webhook_data['order']['invoice_id']);
			
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