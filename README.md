# Generate console commands

Install this bundle using Composer. Register the bundle in `app/AppKernel.php`, then run:

    app/console generate:console-command

You will be asked to supply some information, after which a command class will be auto-generated for you.

![Screenshot](https://raw.githubusercontent.com/matthiasnoback/ConsoleCommandGeneratorBundle/master/Resources/doc/assets/generate-console-command.png)

The resulting console command would look like this:

```php

namespace Matthias\Bundle\DemoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePostCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('post:create')
            ->addArgument('title', InputArgument::REQUIRED, null, null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
```
