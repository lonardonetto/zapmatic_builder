<?php
declare(strict_types=1);

namespace Zapmatic\Spec;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\TextUI\DefaultResultPrinter;
use Throwable;

/**
 * Printer que emite TAP (v13) no stdout para o motor onp-spec verify.
 *
 * Uma linha por teste, com o título e o @spec:AC-xxx lido do docblock do
 * método (mesma convenção de @covers). Skip vira "ok ... # SKIP" (nunca prova),
 * falha/erro/risky/incomplete viram "not ok".
 */
final class TapPrinter extends DefaultResultPrinter
{
    /** @var array<int,string> status por índice: pass|fail|skip|incomplete */
    private array $status = [];

    /** @var array<int,string> nome TAP por índice */
    private array $names = [];

    private int $index = 0;

    private function specTag(Test $test): string
    {
        if (!$test instanceof TestCase) {
            return '';
        }
        try {
            $ref = new \ReflectionMethod(get_class($test), $test->getName(false));
            $doc = $ref->getDocComment();
            if ($doc && preg_match('/@spec:(AC-\d{3,})/', $doc, $m)) {
                return ' @spec:' . $m[1];
            }
        } catch (\Throwable $e) {
            // método não refletível — segue sem tag
        }
        return '';
    }

    private function titleFor(Test $test): string
    {
        $name = get_class($test) . '::' . $test->getName(false);
        return $name . $this->specTag($test);
    }

    public function startTest(Test $test): void
    {
        $this->index++;
        $this->status[$this->index] = 'pass';
        $this->names[$this->index] = $this->titleFor($test);
    }

    public function endTest(Test $test, float $time): void
    {
        $i = $this->index;
        $name = $this->names[$i] ?? '';
        $status = $this->status[$i] ?? 'pass';

        switch ($status) {
            case 'skip':
                $this->write("ok {$i} - {$name} # SKIP\n");
                break;
            case 'incomplete':
                $this->write("not ok {$i} - {$name} # TODO\n");
                break;
            case 'fail':
                $this->write("not ok {$i} - {$name}\n");
                break;
            default:
                $this->write("ok {$i} - {$name}\n");
        }
    }

    public function addError(Test $test, Throwable $t, float $time): void
    {
        $this->status[$this->index] = 'fail';
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->status[$this->index] = 'fail';
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
        $this->status[$this->index] = 'fail';
    }

    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
        $this->status[$this->index] = 'incomplete';
    }

    public function addRiskyTest(Test $test, Throwable $t, float $time): void
    {
        $this->status[$this->index] = 'fail';
    }

    public function addSkippedTest(Test $test, Throwable $t, float $time): void
    {
        $this->status[$this->index] = 'skip';
    }

    public function startTestSuite(TestSuite $suite): void
    {
    }

    public function endTestSuite(TestSuite $suite): void
    {
    }

    public function printResult(TestResult $result): void
    {
        $this->write("1..{$result->count()}\n");
        $this->flush();
    }
}
