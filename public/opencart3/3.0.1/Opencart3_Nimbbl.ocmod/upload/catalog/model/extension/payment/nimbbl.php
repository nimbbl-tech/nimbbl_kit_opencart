<?php 
class ModelExtensionPaymentNimbbl extends Model {
  	public function getMethod($address, $total) {
		$this->load->language('extension/payment/nimbbl');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_nimbbl_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if ($this->config->get('payment_nimbbl_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_nimbbl_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true; 
		} else {
			$status = false;
		}	
		
		$method_data = array();
		$title = $this->config->get('payment_nimbbl_title').'<img src="' . HTTP_SERVER . 'catalog/view/theme/default/image/nimbbllogo.png" />';
		if(!$title)
			$title = $this->language->get('text_title');
		
		if ($status) {  
      		$method_data = array( 
        		'code'       => 'nimbbl',
        		'title'      => $title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_nimbbl_sort_order')
      		);
    	}
   
    	return $method_data;
  	}	
}
?>