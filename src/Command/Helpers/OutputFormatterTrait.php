<?php


namespace NorthStack\NorthStackClient\Command\Helpers;


use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputFormatterTrait
{
    public function displayRecord(OutputInterface $output, array $data) {
        $table = new Table($output);
        $table->setStyle('borderless');
        foreach ($data as $key => $value) {
            // do not show metadata
            if (strpos($key, '@') === 0) {
                continue;
            }
            $table->addRow([ucfirst($key), $value]);
        }
        $table->render();
    }

    public function displayTable(OutputInterface $output, array $data, array $headerToPropertyMap)
    {
        $count = 1;
        $headers = array_map(function ($header) {
            return "<fg=magenta>{$header}</>";
        }, array_keys($headerToPropertyMap));

        $cellMapper = function($value) {
            return "<fg=cyan>{$value}</>";
        };
        $rowKeyFilter = function($key) use ($headerToPropertyMap) {
            return in_array($key, $headerToPropertyMap, true);
        };

        $rows = [];
        foreach ($data as $record) {
            $record = array_filter($record, $rowKeyFilter, ARRAY_FILTER_USE_KEY);
            $rows[] = array_map($cellMapper, $record);

            if ($count % 12 === 0) {
                $rows[] = new TableSeparator();
                $rows[] = $headers;
                $rows[] = new TableSeparator();
            } elseif ($count === count($data)) {
                $rows[] = $headers;
            } else {
                $rows[] = new TableSeparator();
            }
            $count++;
        }

        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setHeaders($headers);
        $table->setRows($rows);

        $table->render();
    }
}
