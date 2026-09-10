<?php

namespace Opencart\Admin\Controller\Extension\Nimbbl\Payment;

/**
 * Nimbbl admin settings controller.
 */
class Nimbbl extends \Opencart\System\Engine\Controller {

    private array $error = [];

    public function index(): void {
        $this->load->language('extension/nimbbl/payment/nimbbl');
        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_nimbbl', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
            );
        }

        // ── Language strings ─────────────────────────────────────────────────
        $lang_keys = [
            'heading_title', 'text_edit', 'text_enabled', 'text_disabled',
            'text_test', 'text_live', 'text_yes', 'text_no', 'text_all_zones', 'text_none',
            'tab_general', 'tab_api_keys', 'tab_advanced', 'tab_webhooks',
            'entry_status', 'entry_title', 'entry_mode', 'entry_geo_zone',
            'entry_total', 'entry_sort_order',
            'entry_test_access_key', 'entry_test_secret_key',
            'entry_live_access_key', 'entry_live_secret_key',
            'entry_api_url', 'entry_checkout_host',
            'entry_order_status', 'entry_order_fail_status',
            'entry_debug', 'entry_encrypt_payload',
            'entry_webhook_url',
            'help_mode', 'help_total', 'help_api_url', 'help_checkout_host',
            'help_debug', 'help_encrypt_payload', 'help_webhook_url',
            'button_save', 'button_cancel',
        ];
        foreach ($lang_keys as $key) {
            $data[$key] = $this->language->get($key);
        }

        // ── Errors ───────────────────────────────────────────────────────────
        $data['error_warning']         = $this->error['warning']          ?? '';
        $data['error_test_access_key'] = $this->error['test_access_key']  ?? '';
        $data['error_test_secret_key'] = $this->error['test_secret_key']  ?? '';
        $data['error_live_access_key'] = $this->error['live_access_key']  ?? '';
        $data['error_live_secret_key'] = $this->error['live_secret_key']  ?? '';

        // ── Breadcrumbs ──────────────────────────────────────────────────────
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/nimbbl/payment/nimbbl', 'user_token=' . $this->session->data['user_token'], true),
            ],
        ];

        // ── Form action + back link ───────────────────────────────────────────
        $data['action'] = $this->url->link('extension/nimbbl/payment/nimbbl', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        // ── Config values (POST → saved config) ──────────────────────────────
        $fields = [
            'payment_nimbbl_status',
            'payment_nimbbl_mode',
            'payment_nimbbl_test_access_key',
            'payment_nimbbl_test_secret_key',
            'payment_nimbbl_live_access_key',
            'payment_nimbbl_live_secret_key',
            'payment_nimbbl_api_url',
            'payment_nimbbl_checkout_host',
            'payment_nimbbl_debug',
            'payment_nimbbl_encrypt_payload',
            'payment_nimbbl_order_status_id',
            'payment_nimbbl_order_fail_status_id',
            'payment_nimbbl_geo_zone_id',
            'payment_nimbbl_total',
            'payment_nimbbl_sort_order',
        ];
        foreach ($fields as $field) {
            $data[$field] = $this->request->post[$field] ?? $this->config->get($field);
        }

        // Defaults for new installs
        if ($data['payment_nimbbl_api_url'] === null) {
            $data['payment_nimbbl_api_url'] = 'https://api.nimbbl.tech';
        }
        if ($data['payment_nimbbl_checkout_host'] === null) {
            $data['payment_nimbbl_checkout_host'] = 'https://sonic.nimbbl.tech';
        }
        if ($data['payment_nimbbl_mode'] === null) {
            $data['payment_nimbbl_mode'] = 'test';
        }

        // ── Order statuses ───────────────────────────────────────────────────
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        // ── Geo zones ────────────────────────────────────────────────────────
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        // ── Webhook URL (read-only) ──────────────────────────────────────────
        $data['webhook_url'] = \HTTP_CATALOG . 'index.php?route=extension/nimbbl/payment/nimbbl.webhook';

        // ── Layout ───────────────────────────────────────────────────────────
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/nimbbl/payment/nimbbl', $data));
    }

    // ── Install / Uninstall ──────────────────────────────────────────────────

    public function install(): void {
        // No custom DB tables — settings stored in oc_setting
    }

    public function uninstall(): void {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('payment_nimbbl');
    }

    // ── Validation ───────────────────────────────────────────────────────────

    private function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/nimbbl/payment/nimbbl')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $mode = $this->request->post['payment_nimbbl_mode'] ?? '';
        if ($mode === 'test') {
            if (empty($this->request->post['payment_nimbbl_test_access_key'])) {
                $this->error['test_access_key'] = $this->language->get('error_test_access_key');
            }
            if (empty($this->request->post['payment_nimbbl_test_secret_key'])) {
                $this->error['test_secret_key'] = $this->language->get('error_test_secret_key');
            }
        } elseif ($mode === 'live') {
            if (empty($this->request->post['payment_nimbbl_live_access_key'])) {
                $this->error['live_access_key'] = $this->language->get('error_live_access_key');
            }
            if (empty($this->request->post['payment_nimbbl_live_secret_key'])) {
                $this->error['live_secret_key'] = $this->language->get('error_live_secret_key');
            }
        }

        return !$this->error;
    }
}
