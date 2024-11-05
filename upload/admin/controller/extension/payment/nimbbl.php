<?php 
class ControllerExtensionPaymentNimbbl extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/nimbbl');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('payment_nimbbl', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_mode'] = $this->language->get('entry_mode');		
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_order_status'] = $this->language->get('entry_order_status');	
		$data['entry_order_fail_status'] = $this->language->get('entry_order_fail_status');	
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_total'] = $this->language->get('entry_total');	
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_test'] = $this->language->get('text_test');
		$data['text_live'] = $this->language->get('text_live');
		
		$data['entry_testendpoint'] = $this->language->get('entry_testendpoint');
		$data['entry_testpublickey'] = $this->language->get('entry_testpublickey');
		$data['entry_testprivatekey'] = $this->language->get('entry_testprivatekey');
		$data['entry_liveendpoint'] = $this->language->get('entry_liveendpoint');
		$data['entry_livepublickey'] = $this->language->get('entry_livepublickey');
		$data['entry_liveprivatekey'] = $this->language->get('entry_liveprivatekey');
		
		
		$data['help_title'] = $this->language->get('help_title');
		$data['help_testendpoint'] = $this->language->get('help_testendpoint');
		$data['help_testpublickey'] = $this->language->get('help_testpublickey');
		$data['help_testprivatekey'] = $this->language->get('help_testprivatekey');
		$data['help_liveendpoint'] = $this->language->get('help_liveendpoint');
		$data['help_livepublickey'] = $this->language->get('help_livepublickey');
		$data['help_liveprivatekey'] = $this->language->get('help_liveprivatekey');
		$data['help_total'] = $this->language->get('help_total');
		$data['help_geozone'] = $this->language->get('help_geozone');
		$data['help_orderstatus'] = $this->language->get('help_orderstatus');
		$data['help_orderfailstatus'] = $this->language->get('help_orderfailstatus');
		$data['help_pluginstatus'] = $this->language->get('help_pluginstatus');
		$data['help_sortorder'] = $this->language->get('help_sortorder');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');        
		$data['tab_general'] = $this->language->get('tab_general');
		
		$data['webhook_url'] = HTTPS_CATALOG."index.php?route=extension/payment/nimbbl/webhook";
		
		if(!isset($this->error['error_title'])) $this->error['error_title'] ='';
		if(!isset($this->error['error_testendpoint'])) $this->error['error_testendpoint'] ='';
		if(!isset($this->error['error_testpublickey'])) $this->error['error_testpublickey'] = '';
		if(!isset($this->error['error_testprivatekey'])) $this->error['error_testprivatekey'] = '';
		if(!isset($this->error['error_liveendpoint'])) $this->error['error_liveendpoint'] ='';
		if(!isset($this->error['error_livepublickey'])) $this->error['error_livepublickey'] = '';
		if(!isset($this->error['error_liveprivatekey'])) $this->error['error_liveprivatekey'] = '';
		if(!isset($this->error['error_status'])) $this->error['error_status'] = '';
		if(!isset($this->error['error_mode'])) $this->error['error_mode'] = '';

 		if ($this->error) {
			$data = array_merge($data,$this->error);
		} 
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
      		'separator' => ' :: '
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/nimbbl', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);
				
		$data['action'] = $this->url->link('extension/payment/nimbbl', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		if (isset($this->request->post['payment_nimbbl_title'])) {
			$data['payment_nimbbl_title'] = $this->request->post['payment_nimbbl_title'];
		} else {
			$data['payment_nimbbl_title'] = $this->config->get('payment_nimbbl_title');
		}
		
		if (isset($this->request->post['payment_nimbbl_mode'])) {
			$data['payment_nimbbl_mode'] = $this->request->post['payment_nimbbl_mode'];
		} else {
			$data['payment_nimbbl_mode'] = $this->config->get('payment_nimbbl_mode');
		}
		
		
		if (isset($this->request->post['payment_nimbbl_testendpoint'])) {
			$data['payment_nimbbl_testendpoint'] = $this->request->post['payment_nimbbl_testendpoint'];
		} else {
			$data['payment_nimbbl_testendpoint'] = $this->config->get('payment_nimbbl_testendpoint');
		}
		
		if (isset($this->request->post['payment_nimbbl_testpublickey'])) {
			$data['payment_nimbbl_testpublickey'] = $this->request->post['payment_nimbbl_testpublickey'];
		} else {
			$data['payment_nimbbl_testpublickey'] = $this->config->get('payment_nimbbl_testpublickey');
		}
		
		if (isset($this->request->post['payment_nimbbl_testprivatekey'])) {
			$data['payment_nimbbl_testprivatekey'] = $this->request->post['payment_nimbbl_testprivatekey'];
		} else {
			$data['payment_nimbbl_testprivatekey'] = $this->config->get('payment_nimbbl_testprivatekey');
		}
		
		if (isset($this->request->post['payment_nimbbl_liveendpoint'])) {
			$data['payment_nimbbl_liveendpoint'] = $this->request->post['payment_nimbbl_liveendpoint'];
		} else {
			$data['payment_nimbbl_liveendpoint'] = $this->config->get('payment_nimbbl_liveendpoint');
		}
		
		if (isset($this->request->post['payment_nimbbl_livepublickey'])) {
			$data['payment_nimbbl_livepublickey'] = $this->request->post['payment_nimbbl_livepublickey'];
		} else {
			$data['payment_nimbbl_livepublickey'] = $this->config->get('payment_nimbbl_livepublickey');
		}
		
		if (isset($this->request->post['payment_nimbbl_liveprivatekey'])) {
			$data['payment_nimbbl_liveprivatekey'] = $this->request->post['payment_nimbbl_liveprivatekey'];
		} else {
			$data['payment_nimbbl_liveprivatekey'] = $this->config->get('payment_nimbbl_liveprivatekey');
		}
		
		if (isset($this->request->post['payment_nimbbl_total'])) {
			$data['payment_nimbbl_total'] = $this->request->post['payment_nimbbl_total'];
		} else {
			$data['payment_nimbbl_total'] = $this->config->get('payment_nimbbl_total'); 
		} 
				
		if (isset($this->request->post['payment_nimbbl_order_status_id'])) {
			$data['payment_nimbbl_order_status_id'] = $this->request->post['payment_nimbbl_order_status_id'];
		} else {
			$data['payment_nimbbl_order_status_id'] = $this->config->get('payment_nimbbl_order_status_id'); 
		} 

		if (isset($this->request->post['payment_nimbbl_order_fail_status_id'])) {
			$data['payment_nimbbl_order_fail_status_id'] = $this->request->post['payment_nimbbl_order_fail_status_id'];
		} else {
			$data['payment_nimbbl_order_fail_status_id'] = $this->config->get('payment_nimbbl_order_fail_status_id'); 
		} 
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['payment_nimbbl_geo_zone_id'])) {
			$data['payment_nimbbl_geo_zone_id'] = $this->request->post['payment_nimbbl_geo_zone_id'];
		} else {
			$data['payment_nimbbl_geo_zone_id'] = $this->config->get('payment_nimbbl_geo_zone_id'); 
		} 
		
		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_nimbbl_status'])) {
			$data['payment_nimbbl_status'] = $this->request->post['payment_nimbbl_status'];
		} else {
			$data['payment_nimbbl_status'] = $this->config->get('payment_nimbbl_status');
		}
		
		if (isset($this->request->post['payment_nimbbl_sort_order'])) {
			$data['payment_nimbbl_sort_order'] = $this->request->post['payment_nimbbl_sort_order'];
		} else {
			$data['payment_nimbbl_sort_order'] = $this->config->get('payment_nimbbl_sort_order');
		}
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

				
		$this->response->setOutput($this->load->view('extension/payment/nimbbl', $data));
	}

	private function validate() {
		$flag=false;
		
		if (!$this->user->hasPermission('modify', 'extension/payment/nimbbl')) {
			$this->error['error_warning'] = $this->language->get('error_permission');
		}
		// parameters mandatory
		if (!$this->request->post['payment_nimbbl_title']) {
			$this->error['error_title'] = $this->language->get('error_title');
		}
		if($this->request->post['payment_nimbbl_mode'] == 'test') {
			if (!$this->request->post['payment_nimbbl_testendpoint']) {
				$this->error['error_testendpoint'] = $this->language->get('error_testendpoint');
			}			
			if (!$this->request->post['payment_nimbbl_testprivatekey']) {
				$this->error['error_testprivatekey'] = $this->language->get('error_testprivatekey');
			}
			if (!$this->request->post['payment_nimbbl_testpublickey']) {
				$this->error['error_testpublickey'] = $this->language->get('error_testpublickey');
			}
		}
		
		if($this->request->post['payment_nimbbl_mode'] == 'live') {
			if (!$this->request->post['payment_nimbbl_liveendpoint']) {
				$this->error['error_liveendpoint'] = $this->language->get('error_liveendpoint');
			}			
			if (!$this->request->post['payment_nimbbl_liveprivatekey']) {
				$this->error['error_liveprivatekey'] = $this->language->get('error_livepublickey');
			}
			if (!$this->request->post['payment_nimbbl_livepublickey']) {
				$this->error['error_livepublickey'] = $this->language->get('error_livepublickey');
			}
		}	
		
		return !$this->error;
	}
}
?>