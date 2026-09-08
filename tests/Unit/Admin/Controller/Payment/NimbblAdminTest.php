<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Controller\Payment;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Nimbbl admin controller.
 *
 * Focuses on:
 *  - install() idempotency  (delete-before-add prevents duplicate event rows on reinstall)
 *  - install() DDL          (CREATE TABLE IF NOT EXISTS oc_nimbbl_payment_meta)
 *  - uninstall()            (all three event codes deleted; meta table NOT dropped)
 *  - webhook URL format     (dot notation `nimbbl.webhook`, not slash `nimbbl/webhook`)
 *
 * Uses a named test-seam subclass (NimbblAdminSeam) with a real constructor so
 * tests can do `new NimbblAdminSeam($spy, $db)` — no Reflection.
 *
 * @covers \Opencart\Admin\Controller\Extension\Nimbbl\Payment\Nimbbl
 */
class NimbblAdminTest extends TestCase
{
    // ── Bootstrap (once per process) ─────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'oc_');
        }

        if (!class_exists(\Opencart\System\Engine\Controller::class)) {
            // Defined by catalog bootstrap if NimbblTest ran first; define if not.
            if (!class_exists(\Opencart\System\Engine\Controller::class)) {
                eval('namespace Opencart\\System\\Engine;
                      abstract class Controller {
                          public object $db;
                      }');
            }
        }

        if (!class_exists(\Opencart\Admin\Controller\Extension\Nimbbl\Payment\Nimbbl::class)) {
            require_once dirname(__DIR__, 5)
                . '/src/extension/nimbbl/admin/controller/payment/nimbbl.php';
        }

        // Test-seam: constructor pre-wires stubs so install()/uninstall() find
        // model_setting_event, model_setting_setting, db, and load without
        // needing the OC4 DI container.
        if (!class_exists(\NimbblAdminSeam::class)) {
            eval('
use Opencart\\Admin\\Controller\\Extension\\Nimbbl\\Payment\\Nimbbl as AdminBase;

class NimbblAdminSeam extends AdminBase {
    public function __construct(object $eventSpy, object $db, object $settingSpy) {
        $this->db                    = $db;
        $this->model_setting_event   = $eventSpy;
        $this->model_setting_setting = $settingSpy;
        // Stub load so load->model() is a no-op (model properties already injected).
        $this->load = new class { public function model(string $n): void {} };
    }
}
            ');
        }
    }

    // ── Stub factories ────────────────────────────────────────────────────────

    /** Event model spy — records addEvent() and deleteEventByCode() in call order. */
    private function makeEventSpy(): object
    {
        return new class {
            public array $added    = [];
            public array $deleted  = [];
            public array $timeline = [];

            public function addEvent(array $e): void {
                $this->added[]    = $e;
                $this->timeline[] = 'add:' . $e['code'];
            }

            public function deleteEventByCode(string $code): void {
                $this->deleted[]  = $code;
                $this->timeline[] = 'delete:' . $code;
            }
        };
    }

    /** Setting model stub. */
    private function makeSettingSpy(): object
    {
        return new class {
            public function deleteSetting(string $group): void {}
        };
    }

    /** DB spy — captures every SQL string. */
    private function makeDbSpy(): object
    {
        return new class {
            public array $queries = [];
            public function query(string $sql): object {
                $this->queries[] = $sql;
                $r = new \stdClass();
                $r->row = $r->rows = [];
                $r->num_rows = 0;
                return $r;
            }
            public function escape(string $v): string { return addslashes($v); }
        };
    }

    private function seam(?object $eventSpy = null, ?object $dbSpy = null): \NimbblAdminSeam
    {
        return new \NimbblAdminSeam(
            $eventSpy  ?? $this->makeEventSpy(),
            $dbSpy     ?? $this->makeDbSpy(),
            $this->makeSettingSpy()
        );
    }

    // ── install() — idempotency ───────────────────────────────────────────────

    /** @test */
    public function install_deletesAllThreeEventCodesBeforeAdding(): void
    {
        $spy  = $this->makeEventSpy();
        $ctrl = $this->seam($spy);

        $ctrl->install();

        $this->assertContains('nimbbl_cart_button',        $spy->deleted);
        $this->assertContains('nimbbl_checkout_intercept', $spy->deleted);
        $this->assertContains('nimbbl_admin_order_info',   $spy->deleted);
    }

    /** @test */
    public function install_addsExactlyThreeEvents(): void
    {
        $spy  = $this->makeEventSpy();
        $ctrl = $this->seam($spy);

        $ctrl->install();

        $this->assertCount(3, $spy->added);
        $codes = array_column($spy->added, 'code');
        $this->assertContains('nimbbl_cart_button',        $codes);
        $this->assertContains('nimbbl_checkout_intercept', $codes);
        $this->assertContains('nimbbl_admin_order_info',   $codes);
    }

    /** @test */
    public function install_allDeletionsHappenBeforeAnyAddition(): void
    {
        $spy  = $this->makeEventSpy();
        $ctrl = $this->seam($spy);

        $ctrl->install();

        $firstAdd   = PHP_INT_MAX;
        $lastDelete = -1;
        foreach ($spy->timeline as $i => $entry) {
            if (str_starts_with($entry, 'delete:')) { $lastDelete = $i; }
            if (str_starts_with($entry, 'add:'))    { $firstAdd   = min($firstAdd, $i); }
        }

        $this->assertGreaterThan(-1,          $lastDelete, 'At least one delete expected');
        $this->assertLessThan(PHP_INT_MAX,    $firstAdd,   'At least one add expected');
        $this->assertLessThan(
            $firstAdd, $lastDelete,
            'All deleteEventByCode() calls must precede the first addEvent() call'
        );
    }

    /** @test */
    public function install_isIdempotentOnReinstall(): void
    {
        $spy  = $this->makeEventSpy();
        $ctrl = $this->seam($spy);

        $ctrl->install();
        $ctrl->install(); // simulate reinstall

        // 3 deletes × 2 installs = 6, same for adds
        $this->assertCount(6, $spy->deleted, 'Each install() must delete all 3 event codes');
        $this->assertCount(6, $spy->added,   'Each install() must add all 3 events');
    }

    // ── install() — DDL ──────────────────────────────────────────────────────

    /** @test */
    public function install_issuesCreateTableIfNotExistsDdl(): void
    {
        $db   = $this->makeDbSpy();
        $ctrl = $this->seam(null, $db);

        $ctrl->install();

        $allSql = implode(' ', $db->queries);
        $this->assertStringContainsStringIgnoringCase('CREATE TABLE IF NOT EXISTS', $allSql);
        $this->assertStringContainsString('nimbbl_payment_meta', $allSql);
    }

    // ── uninstall() ───────────────────────────────────────────────────────────

    /** @test */
    public function uninstall_deletesAllThreeEventCodes(): void
    {
        $spy  = $this->makeEventSpy();
        $ctrl = $this->seam($spy);

        $ctrl->uninstall();

        $this->assertContains('nimbbl_cart_button',        $spy->deleted);
        $this->assertContains('nimbbl_checkout_intercept', $spy->deleted);
        $this->assertContains('nimbbl_admin_order_info',   $spy->deleted);
    }

    /** @test */
    public function uninstall_doesNotDropPaymentMetaTable(): void
    {
        $db   = $this->makeDbSpy();
        $ctrl = $this->seam(null, $db);

        $ctrl->uninstall();

        $allSql = implode(' ', $db->queries);
        $this->assertStringNotContainsStringIgnoringCase(
            'DROP TABLE',
            $allSql,
            'uninstall() must NOT drop oc_nimbbl_payment_meta — payment records must survive reinstall'
        );
    }

    // ── Webhook URL format ────────────────────────────────────────────────────

    /**
     * The webhook URL displayed in the admin panel must use OC4 dot notation so
     * the router invokes Nimbbl::webhook() on the existing class.
     * Slash notation would look for a non-existent NimbblWebhook class → HTTP 404.
     *
     * We match on the full route= string to avoid tripping on inline comments
     * that legitimately mention both forms for documentation purposes.
     *
     * @test
     */
    public function webhookUrl_routeStringUsesDotNotation(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 5)
            . '/src/extension/nimbbl/admin/controller/payment/nimbbl.php'
        );

        // Positive: the assigned URL must contain the dot form in the route param
        $this->assertMatchesRegularExpression(
            '/route=extension\/nimbbl\/payment\/nimbbl\.webhook/',
            $source,
            'Webhook URL must use dot notation (nimbbl.webhook)'
        );
    }

    /** @test */
    public function webhookUrl_routeStringDoesNotUseSlashNotation(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 5)
            . '/src/extension/nimbbl/admin/controller/payment/nimbbl.php'
        );

        // Negative: no route= that ends with /nimbbl/webhook (slash form → 404)
        $this->assertDoesNotMatchRegularExpression(
            '/route=extension\/nimbbl\/payment\/nimbbl\/webhook/',
            $source,
            'Webhook URL must NOT use slash notation — routes to a non-existent class (HTTP 404)'
        );
    }
}
