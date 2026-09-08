<?php

namespace Opencart\Catalog\Model\Extension\Nimbbl\Payment;

/**
 * Nimbbl catalog model — determines whether the payment method is available
 * for the current cart (geo zone + minimum order total gating).
 *
 * OC4 calls getMethods() (plural). Returns method descriptor array or [] if unavailable.
 */
class Nimbbl extends \Opencart\System\Engine\Model {

    /**
     * OC4 payment model interface — called by checkout/payment_method model.
     *
     * @param array $address   Customer billing address (zone_id, country_id, …)
     * @param float $total     Cart total in store currency
     * @return array           Method descriptor or []
     */
    public function getMethods(array $address = [], float $total = 0): array {
        $this->load->language('extension/nimbbl/payment/nimbbl');

        // ── Status gate ──────────────────────────────────────────────────────
        if (!$this->config->get('payment_nimbbl_status')) {
            return [];
        }

        // ── Minimum total gate ───────────────────────────────────────────────
        $minimum = (float) ($this->config->get('payment_nimbbl_total') ?? 0);
        if ($minimum > 0 && $total < $minimum) {
            return [];
        }

        // ── Geo zone gate ────────────────────────────────────────────────────
        $geo_zone_id = (int) ($this->config->get('payment_nimbbl_geo_zone_id') ?? 0);
        if ($geo_zone_id) {
            $query = $this->db->query(
                "SELECT * FROM `" . \DB_PREFIX . "zone_to_geo_zone`
                 WHERE `geo_zone_id` = '" . (int) $geo_zone_id . "'
                   AND `country_id`  = '" . (int) ($address['country_id'] ?? 0) . "'
                   AND (`zone_id`    = '0'
                        OR `zone_id` = '" . (int) ($address['zone_id'] ?? 0) . "')"
            );
            if (!$query->num_rows) {
                return [];
            }
        }

        $title      = $this->language->get('heading_title');
        $sort_order = (int) ($this->config->get('payment_nimbbl_sort_order') ?? 0);

        return [
            'code'       => 'nimbbl',
            'name'       => $title,
            'option'     => [
                'nimbbl' => [
                    'code' => 'nimbbl.nimbbl',
                    'name' => $title,
                ],
            ],
            'sort_order' => $sort_order,
        ];
    }
}
