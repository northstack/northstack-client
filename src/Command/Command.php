<?php
namespace NorthStack\NorthStackClient\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Command extends \duncan3dc\Console\Command
{
    use LoginRequiredTrait;
    protected $lock = false;
    protected $args;

    public function printBlock(OutputInterface $output, array $messages, string $style = 'error', bool $large = true)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        $output->writeln($formatter->formatBlock($messages, $style, $large));
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $arguments = $this->getDefinition()->getArguments();
        // ensure we leave the command item out -- no need to follow up on that.
        unset($arguments['command']);
        $userInput = $input->getArguments();
        /** @var QuestionHelper $qh */
        $qh = $this->getHelper('question');

        foreach ($arguments as $argName => $settings) {
            if (!$settings->isRequired() || null !== $userInput[$argName]) {
                continue;
            }

            $question = new Question($settings->getDescription() . ': ');
            // since this is required... You shall not pass! We will keep asking... forever...
            while (null === $userInput[$argName]) {
                $userInput[$argName] = $qh->ask($input, $output, $question);
            }

            $input->setArgument($argName, $userInput[$argName]);
        }
    }
}
