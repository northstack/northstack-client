<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\BaseLogFormat;

class TemplateLogFormat extends BaseLogFormat
{

    protected $template;

    protected function renderLog($msg)
    {
        $this->renderTemplate($msg);
    }

    protected function renderTemplate($data)
    {
        $out = preg_replace_callback(
            '@\{\{(?<term>[^}]+)\}\}@',
            function($matches) use ($data) {
                $term = $matches['term'];
                $value = @$data->{$term};
                if ($term === 'timestamp' || $term === '@timestamp')
                {
                    $value = $this->renderTimestamp($value);
                }

                return $value;
            },
            $this->template
        );
        $this->io->writeln($out);
    }
}
