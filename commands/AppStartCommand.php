<?php
namespace Serverpilot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Philo\Blade\Blade;

class AppStartCommand extends ServerpilotCommand
{
    /**
     * Command configuration.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('app:start')
             ->setDescription('Start an application.')
             ->setHelp('This command starts an application.')
             ->addOption('appName', null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * Execute command.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($this->userInput($input, $output)) {
            if($this->generateFiles($output)){
                if($this->startApp($output)) {
                    $output->writeln('<info>App started!</info>');
                }
            }
        }
    }

    /**
     * Ask user for name and app.
     *
     * @return bool
     */
    protected function userInput($input, $output)
    {
        return $this->askForApp($input, $output, 'Which app would you like to start?', 'stopped');
    }

    /**
     * Generate application files based on template.
     *
     * @since 1.0.0
     * @return bool
     */
    protected function generateFiles($output) {
        // Get app environment
        $env = sp_get_env($this->appDir);

        if(isset($env['APP_STACK'])) {
            $output->writeln("<info>Generating app configuration...</info>");

            $bladeFolder = SERVER_STACK_DIR.'/'.$env['APP_STACK'].'/config';
            $cache = SERVER_WORKDIR . '/cache';
            $views = sp_path($bladeFolder);

            $generate = ['docker-compose' => 'yml', 'php' => 'ini', 'varnish' => 'vcl'];

            foreach($generate as $file=>$ext) {
                $filePath = $bladeFolder.'/'.$file.'.blade.php';
                if(file_exists($filePath)) {
                    $blade = new Blade($views, $cache);
                    $content = $blade->view()->make($file, ['env' => $env])->render();
                    $destFile = sp_path($this->appDir.'/'.$file.($ext ? '.'.$ext : ''));
                    $writeFile = fopen($destFile, "w") or die("Unable to open file!");
                    fwrite($writeFile, $content);
                    fclose($writeFile);
                }
            }
        }

        return true;
    }

    /**
     * Starts the app.
     *
     * @return bool
     */
    protected function startApp($output) {
        $output->writeln("Starting app ".$this->appName.", please wait...");
        $process = new Process('cd '.$this->appDir.' && docker-compose up -d');
        $process->setTimeout(3600);

        try {
            $process->mustRun();

            // Run start command (if exists)
            if(file_exists($this->appDir.'/interface.php')){
                require_once $this->appDir.'/interface.php';
                $appInterfaceClass = '\Serverpilot\App\\'.ucfirst($this->appName).'\AppInterface';
                if(method_exists($appInterfaceClass, 'start')){
                    $appInterfaceClass::start($output);
                }
            }

            return true;
        } catch (ProcessFailedException $e) {
            $output->writeln("<error>".$e->getMessage()."</error>");
        }

        return false;
    }

}
