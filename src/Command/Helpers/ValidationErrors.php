<?php


namespace NorthStack\NorthStackClient\Command\Helpers;


use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ValidationErrors
{
    public function displayValidationErrors(ResponseInterface $response, OutputInterface $output)
    {
        $data = json_decode($response->getBody()->getContents(), true);
        foreach ($data as $sectionKey => $section) {
            if ($sectionKey === 'status') {
                continue;
            }
            foreach ($section as $prop => $messages) {
                $output->writeln("Errors for property `{$prop}`:");
                foreach ($messages['messages'] as $message) {
                    $output->writeln(" - {$message}");
                }
                /** @noinspection DisconnectedForeachInstructionInspection */
                $output->writeln('');
            }
        }
    }
}
