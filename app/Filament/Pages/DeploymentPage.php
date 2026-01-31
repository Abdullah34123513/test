<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeploymentPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    
    protected static ?string $navigationLabel = 'Deployment';
    
    protected static ?string $title = 'System Deployment';

    protected static string $view = 'filament.pages.deployment-page';
    
    protected static ?string $navigationGroup = 'System';
    
    public $output = '';

    public function deploy()
    {
        $this->output = "🚀 Starting Deployment...\n";
        
        // Diagnostics
        try {
            $this->output .= "--- Diagnostics ---\n";
            $this->output .= "User: " .  exec('whoami') . "\n";
            $this->output .= "Dir: " .  base_path() . "\n";
            $this->output .= "Node: " .  exec('node -v') . "\n";
            $this->output .= "Git: " .  exec('git --version') . "\n";
            $this->output .= "-------------------\n\n";
        } catch (\Exception $e) {
             $this->output .= "Diagnostic Warning: " . $e->getMessage() . "\n";
        }

        // Command to run the node script
        // We use full path or just node assuming it is in PATH
        // If 'node' is not found, we might need the full path (e.g. /usr/bin/node or /home/user/.nvm/...)
        $command = ['node', 'deploy.cjs'];
        
        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300); // 5 minutes timeout
        
        try {
            $process->start();
            
            foreach ($process as $type => $data) {
                $this->output .= $data;
                // We can't stream to UI easily in Livewire without polling, 
                // so we just collect output for now.
                // For real-time, we'd need a more complex setup or just show final result.
            }
            
            $process->wait();
            
            if ($process->isSuccessful()) {
                 Notification::make()
                    ->title('Deployment Successful')
                    ->success()
                    ->send();
            } else {
                 Notification::make()
                    ->title('Deployment Failed')
                    ->danger()
                    ->send();
            }
            
        } catch (\Exception $e) {
            $this->output .= "\n❌ Error: " . $e->getMessage();
             Notification::make()
                ->title('Deployment Error')
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deploy')
                ->label('Deploy Now')
                ->color('primary')
                ->requiresConfirmation()
                ->action('deploy'),
        ];
    }
}
