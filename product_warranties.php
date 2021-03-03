<?php
/* 
 $Project: Product Warranty $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.0.3.1 $ ($Revision: 159 $) 
*/

namespace extension\ka_extensions\product_warranty;

class ControllerProductWarranties extends \KaController {

	protected $errors = array();

	function onLoad() {
		if (!\KaGlobal::isKaInstalled('product_warranty')) {
			$this->response->redirect($this->url->link('account/login', '', true));
		}
		
		$this->language->load('extension/ka_extensions/product_warranty/common');
		$this->load->kamodel('extension/ka_extensions/product_warranty/product_warranties');
	}

	
	public function index() {

		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties', '', true);
	  		$this->response->redirect($this->url->link('account/login', '', true));
    	}

  		$params = array(
  			'sort'  => 'date_added', 
  			'order' => 'DESC', 
  			'page'  => '1',
  			'filter_cname'      => '',
  			'filter_pname'      => '',
  			'filter_pid'        => '',
  			'filter_pdate_from' => '',
  			'filter_pdate_to'   => '',
  			'filter_edate_from' => '',
  			'filter_edate_to'   => '',
  			'filter_status'     => '',
  			'filter_cname'      => '',
  		);
    	
  		$url_array = array();
  		foreach ($params as $k => $v) {
			if (isset($this->request->get[$k])) {
				$params[$k]    = $this->request->get[$k];
				$url_array[$k] = $k . '=' . $params[$k];
	  		}
	  	}
		$url = implode('&', $url_array);
  		$this->session->data['product_warranties_url'] = $url;
  		
		$this->document->setTitle($this->language->get('Product Warranty'));
		
      	$this->data['breadcrumbs'] = array();

