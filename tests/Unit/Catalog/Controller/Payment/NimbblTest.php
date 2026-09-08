<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Controller\Payment;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Nimbbl catalog controller helper methods.
 *
 * Protected helpers are exercised via a named test-seam subclass (NimbblCatalogSeam)
 * that promotes them to public. The seam is instantiated with `new NimbblCatalogSeam()`
 * — no Reflection involved.
 *
 * OC4 dependencies (db, config) are replaced with lightweight anonymous stubs.
 *
 * @covers \Opencart\Catalog\Controller\Extension\Nimbbl\Payment\Nimbbl
 */
class NimbblTest extends TestCase
{
    // ── Bootstrap (once per process) ─────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'oc_');
        }

        // Minimal OC4 base stub — no constructor, just the two public properties
        // the catalog controller reads.
        if (!class_exists(\Opencart\System\Engine\Controller::class)) {
            eval('namespace Opencart\\System\\Engine;
                  abstract class Controller {
                      public object $db;
                      public object $config;
                  }');
        }

        if (!class_exists(\Opencart\Catalog\Controller\Extension\Nimbbl\Payment\Nimbbl::class)) {
            require_once dirname(__DIR__, 5)
                . '/src/extension/nimbbl/catalog/controller/payment/nimbbl.php';
        }

        // Test-seam: promotes the four protected helpers to public and adds a
        // convenience constructor so we can do `new NimbblCatalogSeam($db, $cfg)`.
        if (!class_exists(\NimbblCatalogSeam::class)) {
            eval('
use Opencart\\Catalog\\Controller\\Extension\\Nimbbl\\Payment\\Nimbbl as NimbblBase;

class NimbblCatalogSeam extends NimbblBase {
    public function __construct(object $db, object $config) {
        $this->db     = $db;
        $this->config = $config;
    }

    // Promote protected helpers to public so tests can call them directly.
    public function isCodPaymentMode(string $mode): bool        { return parent::isCodPaymentMode($mode); }
    public function saveNimbblMeta(int $id, array $meta): void  { parent::saveNimbblMeta($id, $meta); }
    public function getNimbblMeta(int $id, string $key): string { return parent::getNimbblMeta($id, $key); }
    public function ensureNimbblMetaTable(): void               { parent::ensureNimbblMetaTable(); }
}
            ');
        }
    }

    // ── Stub factories ────────────────────────────────────────────────────────

    /**
     * db stub — query() captures every SQL string; escape() is pass-through.
     * Read $db->queries after calling the method under test.
     */
    private function makeDb(?array $row = null, int $num_rows = 0): object
    {
        return new class ($row ?? [], $num_rows) {
            private array $row;
            private int   $num;
            public  array $queries = [];

            public function __construct(array $r, int $n) { $this->row = $r; $this->num = $n; }

            public function query(string $sql): object {
                $this->queries[] = $sql;
                $r = new \stdClass();
                $r->row      = $this->row;
                $r->rows     = $this->num > 0 ? [$this->row] : [];
                $r->num_rows = $this->num;
                return $r;
            }

            public function escape(string $v): string { return addslashes($v); }
        };
    }

    private function makeConfig(array $map = []): object
    {
        return new class ($map) {
            private array $m;
            public function __construct(array $m) { $this->m = $m; }
            public function get(string $key): mixed { return $this->m[$key] ?? null; }
        };
    }

    private function seam(array $configMap = [], ?object $db = null): \NimbblCatalogSeam
    {
        return new \NimbblCatalogSeam(
            $db ?? $this->makeDb(),
            $this->makeConfig($configMap)
        );
    }

    // ── isCodPaymentMode() ───────────────────────────────────────────────────

    /** @test */
    public function isCodPaymentMode_exactMatch(): void
    {
        $this->assertTrue($this->seam()->isCodPaymentMode('Cash on Delivery'));
    }

    /** @test */
    public function isCodPaymentMode_caseInsensitive(): void
    {
        $s = $this->seam();
        $this->assertTrue($s->isCodPaymentMode('CASH ON DELIVERY'));
        $this->assertTrue($s->isCodPaymentMode('cash on delivery'));
        $this->assertTrue($s->isCodPaymentMode('Cash On Delivery'));
    }

    /** @test */
    public function isCodPaymentMode_stripsWhitespace(): void
    {
        $s = $this->seam();
        $this->assertTrue($s->isCodPaymentMode('  Cash on Delivery  '));
        $this->assertTrue($s->isCodPaymentMode("\tCash on Delivery\n"));
    }

    /** @test */
    public function isCodPaymentMode_returnsFalseForOnlinePayments(): void
    {
        $s = $this->seam();
        $this->assertFalse($s->isCodPaymentMode('UPI'));
        $this->assertFalse($s->isCodPaymentMode('Credit Card'));
        $this->assertFalse($s->isCodPaymentMode(''));
        $this->assertFalse($s->isCodPaymentMode('cod'));
    }

    // ── ensureNimbblMetaTable() ──────────────────────────────────────────────

    /** @test */
    public function ensureNimbblMetaTable_runsCreateTableOnFirstCall(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        $s->ensureNimbblMetaTable();

        $this->assertCount(1, $db->queries, 'Exactly one DDL statement expected');
        $this->assertStringContainsStringIgnoringCase('CREATE TABLE IF NOT EXISTS', $db->queries[0]);
        $this->assertStringContainsString('nimbbl_payment_meta', $db->queries[0]);
    }

    /** @test */
    public function ensureNimbblMetaTable_secondCallNeverIssuesAdditionalDdl(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        // The static flag may already be set by the previous test in this process
        // (PHP static variables persist for the whole process).  That itself proves
        // idempotency is working — 0 queries on a "first" call in this test means
        // the global flag was already set and the guard fired correctly.
        $s->ensureNimbblMetaTable();
        $countAfterFirst = count($db->queries); // 0 or 1 depending on prior tests

        $s->ensureNimbblMetaTable(); // second call must add nothing regardless

        $this->assertCount(
            $countAfterFirst,
            $db->queries,
            'A repeated call to ensureNimbblMetaTable() must not issue any additional DDL query'
        );
    }

    // ── saveNimbblMeta() ─────────────────────────────────────────────────────

    /** @test */
    public function saveNimbblMeta_insertsWithAllowedColumns(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        $s->saveNimbblMeta(42, [
            'nimbbl_order_id'       => 'NIM-100',
            'nimbbl_transaction_id' => 'TXN-200',
            'payment_mode'          => 'UPI',
            'payment_state'         => 'paid',
        ]);

        $allSql = implode(' ', $db->queries);
        $this->assertStringContainsString('INSERT INTO', $allSql);
        $this->assertStringContainsString('nimbbl_payment_meta', $allSql);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $allSql);
        $this->assertStringContainsString('NIM-100', $allSql);
        $this->assertStringContainsString('TXN-200', $allSql);
        $this->assertStringContainsString('UPI', $allSql);
        $this->assertStringContainsString('paid', $allSql);
    }

    /** @test */
    public function saveNimbblMeta_stripsUnrecognisedColumns(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        $s->saveNimbblMeta(42, [
            'nimbbl_order_id' => 'NIM-100',
            'injected_col'    => 'DROP TABLE oc_order',
        ]);

        $allSql = implode(' ', $db->queries);
        $this->assertStringNotContainsString('injected_col', $allSql);
        $this->assertStringNotContainsString('DROP TABLE', $allSql);
        $this->assertStringContainsString('NIM-100', $allSql);
    }

    /** @test */
    public function saveNimbblMeta_doesNothingWhenOrderIdIsZero(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        $s->saveNimbblMeta(0, ['nimbbl_order_id' => 'NIM-100']);

        $this->assertEmpty($db->queries, 'No queries must be issued for order_id = 0');
    }

    /** @test */
    public function saveNimbblMeta_doesNothingWhenMetaArrayIsEmpty(): void
    {
        $db = $this->makeDb();
        $s  = $this->seam([], $db);

        $s->saveNimbblMeta(42, []);

        $this->assertEmpty($db->queries);
    }

    // ── getNimbblMeta() ──────────────────────────────────────────────────────

    /** @test */
    public function getNimbblMeta_returnsStoredValue(): void
    {
        $db = $this->makeDb(['nimbbl_transaction_id' => 'TXN-999'], 1);
        $s  = $this->seam([], $db);

        $this->assertSame('TXN-999', $s->getNimbblMeta(42, 'nimbbl_transaction_id'));
    }

    /** @test */
    public function getNimbblMeta_returnsEmptyStringWhenRowMissing(): void
    {
        $db = $this->makeDb(null, 0);
        $s  = $this->seam([], $db);

        $this->assertSame('', $s->getNimbblMeta(99, 'nimbbl_order_id'));
    }

    /** @test */
    public function getNimbblMeta_returnsEmptyStringForDisallowedColumn(): void
    {
        $db = $this->makeDb(['anything' => 'secret'], 1);
        $s  = $this->seam([], $db);

        $this->assertSame('', $s->getNimbblMeta(42, 'password'));
        $this->assertEmpty($db->queries, 'Disallowed column must not issue a DB query');
    }

    /** @test */
    public function getNimbblMeta_returnsEmptyStringWhenOrderIdIsZero(): void
    {
        $db = $this->makeDb(['nimbbl_order_id' => 'X'], 1);
        $s  = $this->seam([], $db);

        $this->assertSame('', $s->getNimbblMeta(0, 'nimbbl_order_id'));
        $this->assertEmpty($db->queries);
    }

    // ── createCartFlowOrder() ────────────────────────────────────────────────

    /**
     * Register the cart-flow test seam once per process.
     * NimbblCartFlowSeam extends NimbblBase and surfaces:
     *   - createCartFlowOrder() (public proxy)
     *   - $session, $cart, $request, $model_checkout_order as settable properties
     */
    private static function ensureCartFlowSeam(): void
    {
        if (class_exists(\NimbblCartFlowSeam::class)) {
            return;
        }

        eval('
use Opencart\\Catalog\\Controller\\Extension\\Nimbbl\\Payment\\Nimbbl as NimbblBase2;

class NimbblCartFlowSeam extends NimbblBase2 {
    public object  $session;
    public object  $cart;
    public object  $request;
    public ?object $model_checkout_order = null;

    public function __construct(object $db, object $config) {
        $this->db     = $db;
        $this->config = $config;
        $s = new \\stdClass(); $s->data = [];
        $this->session = $s;
        $this->request = new class { public array $server = []; };
        $this->cart = new class {
            public function getProducts(): array { return []; }
            public function getTotal(): float    { return 0.0; }
            public function hasProducts(): bool  { return false; }
        };
    }

    public function createCartFlowOrder(array $r): int { return parent::createCartFlowOrder($r); }
    public function ensureNimbblMetaTable(): void       { parent::ensureNimbblMetaTable(); }
    public function saveNimbblMeta(int $id, array $m): void { parent::saveNimbblMeta($id, $m); }
}
        ');
    }

    /**
     * DB stub whose query() returns different results based on SQL content fragments.
     *
     * @param array<string,array> $patterns  map of SQL fragment → ['num_rows', 'row', 'rows']
     */
    private function makeSmartDb(array $patterns = [], int $lastId = 99): object
    {
        return new class ($patterns, $lastId) {
            private array $pat;
            private int   $lid;
            public  array $queries = [];

            public function __construct(array $p, int $l) { $this->pat = $p; $this->lid = $l; }

            public function query(string $sql): object {
                $this->queries[] = $sql;
                $matched = null;
                foreach ($this->pat as $fragment => $data) {
                    if (str_contains($sql, $fragment)) { $matched = $data; break; }
                }
                $r = new \stdClass();
                $r->num_rows = $matched['num_rows'] ?? 0;
                $r->row      = $matched['row']      ?? [];
                $r->rows     = $matched['rows']      ?? [];
                return $r;
            }

            public function getLastId(): int           { return $this->lid; }
            public function escape(string $v): string  { return addslashes($v); }
        };
    }

    /** Stub for $model_checkout_order with recordable calls. */
    private function makeOrderModel(int $addOrderReturn = 99, ?array $getOrderReturn = null): object
    {
        return new class ($addOrderReturn, $getOrderReturn) {
            public  array  $addOrderCalls   = [];
            public  array  $addHistoryCalls = [];
            private int    $returnId;
            private ?array $orderData;

            public function __construct(int $id, ?array $od) { $this->returnId = $id; $this->orderData = $od; }

            public function addOrder(array $data): int {
                $this->addOrderCalls[] = $data;
                return $this->returnId;
            }

            public function addHistory(int $oid, int $sid, string $comment, bool $notify): void {
                $this->addHistoryCalls[] = compact('oid', 'sid', 'comment', 'notify');
            }

            public function getOrder(int $oid): ?array { return $this->orderData; }
        };
    }

    /** Build a NimbblCartFlowSeam with given config and db. */
    private function cfSeam(array $config = [], ?object $db = null, ?object $orderModel = null): \NimbblCartFlowSeam
    {
        self::ensureCartFlowSeam();
        $db   = $db ?? $this->makeSmartDb();
        $seam = new \NimbblCartFlowSeam($db, $this->makeConfig($config));
        if ($orderModel !== null) {
            $seam->model_checkout_order = $orderModel;
        }
        return $seam;
    }

    /** @test */
    public function createCartFlowOrder_idempotent_returnsExistingOrderId(): void
    {
        // DB says nimbbl_order_id=NIM-1 already maps to OC order 42
        $db = $this->makeSmartDb([
            'nimbbl_payment_meta' => ['num_rows' => 1, 'row' => ['order_id' => '42']],
        ]);
        $model = $this->makeOrderModel();
        $s     = $this->cfSeam([], $db, $model);

        $result = $s->createCartFlowOrder(['order_id' => 'NIM-1', 'payment_mode' => 'UPI']);

        $this->assertSame(42, $result, 'Must return existing OC order_id');
        $this->assertEmpty($model->addOrderCalls, 'addOrder must NOT be called when idempotency guard fires');
    }

    /** @test */
    public function createCartFlowOrder_createsOcOrderForOnlinePayment(): void
    {
        // DB: no existing meta row; currency lookup returns INR
        $db = $this->makeSmartDb([
            'nimbbl_payment_meta' => ['num_rows' => 0],
            'oc_currency'         => ['num_rows' => 1, 'row' => ['currency_id' => 3, 'value' => '1.00']],
        ], 77 /* lastInsertId unused — addOrder handled by model */);

        $model = $this->makeOrderModel(77);
        $s     = $this->cfSeam(
            ['payment_nimbbl_order_status_id' => 5, 'config_language_id' => 1],
            $db, $model
        );
        // Provide minimal session customer
        $s->session->data['customer'] = [
            'customer_id' => 1, 'customer_group_id' => 1,
            'firstname' => 'Test', 'lastname' => 'User',
            'email' => 'test@example.com', 'telephone' => '9999999999',
        ];

        $result = $s->createCartFlowOrder([
            'order_id'       => 'NIM-2',
            'transaction_id' => 'TXN-2',
            'invoice_id'     => 'cart_abc123',
            'payment_mode'   => 'UPI',
        ]);

        $this->assertSame(77, $result);
        $this->assertCount(1, $model->addOrderCalls, 'addOrder must be called exactly once');
        $this->assertCount(1, $model->addHistoryCalls, 'addHistory must be called once for initial status');
        $this->assertSame(5, $model->addHistoryCalls[0]['sid'], 'Online payment → order_status_id from config');

        // saveNimbblMeta should have fired an INSERT
        $allSql = implode(' ', $db->queries);
        $this->assertStringContainsString('INSERT INTO', $allSql);
        $this->assertStringContainsString('NIM-2', $allSql);
    }

    /** @test */
    public function createCartFlowOrder_usesCodStatusForCodPaymentMode(): void
    {
        $db = $this->makeSmartDb([
            'nimbbl_payment_meta' => ['num_rows' => 0],
            'oc_currency'         => ['num_rows' => 1, 'row' => ['currency_id' => 3, 'value' => '1.00']],
        ]);

        $model = $this->makeOrderModel(88);
        $s     = $this->cfSeam([
            'payment_nimbbl_cod_order_status_id' => 7,
            'payment_nimbbl_order_status_id'     => 5,
            'config_language_id'                 => 1,
        ], $db, $model);

        $s->createCartFlowOrder([
            'order_id'     => 'NIM-3',
            'payment_mode' => 'Cash on Delivery',
        ]);

        $this->assertCount(1, $model->addHistoryCalls);
        $this->assertSame(7, $model->addHistoryCalls[0]['sid'], 'COD mode must use cod_order_status_id');
    }

    /** @test */
    public function createCartFlowOrder_fallsBackToPendingWhenStatusConfigIsZero(): void
    {
        $db = $this->makeSmartDb([
            'nimbbl_payment_meta' => ['num_rows' => 0],
            'oc_currency'         => ['num_rows' => 0],  // no currency row → defaults
        ]);

        $model = $this->makeOrderModel(55);
        // Both status configs return 0 → method must use 1 (Pending)
        $s = $this->cfSeam([
            'payment_nimbbl_order_status_id'     => 0,
            'payment_nimbbl_cod_order_status_id' => 0,
        ], $db, $model);

        $s->createCartFlowOrder(['order_id' => 'NIM-4', 'payment_mode' => 'UPI']);

        $this->assertCount(1, $model->addHistoryCalls);
        $this->assertSame(1, $model->addHistoryCalls[0]['sid'], 'Must fall back to status 1 (Pending) when config returns 0');
    }

    /** @test */
    public function createCartFlowOrder_returnsZeroWhenAddOrderFails(): void
    {
        $db = $this->makeSmartDb([
            'nimbbl_payment_meta' => ['num_rows' => 0],
            'oc_currency'         => ['num_rows' => 0],
        ]);

        $model = $this->makeOrderModel(0); // addOrder returns 0 → failure
        $s     = $this->cfSeam(['payment_nimbbl_order_status_id' => 5], $db, $model);

        $result = $s->createCartFlowOrder(['order_id' => 'NIM-5', 'payment_mode' => 'UPI']);

        $this->assertSame(0, $result, 'Must return 0 when addOrder() returns 0');
        $this->assertEmpty($model->addHistoryCalls, 'addHistory must not be called when addOrder fails');
    }

    /** @test */
    public function createCartFlowOrder_skipsIdempotencyCheckWhenNoNimbblOrderId(): void
    {
        $db = $this->makeSmartDb([
            'oc_currency' => ['num_rows' => 0],
        ]);

        $model = $this->makeOrderModel(66);
        $s     = $this->cfSeam(['payment_nimbbl_order_status_id' => 5], $db, $model);

        $result = $s->createCartFlowOrder(['payment_mode' => 'UPI']); // no order_id key

        // No idempotency SELECT should appear in queries
        $metaQueries = array_filter($db->queries, static fn($q) => str_contains($q, 'WHERE `nimbbl_order_id`'));
        $this->assertEmpty($metaQueries, 'No idempotency SELECT when nimbbl_order_id is absent');
        $this->assertSame(66, $result);
    }
}
