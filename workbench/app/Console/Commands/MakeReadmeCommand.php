<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

class MakeReadmeCommand extends Command
{
    protected $signature = 'workbench:make-readme';

    protected $description = 'Generate the README.md file';

    public function handle(): void
    {
        $this->info('Generating README.md...');

        $assembler = new MarkdownAssembler('workbench/resources/md');

        $assembler->storeVersionBadge();
        $assembler->storeDownloadBadge();
        $assembler->storeLocalBadge('', '.github/phpstan.svg');
        $assembler->storeLocalBadge('', '.github/coverage.svg');
        $assembler->addBadges();

        $assembler->addMarkdown('why.md');
        $assembler->addMarkdown('introduction.md');
        $assembler->addTableOfContents();
        $assembler->addMarkdown('example.md');
        $assembler->addMarkdown('wizard.md');
        $assembler->addMarkdown('definitions.md');

        $assembler->writeMarkdown('README.md');

        $this->info('README.md generated successfully.');
    }
}