      	$this->data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
      	); 

      	$this->data['breadcrumbs'][] = array(       	
        	'text'      => $this->language->get('Account'),
			'href'      => $this->url->link('account/account', '', true),
      	);
		
      	$this->data['breadcrumbs'][] = array(       	
        	'text'      => $this->language->get('Product Warranty'),
      	);
		
    	$this->data['heading_title'] = $this->language->get('Product Warranties');
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$this->data['product_warranties'] = array();
		
		$page_limit = 10;
		$params['start'] = ($params['page'] - 1) * $page_limit;
		$params['limit'] = $page_limit;
		
		$data = array(				  
			'sort'  => 'date_added',
			'order' => 'DESC',
			'start' => ($page - 1) * $page_limit,
			'limit' => $page_limit,
			'filter_customer_name' => $params['filter_cname'],
			'filter_product_name'  => $params['filter_pname'],
			'filter_product_id'    => $params['filter_pid'],

  			'filter_pdate_from' => $params['filter_pdate_from'],
  			'filter_pdate_to'   => $params['filter_pdate_to'],
  			'filter_edate_from' => $params['filter_edate_from'],
  			'filter_edate_to'   => $params['filter_edate_to'],
  			'filter_status'     => $params['filter_status'],
		);
		
		$pw_total = $this->kamodel_product_warranties->getProductWarrantiesTotal($data);
		$results = $this->kamodel_product_warranties->getProductWarranties($data);

		$this->data['is_customer_column'] = $this->config->get('ka_pw_show_cname_column');
		$this->data['is_filter_visible']  = $this->config->get('ka_pw_show_filter');
		
    	foreach ($results as &$result) {
    	
    		$pw = $this->kamodel_product_warranties->getProductWarranty($result['product_warranty_id']);
			$result = array_merge($result, $pw);
    	
			if ($result['purchase_date'] != '0000-00-00') {    	
				$result['purchase_date']  = date($this->language->get('date_format_short'), strtotime($result['purchase_date']));
			} else {
				$result['purchase_date']  = $this->language->get('n/a');
			}
			
			if ($result['status'] == 'pending') {
				$result['warranty_period'] = $this->language->get('Pending');
				$result['expires_on']      = $this->language->get('n/a');
			} elseif ($result['status'] == 'declined') {
				$result['warranty_period'] = $this->language->get('Declined');
				$result['expires_on']      = $this->language->get('n/a');
			} elseif ($result['status'] == 'approved') {
				if (!empty($result['expires_on'])) {
					$result['expires_on']      = date($this->language->get('date_format_short'), strtotime($result['expires_on']));
				} else {
					$result['expires_on'] = $this->language->get('n/a');
				}
				$result['warranty_period_text'] = $this->kamodel_product_warranties->formatWarrantyPeriod($result['warranty_period_id']);
			}

			if (empty($result['expires_on']) || $result['expires_on'] == '0000-00-00') {
				$result['expires_on'] = $this->language->get('n/a');
			}
			
			$result['view'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties/view', "product_warranty_id=$result[product_warranty_id]", true);
			$result['pdf'] = $this->url->link('extension/ka_extensions/product_warranty/pdf', "product_warranty_id=$result[product_warranty_id]", true);
		}
		$this->data['product_warranties'] = $results;
		$this->data['statuses'] = $this->kamodel_product_warranties->getStatuses();

		$pagination = new \KaPagination();
		$pagination->total = $pw_total;
		$pagination->page = $page;
		$pagination->limit = $page_limit; 
		$pagination->text = $this->language->get('text_pagination');
		
		$_url_array = $url_array;
		if (isset($_url_array['page'])) {
			unset($_url_array['page']);
		}
		$url = implode('&', $_url_array);
		if (!empty($url)) {
			$url = '&' . $url;
		}
		$pagination->url = $this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url . '&page={page}', true);
			
		$this->data['pagination'] = $pagination->render();
		$this->data['results'] = $pagination->getResults();
		$this->data['params'] = $params;
		
		$this->data['delete']       = $this->url->link('extension/ka_extensions/product_warranty/product_warranties/delete', '', true);
		$this->data['add_new_link'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties/save', '', true);

		$this->template = 'extension/ka_extensions/product_warranty/product_warranties';

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
		
		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'	
		);
						
		$this->response->setOutput($this->render());		
	}
	
	
  	public function save() {

  		$this->document->setTitle($this->language->get('Product Warranty'));
  	
  		$err = array();
		if (!$this->customer->isLogged() && $this->config->get('ka_pw_allow_not_logged') != 'Y') {
			$this->addTopMessage("Please log in to register a product warranty", 'E');
			$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties/access_denied', '', true));
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

			$this->kamodel_product_warranties->savePostedFiles();

			if (!empty($this->request->post['product_warranty_id'])) {
				$this->addTopMessage("You are not allowed to edit these records", 'E');
				$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties/access_denied', '', true));
			}
			
			if ($this->validateForm()) {

 				$data = $this->request->post;
 				
 				$err  = '';
	      		$warranty_id = $this->kamodel_product_warranties->saveProductWarranty($data, $err);
				
				$url = '';
				if (!empty($this->session->data['product_warranties_url'])) {
					$url = '&' . $this->session->data['product_warranties_url'];
				}

	      		if (empty($err)) {
		      		$this->addTopMessage($this->language->get("Record has been added successfully."));
		      		
					if ($this->customer->isLogged()) {
						if (!empty($warranty_id) && !empty($this->request->post['print_flag'])) {
							$this->load->kamodel('extension/ka_extensions/product_warranty/pdf');
							$this->kamodel_pdf->generatePdf($warranty_id);
							return;
						} else {
							$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true));
						}
					} else {
						$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties/save', '', true));
					}
		      		
		      	} else {
		      		$this->addTopMessage($err, 'E');
				}
			}

		} else {
			$this->kamodel_product_warranties->clearPostedFiles();
		}		
	
    	$this->getForm();
  	}
  	
  	
  	public function delete() {

		$url = '';
		if (!empty($this->session->data['product_warranties_url'])) {
			$url = '&' . $this->session->data['product_warranties_url'];
		}

		if (!$this->customer->isLogged()) {
			$this->response->redirect($this->url->link('common/home', $url, true));
		}
  	
		$error = false;  			
    	if (isset($this->request->post['product_warranty_id'])) {
			if (!$this->kamodel_product_warranties->deleteProductWarranty($this->request->post['product_warranty_id'])) {
				$this->addTopMessage($this->kamodel_product_warranties->getLastError(), 'E');
				$error = true;
			}
		}

		if (!$error) {
			$this->addTopMessage("Operation is completed successfully");
		}
		$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true));
  	}


  	public function view() {

  		$fields = $this->kamodel_product_warranties->getFields('V');
  	
		$url = '';
		if (!empty($this->session->data['product_warranties_url'])) {
			$url = '&' . $this->session->data['product_warranties_url'];
		}
		
		if (empty($this->request->get['product_warranty_id'])) {
			$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true));
		}
		  	
  		$product_warranty = $this->kamodel_product_warranties->getProductWarranty($this->request->get['product_warranty_id']);

		if (empty($product_warranty)) {
			$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true));
		}
  		
  		if ($product_warranty['purchase_date'] == '0000-00-00') {
  			$product_warranty['purchase_date'] = $this->language->get('n/a');
  		} else {
	  		$product_warranty['purchase_date'] = date($this->language->get('date_format_short'), strtotime($product_warranty['purchase_date']));
	  	}

	  	if ($product_warranty['date_added'] == '0000-00-00') {
	  		$product_warranty['date_added'] = $this->language->get('n/a');
	  	} else {
	  		$product_warranty['date_added'] = date($this->language->get('date_format_short'), strtotime($product_warranty['date_added']));
	  	}
		
  		if ($product_warranty['status'] == 'approved') {
	  		$product_warranty['expires_on']           = date($this->language->get('date_format_short'), strtotime($product_warranty['expires_on']));
	  		$product_warranty['warranty_period_text'] = $this->kamodel_product_warranties->formatWarrantyPeriod($product_warranty['warranty_period_id']);
		}
		
		if (empty($product_warranty['expires_on']) || $product_warranty['expires_on'] == '0000-00-00') {
			$product_warranty['expires_on'] = $this->language->get('n/a');
		}

		$this->data['product_warranty'] = $product_warranty;

  		$this->data['breadcrumbs'] = array();
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', '', true),
   		);

      	$this->data['breadcrumbs'][] = array(       	
        	'text'      => $this->language->get('Account'),
			'href'      => $this->url->link('account/account', '', true),
      	);
   		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('Product Warranties'),
			'href'      => $this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true),
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $product_warranty['product_name'],
   		);
   				
		$this->data['cancel'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true);

		$this->data['fields']   = $fields;
		$this->data['statuses'] = $this->kamodel_product_warranties->getStatuses();
		
		$this->template = 'extension/ka_extensions/product_warranty/product_warranty_view';

		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'	
		);
						
		$this->response->setOutput($this->render());	
  	}

  	  	  	
  	protected function getForm() {

   		$fields = $this->kamodel_product_warranties->getFields('N');

		$url = '';
		if (!empty($this->session->data['product_warranties_url'])) {
			$url = '&' . $this->session->data['product_warranties_url'];
		}

		// get product warranty array
		//		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
		
			$org_product_warranty = array();
			if (!empty($this->request->post['product_warranty_id'])) {
				$org_product_warranty = $this->kamodel_product_warranties->getProductWarranty($this->request->post['product_warranty_id']);
			}
		
			$product_warranty = $this->request->post;
			$this->data['errors'] = $this->errors;
			
			foreach ($fields as $fld) {
				$code  = $fld['code_name'];
				$value = '';

				if ($fld['field_type'] == 'file') {
					if (!empty($product_warranty[$code])) {
						$file = $this->kamodel_product_warranties->getPostedFile($code, $product_warranty[$code]);
						if ($file) {
							$product_warranty[$code] = $file;
						} else {
							$product_warranty[$code] = $org_product_warranty[$code];
						}
					}
				}
			}
			
		} else {
			if (!empty($this->request->get['product_warranty_id'])) {
				$product_warranty = $this->kamodel_product_warranties->getProductWarranty($this->request->get['product_warranty_id']);
			} else {
				$product_warranty = array(
					'product_name'        => '',
					'product_id'          => 0,
					'warranty_period_id'  => 0,
					'status'              => 'pending'
				);
				if ($this->customer->isLogged()) {
					$product_warranty['dealer_name']  = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
					$product_warranty['customer_phone'] = $this->customer->getTelephone();
					$product_warranty['dealer_email'] = $this->customer->getEmail();
					$product_warranty['customer_email'] = $this->customer->getEmail();
				}				
			}
		}

		$warranty_product = array();
		if (!empty($product_warranty['product_id'])) {
			$warranty_product = $this->kamodel_product_warranties->getWarrantyProduct($product_warranty['product_id']);
			$product_warranty['product_name'] = $warranty_product['name'];
		} else {
			$product_warranty['product_id'] = 0;
		}
				
		$this->data['product_warranty']  = $product_warranty;
		$this->data['wp_selector'] = $this->config->get('ka_pw_wp_selector');
		if ($this->data['wp_selector'] != 'A') {
			$this->data['warranty_products'] = $this->kamodel_product_warranties->getWarrantyProducts();
		}

		$this->data['fields'] = $fields;
		
