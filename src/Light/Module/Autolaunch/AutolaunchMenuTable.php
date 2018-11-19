<?php

namespace Light\Module\Autolaunch;

use Carbon\Carbon;
use Cron\CronExpression;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\Table;

class AutolaunchMenuTable extends Table
{
    public function __construct()
    {
        parent::__construct();

        $this->tableLinkModule = 'Autolaunch';

        $crontabLines = explode("\n",
            str_replace('run-one', '', shell_exec("sudo -u " . Config::$crontabUser . " -S crontab -l")));

        $this->addFields(

            F::createHtml('ClassName', [
                'name' => 'class',
                'getHtmlFunction' => function ($value) {
                    $parts = explode('\\', $value);
                    $shortClass = end($parts);
                    return Link::create(Autolaunch::class, $shortClass)->params(['class' => $value])->getHtml();
                }
            ]),

            F::createHtml('Description', [
                'name' => 'Описание',
                'getHtmlFunction' => function ($value, array $data) {
                    $className = $data['ClassName'];
                    /** @var AutolaunchAbstract $className */
                    $outHtml = $className::getTitle();
                    $desc = $className::getDescription();
                    if ($desc) {
                        $outHtml .= '<br><span class="note">' . $desc . '</span>';
                    }

                    return $outHtml;
                }
            ]),

            F::createString('cron', [
                'getHtmlFunction' => function ($value, array $data) use ($crontabLines) {
                    foreach ($crontabLines as $line) {
                        if (isset($line[0]) && $line[0] === '#') {
                            continue;
                        }

                        // В консоли для простоты имя autolaunch скприта указнывается кратко
                        // Все классы из неймспейса проекта Basis\Autolaunch\<class> указывается как просто <class>
                        // А классы из неймспейса движка Light\Autolaunch\<class> указываются как Light/<class>
                        $replaceFrom = [PROJECT_NAMESPACE . '\\', 'Autolaunch\\', 'Light\\'];
                        $replaceTo = ['', '', 'Light/'];
                        $cliClass = str_replace($replaceFrom, $replaceTo, $data['ClassName']);

                        if (strpos($line, $cliClass)) {
                            $cron = substr($line, 0, strpos($line, ' php'));
                            $outHtml = '<code title="' . htmlspecialchars($line) . '">' . $cron . '</code>';
                            $carbon = new Carbon(CronExpression::factory($cron)->getNextRunDate()->format('Y-m-d H:i:s'));
                            $outHtml .= '<br><span class="note">Следующий запуск: ' . $carbon->diffForHumans() . '</span>';
                            return $outHtml;
                        }
                    }

                    return '';
                }
            ])

        );

        $this->tableLoadData();
    }

    private function tableLoadData()
    {
        $this->scanNamespace(PROJECT_NAMESPACE . '\\Autolaunch');
        $this->scanNamespace('Light\\Autolaunch');
    }

    /**
     * Загружает все классы из указанного неймспйса в $this->_tableData
     * @param string $namespace
     */
    private function scanNamespace(string $namespace)
    {
        $pathAbs = LIGHT_DIR_ROOT . "/src/" . str_replace('\\', '/', $namespace);
        if (!is_dir($pathAbs)) {
            return;
        }

        foreach (glob($pathAbs . '/*.php') as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $className = "$namespace\\$filename";
            $this->_tableData[] = ['ClassName' => $className];
        }
    }

    protected function getTableRowLinkUrlParams(array $rowDataCalculated, int $rowNum): array
    {
        return ['class' => $rowDataCalculated['ClassName']];
    }
}
