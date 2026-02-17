<?php

namespace Codeception\Extension;

use Codeception\Configuration;
use Codeception\Coverage\Subscriber\Printer;
use Codeception\Event\PrintResultEvent;
use Codeception\Events;
use Codeception\Extension;
use CoverageChecker\Checker;
use CoverageChecker\ClassChecker;
use CoverageChecker\Error;
use CoverageChecker\LineChecker;
use CoverageChecker\MethodChecker;
use Exception;
use PHPUnit\TextUI\Output\DefaultPrinter;

class CoverageChecker extends Extension
{
    public static array $events = [
        Events::RESULT_PRINT_AFTER => 'checkCoverage'
    ];
    private bool $_enabled;
    private array $_checkers = [];

    /**
     * @param array $config - Configuration from codeception.yml file
     * @param array $options - console parameters
     *
     * @throws ConfigurationException
     */
    public function __construct(array $config, array $options)
    {
        $config = array_merge(Configuration::config(), $config);
        $this->_enabled =
            isset($config['coverage']) &&
            isset($config['coverage']['enabled']) &&
            $config['coverage']['enabled'] == true &&
            isset($config['coverage']['check']) &&
            (
                (isset($options['coverage']) && $options['coverage'] !== false) ||
                (isset($options['coverage-xml']) && $options['coverage-xml'] !== false) ||
                (isset($options['coverage-html']) && $options['coverage-html'] !== false) ||
                (isset($options['coverage-text']) && $options['coverage-text'] !== false) ||
                (isset($options['coverage-crap4j']) && $options['coverage-crap4j'] !== false) ||
                (isset($options['coverage-phpunit']) && $options['coverage-phpunit'] !== false)
            );
        if ($this->_enabled) {
            Error::$noColors = (isset($options['no-colors']) && $options['no-colors'] !== false);
            $this->init($config['coverage']);
        }
        parent::__construct($config, $options);
    }

    /**
     * @param array $config - The config of the coverage part of codeception
     */
    protected function init(array $config): void
    {
        foreach ($config['check'] as $checkType => $limits) {
            $lowLimit = isset($limits['low_limit']) ? number_format($limits['low_limit'], 2, '.', '') : null;
            switch (strtolower($checkType)) {
                case 'classes':
                    $this->_checkers[] = new ClassChecker($lowLimit);
                    break;
                case 'methods':
                    $this->_checkers[] = new MethodChecker($lowLimit);
                    break;
                case 'lines':
                    $this->_checkers[] = new LineChecker($lowLimit);
                    break;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function checkCoverage(PrintResultEvent $event): void
    {
        if ($this->_enabled) {
            $report = Printer::$coverage->getReport();
            foreach ($this->_checkers as $checker) {
                $checker->check(DefaultPrinter::standardOutput(), $report);
            }

            if (Checker::$hasError) {
                throw new Exception();
            }
        }
    }
}