/* disabled by #m23236
		if ($this->customer->isLogged()) {
			$this->data['allow_print'] = true;
		}
*/		
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
				
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', '', true),    		
   		);

      	$this->data['breadcrumbs'][] = array(       	
        	'text'      => $this->language->get('Account'),
			'href'      => $this->url->link('account/account', '', true),
      	);
   		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('Product Warranties'),
			'href'      => $this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true),
   		);

   		if (!empty($product_warranty['product_name'])) {
			$this->data['breadcrumbs'][] = array(
				'text'      => $product_warranty['product_name'],
			);
		}

		$this->data['action'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties/save', '', true);
		$this->data['cancel'] = $this->url->link('extension/ka_extensions/product_warranty/product_warranties', $url, true);

		$this->template = 'extension/ka_extensions/product_warranty/product_warranty_form';
		
		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'	
		);
						
		$this->response->setOutput($this->render());	
  	}

  	
	protected function validateForm() {

		$fields = $this->kamodel_product_warranties->getFields();

		if ($this->kamodel_product_warranties->isProductIdMandatory()) {
			if (empty($this->request->post['product_id'])) {
				$this->errors['product_name'] = $this->language->get('Product must be selected');
			}
		}
		
      	if (!empty($fields)) {
      		foreach ($fields as $field) {
      		
      			$name = $field['code_name'];
      			
      			if ($field['required']) {
      				$is_empty = true;
      			 	if ($name == 'product_name') {
      			 		if (!empty($this->request->post['product_name']) || !empty($this->request->post['product_id'])) {
      			 			$is_empty = false;
      			 		}
      			 	} elseif (!empty($this->request->post[$name])) {
      			 		$is_empty = false;
      			 	}
      			 		
      			 	if ($is_empty) {
						$this->errors[$name] = $this->language->get("Field cannot be empty");
					}
      			}

      			if (empty($field['required']) && empty($this->request->post[$name])) {
					continue;
				}

      			if (empty($this->errors)) {
	      			if ($name == 'serial_number' && $this->kamodel_product_warranties->isWarrantyRegistered($this->request->post)) {
	      			
						$this->errors[$name] = $this->language->get("Warranty number is already registered in the store.");
						
	      			} elseif ($name == 'purchase_date') {
  					
  						$date = trim($this->request->post[$name]);
  						if (!preg_match('/(\d{4})-(\d{2})-(\d{2})/', $date, $matches)) {
  							$this->errors[$name] = $this->language->get('Wrong date format');
  							continue;
  						}
  						
  						$date = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
  						$min_date = strtotime("-100 years");
  						$max_date = strtotime("+10 years");
  						if ($date < $min_date || $date > $max_date) {
  							$this->errors[$name] = $this->language->get('Date is not real');
  						}
  					}
	      		}
      		}
      	}
      	if (!empty($this->errors)) {
      		$this->addTopMessage("Invalid form data. Please verify all fields and submit it again.", 'E');
      		return false;
      	}

    	return true;		
	}


	public function access_denied() {
		$this->language->load('ka_extensions/ka_extensions');
		
		$this->document->setTitle($this->language->get('Access Denied'));
		
		$this->data['breadcrumbs'] = array();
      	$this->data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),
      	);		
		
		if (isset($this->request->get['route'])) {
       		$this->data['breadcrumbs'][] = array(
        		'text'      => $this->language->get('Access Denied'),
      		);	   	
		}
		
		$this->data['heading_title'] = $this->language->get('Access Denied');
		
		$this->data['button_continue'] = $this->language->get('button_continue');
		$this->data['continue'] = $this->url->link('common/home');

		$this->template = 'extension/ka_extensions/product_warranty/access_denied';
		
		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		);
		
		$this->response->setOutput($this->render());
  	}		

  	
	public function download() {

		$pw = null;
 		if (isset($this->request->get['product_warranty_id'])) {		
			$pw = $this->kamodel_product_warranties->getProductWarranty($this->request->get['product_warranty_id']);
		}
		
		// check access to the download content
		//
		$customer_id = $this->customer->getId();
		if (empty($customer_id) || $customer_id != $pw['customer_id']) {
			$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties/access_denied', '', true));
		}

		if (empty($this->request->get['code'])) {
			$code = 'file_proof';
		} else {
			$code = $this->request->get['code'];
		}
		
		if (empty($pw[$code])) {
			$this->response->redirect($this->url->link('extension/ka_extensions/product_warranty/product_warranties', true));
		}

		$file = $this->kamodel_product_warranties->getFileDir() . $pw[$code]['file'];

		if (!headers_sent()) {
			if (file_exists($file)) {
				header('Content-Type: application/octet-stream');
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename="' . $pw[$code]['name'] . '"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				
				readfile($file, 'rb');
									
			} else {
				exit('Error: Could not find file ' . $file . '!');
			}
		} else {
			exit('Error: Headers already sent out!');
		}
		exit;
	}
	

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->kamodel_product_warranties->getWarrantyProducts($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'product_id' => $result['product_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}

class_alias(__NAMESPACE__ . '\ControllerProductWarranties', '\ControllerExtensionKaExtensionsProductWarrantyProductWarranties');